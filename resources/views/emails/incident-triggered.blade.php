@component('emails.layout', ['locale' => $locale])
<p><strong>{{ __('mail.incident_triggered.body', ['monitor' => $monitor->name], $locale) }}</strong></p>

<table role="presentation" style="font-size:13px;color:#404040;margin:16px 0;">
    <tr>
        <td style="padding:2px 12px 2px 0;color:#737373;">{{ __('mail.incident_triggered.cause', [], $locale) }}</td>
        <td>{{ $causeLabel }}</td>
    </tr>
    <tr>
        <td style="padding:2px 12px 2px 0;color:#737373;">{{ __('mail.incident_triggered.started_at', [], $locale) }}</td>
        <td>{{ $incident->started_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
    </tr>
</table>

@if ($incident->flapping)
<p style="background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:10px 12px;font-size:13px;color:#92400e;">
    {{ __('mail.incident_triggered.flapping_warning', [], $locale) }}
</p>
@endif

<p>
    <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#171717;color:#ffffff;text-decoration:none;padding:10px 20px;border-radius:6px;font-size:14px;">
        {{ __('mail.incident_triggered.cta', [], $locale) }}
    </a>
</p>
@endcomponent
