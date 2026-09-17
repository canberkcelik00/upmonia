@component('emails.layout', ['locale' => $locale])
<p>{{ __('mail.welcome.greeting', ['name' => $user->name], $locale) }}</p>
<p>{{ __('mail.welcome.body', [], $locale) }}</p>
<p>
    <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#0d0e12;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;font-size:14px;font-weight:500;">
        {{ __('mail.welcome.cta', [], $locale) }}
    </a>
</p>
@endcomponent
