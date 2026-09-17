@component('emails.layout', ['locale' => $locale])
<p>{{ __('mail.verify_email.greeting', ['name' => $user->name], $locale) }}</p>
<p>{{ __('mail.verify_email.body', [], $locale) }}</p>
<p>
    <a href="{{ $verifyUrl }}" style="display:inline-block;background:#0d0e12;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;font-size:14px;font-weight:500;">
        {{ __('mail.verify_email.cta', [], $locale) }}
    </a>
</p>
<p style="color:#6a6d76;font-size:12px;">{{ __('mail.verify_email.ignore', [], $locale) }}</p>
@endcomponent
