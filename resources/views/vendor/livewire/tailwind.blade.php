@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';

$pageBtn = 'inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm font-medium transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600';
$pageIdle = 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 active:scale-[0.98] dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100';
$pageActive = 'bg-neutral-900 text-white dark:bg-neutral-100 dark:text-neutral-900';
$pageDisabled = 'cursor-not-allowed text-neutral-300 dark:text-neutral-700';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="{{ __('app.pagination_nav') }}" class="flex items-center justify-between gap-4">
            {{-- Mobile: prev / next only --}}
            <div class="flex flex-1 justify-between sm:hidden">
                @if ($paginator->onFirstPage())
                    <span class="{{ $pageBtn }} {{ $pageDisabled }} gap-1 px-3">
                        <x-phosphor-caret-left class="size-3.5" /> {{ __('app.pagination_previous') }}
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="{{ $pageBtn }} {{ $pageIdle }} gap-1 px-3">
                        <x-phosphor-caret-left class="size-3.5" /> {{ __('app.pagination_previous') }}
                    </button>
                @endif

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="{{ $pageBtn }} {{ $pageIdle }} gap-1 px-3">
                        {{ __('app.pagination_next') }} <x-phosphor-caret-right class="size-3.5" />
                    </button>
                @else
                    <span class="{{ $pageBtn }} {{ $pageDisabled }} gap-1 px-3">
                        {{ __('app.pagination_next') }} <x-phosphor-caret-right class="size-3.5" />
                    </span>
                @endif
            </div>

            {{-- Desktop: range summary + numbered pages --}}
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    {!! __('app.pagination_showing', [
                        'first' => '<span class="font-mono font-medium text-neutral-700 dark:text-neutral-300">'.$paginator->firstItem().'</span>',
                        'last' => '<span class="font-mono font-medium text-neutral-700 dark:text-neutral-300">'.$paginator->lastItem().'</span>',
                        'total' => '<span class="font-mono font-medium text-neutral-700 dark:text-neutral-300">'.$paginator->total().'</span>',
                    ]) !!}
                </p>

                <div class="flex items-center gap-1">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('app.pagination_previous') }}" class="{{ $pageBtn }} {{ $pageDisabled }}">
                            <x-phosphor-caret-left class="size-4" aria-hidden="true" />
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="{{ $pageBtn }} {{ $pageIdle }}" aria-label="{{ __('app.pagination_previous') }}">
                            <x-phosphor-caret-left class="size-4" />
                        </button>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true" class="px-1 text-sm text-neutral-400 dark:text-neutral-600">{{ $element }}</span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                    @if ($page == $paginator->currentPage())
                                        <span aria-current="page" class="{{ $pageBtn }} {{ $pageActive }} font-mono">{{ $page }}</span>
                                    @else
                                        <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="{{ $pageBtn }} {{ $pageIdle }} font-mono" aria-label="{{ __('app.pagination_goto_page', ['page' => $page]) }}">
                                            {{ $page }}
                                        </button>
                                    @endif
                                </span>
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="{{ $pageBtn }} {{ $pageIdle }}" aria-label="{{ __('app.pagination_next') }}">
                            <x-phosphor-caret-right class="size-4" />
                        </button>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('app.pagination_next') }}" class="{{ $pageBtn }} {{ $pageDisabled }}">
                            <x-phosphor-caret-right class="size-4" aria-hidden="true" />
                        </span>
                    @endif
                </div>
            </div>
        </nav>
    @endif
</div>
