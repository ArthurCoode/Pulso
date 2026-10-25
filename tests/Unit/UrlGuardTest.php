<?php

namespace Tests\Unit;

use App\Exceptions\UnsafeUrlException;
use App\Services\UrlGuard;
use Tests\TestCase;

class UrlGuardTest extends TestCase
{
    private function assertBlocked(string $url): void
    {
        try {
            app(UrlGuard::class)->assertSafe($url);
        } catch (UnsafeUrlException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail("Deveria ter bloqueado: {$url}");
    }

    public function test_bloqueia_enderecos_privados_e_reservados(): void
    {
        $urls = [
            'http://127.0.0.1/',
            'http://10.0.0.5/',
            'http://192.168.1.10/',
            'http://172.16.0.1/',
            'http://169.254.169.254/latest/meta-data/',
            'http://0.0.0.0/',
            'http://100.64.0.1/',
            'http://[::1]/',
            'http://[::ffff:127.0.0.1]/',
            'http://[fc00::1]/',
        ];

        foreach ($urls as $url) {
            $this->assertBlocked($url);
        }
    }

    public function test_bloqueia_esquemas_que_nao_sao_http(): void
    {
        foreach (['file:///etc/passwd', 'ftp://8.8.8.8/', 'gopher://8.8.8.8/', 'javascript:alert(1)'] as $url) {
            $this->assertBlocked($url);
        }
    }

    public function test_bloqueia_url_malformada(): void
    {
        $this->assertBlocked('isto nao e uma url');
        $this->assertBlocked('http://');
    }

    public function test_permite_ips_publicos(): void
    {
        $guard = app(UrlGuard::class);

        foreach (['http://8.8.8.8/', 'https://1.1.1.1/caminho', 'http://[2606:4700:4700::1111]/'] as $url) {
            $guard->assertSafe($url);
        }

        $this->addToAssertionCount(1);
    }

    public function test_ip_literal_nao_precisa_de_fixacao_de_dns(): void
    {
        $target = app(UrlGuard::class)->resolveSafe('https://8.8.8.8:8443/x');

        $this->assertSame('8.8.8.8', $target['host']);
        $this->assertSame(8443, $target['port']);
        $this->assertSame([], $target['ips']);
    }

    public function test_libera_hosts_privados_quando_configurado(): void
    {
        config(['pulso.allow_private_hosts' => true]);

        app(UrlGuard::class)->assertSafe('http://127.0.0.1/');

        $this->addToAssertionCount(1);
    }
}