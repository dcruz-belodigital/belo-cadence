@php
    use App\ValueObjects\ClientAttributeChoice;
    use App\ValueObjects\ClientAttributeField;

    /*
    | The field tree is read here rather than written, so a plain recursive closure draws
    | it — no bounded depth needed on this side.
    */
    $describeFields = function (array $fields, int $depth = 0) use (&$describeFields): string {
        $lines = [];

        foreach ($fields as $field) {
            /** @var ClientAttributeField $field */
            $name = $field->isNamed() ? $field->name : __('client_attributes.show.unnamed');

            $lines[] = str_repeat('    ', $depth).$name.' — '.$field->type->label()
                .($field->isRequired ? ' ('.__('client_attributes.fields.field_required').')' : '');

            if ($field->fields !== []) {
                $lines[] = $describeFields($field->fields, $depth + 1);
            }
        }

        return implode("\n", array_filter($lines));
    };
@endphp

<x-app-layout :heading="$attribute->name"
               :back="route('admin.client-attributes.index')"
               :back-label="__('client_attributes.title')" width="narrow">
    <x-page-header :description="$attribute->hint">
        <x-slot:actions>
            @can('update', $attribute)
                <x-button :href="route('admin.client-attributes.edit', $attribute)" variant="secondary" icon="pencil">
                    {{ __('common.actions.edit') }}
                </x-button>
            @endcan

            @can('delete', $attribute)
                <x-confirm-form :action="route('admin.client-attributes.destroy', $attribute)"
                                method="DELETE"
                                :title="__('client_attributes.delete.title')"
                                :message="__('client_attributes.delete.message', ['count' => $attribute->values_count])"
                                :confirm="__('client_attributes.delete.confirm')"
                                trigger-variant="secondary"
                                trigger-size="md"
                                icon="trash">
                    <x-slot:trigger>{{ __('client_attributes.actions.delete') }}</x-slot:trigger>
                </x-confirm-form>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-6">
        @unless ($attribute->is_active)
            <x-alert variant="warning">{{ __('client_attributes.hints.is_active') }}</x-alert>
        @endunless

        <x-card :title="__('client_attributes.show.details')">
            <x-detail-list>
                <x-detail-item :label="__('client_attributes.fields.name')">{{ $attribute->name }}</x-detail-item>

                <x-detail-item :label="__('client_attributes.fields.key')">
                    <span class="font-mono">{{ $attribute->key }}</span>
                </x-detail-item>

                <x-detail-item :label="__('client_attributes.fields.type')">{{ $attribute->type->label() }}</x-detail-item>

                <x-detail-item :label="__('client_attributes.fields.position')">
                    <span class="numeric">{{ $attribute->position }}</span>
                </x-detail-item>

                <x-detail-item :label="__('client_attributes.fields.is_required')">
                    {{ $attribute->is_required ? __('common.yes') : __('common.no') }}
                </x-detail-item>

                <x-detail-item :label="__('client_attributes.fields.is_active')">
                    <x-badge :variant="$attribute->is_active ? 'success' : 'neutral'">
                        {{ $attribute->is_active ? __('client_attributes.states.active') : __('client_attributes.states.inactive') }}
                    </x-badge>
                </x-detail-item>

                <x-detail-item :label="__('client_attributes.columns.clients')" wide>
                    {{ $attribute->values_count > 0
                        ? __('client_attributes.show.usage', ['count' => $attribute->values_count])
                        : __('client_attributes.show.usage_none') }}
                </x-detail-item>
            </x-detail-list>
        </x-card>

        @if ($attribute->type->usesChoices())
            <x-card :title="__('client_attributes.show.options')">
                <ul class="space-y-1 text-body">
                    @foreach ($attribute->options->choices as $choice)
                        <li class="{{ $choice->isActive ? '' : 'text-foreground-subtle' }}">
                            {{ $choice->value }}
                            @unless ($choice->isActive)
                                <span class="text-meta">— {{ __('client_attributes.show.retired') }}</span>
                            @endunless
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        @if ($attribute->type->usesFields())
            <x-card :title="__('client_attributes.show.fields')">
                <p class="whitespace-pre-line text-body">{{ $describeFields($attribute->fields) }}</p>
            </x-card>
        @endif
    </div>
</x-app-layout>
