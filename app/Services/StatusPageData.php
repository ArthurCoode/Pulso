<?php

namespace App\Services;

use App\Enums\MonitorStatus;
use App\Models\Check;
use App\Models\Incident;
use App\Models\Monitor;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Monta os dados da status page. O retorno usa só arrays, números e textos
 * (nada de objetos), para poder ser guardado no cache sem problemas.
 */
class StatusPageData
{
    private const DAYS = 90;

    public function build(): array
    {
        $monitors = Monitor::where('is_public', true)->orderBy('name')->get();

        if ($monitors->isEmpty()) {
            return [
                'generated_at' => now()->timestamp,
                'overall' => null,
                'monitors' => [],
                'incidents' => [],
            ];
        }

        return [
            'generated_at' => now()->timestamp,
            'overall' => $this->overall($monitors),
            'monitors' => $this->monitorsData($monitors),
            'incidents' => $this->incidents($monitors),
        ];
    }

    /**
     * @param  Collection<int, Monitor>  $monitors
     */
    private function overall(Collection $monitors): array
    {
        $active = $monitors->where('is_active', true);

        if ($active->isEmpty()) {
            return ['class' => 'unknown', 'label' => 'Nenhum serviço em monitoramento no momento'];
        }

        $down = $active->filter(fn (Monitor $m) => $m->status === MonitorStatus::Down)->count();

        if ($down === 0) {
            return ['class' => 'up', 'label' => 'Todos os sistemas operacionais'];
        }

        if ($down === $active->count()) {
            return ['class' => 'down', 'label' => 'Sistemas fora do ar'];
        }

        return ['class' => 'warn', 'label' => 'Alguns sistemas estão com problemas'];
    }

    /**
     * @param  Collection<int, Monitor>  $monitors
     */
    private function monitorsData(Collection $monitors): array
    {
        $timezone = config('pulso.display_timezone');
        $firstDay = now($timezone)->startOfDay()->subDays(self::DAYS - 1);
        $perDay = $this->dailyTotals($monitors->pluck('id')->all(), $firstDay);

        return $monitors->map(function (Monitor $monitor) use ($perDay, $firstDay) {
            $days = [];
            $sumOk = 0;
            $sumTotal = 0;

            for ($i = 0; $i < self::DAYS; $i++) {
                $date = $firstDay->copy()->addDays($i);
                $row = $perDay[$monitor->id][$date->format('Y-m-d')] ?? null;

                $pct = null;
                if ($row !== null && $row['total'] > 0) {
                    $pct = round($row['ok'] / $row['total'] * 100, 2);
                    $sumOk += $row['ok'];
                    $sumTotal += $row['total'];
                }

                $days[] = [
                    'label' => $date->format('d/m/Y'),
                    'pct' => $pct,
                    'class' => $this->dayClass($pct),
                ];
            }

            [$state, $label] = $this->stateOf($monitor);

            return [
                'name' => $monitor->name,
                'state' => $state,
                'label' => $label,
                'uptime' => $sumTotal > 0 ? round($sumOk / $sumTotal * 100, 2) : null,
                'days' => $days,
            ];
        })->all();
    }

    /**
     * Totais de checagens por monitor e por dia local, numa única consulta.
     *
     * @param  list<int>  $ids
     * @return array<int, array<string, array{total: int, ok: int}>>
     */
    private function dailyTotals(array $ids, CarbonInterface $firstDay): array
    {
        // Deslocamento do fuso de exibição em relação ao UTC, em minutos.
        $offset = (int) now(config('pulso.display_timezone'))->utcOffset();
        $day = $this->localDayExpression($offset);

        $rows = Check::query()
            ->whereIn('monitor_id', $ids)
            // O banco guarda UTC: o limite precisa ser convertido antes de comparar.
            ->where('checked_at', '>=', $firstDay->copy()->utc())
            ->selectRaw("monitor_id, {$day} as day, COUNT(*) as total, SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as ok")
            ->groupByRaw("monitor_id, {$day}")
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->monitor_id][$row->day] = [
                'total' => (int) $row->total,
                'ok' => (int) $row->ok,
            ];
        }

        return $result;
    }

    /**
     * Expressão SQL que converte checked_at (UTC) para a data local.
     * $offset é um inteiro calculado aqui dentro, nunca vem do usuário.
     */
    private function localDayExpression(int $offset): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => sprintf("DATE(checked_at, '%+d minutes')", $offset),
            'mysql', 'mariadb' => "DATE(DATE_ADD(checked_at, INTERVAL {$offset} MINUTE))",
            default => throw new RuntimeException('Status page: driver de banco não suportado (use SQLite ou MySQL).'),
        };
    }

    /**
     * Cor da barra do dia. Uma falha isolada (>= 99,9%) ainda fica verde,
     * mas uma queda de verdade (alguns minutos) já aparece em amarelo ou vermelho.
     */
    private function dayClass(?float $pct): string
    {
        return match (true) {
            $pct === null => 'none',
            $pct >= 99.9 => 'up',
            $pct >= 99 => 'warn',
            default => 'down',
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function stateOf(Monitor $monitor): array
    {
        if (! $monitor->is_active) {
            return ['maintenance', 'Em manutenção'];
        }

        return match ($monitor->status) {
            MonitorStatus::Up => ['up', 'Operacional'],
            MonitorStatus::Down => ['down', 'Fora do ar'],
            MonitorStatus::Unknown => ['unknown', 'Aguardando'],
        };
    }

    /**
     * Incidentes dos últimos 14 dias, mais qualquer um ainda aberto.
     * Só nome e horários: a causa técnica não é divulgada.
     *
     * @param  Collection<int, Monitor>  $monitors
     */
    private function incidents(Collection $monitors): array
    {
        return Incident::query()
            ->whereIn('monitor_id', $monitors->pluck('id')->all())
            ->where(function ($query) {
                $query->where('started_at', '>=', now()->subDays(14))
                    ->orWhereNull('resolved_at');
            })
            ->with('monitor:id,name')
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (Incident $incident) => [
                'monitor' => $incident->monitor->name,
                'started_at' => $incident->started_at->timestamp,
                'resolved_at' => $incident->resolved_at?->timestamp,
                'seconds' => (int) abs($incident->started_at->diffInSeconds($incident->resolved_at ?? now())),
            ])
            ->all();
    }
}