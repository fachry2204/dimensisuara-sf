<?php

namespace App\Notifications\Customer\Publishing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Notifications\Channels\DatabaseChannel;
use App\Services\WhatsAppService;
use App\Models\Publishing\Publishing;

class AdminRejected extends Notification implements ShouldQueue
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
        $this->subject = __('Publishing Request Rejected');
        $this->content = [
            'Your publishing request with title "' . $publishing->title . '" has been rejected by the administrator.',
            'Reason: ' . $publishing->reason,
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
            'message'   => $this->content[0] . ' ' . $this->content[1],
            'route'     => [
                'name' => 'publishing.show',
                'id'    => $this->publishing->id,
            ]
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->content[0])
            ->line($this->content[1])
            ->action(__('View Request'), $this->link)
            ->line($this->content[2]);
    }

    public function toWhatsAppService(object $notifiable): string
    {
        $content    = '*' . $this->subject . "*\n\n";
        $content   .= $this->content[0] . "\n\n";
        $content   .= $this->content[1] . "\n\n";
        $content   .= $this->link . "\n\n";
        $content   .= $this->content[2] . "\n\n";

        return $content;
    }
}
