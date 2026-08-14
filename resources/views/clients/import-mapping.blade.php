<x-app-layout :heading="__('imports.clients.title')"
              :back="route('clients.import.create')"
              :back-label="__('imports.mapping.back_to_upload')" width="narrow">
    <x-page-header :description="__('imports.mapping.description')" />

    <x-import.mapping :template="$template"
                      :action="route('clients.import.run')"
                      :start-over-url="route('clients.import.create')"
                      :file-name="$fileName"
                      :headings="$headings"
                      :mapping="$mapping"
                      :preview-rows="$previewRows"
                      :row-count="$rowCount"
                      :submit-label="__('imports.mapping.submit')" />
</x-app-layout>
