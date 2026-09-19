<?php

namespace App\Notifications\Admin;

use App\Models\File;
use App\Notifications\Channels\DatabaseChannel;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrackBackupFailed extends Notification implements ShouldQueue
{
    use Queueable;

    protected $file;

    protected $attempts;

    protected $error;

    protected $subject;

    protected $content;

    public function __construct(File $file, int $attempts, string $error)
    {
        $this->file = $file;
        $this->attempts = $attempts;
        $this->error = $error;
        $this->subject = __('Track file backup failed');
        $this->content = [
            'Track file "'.($file->original_name ?: 'unknown').'" failed to back up after '.$attempts.' attempts.',
            'Error: '.$error,
            'Please check the FTP backup configuration.',
        ];
    }

    public function via(object $notifiable): array
    {
        return ['mail', DatabaseChannel::class, WhatsAppService::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'subject' => $this->subject,
            'message' => $this->content[0],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->content[0])
            ->line($this->content[1])
            ->line($this->content[2]);
    }

    public function toWhatsAppService(object $notifiable)
    {
        $content = '*'.$this->subject."*\n\n";
        $content .= $this->content[0]."\n\n";
        $content .= $this->content[1]."\n\n";
        $content .= $this->content[2]."\n\n";

        return $content;
    }
}
