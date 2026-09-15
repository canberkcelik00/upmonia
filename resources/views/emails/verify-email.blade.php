@component('emails.layout', ['locale' => $locale])
<p>{{ __('mail.verify_email.greeting', ['name' => $user->name], $locale) }}</p>
<p>{{ __('mail.verify_email.body', [], $locale) }}</p>
<p>
    <a href="{{ $verifyUrl }}" style="display:inline-block;background:#171717;color:#ffffff;text-decoration:none;padding:10px 20px;border-radius:6px;font-size:14px;">
        {{ __('mail.verify_email.cta', [], $locale) }}
    </a>
</p>
<p style="color:#737373;font-size:12px;">{{ __('mail.verify_email.ignore', [], $locale) }}</p>
@endcomponent
