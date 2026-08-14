@props([
    'document',
    'navLabel',
    'emptyMessage' => null,
])

@php
    $sections = collect($document->sections)
        ->map(fn ($section): array => ['id' => $section->id, 'label' => $section->heading])
        ->all();
@endphp

{{-- A markdown document beside its own contents list, laid out like any other sectioned page. --}}
<x-sectioned-page :sections="$sections" :nav-label="$navLabel">
    @if ($document->introHtml !== '')
        <x-card>
            <div class="markdown">{!! $document->introHtml !!}</div>
        </x-card>
    @endif

    @forelse ($document->sections as $section)
        <x-card :id="$section->id" class="scroll-mt-24">
            <h2 class="text-page-title">{{ $section->heading }}</h2>

            <div class="markdown mt-4">{!! $section->html !!}</div>
        </x-card>
    @empty
        <x-card>
            <x-empty-state :title="$emptyMessage ?? __('support.empty')" icon="document" />
        </x-card>
    @endforelse
</x-sectioned-page>
