<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MarketingPagesTest extends TestCase
{
    public function test_home_page_loads_in_turkish(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        // The headline's outage word is wrapped in its own <span> for the colour accent
        // (see marketing.hero_title_accent), so the sentence is checked as rendered text,
        // not as one unbroken string in the HTML source.
        $response->assertSeeText('Müşteri sitelerinden biri çökünce ilk sen bil.');
    }

    public function test_home_page_loads_in_english(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get('/');

        $response->assertOk();
        $response->assertSeeText('Know first when a client site goes down.');
    }

    public function test_features_page_loads(): void
    {
        $response = $this->get('/ozellikler');

        $response->assertOk();
        $response->assertSee(route('signup'), false);
    }

    /**
     * No sales/pricing language anywhere in the marketing views — see the plan's "Satış yok"
     * decision and docs/brand/upmonia-brand-guidelines.html, "Tanıtım sayfaları".
     */
    public function test_marketing_views_have_no_pricing_or_sales_language(): void
    {
        $files = File::allFiles(resource_path('views/marketing'));
        $files[] = new \SplFileInfo(resource_path('views/layouts/marketing.blade.php'));

        $forbidden = ['fiyat', 'ücretsiz dene', 'deneme süresi', 'pricing', 'free trial', '₺'];

        foreach ($files as $file) {
            $contents = mb_strtolower(File::get($file->getPathname()));

            foreach ($forbidden as $word) {
                $this->assertStringNotContainsString($word, $contents, "{$file->getFilename()} contains forbidden sales/pricing text: \"{$word}\"");
            }
        }
    }

    /**
     * Brand guard: marketing views only use the neutral/status design tokens (bg-surface,
     * text-ink, bg-down-soft, ...), never a raw Tailwind palette colour, an uppercase
     * transform, a gradient or a shadow beyond the system's one popover shadow — see
     * docs/brand/upmonia-brand-guidelines.html, "Renk" and "Arayüz".
     */
    public function test_marketing_views_stay_inside_the_brand_system(): void
    {
        $files = File::allFiles(resource_path('views/marketing'));
        $files[] = new \SplFileInfo(resource_path('views/layouts/marketing.blade.php'));

        $patterns = [
            '/\b(bg|text|border)-(red|green|emerald|amber|yellow|blue|indigo|purple|pink|orange|teal|cyan|sky|violet|fuchsia|rose|lime|gray|zinc|neutral|stone|slate)-\d{2,3}\b/' => 'raw Tailwind palette colour',
            '/\buppercase\b/' => 'uppercase transform',
            '/\bbg-gradient-/' => 'gradient',
            '/\bshadow-(sm|md|lg|xl|2xl)\b/' => 'shadow beyond the system shadow',
        ];

        foreach ($files as $file) {
            $contents = File::get($file->getPathname());

            foreach ($patterns as $pattern => $label) {
                $this->assertDoesNotMatchRegularExpression($pattern, $contents, "{$file->getFilename()} uses a {$label}, outside the brand system.");
            }
        }
    }
}
