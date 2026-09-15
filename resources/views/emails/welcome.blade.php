@component('emails.layout', ['locale' => $locale])
<p>{{ __('mail.welcome.greeting', ['name' => $user->name], $locale) }}</p>
<p>{{ __('mail.welcome.body', [], $locale) }}</p>
<p>
    <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#171717;color:#ffffff;text-decoration:none;padding:10px 20px;border-radius:6px;font-size:14px;">
        {{ __('mail.welcome.cta', [], $locale) }}
    </a>
</p>
@endcomponent
