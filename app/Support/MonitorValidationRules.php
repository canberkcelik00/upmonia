<?php

namespace App\Support;

/**
 * Per-type required-field validation shared by the create (monitors.form) and edit-in-place
 * (monitors.show) Livewire components, so the two can't drift apart. Mirrors the
 * monitors_target_ck CHECK constraint in the create_monitors_table migration — enforced at
 * both layers deliberately (defense in depth), not because either one alone is untrusted.
 */
class MonitorValidationRules
{
    public static function rules(string $type): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:http,keyword,ssl,tcp_port,heartbeat'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'interval_s' => ['required', 'integer', 'min:60', 'max:86400'],
            'timeout_ms' => ['required', 'integer', 'min:1000', 'max:60000'],
            'confirm_threshold' => ['required', 'integer', 'min:1', 'max:10'],
            'recover_threshold' => ['required', 'integer', 'min:1', 'max:10'],
            'enabled' => ['boolean'],
        ];

        if (in_array($type, ['http', 'keyword', 'ssl'], true)) {
            $rules['url'] = ['required', 'url', 'max:2048'];
        }

        if (in_array($type, ['http', 'keyword'], true)) {
            $rules['method'] = ['required', 'in:GET,POST,PUT,PATCH,DELETE,HEAD'];
            $rules['expected_status_raw'] = ['nullable', 'string'];
            $rules['follow_redirects'] = ['boolean'];
            $rules['max_redirects'] = ['required', 'integer', 'min:0', 'max:10'];
            $rules['verify_ssl'] = ['boolean'];
            $rules['headers_raw'] = ['nullable', 'string'];
            $rules['body'] = ['nullable', 'string', 'max:65535'];
        }

        if ($type === 'keyword') {
            $rules['keyword'] = ['required', 'string', 'max:255'];
            $rules['keyword_mode'] = ['required', 'in:present,absent'];
        }

        if ($type === 'ssl') {
            $rules['ssl_warn_days'] = ['required', 'integer', 'min:1', 'max:90'];
            $rules['verify_ssl'] = ['boolean'];
        }

        if ($type === 'tcp_port') {
            $rules['host'] = ['required', 'string', 'max:255'];
            $rules['port'] = ['required', 'integer', 'min:1', 'max:65535'];
        }

        if ($type === 'heartbeat') {
            $rules['heartbeat_grace_s'] = ['required', 'integer', 'min:60', 'max:86400'];
        }

        return $rules;
    }

    /**
     * Parses the textarea-friendly "Key: Value" per line format used by the form into the
     * associative array Monitor::$headers (encrypted cast) expects.
     */
    public static function parseHeaders(?string $raw): array
    {
        $headers = [];

        foreach (explode("\n", (string) $raw) as $line) {
            $line = trim($line);
            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = explode(':', $line, 2);
            $headers[trim($key)] = trim($value);
        }

        return $headers;
    }

    public static function headersToRaw(?array $headers): string
    {
        if (empty($headers)) {
            return '';
        }

        $lines = [];
        foreach ($headers as $key => $value) {
            $lines[] = "{$key}: {$value}";
        }

        return implode("\n", $lines);
    }

    /**
     * Parses a comma-separated list of status codes ("200, 201, 204") into an int array.
     */
    public static function parseExpectedStatus(?string $raw): array
    {
        if (blank($raw)) {
            return [];
        }

        return collect(explode(',', $raw))
            ->map(fn ($v) => trim($v))
            ->filter(fn ($v) => $v !== '' && ctype_digit($v))
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();
    }

    public static function expectedStatusToRaw(?array $codes): string
    {
        return implode(', ', $codes ?? []);
    }
}
