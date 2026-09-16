{{-- overflow-x-auto (not overflow-hidden) so a wide table scrolls on small screens
     instead of being clipped; the inner min-w keeps columns readable while scrolling. --}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-[var(--shadow-card)] dark:border-neutral-800 dark:bg-neutral-900']) }}>
    <table class="w-full min-w-[34rem] text-sm">
        @isset($head)
            <thead class="border-b border-neutral-200 text-left text-xs font-medium tracking-wide text-neutral-500 uppercase dark:border-neutral-800 dark:text-neutral-400">
                <tr class="[&>th]:px-5 [&>th]:py-3">{{ $head }}</tr>
            </thead>
        @endisset
        {{-- Cell padding lives here so every table shares one rhythm; the descendant
             selector outranks any px-*/py-* left on individual cells. --}}
        <tbody class="divide-y divide-neutral-100 [&_td]:px-5 [&_td]:py-3.5 dark:divide-neutral-800/80">
            {{ $slot }}
        </tbody>
    </table>
</div>
