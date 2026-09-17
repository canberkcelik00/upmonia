<x-layouts::marketing :title="config('app.name')" :description="__('marketing.meta_description')">
    {{-- Hero: the single-sentence answer, plus a live product preview next to it —
         never a screenshot, the same x-ui components the real app renders with. --}}
    <section class="mx-auto grid max-w-[1200px] items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:gap-14 lg:px-7 lg:py-16">
        <div data-reveal>
            <h1 class="text-display font-bold text-ink" style="text-wrap: balance;">{{ __('marketing.hero_title_before') }}<span class="text-down">{{ __('marketing.hero_title_accent') }}</span>{{ __('marketing.hero_title_after') }}</h1>
            <p class="mt-4 max-w-[42ch] text-[16px] text-ink-2">{{ __('marketing.hero_lede') }}</p>
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <x-ui.button variant="primary" :href="route('signup')">{{ __('marketing.nav_signup') }}</x-ui.button>
                <x-ui.button variant="ghost" href="#nasil-calisir">
                    {{ __('marketing.hero_cta_secondary') }}
                    <x-phosphor-arrow-right class="size-3.5" />
                </x-ui.button>
            </div>
        </div>

        <div data-reveal data-reveal-delay=".1s">
            @include('marketing.previews.monitors')
        </div>
    </section>

    {{-- The brand's signature graphic, at banner scale — the same tick strip every monitor
         row uses, shown large the way the brand book's own cover does. --}}
    <section class="border-y border-line bg-surface">
        <div class="mx-auto max-w-[1200px] px-4 py-6 sm:px-6 lg:px-7">
            <x-ui.tick-strip
                :tones="\App\Support\Marketing\PreviewData::heroStripTones()"
                :label="__('marketing.strip_caption')"
                class="h-14 gap-[3px] sm:h-16"
                data-reveal-stagger
            />
            <p class="mt-2 flex items-center justify-between gap-3 font-mono text-[11.5px] text-muted" data-reveal data-reveal-delay=".35s">
                <span>13:32</span>
                <span>{{ __('marketing.strip_caption') }}</span>
                <span class="text-down-text">14:29 {{ mb_strtolower(__('app.status_down')) }}</span>
            </p>
        </div>
    </section>

    {{-- The three principles, as one plain numbered list — the point is that this reads as
         a distinct kind of block from the hero and the strip above it, not a repeat of the
         same "cards in a row" pattern (see the brand guide's second-pass "Tanıtım: ana
         sayfa" screen). Each item gets its own border, but stays a single-column list, not
         a grid of boxes side by side. --}}
    <section id="nasil-calisir" class="mx-auto max-w-[900px] px-4 py-16 sm:px-6 lg:px-7">
        <div class="grid gap-3">
            <div class="grid grid-cols-[28px_1fr] items-baseline gap-4 rounded-panel border border-line bg-surface p-5 transition-transform duration-150 hover:-translate-y-0.5" data-reveal>
                <span class="font-mono text-xs text-faint">01</span>
                <div>
                    <h2 class="text-[17px] font-semibold tracking-[-0.01em] text-ink">{{ __('marketing.principle_color_title') }}</h2>
                    <p class="mt-1 max-w-[56ch] text-[14.5px] text-muted">{{ __('marketing.principle_color_body') }}</p>
                </div>
            </div>
            <div class="grid grid-cols-[28px_1fr] items-baseline gap-4 rounded-panel border border-line bg-surface p-5 transition-transform duration-150 hover:-translate-y-0.5" data-reveal data-reveal-delay=".08s">
                <span class="font-mono text-xs text-faint">02</span>
                <div>
                    <h2 class="text-[17px] font-semibold tracking-[-0.01em] text-ink">{{ __('marketing.principle_answer_title') }}</h2>
                    <p class="mt-1 max-w-[56ch] text-[14.5px] text-muted">{{ __('marketing.principle_answer_body') }}</p>
                </div>
            </div>
            <div class="grid grid-cols-[28px_1fr] items-baseline gap-4 rounded-panel border border-line bg-surface p-5 transition-transform duration-150 hover:-translate-y-0.5" data-reveal data-reveal-delay=".16s">
                <span class="font-mono text-xs text-faint">03</span>
                <div>
                    <h2 class="text-[17px] font-semibold tracking-[-0.01em] text-ink">{{ __('marketing.principle_number_title') }}</h2>
                    <p class="mt-1 max-w-[56ch] text-[14.5px] text-muted">{{ __('marketing.principle_number_body') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Closing CTA. --}}
    <section class="border-t border-line bg-surface">
        <div class="mx-auto flex max-w-[1200px] flex-col items-center gap-5 px-4 py-16 text-center sm:px-6 lg:px-7" data-reveal>
            <p class="max-w-[24ch] text-[25px] font-bold tracking-[-0.02em] text-ink">{{ __('marketing.cta_title') }}</p>
            <x-ui.button variant="primary" :href="route('signup')">{{ __('marketing.nav_signup') }}</x-ui.button>
        </div>
    </section>
</x-layouts::marketing>
