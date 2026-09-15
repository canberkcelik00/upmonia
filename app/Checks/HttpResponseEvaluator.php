<?php

namespace App\Checks;

use App\Models\Monitor;

/**
 * The status/keyword pass-or-fail rules, factored out so both the synchronous HttpChecker
 * (single monitor, e.g. the dashboard's "check now" button) and ConcurrentHttpChecker (the
 * curl_multi batch path ProbeRun uses) apply the exact same business rules regardless of
 * which transport fetched the response.
 */
class HttpResponseEvaluator
{
    /**
     * @return array{ok: bool, errorClass: ?string, errorMsg: ?string}
     */
    public static function evaluate(Monitor $monitor, int $statusCode, string $body): array
    {
        $expected = $monitor->expected_status ?: null;
        $statusOk = $expected ? in_array($statusCode, $expected, true) : ($statusCode >= 200 && $statusCode < 400);

        if (! $statusOk) {
            return [
                'ok' => false,
                'errorClass' => $statusCode >= 500 ? 'http_5xx' : 'http_unexpected_status',
                'errorMsg' => "Received status {$statusCode}",
            ];
        }

        if ($monitor->type === 'keyword' && $monitor->keyword) {
            $found = str_contains($body, $monitor->keyword);
            $wantPresent = $monitor->keyword_mode !== 'absent';

            if ($found !== $wantPresent) {
                return [
                    'ok' => false,
                    'errorClass' => $wantPresent ? 'keyword_missing' : 'keyword_present',
                    'errorMsg' => $wantPresent ? 'Expected keyword not found in response body' : 'Unwanted keyword found in response body',
                ];
            }
        }

        return ['ok' => true, 'errorClass' => null, 'errorMsg' => null];
    }
}
