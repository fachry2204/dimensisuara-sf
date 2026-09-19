<?php

namespace App\Notifications\Customer\Settlement;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Notifications\Channels\DatabaseChannel;

class Completed extends Notification implements ShouldQueue
{
    use Queueable;

    protected $settlement;
    protected $subject;
    protected $content;
    protected $link;

    public function __construct(
        \App\Models\Settlement\Settlement $settlement
    )
    {
        $this->settlement = $settlement;
        $this->subject = __('Transaction completed');
        $this->content = [
            __('Your release royalty for Q:quarter :year has been transferred to your bank account.', [
                    'quarter'   => $settlement->quarter,
                    'year'      => $settlement->year
                ]
            ),
            __('Thank you for your trust in our service'),
        ];
        $this->link = route('settlements.show', $settlement->id);

    }

    public function via(object $notifiable): array
    {
        return ['mail', DatabaseChannel::class, \App\Services\WhatsAppService::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'subject'   => $this->subject,
            'message'   => $this->content[0],
            'route'     => [
                'name' => 'settlements.show',
                'id'    => $this->settlement->id,
            ]
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->content[0])
            ->action(__('Transaction link'), $this->link)
            ->line($this->content[1]);
    }

    public function toArray(object $notifiable): array
    {
        $content    = '*'. $this->subject ."*\n\n";
        $content   .= $this->content[0] . "\n\n";
        $content   .= $this->link . "\n\n";
        $content   .= $this->content[1] . "\n\n";

        return $content;
    }
}
