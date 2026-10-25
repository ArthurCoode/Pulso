@php
    if (! $monitor->is_active) {
        $badgeClass = 'paused';
        $badgeLabel = 'Pausado';
    } else {
        $badgeClass = $monitor->status->value;
        $badgeLabel = $monitor->status->label();
    }
@endphp
<span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>