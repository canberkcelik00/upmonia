<?php

return [

    'welcome' => [
        'subject' => 'Welcome to Uptik',
        'greeting' => 'Hi :name,',
        'body' => 'Your Uptik account is ready. You can start by adding your first monitor.',
        'cta' => 'Go to dashboard',
    ],

    'verify_email' => [
        'subject' => 'Verify your email address',
        'greeting' => 'Hi :name,',
        'body' => 'Click the link below to verify your email address. The link is valid for 24 hours.',
        'cta' => 'Verify my email',
        'ignore' => 'If you did not request this, you can safely ignore this email.',
    ],

    'reset_password' => [
        'subject' => 'Password reset request',
        'greeting' => 'Hi :name,',
        'body' => 'We received a request to reset your account password. Click the link below to set a new one. The link is valid for 60 minutes.',
        'cta' => 'Reset my password',
        'ignore' => 'If you did not request this, you can safely ignore this email — your password will not change.',
    ],

    'channel_verify' => [
        'subject' => 'Verify your notification channel',
        'body' => ':org wants to add you as an alert notification channel. Click the link below to confirm. The link is valid for 24 hours.',
        'cta' => 'Verify channel',
        'ignore' => 'If you did not request this, you can safely ignore this email.',
    ],

    'incident_triggered' => [
        'subject' => ':monitor is now DOWN',
        'body' => 'An outage was detected for :monitor.',
        'cause' => 'Cause',
        'started_at' => 'Started at',
        'flapping_warning' => 'This monitor has changed state more than once in the last 10 minutes (flapping) — this may be a transient network issue.',
        'cta' => 'View in dashboard',
    ],

    'test_email' => [
        'subject' => 'Uptik test email',
        'body' => 'This is a test email for the ":channel" notification channel. If you can see this, the channel is working correctly.',
    ],

    'incident_resolved' => [
        'subject' => ':monitor is back UP',
        'body' => 'The outage for :monitor has ended.',
        'duration' => 'Duration',
        'cta' => 'View in dashboard',
    ],

];
