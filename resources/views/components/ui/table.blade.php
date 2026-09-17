{{-- overflow-x-auto (not overflow-hidden) so a wide table scrolls on small screens
     instead of being clipped; the inner min-w keeps columns readable while scrolling. --}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-panel border border-line bg-surface']) }}>
    <table class="w-full min-w-[34rem] text-[13.5px]">
        @isset($head)
            <thead class="border-b border-line text-left text-xs font-medium text-muted">
                <tr class="[&>th]:px-4 [&>th]:py-2.5">{{ $head }}</tr>
            </thead>
        @endisset
        {{-- Cell padding lives here so every table shares one rhythm; the descendant
             selector outranks any px-*/py-* left on individual cells. --}}
        <tbody class="divide-y divide-line [&_td]:px-4 [&_td]:py-3">
            {{ $slot }}
        </tbody>
    </table>
</div>
