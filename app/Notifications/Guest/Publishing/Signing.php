<?php

namespace App\Notifications\Guest\Publishing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\AnonymousNotifiable;

use App\Notifications\Channels\DatabaseChannel;

use App\Services\WhatsAppService;

class Signing extends Notification implements ShouldQueue
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
        $title      = $signee->publishing->title;

        $this->signee = $signee;
        $this->subject = 'New Publishing Request on SoundFresh';
        $this->content = [
            'User '.$requestor.' has requested a publishing contract for song "'.$title.'". Please review and sign the contract.',
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
            'message'   => $this->content[0],
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
            ->action('View Request', $this->link)
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
