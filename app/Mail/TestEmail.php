<?php

namespace App\Mail;

use App\Models\AlertChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AlertChannel $channel, public string $recipientLocale) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.test_email.subject', [], $this->recipientLocale));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.test',
            with: ['channelName' => $this->channel->name, 'locale' => $this->recipientLocale],
        );
    }
}
