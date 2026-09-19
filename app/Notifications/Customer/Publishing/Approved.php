<?php

namespace App\Notifications\Customer\Publishing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Notifications\Channels\DatabaseChannel;

use App\Services\WhatsAppService;

class Approved extends Notification implements ShouldQueue
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
    public function __construct(
        \App\Models\Publishing\Signee $signee
    )
    {
        $this->signee = $signee;
        $this->publishing = $signee->publishing;
        $this->subject = __('Publishing Approved');
        $this->content = [
            'Signee ' . $signee->full_name . ' (' . $signee->email . ') has approved your publishing request with title "' . $this->publishing->title . '".',
            'Please wait for other signees to approve your publishing request.',
            'Thank you',
        ];

        $this->link = route('publishing.show', $this->publishing->id);

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
        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->content[0])
            ->line($this->content[1])
            ->action(__('Publishing link'), $this->link)
            ->line($this->content[2]);
    }

    public function toWhatsAppService(object $notifiable)
    {
        $content    = '*'. $this->subject ."*\n\n";
        $content   .= $this->content[0] . "\n\n";
        $content   .= $this->content[1] . "\n\n";
        $content   .= $this->link . "\n\n";
        $content   .= $this->content[2] . "\n\n";

        return $content;
    }
}
