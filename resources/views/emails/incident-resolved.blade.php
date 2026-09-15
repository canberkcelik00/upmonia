@component('emails.layout', ['locale' => $locale])
<p><strong>{{ __('mail.incident_resolved.body', ['monitor' => $monitor->name], $locale) }}</strong></p>

<table role="presentation" style="font-size:13px;color:#404040;margin:16px 0;">
    <tr>
        <td style="padding:2px 12px 2px 0;color:#737373;">{{ __('mail.incident_resolved.duration', [], $locale) }}</td>
        <td>{{ \Carbon\CarbonInterval::seconds($incident->duration_s ?? 0)->cascade()->locale($locale)->forHumans(['short' => true]) }}</td>
    </tr>
</table>

<p>
    <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#171717;color:#ffffff;text-decoration:none;padding:10px 20px;border-radius:6px;font-size:14px;">
        {{ __('mail.incident_resolved.cta', [], $locale) }}
    </a>
</p>
@endcomponent
