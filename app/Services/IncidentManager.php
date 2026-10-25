<?php

namespace App\Services;

use App\Enums\MonitorStatus;
use App\Models\Monitor;

class IncidentManager
{
    public function __construct(private AlertNotifier $notifier)
    {
    }

    public function apply(Monitor $monitor, CheckResult $result): void
    {
        if ($result->isSuccessful) {
            $this->handleSuccess($monitor);
        } else {
            $this->handleFailure($monitor, $result);
        }
    }

    private function handleSuccess(Monitor $monitor): void
    {
        $wasDown = $monitor->status === MonitorStatus::Down;

        $monitor->consecutive_failures = 0;
        $monitor->status = MonitorStatus::Up;
        $monitor->save();

        if (! $wasDown) {
            return;
        }

        $incident = $monitor->openIncident()->first();

        if ($incident === null) {
            return;
        }

        $incident->update(['resolved_at' => now()]);

        $this->notifier->monitorRecovered($monitor, $incident);
    }

    private function handleFailure(Monitor $monitor, CheckResult $result): void
    {
        // O teto evita estourar a coluna (smallint) se o site ficar semanas fora.
        $monitor->consecutive_failures = min($monitor->consecutive_failures + 1, 1000);

        $shouldOpenIncident = $monitor->status !== MonitorStatus::Down
            && $monitor->consecutive_failures >= config('pulso.failure_threshold');

        if ($shouldOpenIncident) {
            $monitor->status = MonitorStatus::Down;
        }

        $monitor->save();

        if (! $shouldOpenIncident) {
            return;
        }

        $incident = $monitor->incidents()->create([
            'started_at' => now(),
            'cause' => $result->error,
        ]);

        $this->notifier->monitorDown($monitor, $incident);
    }
}