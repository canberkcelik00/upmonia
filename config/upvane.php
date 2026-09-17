<?php

return [

    'default_region' => env('DEFAULT_MONITOR_REGION', 'local'),

    'check_retention_hours' => (int) env('CHECK_RETENTION_HOURS', 48),

    'allow_private_targets' => env('UPVANE_ALLOW_PRIVATE_TARGETS', false) && ! app()->environment('production'),

    // Guards /deploy — see App\Http\Controllers\DeployController. Leave unset to disable it
    // entirely (the controller 404s rather than falling back to "no token required").
    'deploy_token' => env('DEPLOY_TOKEN'),

    'resend' => [
        'api_key' => env('RESEND_API_KEY'),
        'from' => env('RESEND_FROM'),
    ],

    // Probe tick (App\Console\Commands\ProbeRun) tuning — sized so a single cron-triggered
    // PHP process comfortably finishes within cPanel's 1-minute cron granularity.
    'probe' => [
        'batch_size' => (int) env('UPVANE_PROBE_BATCH_SIZE', 60),
        'concurrency' => (int) env('UPVANE_PROBE_CONCURRENCY', 20),
        'lock_seconds' => (int) env('UPVANE_PROBE_LOCK_SECONDS', 50),
    ],

    // SSRF guard port allowlists (App\Checks\SsrfGuard). Anything not listed here is refused
    // even if the resolved IP itself is public — keeps a misconfigured monitor from being used
    // to port-scan the wider internet from this server.
    'ssrf' => [
        'http_ports' => array_values(array_unique(array_merge(
            [80, 443, 8080, 8443],
            array_filter(array_map('intval', explode(',', (string) env('UPVANE_EXTRA_HTTP_PORTS', ''))))
        ))),
        'tcp_ports' => array_values(array_unique(array_merge(
            [21, 22, 25, 53, 110, 143, 443, 465, 587, 993, 995, 3306, 5432, 6379, 8080, 8443, 9200, 11211, 27017],
            array_filter(array_map('intval', explode(',', (string) env('UPVANE_EXTRA_TCP_PORTS', ''))))
        ))),
    ],

];
