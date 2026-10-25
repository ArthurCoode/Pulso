<?php

namespace App\Support;

use App\Models\Incident;
use Carbon\CarbonInterface;

class Format
{
    /**
     * Data/hora convertida para o fuso de exibição (o banco fica em UTC).
     */
    public static function dt(?CarbonInterface $date, string $format = 'd/m/Y H:i:s'): string
    {
        if ($date === null) {
            return '—';
        }

        return $date->copy()->timezone(config('pulso.display_timezone'))->format($format);
    }

    public static function percent(?float $value): string
    {
        return $value === null ? '—' : number_format($value, 2, ',', '.').'%';
    }

    public static function duration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours === 0) {
            return $minutes.' min';
        }

        return $hours.'h '.str_pad((string) $minutes, 2, '0', STR_PAD_LEFT).'min';
    }

    /**
     * Duração de um incidente (se ainda estiver aberto, conta até agora).
     */
    public static function incidentDuration(Incident $incident): string
    {
        $end = $incident->resolved_at ?? now();

        return self::duration((int) abs($incident->started_at->diffInSeconds($end)));
    }
}