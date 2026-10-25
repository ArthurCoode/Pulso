<?php

namespace Tests\Feature;

use App\Enums\MonitorStatus;
use App\Models\Incident;
use App\Models\Monitor;
use App\Services\AlertNotifier;
use App\Services\CheckResult;
use App\Services\IncidentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class IncidentManagerTest extends TestCase
{
    use RefreshDatabase;

    private function createMonitor(): Monitor
    {
        return Monitor::create(['name' => 'Site', 'url' => 'http://8.8.8.8/']);
    }

    private function failure(): CheckResult
    {
        return new CheckResult(false, null, null, 'erro de teste');
    }

    private function success(): CheckResult
    {
        return new CheckResult(true, 200, 120);
    }

    public function test_falhas_abaixo_do_limite_nao_abrem_incidente_nem_alertam(): void
    {
        $this->mock(AlertNotifier::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('monitorDown');
            $mock->shouldNotReceive('monitorRecovered');
        });

        $monitor = $this->createMonitor();
        $manager = app(IncidentManager::class);

        $manager->apply($monitor, $this->failure());
        $manager->apply($monitor, $this->failure());

        $this->assertSame(MonitorStatus::Unknown, $monitor->status);
        $this->assertSame(2, $monitor->consecutive_failures);
        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_atingir_o_limite_marca_down_abre_incidente_e_alerta_uma_vez(): void
    {
        $this->mock(AlertNotifier::class, function (MockInterface $mock) {
            $mock->shouldReceive('monitorDown')->once();
        });

        $monitor = $this->createMonitor();
        $manager = app(IncidentManager::class);

        foreach (range(1, 3) as $i) {
            $manager->apply($monitor, $this->failure());
        }

        $this->assertSame(MonitorStatus::Down, $monitor->status);
        $this->assertDatabaseCount('incidents', 1);
        $this->assertSame('erro de teste', Incident::first()->cause);
    }

    public function test_falhas_continuas_nao_geram_novo_incidente_nem_novo_alerta(): void
    {
        $this->mock(AlertNotifier::class, function (MockInterface $mock) {
            $mock->shouldReceive('monitorDown')->once();
        });

        $monitor = $this->createMonitor();
        $manager = app(IncidentManager::class);

        foreach (range(1, 8) as $i) {
            $manager->apply($monitor, $this->failure());
        }

        $this->assertDatabaseCount('incidents', 1);
        $this->assertSame(8, $monitor->consecutive_failures);
    }

    public function test_sucesso_apos_queda_fecha_o_incidente_e_alerta_a_recuperacao(): void
    {
        $this->mock(AlertNotifier::class, function (MockInterface $mock) {
            $mock->shouldReceive('monitorDown')->once();
            $mock->shouldReceive('monitorRecovered')->once();
        });

        $monitor = $this->createMonitor();
        $manager = app(IncidentManager::class);

        foreach (range(1, 3) as $i) {
            $manager->apply($monitor, $this->failure());
        }

        $manager->apply($monitor, $this->success());

        $this->assertSame(MonitorStatus::Up, $monitor->status);
        $this->assertSame(0, $monitor->consecutive_failures);
        $this->assertNotNull(Incident::first()->resolved_at);
    }

    public function test_sucesso_normal_nao_alerta_nem_abre_incidente(): void
    {
        $this->mock(AlertNotifier::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('monitorDown');
            $mock->shouldNotReceive('monitorRecovered');
        });

        $monitor = $this->createMonitor();

        app(IncidentManager::class)->apply($monitor, $this->success());

        $this->assertSame(MonitorStatus::Up, $monitor->status);
        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_o_limite_de_falhas_e_configuravel(): void
    {
        config(['pulso.failure_threshold' => 1]);

        $this->mock(AlertNotifier::class, function (MockInterface $mock) {
            $mock->shouldReceive('monitorDown')->once();
        });

        $monitor = $this->createMonitor();

        app(IncidentManager::class)->apply($monitor, $this->failure());

        $this->assertSame(MonitorStatus::Down, $monitor->status);
    }
}