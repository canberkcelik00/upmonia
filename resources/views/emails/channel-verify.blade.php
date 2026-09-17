@component('emails.layout', ['locale' => $locale])
<p>{{ __('mail.channel_verify.body', ['org' => $orgName], $locale) }}</p>
<p>
    <a href="{{ $verifyUrl }}" style="display:inline-block;background:#0d0e12;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;font-size:14px;font-weight:500;">
        {{ __('mail.channel_verify.cta', [], $locale) }}
    </a>
</p>
<p style="color:#6a6d76;font-size:12px;">{{ __('mail.channel_verify.ignore', [], $locale) }}</p>
@endcomponent
