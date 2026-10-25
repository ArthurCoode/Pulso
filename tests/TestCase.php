<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Trava de segurança: roda ANTES do RefreshDatabase apagar as tabelas.
     * Se os testes apontarem para um banco em arquivo, aborta.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $default = $this->app['config']->get('database.default');
        $database = $this->app['config']->get("database.connections.{$default}.database");

        if ($database !== ':memory:') {
            throw new RuntimeException("Os testes devem usar SQLite em memória (veja o phpunit.xml). Banco atual: {$database}");
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Qualquer requisição HTTP não simulada vira erro, em vez de sair para a internet.
        Http::preventStrayRequests();

        // Configuração-base fixa: o seu .env local (token do Telegram,
        // allow_private_hosts=true, etc.) não pode influenciar os testes.
        config([
            'pulso.allow_private_hosts' => false,
            'pulso.failure_threshold' => 3,
            'pulso.max_job_age' => 120,
            'pulso.retention_days' => 120,
            'pulso.display_timezone' => 'America/Bahia',
            'pulso.admin.user' => null,
            'pulso.admin.password' => null,
            'pulso.alerts.mail_to' => null,
            'pulso.alerts.telegram_token' => null,
            'pulso.alerts.telegram_chat_id' => null,
        ]);
    }
}