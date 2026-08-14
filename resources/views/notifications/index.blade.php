<x-app-layout :heading="__('notifications.title')">
    <x-page-header :description="__('notifications.description')">
        <x-slot:actions>
            @if ($notifications->isNotEmpty())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <x-button type="submit" variant="secondary" icon="check-circle">
                        {{ __('notifications.mark_all_read') }}
                    </x-button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card flush>
        @if ($notifications->isEmpty())
            <x-empty-state :title="__('notifications.empty')" :description="__('notifications.empty_description')" icon="bell" />
        @else
            <ul class="divide-y divide-border">
                @foreach ($notifications as $notification)
                    <li class="flex flex-wrap items-start justify-between gap-4 px-4 py-4 {{ $notification->read_at === null ? 'bg-primary-soft/30' : '' }}">
                        <div class="flex min-w-0 gap-3">
                            <span class="mt-1.5 flex size-2 shrink-0 rounded-pill {{ $notification->read_at === null ? 'bg-primary' : 'bg-border-strong' }}"></span>

                            <div class="min-w-0 space-y-1">
                                <p class="text-label">{{ $notification->data['title'] ?? __('notifications.fallback_title') }}</p>

                                @isset($notification->data['message'])
                                    <p class="text-body text-foreground-muted">{{ $notification->data['message'] }}</p>
                                @endisset

                                <p class="text-meta text-foreground-subtle">
                                    <x-datetime :value="$notification->created_at" />
                                </p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            <x-button type="submit" variant="ghost" size="sm" icon-after="chevron-right">
                                {{ __('common.actions.view') }}
                            </x-button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-slot:footer>
            <x-pagination :paginator="$notifications" />
        </x-slot:footer>
    </x-card>
</x-app-layout>
