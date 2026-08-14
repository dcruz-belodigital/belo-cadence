<x-app-layout :heading="__('imports.users.title')"
              :back="route('admin.users.index')"
              :back-label="__('users.title')" width="narrow">
    <x-page-header :description="__('imports.users.description')">
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
                     :action="route('admin.users.import.store')"
                     :template-url="route('admin.users.import.template')"
                     :cancel-url="route('admin.users.index')"
                     :submit-label="__('imports.users.submit')">
        <x-slot:notes>
            <li>{{ __('imports.identifier_hint', ['column' => 'id']) }}</li>
            <li>{{ __('imports.users.password_hint') }}</li>
            <li>{{ __('imports.users.roles_hint') }}</li>
        </x-slot:notes>
    </x-import.upload>
</x-app-layout>
