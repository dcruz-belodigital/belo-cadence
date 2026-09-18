<x-app-layout :heading="__('dashboard.title')">
    <x-page-header :description="__('dashboard.description')" />

    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @if ($dueToday !== null)
                <x-metric :label="__('dashboard.metrics.due_today')"
                          :value="$dueToday"
                          :description="__('dashboard.metrics.due_today_hint')"
                          icon="clock"
                          :variant="$dueToday > 0 ? 'warning' : 'neutral'"
                          :href="route('cadence.upcoming', ['range' => 'today'])" />

                <x-metric :label="__('dashboard.metrics.due_this_week')"
                          :value="$dueThisWeek"
                          icon="calendar"
                          variant="info"
                          :href="route('cadence.upcoming', ['range' => 'next_7_days'])" />
            @endif

            @if ($sentThisMonth !== null)
                <x-metric :label="__('dashboard.metrics.sent_this_month')"
                          :value="$sentThisMonth"
                          icon="check-circle"
                          variant="success"
                          :href="route('cadence.deliveries.index', ['status' => 'sent'])" />

                <x-metric :label="__('dashboard.metrics.failed_this_month')"
                          :value="$failedThisMonth"
                          icon="warning"
                          :variant="$failedThisMonth > 0 ? 'danger' : 'neutral'"
                          :href="route('cadence.deliveries.index', ['status' => 'failed'])" />
            @endif
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            @if ($upcoming !== null)
                <x-card :title="__('dashboard.panels.upcoming')" flush>
                    <x-slot:actions>
                        <x-button :href="route('cadence.upcoming')" variant="ghost" size="sm" icon-after="chevron-right">
                            {{ __('dashboard.panels.upcoming_all') }}
                        </x-button>
                    </x-slot:actions>

                    @if ($upcoming->isEmpty())
                        <x-empty-state :title="__('dashboard.panels.upcoming_empty')" icon="calendar" />
                    @else
                        <ul class="divide-y divide-border">
                            @foreach ($upcoming as $schedule)
                                <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                                    <div class="min-w-0">
                                        <a href="{{ $schedule->client !== null ? route('clients.show', $schedule->client) : route('cadence.schedules.show', $schedule) }}"
                                           class="focus-ring block truncate rounded-control text-label transition hover:text-primary">
                                            {{ $schedule->displayName() }}
                                        </a>
                                        <p class="truncate text-meta text-foreground-muted">{{ $schedule->template->label() }}</p>
                                    </div>

                                    <span class="text-meta text-foreground-muted">
                                        <x-datetime :value="$schedule->next_send_at" />
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            @endif

            @if ($recentFailures !== null)
                <x-card :title="__('dashboard.panels.failures')" flush>
                    <x-slot:actions>
                        <x-button :href="route('cadence.deliveries.index', ['status' => 'failed'])" variant="ghost" size="sm" icon-after="chevron-right">
                            {{ __('dashboard.panels.failures_all') }}
                        </x-button>
                    </x-slot:actions>

                    @if ($recentFailures->isEmpty())
                        <x-empty-state :title="__('dashboard.panels.failures_empty')"
                                       :description="__('dashboard.panels.failures_empty_description')"
                                       icon="check-circle" />
                    @else
                        <ul class="divide-y divide-border">
                            @foreach ($recentFailures as $delivery)
                                <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                                    <div class="min-w-0">
                                        <a href="{{ route('cadence.deliveries.show', $delivery) }}" class="focus-ring block truncate rounded-control text-label transition hover:text-primary">
                                            {{ $delivery->target_name ?? $delivery->client?->name }}
                                        </a>
                                        <p class="truncate text-meta text-foreground-muted">{{ $delivery->subject }}</p>
                                    </div>

                                    <span class="text-meta text-foreground-muted">
                                        <x-datetime :value="$delivery->attempted_at" />
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            @endif

            @if ($recentDeliveries !== null)
                <x-card :title="__('dashboard.panels.recent')" flush class="xl:col-span-2">
                    @if ($recentDeliveries->isEmpty())
                        <x-empty-state :title="__('dashboard.panels.recent_empty')" icon="envelope" />
                    @else
                        <ul class="divide-y divide-border">
                            @foreach ($recentDeliveries as $delivery)
                                <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                                    <div class="min-w-0">
                                        <a href="{{ route('cadence.deliveries.show', $delivery) }}" class="focus-ring block truncate rounded-control text-label transition hover:text-primary">
                                            {{ $delivery->target_name ?? $delivery->client?->name }}
                                        </a>
                                        <p class="truncate text-meta text-foreground-muted">{{ $delivery->subject }}</p>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <x-badge :variant="$delivery->status->badgeVariant()">{{ $delivery->status->label() }}</x-badge>

                                        <span class="text-meta text-foreground-muted">
                                            <x-datetime :value="$delivery->sent_at" />
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
