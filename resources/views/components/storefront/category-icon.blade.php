@props([
    'label' => '',
    'iconUrl' => null,
    'imgClass' => '',
    'svgClass' => '',
])

@php
    $customIconUrl = trim((string) ($iconUrl ?? ''));
    $labelText = (string) ($label ?? '');
@endphp

@if($customIconUrl !== '')
    <img
        src="{{ $customIconUrl }}"
        alt=""
        loading="lazy"
        @if($imgClass !== '') class="{{ $imgClass }}" @endif
        onerror="this.hidden=true; if (this.nextElementSibling) this.nextElementSibling.hidden=false;"
    >
    <svg hidden viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" @if($svgClass !== '') class="{{ $svgClass }}" @endif>{!! \App\Support\CategoryIconDefaults::svgPaths($labelText) !!}</svg>
@else
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" @if($svgClass !== '') class="{{ $svgClass }}" @endif>{!! \App\Support\CategoryIconDefaults::svgPaths($labelText) !!}</svg>
@endif
