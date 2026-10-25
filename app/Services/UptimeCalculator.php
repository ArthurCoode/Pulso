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

    /**
     * As últimas checagens de cada monitor, prontas para desenhar o "traço"
     * da listagem. Uma consulta só para todos os monitores.
     *
     * Cada item tem 'ok' (deu certo?) e 'height' (porcentagem de 30 a 100):
     * checagens bem-sucedidas crescem com o tempo de resposta, falhas
     * ocupam a altura toda.
     *
     * @param  list<int>  $monitorIds
     * @return array<int, list<array{ok: bool, height: int}>>
     */
    public function recentTicksForAll(array $monitorIds, int $limit = 30, int $withinHours = 3): array
    {
        if ($monitorIds === []) {
            return [];
        }

        $rows = Check::query()
            ->whereIn('monitor_id', $monitorIds)
            ->where('checked_at', '>=', now()->subHours($withinHours))
            ->orderBy('id')
            ->get(['monitor_id', 'is_successful', 'response_time_ms']);

        $ticks = [];

        foreach ($rows->groupBy('monitor_id') as $monitorId => $checks) {
            $recent = $checks->slice(-$limit)->values();

            // Maior tempo entre as bem-sucedidas: é a régua do traço deste monitor.
            $max = max(1, (int) $recent->where('is_successful', true)->max('response_time_ms'));

            $ticks[$monitorId] = $recent->map(fn ($check) => [
                'ok' => (bool) $check->is_successful,
                'height' => $check->is_successful
                    ? (int) round(30 + 70 * min(1, ((int) $check->response_time_ms) / $max))
                    : 100,
            ])->all();
        }

        return $ticks;
    }
}
