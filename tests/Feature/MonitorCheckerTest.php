<?php

namespace Tests\Feature;

use App\Models\Monitor;
use App\Services\CheckResult;
use App\Services\MonitorChecker;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonitorCheckerTest extends TestCase
{
    // Os monitores aqui não são salvos: o checker não usa o banco.
    // URLs com IP literal público evitam qualquer consulta de DNS.
    private function monitor(array $attributes = []): Monitor
    {
        return new Monitor($attributes + ['name' => 'Teste', 'url' => 'http://8.8.8.8/']);
    }

    private function runCheck(Monitor $monitor): CheckResult
    {
        return app(MonitorChecker::class)->check($monitor);
    }

    public function test_sucesso_quando_o_status_confere(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $result = $this->runCheck($this->monitor());

        $this->assertTrue($result->isSuccessful);
        $this->assertSame(200, $result->statusCode);
        $this->assertNotNull($result->responseTimeMs);
        $this->assertNull($result->error);
    }

    public function test_falha_quando_o_status_e_diferente(): void
    {
        Http::fake(['*' => Http::response('erro', 500)]);

        $result = $this->runCheck($this->monitor());

        $this->assertFalse($result->isSuccessful);
        $this->assertSame(500, $result->statusCode);
        $this->assertStringContainsString('esperado 200', $result->error);
    }

    public function test_palavra_chave_ausente_falha_e_presente_passa(): void
    {
        Http::fake(['*' => Http::response('bem-vindo ao site', 200)]);

        $missing = $this->runCheck($this->monitor(['keyword' => 'login']));
        $this->assertFalse($missing->isSuccessful);
        $this->assertStringContainsString('Palavra-chave', $missing->error);

        $present = $this->runCheck($this->monitor(['keyword' => 'bem-vindo']));
        $this->assertTrue($present->isSuccessful);
    }

    public function test_falha_de_rede_vira_resultado_de_falha(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: tempo esgotado'));

        $result = $this->runCheck($this->monitor());

        $this->assertFalse($result->isSuccessful);
        $this->assertNull($result->statusCode);
        $this->assertStringContainsString('tempo esgotado', $result->error);
    }

    public function test_host_privado_e_bloqueado_sem_enviar_requisicao(): void
    {
        Http::fake();

        $result = $this->runCheck($this->monitor(['url' => 'http://10.0.0.1/']));

        $this->assertFalse($result->isSuccessful);
        $this->assertStringContainsString('não permitido', $result->error);
        Http::assertNothingSent();
    }

    public function test_redirecionamento_para_rede_interna_e_bloqueado(): void
    {
        Http::fake(['*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/admin'])]);

        $result = $this->runCheck($this->monitor());

        $this->assertFalse($result->isSuccessful);
        $this->assertStringContainsString('não permitido', $result->error);
        // Só a primeira requisição saiu; o destino interno nunca foi acessado.
        Http::assertSentCount(1);
    }

    public function test_segue_redirecionamento_para_destino_publico(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push('', 302, ['Location' => 'http://1.1.1.1/'])
            ->push('ok', 200)]);

        $result = $this->runCheck($this->monitor());

        $this->assertTrue($result->isSuccessful);
        Http::assertSentCount(2);
    }

    public function test_limita_a_quantidade_de_redirecionamentos(): void
    {
        Http::fake(['*' => Http::response('', 302, ['Location' => 'http://8.8.4.4/'])]);

        $result = $this->runCheck($this->monitor());

        $this->assertFalse($result->isSuccessful);
        $this->assertStringContainsString('Redirecionamentos demais', $result->error);
        // A requisição inicial mais 3 redirecionamentos.
        Http::assertSentCount(4);
    }

    public function test_nao_segue_redirecionamento_quando_ele_e_o_status_esperado(): void
    {
        Http::fake(['*' => Http::response('', 302, ['Location' => 'http://1.1.1.1/'])]);

        $result = $this->runCheck($this->monitor(['expected_status' => 302]));

        $this->assertTrue($result->isSuccessful);
        Http::assertSentCount(1);
    }
}