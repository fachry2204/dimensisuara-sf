<?php

namespace App\Notifications\Guest\Publishing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Storage;

use App\Notifications\Channels\DatabaseChannel;
use App\Models\Publishing\Signee;
use App\Services\ContractService;

class AdminApproved extends Notification implements ShouldQueue
{
    use Queueable;

    protected $signee;
    protected $publishing;
    protected $subject;
    protected $content;
    protected $link;

    /**
     * Create a new notification instance.
     */
    public function __construct(Signee $signee)
    {
        $this->signee = $signee;
        $this->publishing = $signee->publishing;
        $requestorName = $this->publishing->requestor ? $this->publishing->requestor->full_name : 'Customer';
        
        $this->subject = __('Publishing Request Approved');
        $this->content = [
            'The publishing request with title "' . $this->publishing->title . '" requested by ' . $requestorName . ' has been approved by the administrator.',
            'Please find the attached Songwriter Statement.',
            'Thank you',
        ];
        $this->link = route('sign.publishing.show', $signee->token);
    }

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (! $notifiable instanceof AnonymousNotifiable) {
            $channels[] = DatabaseChannel::class;
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'subject'   => $this->subject,
            'message'   => $this->content[0],
            'route'     => [
                'name' => 'sign.publishing.show',
                'id'    => $this->signee->token,
            ]
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject)
            ->line($this->content[0])
            ->line($this->content[1])
            ->action(__('View Request'), $this->link)
            ->line($this->content[2]);

        // Attach ONLY the Songwriter Statement
        $statementContract = $this->publishing->statementContract;
        $statementPdf = null;
        if ($statementContract) {
            if ($statementContract->file) {
                $statementPdf = Storage::disk($statementContract->file->disk)->get($statementContract->file->identifier);
            } else {
                $statementPdf = ContractService::generatePublishingStatementPdf($this->publishing, $statementContract, false);
            }
        } else {
            $statementPdf = ContractService::generatePublishingStatementPdf($this->publishing, null, false);
        }
        if ($statementPdf) {
            $statementFilename = $statementContract && $statementContract->docnum
                ? str_replace('/', '_', $statementContract->docnum) . '.pdf'
                : 'Songwriter_Statement.pdf';
            $mail->attachData($statementPdf, $statementFilename, ['mime' => 'application/pdf']);
        }

        return $mail;
    }
}
