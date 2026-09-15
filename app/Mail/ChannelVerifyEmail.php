<?php

namespace App\Mail;

use App\Models\AlertChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChannelVerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AlertChannel $channel,
        public string $rawToken,
        public string $toAddress,
        public string $recipientLocale,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.channel_verify.subject', [], $this->recipientLocale),
            to: [$this->toAddress],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.channel-verify',
            with: [
                'orgName' => $this->channel->organization->name,
                'locale' => $this->recipientLocale,
                'verifyUrl' => route('verify-channel', ['token' => $this->rawToken]),
            ],
        );
    }
}
