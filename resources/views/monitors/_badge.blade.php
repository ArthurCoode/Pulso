@php
    if (! $monitor->is_active) {
        $stateClass = 's-paused';
        $stateLabel = 'Pausado';
    } else {
        $stateClass = 's-'.$monitor->status->value;
        $stateLabel = $monitor->status->label();
    }
@endphp
<span class="chip {{ $stateClass }}"><i class="dot"></i>{{ $stateLabel }}</span>
