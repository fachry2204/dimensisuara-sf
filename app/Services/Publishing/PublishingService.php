<?php

namespace App\Services\Publishing;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use App\Exceptions\Publishing\InvalidApprovalStatusException;
use App\Exceptions\Publishing\InvalidRequestStatusException;
use App\Exceptions\Publishing\InvalidSendingStatusException;

use App\Models\User\User;
use App\Models\Publishing\Publishing;
use App\Models\Publishing\Signee;
use App\Models\Publishing\Document;

use App\Notifications\Admin\Publishing\Cancelled as AdminCancelledNotification;
use App\Notifications\Admin\Publishing\Completed as AdminCompletedNotification;
use App\Notifications\Admin\Publishing\Submission as AdminPublishedNotification;
use App\Notifications\Admin\Publishing\Rejected as AdminRejectedNotification;

use App\Notifications\Guest\Publishing\Cancelled as GuestCancelledNotification;
use App\Notifications\Guest\Publishing\Completed as GuestCompletedNotification;
use App\Notifications\Guest\Publishing\Rejected as GuestRejectedNotification;
use App\Notifications\Guest\Publishing\Signing as GuestSignNotification;

use App\Notifications\Customer\Publishing\Approved as CustomerApprovedNotification;
use App\Notifications\Customer\Publishing\Completed as CustomerCompletedNotification;
use App\Notifications\Customer\Publishing\Rejected as CustomerRejectedNotification;

use App\Services\ContractService;
use App\Services\FileService;

class PublishingService
{
    protected $contractService;
    protected $fileService;

    public function __construct(ContractService $contractService, FileService $fileService)
    {
        $this->contractService = $contractService;
        $this->fileService = $fileService;
    }

    /**
     * User actions
     */
    public function create($data, $profileid)
    {
        return DB::transaction(function () use ($data, $profileid) {

            // Create publishing
            $publishing = new Publishing;

            // Check if aleady exists
            if ($data['id']) {
                $existing = Publishing::find($data['id'])
                    ->where('profileid', current_user()->profile->id)
                    ->where('status', 'draft')
                    ->first();

                if ($existing) {
                    $publishing = $existing;
                }
            }

            // Check status
            if ($publishing->status && $publishing->status != 'draft') {
                throw new InvalidRequestStatusException($publishing->status, ['draft']);
            }

            // fill values
            $publishing->fill($data);
            $publishing->profileid = $profileid;
            $publishing->status = 'draft';
            $publishing->songwriters = json_encode($data['songwriters'] ?? []);
            $publishing->due_date = null;
            $publishing->save();

            // Action if not draft
            if ($data['status'] == 'pending') {
                $this->publish($publishing);
            }

            // Return from transaction
            return $publishing;
        });
    }

    public function publish(Publishing $publishing)
    {
        DB::transaction(function () use ($publishing) {

            // Check status
            if ($publishing->status && $publishing->status !== 'draft') {
                throw new InvalidRequestStatusException($publishing->status, ['draft']);
            }

            // Update status
            $publishing->published_at = now();
            $publishing->due_date = now()->addDays(5)->toDateString();
            $publishing->status = 'pending';
            $publishing->contract_expired_at = now()->addYears(5);
            $publishing->contract_auto_renew = false;
            $publishing->save();

            // Generate Signees from Request JSON
            if ($publishing->songwriters) {
                foreach (json_decode($publishing->songwriters, true) as $signee) {
                    $isCurrentUser = ($signee['email'] == current_user()->email);

                    // Create signee
                    $publishing->signees()->create([
                        'full_name' => $signee['name'],
                        'ktp_num' => $signee['ktp_num'] ?? null,
                        'phone' => $signee['phone'] ?? null,
                        'email' => $signee['email'],
                        'lmk' => $signee['lmk'] ?? null,
                        'publisher' => $signee['publisher'] ?? null,
                        'token' => Str::uuid(),
                        'profileid' => $publishing->profileid,
                        'role' => $signee['role'],
                        'share' => round((float) $signee['share'], 3),
                        'status' => $isCurrentUser ? 'accepted' : 'pending',
                        'accept_terms' => $isCurrentUser ? true : false,
                        'accepted_at' => $isCurrentUser ? now() : null,
                    ]);
                }
            }

            // Clear the temporary JSON after signees are created
            // The signees table is now the source of truth
            $publishing->songwriters = null;

            // Update publishing request status if all signees are accepted
            $signeeLeft = $publishing->signees()->where('status', 'pending')->exists();
            if (!$signeeLeft) {
                $publishing->status = 'review';
                $publishing->finished_at = now();
            }

            $publishing->save();
        });

        // Load signees
        $publishing->load('signees');

        // Send notification to admin
        $admins = User::getByPermission('view-releases');
        Notification::send($admins, new AdminPublishedNotification($publishing));

        // Dispatch notifications based on completion status
        if ($publishing->status == 'review') {
            // Send notification to admins
            Notification::send($admins, new AdminCompletedNotification($publishing));

            // Send notification to requestor
            Notification::send($publishing->requestor->user, new CustomerCompletedNotification($publishing));

            // Send notification to songwriters
            foreach ($publishing->signees as $signee) {
                $notification = new GuestCompletedNotification($signee);
                $signee->sendNotification($notification);
            }
        } else {
            // Send notification to songwriters who are pending
            foreach ($publishing->signees as $signee) {
                if ($signee->status == 'pending') {
                    $notification = new GuestSignNotification($signee);
                    $signee->sendNotification($notification);
                }
            }
        }
    }

    public function cancel(Publishing $publishing)
    {
        DB::transaction(function () use ($publishing) {

            // Check status
            if ($publishing->status !== 'pending') {
                throw new InvalidRequestStatusException($publishing->status, ['pending']);
            }

            // Update status
            $publishing->status = 'cancelled';
            $publishing->finished_at = now();
            $publishing->save();

            // Update signees
            $publishing->signees()->where('status', 'pending')->update([
                'status' => 'cancelled'
            ]);
        });

        // Send notification to admin
        $admins = User::getByPermission('view-releases');
        Notification::send($admins, new AdminCancelledNotification($publishing));

        // Send notification to songwriters
        foreach ($publishing->signees as $signee) {
            $notification = new GuestCancelledNotification($signee);
            $signee->sendNotification($notification);
        }

        return $publishing;
    }

    public function delete(Publishing $publishing)
    {
        // Check status
        if (!in_array($publishing->status, ['draft', 'rejected'])) {
            throw new InvalidRequestStatusException($publishing->status, ['draft', 'rejected']);
        }

        DB::transaction(function () use ($publishing) {
            // Signees will be automatically deleted by CASCADE FK constraint
            // Documents (statement/nuon) are preserved as archival records
            $publishing->delete();
        });
    }

    public function resend(Signee $signee)
    {
        // Check status
        if ($signee->publishing->status !== 'pending') {
            throw new InvalidSendingStatusException();
        }

        $notification = new GuestSignNotification($signee);
        $signee->sendNotification($notification);
    }

    /**
     * Signee actions
     */
    public function signeeUpdatePublisher(Signee $signee, $data)
    {
        return DB::transaction(function () use ($signee, $data) {

            // Get publishers from request
            $publishers = [];
            if (isset($data['name']) && is_array($data['name'])) {
                $len = count($data['name']);
                for ($i = 0; $i < $len; $i++) {
                    $publishers[] = [
                        'name' => $data['name'][$i],
                        'date' => isset($data['date'][$i]) ? $data['date'][$i] : null,
                    ];
                }
            }

            // Save as json
            $signee->publishers = json_encode($publishers);
            $signee->save();

            return $signee;
        });
    }

    public function signeeApprove(Signee $signee, $data)
    {
        // Get data
        $publishing = $signee->publishing;

        // Validate status
        if ($publishing->status != 'pending' || $signee->status != 'pending') {
            throw new InvalidApprovalStatusException();
        }

        // Update status
        DB::transaction(function () use ($signee, $publishing, $data) {
            $signee->status = 'accepted';
            $signee->accept_terms = $data['accept_terms'] == 'on' ? true : false;
            $signee->accepted_at = now();
            $signee->save();

            // Update publishing request status if all signees are accepted
            $signeeLeft = $publishing->signees()->where('status', 'pending')->exists();
            if (!$signeeLeft) {
                $publishing->status = 'review';
                $publishing->finished_at = now();
                $publishing->save();
            }
        });

        // Send notification to requestor
        Notification::send($publishing->requestor->user, new CustomerApprovedNotification($signee));

        // On request completed
        if (!$publishing->signees()->where('status', 'pending')->exists()) {

            // Send notification to admins
            $admins = User::getByPermission('view-releases');
            Notification::send($admins, new AdminCompletedNotification($publishing));

            // Send notification to requestor
            Notification::send($publishing->requestor->user, new CustomerCompletedNotification($publishing));

            // Send notification to songwriters
            foreach ($publishing->signees as $signee) {
                $notification = new GuestCompletedNotification($signee);
                $signee->sendNotification($notification);
            }
        }

        return $signee;
    }

    public function signeeReject(Signee $signee, $reason)
    {
        // Get data
        $publishing = $signee->publishing;

        // Validate status
        if ($signee->publishing->status != 'pending' || $signee->status != 'pending') {
            throw new InvalidApprovalStatusException();
        }

        // Update status
        DB::transaction(function () use ($signee, $publishing, $reason) {

            // Update signee status
            $signee->status = 'rejected';
            $signee->save();

            // Update publishing request status
            $publishing->status = 'rejected';
            $publishing->reason = $reason;
            $publishing->rejectorid = $signee->id;
            $publishing->finished_at = now();
            $publishing->save();

            // Update other signees status
            $publishing->signees()->where('status', 'pending')->update([
                'status' => 'cancelled',
            ]);
        });

        // Send notification to admin
        $admins = User::getByPermission('view-releases');
        Notification::send($admins, new AdminRejectedNotification($publishing));

        // Send notification to requestor
        Notification::send($publishing->requestor, new CustomerRejectedNotification($publishing));

        // Send notification to songwriters
        foreach ($publishing->signees as $signee) {
            $notification = new GuestRejectedNotification($signee);
            $signee->sendNotification($notification);
        }

        return $signee;
    }

    /**
     * Admin actions
     */
    public function adminApprove(Publishing $publishing, $data)
    {
        // Validate status
        if ($publishing->status != 'review') {
            throw new InvalidRequestStatusException($publishing->status, ['review']);
        }

        $result = DB::transaction(function () use ($publishing, $data) {
            // Create single publishing contract
            $contractPayload = [
                'profileid' => $publishing->requestor->id,
                'type' => 'publishing',
                'number' => $data['contract_number'],
                'month' => $data['contract_month'],
                'year' => $data['contract_year']
            ];
            $contract = $this->contractService->store($contractPayload);
            $contract->owner()->associate($publishing);
            $contract->save();

            // Create statement contract
            $statementPayload = [
                'profileid' => $publishing->requestor->id,
                'type' => 'publishing_statement',
                'number' => $data['statement_number'],
                'month' => $data['statement_month'],
                'year' => $data['statement_year'],
            ];
            $statementContract = $this->contractService->store($statementPayload);
            $statementContract->owner()->associate($publishing);
            $statementContract->save();

            // Update status
            $publishing->status = 'completed';
            $publishing->contract_auto_renew = true;
            $publishing->save();

            return $publishing;
        });

        // Eager load relationships needed for attachments / notifications
        $publishing->load(['publishingContract.file', 'statementContract.file', 'statement.file', 'nuon.file', 'requestor.user', 'signees']);

        // Send notification to requestor
        Notification::send($publishing->requestor, new \App\Notifications\Customer\Publishing\AdminApproved($publishing));

        // Send notification to other songwriters (guests)
        foreach ($publishing->signees as $signee) {
            if ($signee->email !== $publishing->requestor->user->email) {
                $signee->sendNotification(new \App\Notifications\Guest\Publishing\AdminApproved($signee));
            }
        }

        return $result;
    }

    public function adminReject(Publishing $publishing, $data)
    {
        // Validate status
        if ($publishing->status != 'review') {
            throw new InvalidRequestStatusException($publishing->status, ['review']);
        }

        DB::transaction(function () use ($publishing, $data) {

            // Update status and reason
            $publishing->status = 'rejected';
            $publishing->reason = $data['reason'];
            $publishing->save();
        });

        // Eager load relationships needed for notifications
        $publishing->load(['requestor.user', 'signees']);

        // Send notification to requestor
        Notification::send($publishing->requestor, new \App\Notifications\Customer\Publishing\AdminRejected($publishing));

        // Send notification to other songwriters (guests)
        foreach ($publishing->signees as $signee) {
            if ($signee->email !== $publishing->requestor->user->email) {
                $signee->sendNotification(new \App\Notifications\Guest\Publishing\AdminRejected($signee));
            }
        }

        return $publishing;
    }

    /** Documents (Statement & NUON) */
    public function upload(Publishing $publishing, $data, $type)
    {
        // Find or create document record
        $document = Document::firstOrCreate(
            [
                'publishingid' => $publishing->id,
                'type' => $type,
            ],
            [
                'profileid' => $publishing->profileid,
                'issued_at' => now(),
            ]
        );

        // Store file attached to document
        FileService::store($data['file'], $document, $publishing->requestor, $type, 'files');
    }

    public function show(Publishing $publishing, $type)
    {
        // Get document
        $document = $publishing->$type;

        // Check if document exists
        if (!$document || !$document->file) {
            throw new NoUploadedFileException();
        }

        // Get file
        $file = Storage::disk($document->file->disk)->get($document->file->identifier);

        // Return response
        return response($file)
            ->header('Content-Type', $document->file->mime_type)
            ->header('Content-Disposition', 'inline; filename="' . $type . '_' . $publishing->id . '.pdf"');
    }

    public function deletePdf(Publishing $publishing, $type)
    {
        // Get document
        $document = $publishing->$type;

        if ($document && $document->file) {
            // Delete file
            FileService::delete($document->file);

            // Optionally delete the document record itself
            // Since documents are archival, we might want to keep the record
            // $document->delete();
        }
    }
}
