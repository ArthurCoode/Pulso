<?php

namespace App\Jobs;

use App\Models\Check;
use App\Models\Monitor;
use App\Services\MonitorChecker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunMonitorCheck implements ShouldQueue
{
    use Queueable;

    // Uma checagem que falha NÃO é erro do Job (o checker já trata isso),
    // então não faz sentido tentar de novo automaticamente.
    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public int $monitorId)
    {
    }

    public function handle(MonitorChecker $checker): void
    {
        $monitor = Monitor::find($this->monitorId);

        // O monitor pode ter sido apagado ou pausado entre o despacho e a execução.
        if ($monitor === null || ! $monitor->is_active) {
            return;
        }

        $result = $checker->check($monitor);
        $checkedAt = now();

        Check::create([
            'monitor_id' => $monitor->id,
            'status_code' => $result->statusCode,
            'response_time_ms' => $result->responseTimeMs,
            'is_successful' => $result->isSuccessful,
            'error' => $result->error,
            'checked_at' => $checkedAt,
        ]);

        $monitor->update(['last_checked_at' => $checkedAt]);
    }
}