@php
    $sections = collect($previews)
        ->map(fn (array $preview): array => [
            'id' => $preview['template']->value,
            'label' => $preview['template']->label(),
        ])
        ->all();
@endphp

<x-app-layout :heading="__('cadence.templates.title')">
    <x-page-header :description="__('cadence.templates.description')" />

    <x-sectioned-page :sections="$sections" :nav-label="__('support.on_this_page')">
        <x-alert variant="info">{{ __('cadence.templates.source_notice') }}</x-alert>

        @foreach ($previews as $preview)
            <x-card :id="$preview['template']->value"
                    :title="$preview['template']->label()"
                    :description="__('cadence.templates.subject').': '.$preview['subject']"
                    class="scroll-mt-24">
                {{-- The message is shown in a sandboxed frame, on the light canvas an email is written for. --}}
                <iframe title="{{ $preview['template']->label() }}"
                        sandbox
                        class="h-[26rem] w-full rounded-control border border-border bg-white"
                        srcdoc="{{ $preview['html'] }}"></iframe>
            </x-card>
        @endforeach

        <p class="text-center text-meta text-foreground-subtle">{{ __('cadence.templates.sample_notice') }}</p>
    </x-sectioned-page>
</x-app-layout>
