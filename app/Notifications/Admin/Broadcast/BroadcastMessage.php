<?php

namespace App\Notifications\Admin\Broadcast;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

use App\Notifications\Channels\DatabaseChannel;
use App\Models\Broadcast;

use App\Services\WhatsAppService;

class BroadcastMessage extends Notification implements ShouldQueue
{
    use Queueable;

    protected $broadcast;
    protected $subject;
    protected $message;

    public function __construct(Broadcast $broadcast)
    {
        $this->broadcast = $broadcast;
        $this->subject = $broadcast->subject;
        $this->message = $broadcast->message;
    }

    public function via(object $notifiable): array
    {
        return ['mail', DatabaseChannel::class, WhatsAppService::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'subject' => $this->subject,
            'message' => $this->message,
            'route' => [
                'name' => 'admin.broadcast.show',
                'id' => $this->broadcast->id,
            ]
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->line(new HtmlString(nl2br(e($this->message))));
    }

    public function toWhatsAppService(object $notifiable)
    {
        $content    = '*'. $this->subject ."*\n\n";
        $content   .= $this->message;

        return $content;
    }
}
