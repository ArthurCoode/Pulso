<?php

namespace App\Console\Commands;

use App\Models\Check;
use Illuminate\Console\Command;

class PruneOldChecks extends Command
{
    protected $signature = 'monitors:prune {--days= : Dias de histórico a manter (mínimo 90)}';

    protected $description = 'Apaga checagens antigas para a tabela checks não crescer sem limite';

    // A status page mostra 90 dias: apagar menos que isso a deixaria incompleta.
    private const MIN_DAYS = 90;

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('pulso.retention_days'));

        if ($days < self::MIN_DAYS) {
            $this->warn('O mínimo é '.self::MIN_DAYS.' dias (a status page usa 90). Usando '.self::MIN_DAYS.'.');
            $days = self::MIN_DAYS;
        }

        $cutoff = now()->subDays($days);
        $deleted = 0;

        // Apaga em lotes de 500: uma exclusão gigante numa tacada só trava
        // o banco (e o painel) enquanto roda.
        do {
            $ids = Check::where('checked_at', '<', $cutoff)
                ->orderBy('id')
                ->limit(500)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += Check::whereIn('id', $ids)->delete();
        } while (true);

        $this->info("{$deleted} checagem(ns) com mais de {$days} dias apagada(s).");

        return self::SUCCESS;
    }
}