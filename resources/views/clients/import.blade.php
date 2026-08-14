<x-app-layout :heading="__('imports.clients.title')"
              :back="route('clients.index')"
              :back-label="__('clients.title')" width="narrow">
    <x-page-header :description="__('imports.clients.description')">
        <x-slot:actions>
            {{-- The whole process is explained once, in the guide, rather than on every import page. --}}
            <x-button :href="route('support.user-guide').'#'.__('imports.guide_anchor')"
                      variant="secondary"
                      size="sm"
                      icon="book">
                {{ __('imports.guide_link') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-import.upload :template="$template"
                     :action="route('clients.import.store')"
                     :template-url="route('clients.import.template')"
                     :cancel-url="route('clients.index')"
                     :submit-label="__('imports.clients.submit')">
        <x-slot:notes>
            <li>{{ __('imports.identifier_hint', ['column' => 'id']) }}</li>
        </x-slot:notes>
    </x-import.upload>
</x-app-layout>
