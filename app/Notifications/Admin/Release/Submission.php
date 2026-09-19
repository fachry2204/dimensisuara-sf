<?php

namespace App\Notifications\Admin\Release;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Notifications\Channels\DatabaseChannel;

use App\Services\WhatsAppService;

class Submission extends Notification implements ShouldQueue
{
    use Queueable;

    protected $release;
    protected $subject;
    protected $content;
    protected $link;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        \App\Models\Release $release
    )
    {
        $this->release = $release;
        $this->subject = __('New Release Submission');
        $this->content = [
            'User ' . $release->profile->full_name . ' has submitted a new release with title "' . $release->title . '".',
            'Please check and update the status immediately.',
            'Thank you',
        ];

        $this->link = route('admin.releases.show', $release->id);

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
                'name' => 'admin.releases.show',
                'id'    => $this->release->id,
                'image' => $this->release->thumbnail ? $this->release->thumbnail->id : null
            ]
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->content[0])
            ->line($this->content[1])
            ->action(__('Release link'), $this->link)
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
