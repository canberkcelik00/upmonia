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
        <x-phosphor-check-circle class="mx-auto mb-3 size-8 text-up" />
        <h1 class="text-xl font-bold tracking-[-0.02em] text-ink">{{ $verified }}</h1>
    @elseif ($status === 'expired')
        <x-phosphor-clock class="mx-auto mb-3 size-8 text-warn" />
        <h1 class="text-xl font-bold tracking-[-0.02em] text-warn-text">{{ $expired }}</h1>
    @else
        <x-phosphor-x-circle class="mx-auto mb-3 size-8 text-down" />
        <h1 class="text-xl font-bold tracking-[-0.02em] text-down-text">{{ $invalid }}</h1>
    @endif

    <a href="{{ route(auth()->check() ? 'monitors.index' : 'login') }}" wire:navigate class="mt-4 inline-block text-sm font-medium text-ink hover:underline">
        {{ __('auth.continue') }}
    </a>
</x-ui.card>
