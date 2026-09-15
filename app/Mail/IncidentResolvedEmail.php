<?php

namespace App\Mail;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IncidentResolvedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Incident $incident, public string $recipientLocale) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.incident_resolved.subject', ['monitor' => $this->incident->monitor->name], $this->recipientLocale),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.incident-resolved',
            with: [
                'incident' => $this->incident,
                'monitor' => $this->incident->monitor,
                'locale' => $this->recipientLocale,
                'dashboardUrl' => route('incidents.index'),
            ],
        );
    }
}
