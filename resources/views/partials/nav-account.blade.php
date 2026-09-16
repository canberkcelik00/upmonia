@php
    $user = auth()->user();
    $initials = collect(preg_split('/\s+/', trim((string) $user?->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<div class="flex items-center gap-2.5 rounded-lg px-1 py-1">
    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-xs font-semibold text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
        {{ $initials !== '' ? $initials : '?' }}
    </span>
    <div class="min-w-0 flex-1">
        <div class="truncate text-sm font-medium text-neutral-900 dark:text-neutral-100" title="{{ $user?->name }}">{{ $user?->name }}</div>
        <div class="truncate text-xs text-neutral-500 dark:text-neutral-400" title="{{ $user?->email }}">{{ $user?->email }}</div>
    </div>
    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
        @csrf
        <button
            type="submit"
            aria-label="{{ __('app.nav_logout') }}"
            title="{{ __('app.nav_logout') }}"
            class="flex size-8 items-center justify-center rounded-lg text-neutral-500 transition-colors duration-150 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100"
        >
            <x-phosphor-sign-out class="size-4" />
        </button>
    </form>
</div>
