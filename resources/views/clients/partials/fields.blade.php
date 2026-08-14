@php
    use App\Enums\ClientStatus;

    /** @var \App\Models\Client|null $client */
    $client ??= null;
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <x-form.field name="name" :label="__('clients.fields.name')" required class="sm:col-span-2">
        <x-form.input name="name" :value="old('name', $client?->name)" required autofocus maxlength="255" />
    </x-form.field>

    <x-form.field name="email" :label="__('clients.fields.email')" :hint="__('clients.hints.email')" required>
        <x-form.input name="email" type="email" :value="old('email', $client?->email?->value)" required maxlength="255" />
    </x-form.field>

    <x-form.field name="status" :label="__('clients.fields.status')" required>
        <x-form.select name="status"
                       :options="collect(ClientStatus::cases())->mapWithKeys(fn (ClientStatus $status) => [$status->value => $status->label()])"
                       :selected="old('status', $client?->status?->value ?? ClientStatus::Active->value)" />
    </x-form.field>

    <x-form.field name="notes" :label="__('clients.fields.notes')" :hint="__('clients.hints.notes')" class="sm:col-span-2">
        <x-form.textarea name="notes" rows="4" maxlength="5000">{{ old('notes', $client?->notes) }}</x-form.textarea>
    </x-form.field>
</div>
