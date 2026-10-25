<?php

namespace App\Enums;

enum MonitorStatus: string
{
    case Up = 'up';
    case Down = 'down';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Up => 'Operacional',
            self::Down => 'Fora do ar',
            self::Unknown => 'Aguardando',
        };
    }
}