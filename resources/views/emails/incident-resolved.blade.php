@php
    $toneHex = ['up' => '#12a05c', 'warn' => '#e5a00d', 'down' => '#e03a2f', 'idle' => '#a3a5ad', 'nodata' => '#e7e7eb'];
@endphp
@component('emails.layout', ['locale' => $locale, 'tone' => 'up'])
<table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom:14px;">
    <tr><td style="background:#e6f6ee;color:#0a7a44;font-size:12.5px;font-weight:600;padding:3px 9px;border-radius:5px;">{{ __('app.status_up', [], $locale) }}</td></tr>
</table>

<h1 style="font-size:20px;font-weight:700;letter-spacing:-.02em;margin:0 0 8px;">{{ __('mail.incident_resolved.body', ['monitor' => $monitor->name], $locale) }}</h1>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;margin:16px 0;border-top:1px solid #e4e4e8;">
    <tr>
        <td style="padding:8px 0;border-bottom:1px solid #e4e4e8;color:#6a6d76;width:110px;">{{ __('mail.incident_resolved.duration', [], $locale) }}</td>
        <td style="padding:8px 0;border-bottom:1px solid #e4e4e8;">{{ \App\Support\Format::shortDuration($incident->duration_s ?? 0) }}</td>
    </tr>
    @if ($clientName)
    <tr>
        <td style="padding:8px 0;border-bottom:1px solid #e4e4e8;color:#6a6d76;">{{ __('mail.client_label', [], $locale) }}</td>
        <td style="padding:8px 0;border-bottom:1px solid #e4e4e8;">{{ $clientName }}</td>
    </tr>
    @endif
</table>

@if (count($ticks))
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 18px;"><tr>
    @foreach ($ticks as $tone)
    <td style="width:8px;height:18px;background:{{ $toneHex[$tone] ?? $toneHex['nodata'] }};font-size:0;line-height:0;">&nbsp;</td>
    <td style="width:2px;font-size:0;">&nbsp;</td>
    @endforeach
</tr></table>
@endif

<p>
    <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#0d0e12;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;font-size:14px;font-weight:500;">
        {{ __('mail.incident_resolved.cta', [], $locale) }}
    </a>
</p>
@endcomponent
