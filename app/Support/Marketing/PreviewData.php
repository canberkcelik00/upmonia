<?php

namespace App\Support\Marketing;

/**
 * Fixed sample data for the marketing pages' live product previews (see
 * docs/brand/upmonia-brand-guidelines.html, "Uygulamalar" → "Tanıtım"). These previews are
 * real Blade components (x-ui.tick-strip, x-ui.status-pill, ...) rendered with sample rows —
 * never a screenshot — so they stay pixel-identical to the actual app and re-tone with it.
 * Numbers here are illustrative, never claimed as real product metrics.
 */
class PreviewData
{
    /**
     * @return list<array{name: string, sub: string, tones: list<string>, status: string, aria: string}>
     */
    public static function monitors(): array
    {
        return [
            [
                'name' => 'Acme Mağaza — ödeme sayfası',
                'sub' => __('app.monitor_type_http'),
                'tones' => self::tones('u27d3'),
                'status' => 'down',
                'aria' => __('marketing.preview_ticks_aria_down', ['ok' => 27, 'fail' => 3]),
            ],
            [
                'name' => 'Kuzey Lojistik — müşteri paneli',
                'sub' => __('app.monitor_type_keyword'),
                'tones' => self::tones('u30'),
                'status' => 'up',
                'aria' => __('marketing.preview_ticks_aria_clean'),
            ],
            [
                'name' => 'Bosphorus Dental — randevu formu',
                'sub' => __('app.monitor_type_http'),
                'tones' => self::tones('u30'),
                'status' => 'up',
                'aria' => __('marketing.preview_ticks_aria_clean'),
            ],
        ];
    }

    /** Wide banner strip under the hero — the brand's signature graphic, at hero scale. */
    public static function heroStripTones(): array
    {
        return self::tones('u31s1u25d3');
    }

    /**
     * Expands a compact run-length code ("u27d3" = 27 up, 3 down) into a tone list, matching
     * the shorthand the brand book's own `data-t` attribute uses. u=up w=warn d=down p=idle n=nodata.
     *
     * @return list<string>
     */
    private static function tones(string $code): array
    {
        $map = ['u' => 'up', 'w' => 'warn', 'd' => 'down', 'p' => 'idle', 'n' => 'nodata'];
        $tones = [];

        preg_match_all('/([uwdpn])(\d+)/', $code, $matches, PREG_SET_ORDER);

        foreach ($matches as [, $letter, $count]) {
            $tones = array_merge($tones, array_fill(0, (int) $count, $map[$letter]));
        }

        return $tones;
    }
}
