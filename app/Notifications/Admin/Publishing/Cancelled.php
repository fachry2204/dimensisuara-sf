<?php

namespace App\Notifications\Admin\Publishing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Notifications\Channels\DatabaseChannel;

use App\Services\WhatsAppService;

class Cancelled extends Notification implements ShouldQueue
{
    use Queueable;

    protected $publishing;
    protected $subject;
    protected $content;
    protected $link;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        \App\Models\Publishing\Publishing $publishing
    )
    {
        $this->publishing = $publishing;
        $this->subject = __('Publishing Cancelled');
        $this->content = [
            'User ' . $publishing->requestor->full_name . ' has cancelled publishing request with title "' . $publishing->title . '".',
            'Thank you',
        ];

        $this->link = route('admin.publishing.show', $publishing->id);

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
                'name' => 'admin.publishing.show',
                'id'    => $this->publishing->id,
            ]
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->content[0])
            ->action(__('Publishing link'), $this->link)
            ->line($this->content[1]);
    }

    public function toWhatsAppService(object $notifiable)
    {
        $content    = '*'. $this->subject ."*\n\n";
        $content   .= $this->content[0] . "\n\n";
        $content   .= $this->link . "\n\n";
        $content   .= $this->content[1] . "\n\n";

        return $content;
    }
}
