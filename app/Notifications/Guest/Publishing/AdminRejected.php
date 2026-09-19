<?php

namespace App\Notifications\Guest\Publishing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\AnonymousNotifiable;

use App\Notifications\Channels\DatabaseChannel;
use App\Models\Publishing\Signee;

class AdminRejected extends Notification implements ShouldQueue
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
    public function __construct(Signee $signee)
    {
        $this->signee = $signee;
        $this->publishing = $signee->publishing;
        $requestorName = $this->publishing->requestor ? $this->publishing->requestor->full_name : 'Customer';

        $this->subject = __('Publishing Request Rejected');
        $this->content = [
            'The publishing request with title "' . $this->publishing->title . '" requested by ' . $requestorName . ' has been rejected by the administrator.',
            'Reason: ' . $this->publishing->reason,
            'Thank you',
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
            'message'   => $this->content[0] . ' ' . $this->content[1],
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
            ->action(__('View Request'), $this->link)
            ->line($this->content[2]);
    }
}
