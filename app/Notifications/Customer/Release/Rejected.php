<?php

namespace App\Notifications\Customer\Release;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Notifications\Channels\DatabaseChannel;

class Rejected extends Notification implements ShouldQueue
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
        $this->subject = __('Release Rejected');
        $this->content = [
            __('Your release with title ":Title" has been rejected with the following reason:', ['title' => $release->title]),
            $release->reason,
            __('You have five days to submit revision based on the reason stated above, or your release will be automatically deleted.'),
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
            'message'   => $this->content[0] . ' ' . $this->content[1],
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
            ->error()
            ->line($this->content[0])
            ->line($this->content[1])
            ->line($this->content[2])
            ->action(__('Release link'), $this->link)
            ->line($this->content[3]);
    }

    public function toWhatsAppService(object $notifiable)
    {
        $content    = '*'. $this->subject ."*\n\n";
        $content   .= $this->content[0] . "\n\n";
        $content   .= $this->content[1] . "\n\n";
        $content   .= $this->content[2] . "\n\n";
        $content   .= $this->link . "\n\n";
        $content   .= $this->content[3] . "\n\n";

        return $content;
    }
}
