<?php

namespace App\Console\Commands;

use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use Illuminate\Console\Command;

class DispatchDueChecks extends Command
{
    protected $signature = 'monitors:dispatch';

    protected $description = 'Despacha a checagem dos monitores cujo horário já venceu';

    public function handle(): int
    {
        // get() em vez de each()/chunk(): estamos alterando next_check_at,
        // a mesma coluna do filtro, e o chunk pularia registros.
        $monitors = Monitor::due()->get();

        foreach ($monitors as $monitor) {
            // "Reserva" o monitor ANTES de despachar. Se a fila estiver lenta,
            // o próximo minuto não despacha o mesmo monitor de novo.
            $monitor->update([
                'next_check_at' => now()->addMinutes($monitor->interval_minutes),
            ]);

            RunMonitorCheck::dispatch($monitor->id);
        }

        $this->info("{$monitors->count()} monitor(es) despachado(s).");

        return self::SUCCESS;
    }
}