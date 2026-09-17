@component('emails.layout', ['locale' => $locale])
<p>{{ __('mail.reset_password.greeting', ['name' => $user->name], $locale) }}</p>
<p>{{ __('mail.reset_password.body', [], $locale) }}</p>
<p>
    <a href="{{ $resetUrl }}" style="display:inline-block;background:#0d0e12;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;font-size:14px;font-weight:500;">
        {{ __('mail.reset_password.cta', [], $locale) }}
    </a>
</p>
<p style="color:#6a6d76;font-size:12px;">{{ __('mail.reset_password.ignore', [], $locale) }}</p>
@endcomponent
