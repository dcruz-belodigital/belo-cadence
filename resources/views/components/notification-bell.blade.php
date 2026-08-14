<x-dropdown :label="__('navigation.notifications')" width="w-80 sm:w-96">
    <x-slot:trigger>
        <span class="relative flex">
            <x-icon name="bell" size="size-5" />

            @if ($unreadCount > 0)
                <span class="absolute -right-1.5 -top-1.5 flex min-w-4 items-center justify-center rounded-pill bg-danger px-1 text-[0.625rem] font-semibold leading-4 text-danger-foreground">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
                <span class="sr-only">{{ __('notifications.unread_count', ['count' => $unreadCount]) }}</span>
            @endif
        </span>
    </x-slot:trigger>

    <div class="flex items-center justify-between gap-2 px-3 py-2.5">
        <p class="text-label">{{ __('notifications.title') }}</p>

        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf

                <button type="submit" class="focus-ring rounded-control text-meta text-primary transition hover:underline">
                    {{ __('notifications.mark_all_read') }}
                </button>
            </form>
        @endif
    </div>

    <x-dropdown.divider />

    @if ($recent->isEmpty())
        <p class="px-3 py-6 text-center text-meta text-foreground-muted">{{ __('notifications.empty') }}</p>
    @else
        <div class="divide-y divide-border">
            @foreach ($recent as $notification)
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf

                    <button type="submit" class="focus-ring-inset flex w-full gap-2.5 px-3 py-2.5 text-left transition hover:bg-surface-muted">
                        <span class="mt-1 flex size-2 shrink-0 rounded-pill {{ $notification->read_at === null ? 'bg-primary' : 'bg-transparent' }}"
                              @if ($notification->read_at === null) aria-label="{{ __('notifications.unread') }}" @endif></span>

                        <span class="min-w-0 flex-1">
                            <span class="block text-label text-foreground">{{ $notification->data['title'] ?? __('notifications.fallback_title') }}</span>

                            @if (isset($notification->data['message']))
                                <span class="mt-0.5 block truncate text-meta text-foreground-muted">{{ $notification->data['message'] }}</span>
                            @endif

                            <span class="mt-1 block text-meta text-foreground-subtle">
                                <x-datetime :value="$notification->created_at" format="datetime" />
                            </span>
                        </span>
                    </button>
                </form>
            @endforeach
        </div>
    @endif

    <x-dropdown.divider />

    <x-dropdown.item :href="route('notifications.index')" icon="inbox">
        {{ __('notifications.view_all') }}
    </x-dropdown.item>
</x-dropdown>
