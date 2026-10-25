<?php

namespace Tests\Feature;

use App\Enums\MonitorStatus;
use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use App\Services\IncidentManager;
use App\Services\MonitorChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RunMonitorCheckTest extends TestCase
{
    use RefreshDatabase;

    private function handle(RunMonitorCheck $job): void
    {
        $job->handle(app(MonitorChecker::class), app(IncidentManager::class));
    }

    public function test_checagem_recente_e_executada_e_registrada(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $monitor = Monitor::create(['name' => 'Site', 'url' => 'http://8.8.8.8/']);

        $this->handle(new RunMonitorCheck($monitor->id));

        $this->assertDatabaseCount('checks', 1);
        $monitor->refresh();
        $this->assertSame(MonitorStatus::Up, $monitor->status);
        $this->assertNotNull($monitor->last_checked_at);
    }

    public function test_job_velho_demais_e_descartado_sem_checar(): void
    {
        Http::fake();

        $monitor = Monitor::create(['name' => 'Site', 'url' => 'http://8.8.8.8/']);

        $job = new RunMonitorCheck($monitor->id);
        $job->dispatchedAt = now()->subMinutes(10)->timestamp;

        $this->handle($job);

        $this->assertDatabaseCount('checks', 0);
        Http::assertNothingSent();
    }

    public function test_monitor_pausado_nao_e_checado(): void
    {
        Http::fake();

        $monitor = Monitor::create(['name' => 'Site', 'url' => 'http://8.8.8.8/', 'is_active' => false]);

        $this->handle(new RunMonitorCheck($monitor->id));

        $this->assertDatabaseCount('checks', 0);
        Http::assertNothingSent();
    }

    public function test_monitor_apagado_nao_causa_erro(): void
    {
        Http::fake();

        $this->handle(new RunMonitorCheck(9999));

        $this->assertDatabaseCount('checks', 0);
    }
}