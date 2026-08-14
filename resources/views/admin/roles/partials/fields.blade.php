@php
    use App\Enums\PermissionName;

    /** @var \App\Models\Role|null $role */
    $role ??= null;
    $selected = old('permissions', $role !== null ? $role->permissions->pluck('name')->all() : []);
@endphp

<div class="space-y-6">
    <x-form.field name="name" :label="__('roles.fields.name')" required>
        <x-form.input name="name" :value="old('name', $role?->name)" required autofocus maxlength="255" />
    </x-form.field>

    <div class="space-y-3">
        <div>
            <x-form.label>{{ __('roles.fields.permissions') }}</x-form.label>
            <p class="mt-1 text-meta text-foreground-subtle">{{ __('roles.hints.permissions') }}</p>
        </div>

        <x-form.error :name="['permissions', 'permissions.*']" />

        <div class="space-y-4">
            @foreach ($groupedPermissions as $group => $permissions)
                <fieldset class="rounded-card border border-border p-4"
                          x-data="{
                              toggleAll(checked) {
                                  $el.querySelectorAll('input[type=checkbox]').forEach((input) => { input.checked = checked });
                              },
                          }">
                    <legend class="flex items-center gap-3 px-1 text-label">
                        {{ $permissions[0]->groupLabel() }}
                    </legend>

                    <div class="mt-2 flex justify-end">
                        <button type="button"
                                class="focus-ring rounded-control text-meta text-primary transition hover:underline"
                                x-on:click="toggleAll(true)">
                            {{ __('common.placeholders.all') }}
                        </button>
                    </div>

                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach ($permissions as $permission)
                            <label class="flex items-start gap-2.5 text-body" for="permission-{{ $permission->value }}">
                                <input type="checkbox"
                                       id="permission-{{ $permission->value }}"
                                       name="permissions[]"
                                       value="{{ $permission->value }}"
                                       @checked(in_array($permission->value, $selected, true))
                                       class="focus-ring mt-0.5 size-4 shrink-0 rounded border-border-strong bg-surface accent-primary">
                                <span>
                                    <span class="block">{{ $permission->label() }}</span>
                                    {{-- The identifier is shown on purpose, so a reader can match a role to the permission names used in code. --}}
                                    <span data-permission-identifier class="block font-mono text-meta text-foreground-subtle">{{ $permission->value }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
        </div>
    </div>
</div>
