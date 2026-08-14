@php
    $messages = collect([
        'success' => session('success') ?? session('status'),
        'warning' => session('warning'),
        'danger' => session('error'),
        'info' => session('info'),
    ])->filter();
@endphp

@if ($messages->isNotEmpty())
    <div class="mb-6 space-y-3">
        @foreach ($messages as $variant => $message)
            <x-alert :variant="$variant" dismissible>{{ $message }}</x-alert>
        @endforeach
    </div>
@endif
