<x-app-layout :heading="__('imports.users.title')"
              :back="route('admin.users.import.create')"
              :back-label="__('imports.mapping.back_to_upload')" width="narrow">
    <x-page-header :description="__('imports.mapping.description')" />

    <x-import.mapping :template="$template"
                      :action="route('admin.users.import.run')"
                      :start-over-url="route('admin.users.import.create')"
                      :file-name="$fileName"
                      :headings="$headings"
                      :mapping="$mapping"
                      :preview-rows="$previewRows"
                      :row-count="$rowCount"
                      :submit-label="__('imports.mapping.submit')" />
</x-app-layout>
