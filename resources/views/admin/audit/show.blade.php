@php
    $renderValues = static function (?array $values): ?string {
        return $values === null
            ? null
            : json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    };
@endphp

<x-app-layout :heading="$audit->action->label()"
               :back="route('admin.audit-log.index')"
               :back-label="__('audit.title')">


    <div class="space-y-6">
        <x-card :title="__('audit.show.details')">
            <x-detail-list>
                <x-detail-item :label="__('audit.columns.when')">
                    <x-datetime :value="$audit->created_at" with-timezone />
                </x-detail-item>

                <x-detail-item :label="__('audit.columns.actor')">
                    @if ($audit->user === null)
                        {{ __('audit.system_actor') }}
                        <span class="block text-meta text-foreground-muted">{{ __('audit.system_actor_hint') }}</span>
                    @else
                        {{ $audit->user->name }}
                        <span class="block text-meta text-foreground-muted">{{ $audit->user->email }}</span>
                    @endif
                </x-detail-item>

                <x-detail-item :label="__('audit.columns.action')">
                    <x-badge :variant="$audit->action->badgeVariant()">{{ $audit->action->label() }}</x-badge>
                </x-detail-item>

                <x-detail-item :label="__('audit.columns.record')">
                    @if ($audit->auditable_type === null)
                        {{ __('common.placeholders.none') }}
                    @else
                        {{ $audit->auditableTypeLabel() }}@if ($audit->auditable_id !== null) #{{ $audit->auditable_id }}@endif

                        @if ($audit->auditable === null)
                            <span class="block text-meta text-foreground-muted">{{ __('audit.show.record_missing') }}</span>
                        @endif
                    @endif
                </x-detail-item>
            </x-detail-list>
        </x-card>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-card :title="__('audit.show.old_values')">
                @if ($audit->old_values === null)
                    <p class="text-body text-foreground-muted">{{ __('audit.show.no_values') }}</p>
                @else
                    <pre class="overflow-x-auto rounded-control border border-border bg-surface-muted p-3 font-mono text-meta">{{ $renderValues($audit->old_values) }}</pre>
                @endif
            </x-card>

            <x-card :title="__('audit.show.new_values')">
                @if ($audit->new_values === null)
                    <p class="text-body text-foreground-muted">{{ __('audit.show.no_values') }}</p>
                @else
                    <pre class="overflow-x-auto rounded-control border border-border bg-surface-muted p-3 font-mono text-meta">{{ $renderValues($audit->new_values) }}</pre>
                @endif
            </x-card>
        </div>

        @if ($audit->metadata !== null)
            <x-card :title="__('audit.show.metadata')">
                <pre class="overflow-x-auto rounded-control border border-border bg-surface-muted p-3 font-mono text-meta">{{ $renderValues($audit->metadata) }}</pre>
            </x-card>
        @endif
    </div>
</x-app-layout>
