<?php

namespace App\Notifications\Admin\Publishing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Notifications\Channels\DatabaseChannel;

use App\Services\WhatsAppService;

class Rejected extends Notification implements ShouldQueue
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
        $this->subject = __('Publishing Rejected');
        $this->content = [
            'Publishing request with title "' . $publishing->title . '" by user ' . $publishing->requestor->full_name . ' has been rejected by a signee.',
            $publishing->rejector->full_name . ' (' . $publishing->rejector->email . '): ' . $publishing->reason,
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
