@props([
    'options', // [value => label]
    'active',
    'action', // sprintf template, e.g. "$set('status', '%s')" — value is substituted in for %s
    'counts' => [], // [value => int], omit a key to hide its count
])

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5 rounded-control border border-line-strong bg-surface p-0.5 text-[13px]']) }}>
    @foreach ($options as $value => $label)
        <button
            type="button"
            wire:click="{{ sprintf($action, $value) }}"
            class="flex items-center gap-1.5 rounded-[4px] px-2.5 py-1 font-medium transition-colors duration-150 {{ (string) $active === (string) $value ? 'bg-surface-2 text-ink' : 'text-muted hover:text-ink' }}"
        >
            {{ $label }}
            @if (array_key_exists($value, $counts))
                <span class="font-mono text-[11.5px] {{ (string) $active === (string) $value ? 'text-muted' : 'text-faint' }}">{{ $counts[$value] }}</span>
            @endif
        </button>
    @endforeach
</div>
