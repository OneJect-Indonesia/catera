<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyAccessLogExportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $downloadUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Weekly Access Log Export - '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-export',
            with: [
                'downloadUrl' => $this->downloadUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
