<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NotificaHtml extends Mailable
{
    public function __construct(public string $oggettoEmail, public string $corpoHtml) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->oggettoEmail);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->corpoHtml);
    }
}
