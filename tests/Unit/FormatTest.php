<?php

namespace Tests\Unit;

use App\Support\Format;
use Tests\TestCase;

class FormatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('tr');
    }

    public function test_percent_rounds_down_and_never_shows_100_with_failures(): void
    {
        // 999/1000 = 99.9%, would round to 100.00 on naive rounding — must stay below 100.
        $this->assertSame('%99,90', Format::percent(999, 1));
        $this->assertSame('%100', Format::percent(1000, 0));
        $this->assertNull(Format::percent(0, 0));
    }

    public function test_percent_floors_instead_of_rounding_to_nearest(): void
    {
        // 2/3 = 66.666...% — floors to 66.66, not 66.67.
        $this->assertSame('%66,66', Format::percent(2, 1));
    }

    public function test_percent_uses_english_punctuation_in_en_locale(): void
    {
        app()->setLocale('en');

        $this->assertSame('99.90%', Format::percent(999, 1));
        $this->assertSame('100%', Format::percent(1000, 0));
    }

    public function test_ms_and_int_group_use_turkish_thousands_separator(): void
    {
        $this->assertSame('1.240 ms', Format::ms(1240));
        $this->assertSame('212 ms', Format::ms(212));
        $this->assertSame('—', Format::ms(null));
        $this->assertSame('1.436×', Format::count(1436));
    }

    public function test_live_duration_switches_to_hh_mm_ss_past_an_hour(): void
    {
        $this->assertSame('04:12', Format::liveDuration(252));
        $this->assertSame('1:02:40', Format::liveDuration(3760));
    }

    public function test_short_duration_uses_at_most_two_units(): void
    {
        $this->assertSame('18 dk', Format::shortDuration(18 * 60));
        $this->assertSame('2 sa 14 dk', Format::shortDuration(2 * 3600 + 14 * 60 + 5));
        $this->assertSame('0 sn', Format::shortDuration(0));
    }
}
