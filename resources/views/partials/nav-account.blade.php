@php
    $user = auth()->user();
@endphp

<div class="border-b border-line px-2.5 py-2">
    <div class="truncate text-sm font-medium text-ink" title="{{ $user?->name }}">{{ $user?->name }}</div>
    <div class="truncate text-xs text-muted" title="{{ $user?->email }}">{{ $user?->email }}</div>
</div>
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button
        type="submit"
        class="mt-1 flex w-full items-center gap-2.5 rounded-[4px] px-2.5 py-1.5 text-left text-sm text-ink-2 transition-colors duration-150 hover:bg-surface-2 hover:text-ink"
    >
        <x-phosphor-sign-out class="size-4" />
        {{ __('app.nav_logout') }}
    </button>
</form>
