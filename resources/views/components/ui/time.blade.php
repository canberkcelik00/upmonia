@props([
    'at',
    'format' => 'datetime',
])

@php
    $at = $at instanceof \Illuminate\Support\Carbon ? $at : \Illuminate\Support\Carbon::parse($at);
@endphp

{!! $at->toDisplayHtml($format) !!}
