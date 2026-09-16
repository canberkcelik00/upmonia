@props([
    'status',
    'verified',
    'expired',
    'invalid',
])

{{-- Shared by auth/⚡verify-email and auth/⚡verify-channel — the two files were
     structurally identical, differing only in which three messages they pass in. --}}
<x-ui.card padding="p-6" class="text-center">
    @if ($status === 'verified')
        <x-phosphor-check-circle class="mx-auto mb-3 size-8 text-emerald-500" />
        <h1 class="text-xl font-semibold tracking-tight text-neutral-900 dark:text-neutral-50">{{ $verified }}</h1>
    @elseif ($status === 'expired')
        <x-phosphor-clock class="mx-auto mb-3 size-8 text-amber-500" />
        <h1 class="text-xl font-semibold tracking-tight text-amber-700 dark:text-amber-400">{{ $expired }}</h1>
    @else
        <x-phosphor-x-circle class="mx-auto mb-3 size-8 text-red-500" />
        <h1 class="text-xl font-semibold tracking-tight text-red-700 dark:text-red-400">{{ $invalid }}</h1>
    @endif

    <a href="{{ route(auth()->check() ? 'monitors.index' : 'login') }}" wire:navigate class="mt-4 inline-block text-sm font-medium text-brand-700 hover:underline dark:text-brand-400">
        {{ __('auth.continue') }}
    </a>
</x-ui.card>
