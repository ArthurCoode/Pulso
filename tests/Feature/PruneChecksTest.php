<?php

namespace Tests\Feature;

use App\Models\Check;
use App\Models\Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneChecksTest extends TestCase
{
    use RefreshDatabase;

    private function checkDaysAgo(Monitor $monitor, int $days): Check
    {
        return Check::create([
            'monitor_id' => $monitor->id,
            'status_code' => 200,
            'response_time_ms' => 100,
            'is_successful' => true,
            'checked_at' => now()->subDays($days),
        ]);
    }

    public function test_apaga_apenas_checagens_alem_da_retencao(): void
    {
        $monitor = Monitor::create(['name' => 'Site', 'url' => 'http://8.8.8.8/']);

        $recent = $this->checkDaysAgo($monitor, 5);
        $kept = $this->checkDaysAgo($monitor, 100);
        $this->checkDaysAgo($monitor, 200);

        // Retenção padrão nos testes: 120 dias.
        $this->artisan('monitors:prune')->assertExitCode(0);

        $this->assertDatabaseCount('checks', 2);
        $this->assertDatabaseHas('checks', ['id' => $recent->id]);
        $this->assertDatabaseHas('checks', ['id' => $kept->id]);
    }

    public function test_nunca_apaga_dentro_dos_90_dias_mesmo_com_valor_menor(): void
    {
        $monitor = Monitor::create(['name' => 'Site', 'url' => 'http://8.8.8.8/']);

        $inside = $this->checkDaysAgo($monitor, 60);
        $this->checkDaysAgo($monitor, 100);

        // Pedir 10 dias é rebaixado para o mínimo de 90.
        $this->artisan('monitors:prune', ['--days' => 10])->assertExitCode(0);

        $this->assertDatabaseCount('checks', 1);
        $this->assertDatabaseHas('checks', ['id' => $inside->id]);
    }
}