<?php

namespace App\Notifications\Guest\Publishing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\AnonymousNotifiable;

use App\Notifications\Channels\DatabaseChannel;

use App\Services\WhatsAppService;

class Rejected extends Notification implements ShouldQueue
{
    use Queueable;

    protected $signee;
    protected $subject;
    protected $content;
    protected $link;

    public function __construct(
        \App\Models\Publishing\Signee $signee
    )
    {
        $requestor  = $signee->publishing->requestor->full_name;
        $publishing = $signee->publishing;
        $title      = $signee->publishing->title;

        $this->signee = $signee;
        $this->subject = 'Publishing Request Rejected';
        $this->content = [
            'Publishing contract for song "'.$title.'" requested by '.$requestor.' has been rejected by a signee.',
            $publishing->rejector->full_name . ' (' . $publishing->rejector->email . '): ' . $publishing->reason,
            __('Thank you for your trust in our service'),
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
            'message'   => $this->content[0] . "\n\n" . $this->content[1],
            'route'     => [
                'name' => 'sign.publishing.show',
                'id'    => $this->signee->token,
            ]
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->content[0])
            ->line($this->content[1])
            ->action('View Request', $this->link)
            ->line($this->content[2]);
    }

    public function toWhatsAppService(object $notifiable)
    {
        $content    = '*'. $this->subject ."*\n\n";
        $content   .= $this->content[0] . "\n\n";
        $content   .= $this->link . "\n\n";
        $content   .= $this->content[1] . "\n\n";
        $content   .= $this->content[2] . "\n\n";

        return $content;
    }
}
