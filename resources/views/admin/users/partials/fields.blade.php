@php
    /** @var \App\Models\User|null $user */
    $user ??= null;
    $isUpdate = $user !== null;
    $selectedRoles = old('roles', $isUpdate ? $user->roles->pluck('name')->all() : []);
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <x-form.field name="name" :label="__('users.fields.name')" required>
        <x-form.input name="name" :value="old('name', $user?->name)" required autofocus maxlength="255" />
    </x-form.field>

    <x-form.field name="email" :label="__('users.fields.email')" required>
        <x-form.input name="email" type="email" :value="old('email', $user?->email?->value)" required maxlength="255" />
    </x-form.field>

    <x-form.field name="password"
                  :label="__('users.fields.password')"
                  :hint="$isUpdate ? __('users.hints.password_optional') : null"
                  :required="! $isUpdate">
        <x-form.input name="password" type="password" autocomplete="new-password" :required="! $isUpdate" />
    </x-form.field>

    <x-form.field name="password_confirmation" :label="__('users.fields.password_confirmation')" :required="! $isUpdate">
        <x-form.input name="password_confirmation" type="password" autocomplete="new-password" :required="! $isUpdate" />
    </x-form.field>

    <div class="space-y-2 sm:col-span-2">
        <x-form.label>{{ __('users.fields.roles') }}</x-form.label>
        <p class="text-meta text-foreground-subtle">{{ __('users.hints.roles') }}</p>

        <div class="grid gap-2 rounded-card border border-border p-3 sm:grid-cols-2">
            @foreach ($roles as $role)
                <label class="flex items-center gap-2.5 text-body" for="role-{{ $role->id }}">
                    <input type="checkbox"
                           id="role-{{ $role->id }}"
                           name="roles[]"
                           value="{{ $role->name }}"
                           @checked(in_array($role->name, $selectedRoles, true))
                           class="focus-ring size-4 rounded border-border-strong bg-surface accent-primary">
                    {{ $role->name }}
                </label>
            @endforeach
        </div>

        <x-form.error :name="['roles', 'roles.*']" />
    </div>

    @unless ($isUpdate)
        <div class="sm:col-span-2">
            <x-form.checkbox name="is_active"
                             :label="__('users.fields.is_active')"
                             :hint="__('users.hints.is_active')"
                             :checked="old('is_active', true)" />
        </div>
    @endunless
</div>
