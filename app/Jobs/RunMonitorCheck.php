<?php

namespace App\Jobs;

use App\Models\Check;
use App\Models\Monitor;
use App\Services\IncidentManager;
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

    // Momento do despacho (timestamp). Declarada fora do construtor, com valor
    // padrão, para que Jobs antigos já gravados na fila (sem esse campo)
    // continuem sendo lidos sem erro.
    public ?int $dispatchedAt = null;

    public function __construct(public int $monitorId)
    {
        $this->dispatchedAt = now()->timestamp;
    }

    public function handle(MonitorChecker $checker, IncidentManager $incidents): void
    {
        // Descarta checagens que ficaram paradas na fila (worker desligado ou
        // sobrecarregado). Sem isso, um backlog processado de uma vez contaria
        // várias "falhas seguidas" em poucos segundos e geraria alarme falso.
        if ($this->dispatchedAt !== null
            && now()->timestamp - $this->dispatchedAt > config('pulso.max_job_age')) {
            return;
        }

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

        $monitor->last_checked_at = $checkedAt;

        // Decide o novo estado (up/down), abre/fecha incidente e avisa.
        // O apply() salva o monitor, incluindo o last_checked_at acima.
        $incidents->apply($monitor, $result);
    }
}