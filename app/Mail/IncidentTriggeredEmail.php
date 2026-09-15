<?php

namespace App\Mail;

use App\Checks\ErrorClassifier;
use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IncidentTriggeredEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Incident $incident, public string $recipientLocale) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.incident_triggered.subject', ['monitor' => $this->incident->monitor->name], $this->recipientLocale),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.incident-triggered',
            with: [
                'incident' => $this->incident,
                'monitor' => $this->incident->monitor,
                'locale' => $this->recipientLocale,
                'causeLabel' => ErrorClassifier::label($this->incident->cause_class, $this->recipientLocale),
                'dashboardUrl' => route('incidents.index'),
            ],
        );
    }
}
