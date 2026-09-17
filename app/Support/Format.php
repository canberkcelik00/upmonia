<?php

namespace App\Support;

/**
 * Locale-aware number/duration formatting for the brand's "rakam veridir" rule (see
 * docs/brand/upmonia-brand-guidelines.html, section 05): every figure in the product goes
 * through here so tr/en punctuation and rounding stay consistent in one place.
 */
class Format
{
    private static function decimalSeparator(): string
    {
        return app()->getLocale() === 'tr' ? ',' : '.';
    }

    private static function groupSeparator(): string
    {
        return app()->getLocale() === 'tr' ? '.' : ',';
    }

    /** "1.240" / "1,240" — grouped integer, no decimals. */
    public static function intGroup(int $value): string
    {
        return number_format($value, 0, self::decimalSeparator(), self::groupSeparator());
    }

    /** "1.240 ms" / "1,240 ms". */
    public static function ms(?int $value): string
    {
        if ($value === null) {
            return '—';
        }

        return self::intGroup($value).' ms';
    }

    /** "1.436×" — a merged run of identical consecutive check results. */
    public static function count(int $value): string
    {
        return self::intGroup($value).'×';
    }

    /**
     * "%99,98" / "99.98%". Always rounds DOWN (a monitor never looks healthier than it was),
     * and only ever shows 100% when there were truly zero failed checks in the window.
     */
    public static function percent(int $ok, int $failed): ?string
    {
        $total = $ok + $failed;

        if ($total === 0) {
            return null;
        }

        if ($failed === 0) {
            $value = '100';
        } else {
            $ratio = floor(($ok / $total) * 10000) / 100;
            $ratio = min($ratio, 99.99); // a failure exists, so it can never display as 100.00
            $value = number_format($ratio, 2, self::decimalSeparator(), self::groupSeparator());
        }

        return app()->getLocale() === 'tr' ? "%{$value}" : "{$value}%";
    }

    /** "04:12" / "1:02:40" — a live, still-running duration. */
    public static function liveDuration(int|float $seconds): string
    {
        $seconds = (int) $seconds;
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        return $h > 0
            ? sprintf('%d:%02d:%02d', $h, $m, $s)
            : sprintf('%02d:%02d', $m, $s);
    }

    /** "18 dk" / "2 sa 14 dk" / "3 gün 1 sa" — a finished duration, at most two units. */
    public static function shortDuration(int|float $seconds): string
    {
        $seconds = (int) $seconds;
        $isTr = app()->getLocale() === 'tr';
        $units = $isTr
            ? ['gün' => 86400, 'sa' => 3600, 'dk' => 60, 'sn' => 1]
            : ['d' => 86400, 'h' => 3600, 'min' => 60, 's' => 1];

        $parts = [];
        $remaining = $seconds;

        foreach ($units as $label => $unitSeconds) {
            if (count($parts) === 2) {
                break;
            }

            $n = intdiv($remaining, $unitSeconds);

            if ($n > 0) {
                $parts[] = $isTr ? "{$n} {$label}" : "{$n}{$label}";
                $remaining -= $n * $unitSeconds;
            } elseif (! empty($parts)) {
                break;
            }
        }

        if (empty($parts)) {
            return $isTr ? '0 sn' : '0s';
        }

        return implode(' ', $parts);
    }
}
