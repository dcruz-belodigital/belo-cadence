<x-app-layout :heading="__('support.changelog.title')">
    <x-page-header :description="__('support.changelog.description')" />

    <x-document :document="$document"
                :nav-label="__('support.releases')"
                :empty-message="__('support.changelog.empty')" />
</x-app-layout>
