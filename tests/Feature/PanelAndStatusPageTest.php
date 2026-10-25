<?php

namespace Tests\Feature;

use App\Enums\MonitorStatus;
use App\Models\Check;
use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAndStatusPageTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): static
    {
        config(['pulso.admin.user' => 'arthur', 'pulso.admin.password' => 'segredo']);

        return $this->withHeaders(['Authorization' => 'Basic '.base64_encode('arthur:segredo')]);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Site',
            'url' => 'http://8.8.8.8/',
            'method' => 'GET',
            'expected_status' => 200,
            'timeout' => 10,
            'interval_minutes' => 1,
        ];
    }

    // ---- Acesso ao painel ----

    public function test_painel_fica_bloqueado_quando_nao_ha_credenciais_fora_do_ambiente_local(): void
    {
        $this->get('/monitors')->assertForbidden();
    }

    public function test_painel_exige_usuario_e_senha_corretos(): void
    {
        config(['pulso.admin.user' => 'arthur', 'pulso.admin.password' => 'segredo']);

        $this->get('/monitors')->assertStatus(401);

        $this->withHeaders(['Authorization' => 'Basic '.base64_encode('arthur:errada')])
            ->get('/monitors')
            ->assertStatus(401);

        $this->asAdmin()->get('/monitors')->assertOk();
    }

    // ---- Cadastro ----

    public function test_cadastro_rejeita_url_de_rede_interna(): void
    {
        $this->asAdmin()
            ->post('/monitors', $this->payload(['url' => 'http://127.0.0.1/']))
            ->assertSessionHasErrors('url');

        $this->assertDatabaseCount('monitors', 0);
    }

    public function test_cadastro_aceita_url_publica(): void
    {
        $this->asAdmin()
            ->post('/monitors', $this->payload(['name' => 'Meu site']))
            ->assertRedirect();

        $this->assertDatabaseHas('monitors', ['name' => 'Meu site', 'is_public' => false]);
    }

    public function test_desmarcar_publico_realmente_desativa(): void
    {
        $monitor = Monitor::create(['name' => 'Site', 'url' => 'http://8.8.8.8/', 'is_public' => true]);

        // Checkbox desmarcado: o navegador simplesmente não envia o campo.
        $this->asAdmin()->put("/monitors/{$monitor->id}", $this->payload());

        $this->assertFalse($monitor->fresh()->is_public);
    }

    public function test_mudar_a_url_reinicia_o_estado_e_fecha_o_incidente(): void
    {
        $monitor = Monitor::create([
            'name' => 'Site',
            'url' => 'http://8.8.8.8/',
            'status' => MonitorStatus::Down,
            'consecutive_failures' => 5,
        ]);
        $incident = Incident::create(['monitor_id' => $monitor->id, 'started_at' => now()->subHour()]);

        $this->asAdmin()->put("/monitors/{$monitor->id}", $this->payload(['url' => 'http://1.1.1.1/']));

        $monitor->refresh();
        $this->assertSame(MonitorStatus::Unknown, $monitor->status);
        $this->assertSame(0, $monitor->consecutive_failures);
        $this->assertNotNull($incident->fresh()->resolved_at);
    }

    // ---- Status page pública ----

    public function test_status_page_e_publica_e_so_mostra_monitores_marcados(): void
    {
        // Com o painel protegido por senha, a status page continua aberta.
        config(['pulso.admin.user' => 'arthur', 'pulso.admin.password' => 'segredo']);

        $public = Monitor::create([
            'name' => 'Servico Publico',
            'url' => 'http://8.8.8.8/segredo-url',
            'is_public' => true,
            'status' => MonitorStatus::Up,
        ]);
        Monitor::create(['name' => 'Servico Interno', 'url' => 'http://1.1.1.1/', 'is_public' => false]);

        Check::create([
            'monitor_id' => $public->id,
            'is_successful' => false,
            'error' => 'ERRO-SECRETO-456',
            'checked_at' => now()->subHour(),
        ]);
        Incident::create([
            'monitor_id' => $public->id,
            'started_at' => now()->subHours(2),
            'resolved_at' => now()->subHour(),
            'cause' => 'CAUSA-SECRETA-123',
        ]);

        $this->get('/status')
            ->assertOk()
            ->assertSee('Servico Publico')
            ->assertSee('Todos os sistemas operacionais')
            ->assertDontSee('Servico Interno')
            ->assertDontSee('segredo-url')
            ->assertDontSee('ERRO-SECRETO-456')
            ->assertDontSee('CAUSA-SECRETA-123');
    }

    public function test_status_page_sem_monitores_publicos_mostra_aviso(): void
    {
        $this->get('/status')
            ->assertOk()
            ->assertSee('Nenhum serviço público configurado');
    }
}