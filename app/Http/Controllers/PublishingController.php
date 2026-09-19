<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Http\Requests\Publishing\StorePublishingRequest;
use App\Http\Requests\Publishing\UpdatePublishingRequest;

use App\Exceptions\Publishing\InvalidRequestStatusException;

use App\Models\Publishing\Publishing;
use App\Models\Publishing\Signee;
use App\Models\Contract;
use App\Models\Release;

use App\Services\Publishing\PublishingService;
use App\Services\ContractService;


class PublishingController extends Controller
{
    protected $service;
    protected $contractService;

    public function __construct(PublishingService $service, ContractService $contractService)
    {
        $this->service = $service;
        $this->contractService = $contractService;
    }

    public function index()
    {
        $publishings = current_user()->profile->publishings;

        return view('customer.publishing.index', compact('publishings'));
    }

    public function create()
    {
        // Create empty publishing
        $publishing = new Publishing;
        $releases = current_user()->profile->releases->where('status', 'released');

        return view('customer.publishing.create', compact('publishing', 'releases'));
    }

    public function edit($id)
    {
        // Find publishing
        $publishing = Publishing::where('profileid', current_user()->profile->id)->findOrFail($id);
        $releases = current_user()->profile->releases->where('status', 'released');

        return view('customer.publishing.create', compact('publishing', 'releases'));
    }

    public function store(StorePublishingRequest $request)
    {
        $context = [];

        try {
            $profileid = current_user()->profile->id;
            $data = $request->only([
                'id',
                'release_id',
                'track_id',
                'title',
                'other_title',
                'sample',
                'sync_license_type',
                'performer',
                'duration_minute',
                'duration_second',
                'genre',
                'language',
                'iswc',
                'isrc',
                'note',
                'status',
                'songwriters',
                'accept_terms',
            ]);

            // Set status
            $data['status'] = $request->input('status') ?? 'pending';
            $data['accept_terms'] = $request->input('accept_terms') ? 1 : 0;

            $result = $this->service->create($data, $profileid);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info('Publishing draft created.', $context);

            // Get publishing
            $publishing = $result;

            // Return to edit if status is draft
            if ($publishing->status == 'draft') {
                Log::info('Publishing request ID ' . $publishing->id . ' (draft) has been created/edited by User ID ' . current_user()->id);
                $route = redirect()->route('publishing.edit', ['id' => $publishing->id])->with('success', 'Publishing request saved as draft.');
            }

            // Return to index if finalized
            else {
                Log::info('Publishing request ID ' . $publishing->id . ' has been created by User ID ' . current_user()->id);
                $route = redirect()->route('publishing.index')->with('success', 'Publishing request created. All songwriters will be notified.');
            }

            return $route;
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Failed to create publishing draft.', $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function show($id)
    {
        $publishing = Publishing::where('profileid', current_user()->profile->id)->findOrFail($id);
        return view('customer.publishing.show', compact('publishing'));
    }

    public function cancel($id)
    {
        $publishing = Publishing::where('profileid', current_user()->profile->id)->findOrFail($id);
        $context = [];

        try {
            $result = $this->service->cancel($publishing);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info('Publishing request cancelled.', $context);

            return $result;
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Failed to cancel publishing request.', $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function destroy($id)
    {
        // Look up scoped to owner AND deletable status. A wrong status or another
        // user's record both 404 here (IDOR protection). We catch the 404 to log the
        // attempt before re-throwing so the framework returns its standard 404.
        try {
            $publishing = Publishing::where('profileid', current_user()->profile->id)
                ->whereIn('status', ['draft', 'rejected'])
                ->findOrFail($id);
        } catch (ModelNotFoundException $th) {
            Log::warning('Blocked publishing delete attempt (not found / not owned / wrong status).', [
                'requested_id' => $id,
                'user_id' => current_user()->id,
                'profile_id' => current_user()->profile->id,
            ]);
            throw $th;
        }

        // Enriched context captured BEFORE deletion so the audit record survives the
        // hard delete + signee CASCADE. Reconstruction is impossible afterwards.
        $context = [
            'model_id' => $publishing->id,
            'model_type' => get_class($publishing),
            'title' => $publishing->title,
            'status' => $publishing->status,
            'signee_count' => $publishing->signees()->count(),
            'signee_emails' => $publishing->signees()->pluck('email')->all(),
        ];

        try {
            $this->service->delete($publishing);

            // Log success
            Log::info('Publishing request deleted.', $context);

            return redirect()->route('publishing.index')->with('success', 'Publishing request deleted.');
        } catch (InvalidRequestStatusException $th) {

            // Expected guard rejection. Reaching here means a request slipped past the
            // controller's whereIn (e.g. a status race). Warn-level, no stack trace.
            $context['message'] = $th->getMessage();
            Log::warning('Blocked publishing delete: invalid status at service guard.', $context);

            return redirect()->back()->withErrors($th->getMessage());
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Failed to delete publishing request.', $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function resend($id)
    {
        $signee = Signee::where('profileid', current_user()->profile->id)->findOrFail($id);
        $context = [
            'parent_id' => $signee->publishing->id,
            'parent_type' => get_class($signee->publishing)
        ];

        try {
            $result = $this->service->resend($signee);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info('Notification sent.', $context);

            return redirect()->back()->with('success', 'Notification sent.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Failed to send notification.', $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function retrieve_contract(int $id)
    {
        try {
            // Find contract
            $contract = Contract::where('profileid', current_user()->profile->id)->findOrFail($id);
            $file = $mime = null;

            // Check if contract is uploaded
            if ($contract->file) {

                // Get file
                $file = Storage::disk($contract->file->disk)->get($contract->file->identifier);
                $mime = $contract->file->mime_type;
            } else {
                // Generate PDF and return
                $file = ContractService::generatePublishingContractPdf($contract);
                $mime = 'application/pdf';
            }

            // Return response
            return response($file)
                ->header('Content-Type', $mime)
                ->header('Content-Disposition', 'inline; filename="' . $contract->docnum . '"');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            abort(404, $e->getMessage());
        } catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            abort(500, $th->getMessage());
        }
    }
}
