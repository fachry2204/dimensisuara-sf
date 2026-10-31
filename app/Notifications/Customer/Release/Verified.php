<?php

namespace App\Notifications\Customer\Release;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Notifications\Channels\DatabaseChannel;

class Verified extends Notification implements ShouldQueue
{
    use Queueable;

    protected $release;
    protected $subject;
    protected $content;
    protected $link;

    public function __construct(
        \App\Models\Release $release
    )
    {
        $this->release = $release;
        $this->subject = __('Release Verified');
        $this->content = [
            __('Your release with title ":Title" has been verified.', ['title' => $release->title]),
            __('Thank you for your trust in our service'),
        ];

        $this->link = route('releases.show', $release->id);

    }

    public function via(object $notifiable): array
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('status_notification_deliveries')) {
            return ['mail', DatabaseChannel::class, \App\Services\WhatsAppService::class];
        }
        // Email/WhatsApp status delivery is handled once by the model observer.
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'subject'   => $this->subject,
            'message'   => $this->content[0],
            'route'     => [
                'name' => 'releases.show',
                'id'    => $this->release->id,
                'image' => $this->release->thumbnail?->id
            ]
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->content[0])
            ->action(__('Release link'), $this->link)
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
