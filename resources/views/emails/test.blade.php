@component('emails.layout', ['locale' => $locale])
<p>{{ __('mail.test_email.body', ['channel' => $channelName], $locale) }}</p>
@endcomponent
