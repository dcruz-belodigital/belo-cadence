<x-app-layout :heading="__('cadence.edit.title')"
               :back="route('cadence.schedules.show', $schedule)"
               :back-label="$schedule->client->name" width="narrow">
    <x-page-header :description="__('cadence.edit.description')" />


    <form method="POST" action="{{ route('cadence.schedules.update', $schedule) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card :title="$schedule->client->name">
            @include('cadence.schedules.partials.fields', [
                'schedule' => $schedule,
                'startsAtValue' => $startsAtInputValue,
            ])
        </x-card>

        <x-form.actions>
            <x-button :href="route('cadence.schedules.show', $schedule)" variant="secondary" type="button">
                {{ __('common.actions.cancel') }}
            </x-button>

            <x-button type="submit">{{ __('cadence.edit.submit') }}</x-button>
        </x-form.actions>
    </form>
</x-app-layout>
