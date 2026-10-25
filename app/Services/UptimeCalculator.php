<?php

namespace App\Services;

use App\Models\Check;
use App\Models\Monitor;

class UptimeCalculator
{
    /**
     * Uptime (%) de um monitor nas últimas $hours horas, ou null se não há dados.
     */
    public function percentage(Monitor $monitor, int $hours): ?float
    {
        $row = Check::query()
            ->where('monitor_id', $monitor->id)
            ->where('checked_at', '>=', now()->subHours($hours))
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as ok')
            ->first();

        if ($row === null || (int) $row->total === 0) {
            return null;
        }

        return round(((int) $row->ok / (int) $row->total) * 100, 2);
    }

    /**
     * Uptime (%) de TODOS os monitores numa consulta só: [monitor_id => porcentagem].
     * Usado na listagem para evitar uma consulta por monitor.
     *
     * @return array<int, float>
     */
    public function percentagesForAll(int $hours): array
    {
        return Check::query()
            ->where('checked_at', '>=', now()->subHours($hours))
            ->groupBy('monitor_id')
            ->selectRaw('monitor_id, COUNT(*) as total, SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as ok')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->monitor_id => round(((int) $row->ok / (int) $row->total) * 100, 2),
            ])
            ->all();
    }

    /**
     * Tempo médio de resposta (ms) das checagens bem-sucedidas.
     */
    public function averageResponseMs(Monitor $monitor, int $hours): ?int
    {
        $avg = Check::query()
            ->where('monitor_id', $monitor->id)
            ->where('is_successful', true)
            ->where('checked_at', '>=', now()->subHours($hours))
            ->avg('response_time_ms');

        return $avg === null ? null : (int) round($avg);
    }
}