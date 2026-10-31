<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class StatusUpdateMail extends Mailable
{
    public function __construct(public array $messageData) {}

    public function build(): self
    {
        return $this->subject($this->messageData['subject'])->view('emails.status-update',['messageData'=>$this->messageData]);
    }
}
