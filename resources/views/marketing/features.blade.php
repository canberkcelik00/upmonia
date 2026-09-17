<x-layouts::marketing :title="__('marketing.features_title').' — '.config('app.name')" :description="__('marketing.features_meta_description')">
    <section class="mx-auto max-w-[1200px] px-4 pt-12 pb-10 sm:px-6 lg:px-7 lg:pt-16">
        <div data-reveal>
            <h1 class="max-w-[20ch] text-display font-bold text-ink" style="text-wrap: balance;">{{ __('marketing.features_hero_title') }}</h1>
            <p class="mt-4 max-w-[58ch] text-[16px] text-ink-2">{{ __('marketing.features_hero_lede') }}</p>
        </div>

        {{-- Six short cards, one scan, no scrolling past a form's worth of detail per
             feature (see the brand guide's simplified "Tanıtım: özellikler" screen). --}}
        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                'monitoring', 'incidents', 'notifications',
                'clients', 'maintenance', 'account',
            ] as $i => $feature)
                <div
                    class="rounded-panel border border-line bg-surface p-5 transition-all duration-150 hover:-translate-y-1 hover:border-line-strong"
                    data-reveal
                    data-reveal-delay="{{ $i * 0.05 }}s"
                >
                    <h2 class="text-[14.5px] font-semibold text-ink">{{ __("marketing.feature_{$feature}_title") }}</h2>
                    <p class="mt-1.5 text-[13.5px] text-muted">{{ __("marketing.feature_{$feature}_desc") }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="border-t border-line bg-surface">
        <div class="mx-auto flex max-w-[1200px] flex-col items-center gap-5 px-4 py-16 text-center sm:px-6 lg:px-7" data-reveal>
            <p class="max-w-[24ch] text-[25px] font-bold tracking-[-0.02em] text-ink">{{ __('marketing.features_cta_title') }}</p>
            <x-ui.button variant="primary" :href="route('signup')">{{ __('marketing.nav_signup') }}</x-ui.button>
        </div>
    </section>
</x-layouts::marketing>
