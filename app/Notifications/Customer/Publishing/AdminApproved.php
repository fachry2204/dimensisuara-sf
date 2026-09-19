<?php

namespace App\Notifications\Customer\Publishing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

use App\Notifications\Channels\DatabaseChannel;
use App\Services\WhatsAppService;
use App\Models\Publishing\Publishing;
use App\Services\ContractService;

class AdminApproved extends Notification implements ShouldQueue
{
    use Queueable;

    protected $publishing;
    protected $subject;
    protected $content;
    protected $link;

    /**
     * Create a new notification instance.
     */
    public function __construct(Publishing $publishing)
    {
        $this->publishing = $publishing;
        $this->subject = __('Publishing Request Approved');
        $this->content = [
            'Your publishing request with title "' . $publishing->title . '" has been approved by the administrator.',
            'Please find the attached Publishing Contract (PKS), Songwriter Statement, and NUON Statement.',
            'Thank you',
        ];
        $this->link = route('publishing.show', $publishing->id);
    }

    public function via(object $notifiable): array
    {
        return ['mail', DatabaseChannel::class, WhatsAppService::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'subject'   => $this->subject,
            'message'   => $this->content[0],
            'route'     => [
                'name' => 'publishing.show',
                'id'    => $this->publishing->id,
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

        // Attach PKS Contract
        $publishingContract = $this->publishing->publishingContract;
        if ($publishingContract) {
            $pksPdf = null;
            if ($publishingContract->file) {
                $pksPdf = Storage::disk($publishingContract->file->disk)->get($publishingContract->file->identifier);
            } else {
                $pksPdf = ContractService::generatePublishingContractPdf($publishingContract, false);
            }
            if ($pksPdf) {
                $pksFilename = $publishingContract->docnum
                    ? str_replace('/', '_', $publishingContract->docnum) . '.pdf'
                    : 'Publishing_Contract.pdf';
                $mail->attachData($pksPdf, $pksFilename, ['mime' => 'application/pdf']);
            }
        }

        // Attach Songwriter Statement
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

        // Attach NUON Statement
        $nuonDoc = $this->publishing->nuon;
        $nuonPdf = null;
        if ($nuonDoc && $nuonDoc->file) {
            $nuonPdf = Storage::disk($nuonDoc->file->disk)->get($nuonDoc->file->identifier);
        } else {
            $nuonPdf = ContractService::generatePublishingNuonPdf($this->publishing, false);
        }
        if ($nuonPdf) {
            $mail->attachData($nuonPdf, 'NUON_Statement.pdf', ['mime' => 'application/pdf']);
        }

        return $mail;
    }

    public function toWhatsAppService(object $notifiable): string
    {
        $content    = '*' . $this->subject . "*\n\n";
        $content   .= $this->content[0] . "\n\n";
        $content   .= $this->link . "\n\n";
        $content   .= $this->content[2] . "\n\n";

        return $content;
    }
}
