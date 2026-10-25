<?php

namespace Tests\Feature;

use App\Enums\MonitorStatus;
use App\Models\Check;
use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private function adminHeaders(): array
    {
        config(['pulso.admin.user' => 'arthur', 'pulso.admin.password' => 'segredo']);

        return ['Authorization' => 'Basic '.base64_encode('arthur:segredo')];
    }

    public function test_folha_de_estilo_existe(): void
    {
        $this->assertFileExists(public_path('css/pulso.css'));
    }

    public function test_painel_vazio_convida_a_cadastrar(): void
    {
        $this->withHeaders($this->adminHeaders())
            ->get('/monitors')
            ->assertOk()
            ->assertSee('Nenhum monitor ainda')
            ->assertSee('Cadastrar o primeiro monitor');
    }

    public function test_telas_do_painel_renderizam_com_dados(): void
    {
        $monitor = Monitor::create([
            'name' => 'Site Exemplo',
            'url' => 'http://8.8.8.8/',
            'status' => MonitorStatus::Down,
            'is_public' => true,
        ]);

        foreach ([120, 180, 90, 240] as $ms) {
            Check::create([
                'monitor_id' => $monitor->id,
                'status_code' => 200,
                'response_time_ms' => $ms,
                'is_successful' => true,
                'checked_at' => now()->subMinutes(10),
            ]);
        }

        Check::create([
            'monitor_id' => $monitor->id,
            'is_successful' => false,
            'error' => 'falha de teste',
            'checked_at' => now()->subMinutes(5),
        ]);

        Incident::create([
            'monitor_id' => $monitor->id,
            'started_at' => now()->subMinutes(5),
            'cause' => 'falha de teste',
        ]);

        $headers = $this->adminHeaders();

        $this->withHeaders($headers)->get('/monitors')
            ->assertOk()
            ->assertSee('Site Exemplo')
            ->assertSee('1 serviço fora do ar');

        $this->withHeaders($headers)->get("/monitors/{$monitor->id}")
            ->assertOk()
            ->assertSee('Incidente em andamento')
            ->assertSee('Tempo de resposta');

        $this->withHeaders($headers)->get('/monitors/create')->assertOk()->assertSee('Novo monitor');
        $this->withHeaders($headers)->get("/monitors/{$monitor->id}/edit")->assertOk()->assertSee('Editar monitor');

        $this->get('/status')->assertOk()->assertSee('Site Exemplo');
    }
}
