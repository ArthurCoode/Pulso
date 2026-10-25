<?php

namespace App\Services;

use App\Models\Monitor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class MonitorChecker
{
    public function __construct(private UrlGuard $guard)
    {
    }

    public function check(Monitor $monitor): CheckResult
    {
        $startedAt = hrtime(true);

        try {
            $this->guard->assertSafe($monitor->url);

            $response = Http::withHeaders(['User-Agent' => config('pulso.user_agent')])
                ->timeout($monitor->timeout)
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 3,
                        'protocols' => ['http', 'https'],
                        // Cada redirecionamento também passa pela validação SSRF.
                        'on_redirect' => fn ($request, $response, $uri) => $this->guard->assertSafe((string) $uri),
                    ],
                ])
                ->send($monitor->method, $monitor->url);
        } catch (Throwable $e) {
            // Falha de rede, timeout, DNS, URL bloqueada... tudo vira um resultado "falhou".
            return new CheckResult(
                isSuccessful: false,
                error: Str::limit($e->getMessage(), 250, ''),
            );
        }

        $elapsedMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);
        $status = $response->status();

        if ($status !== $monitor->expected_status) {
            return new CheckResult(false, $status, $elapsedMs, "Status {$status} (esperado {$monitor->expected_status}).");
        }

        if ($monitor->keyword !== null && $monitor->keyword !== ''
            && ! str_contains($response->body(), $monitor->keyword)) {
            return new CheckResult(false, $status, $elapsedMs, 'Palavra-chave não encontrada na resposta.');
        }

        return new CheckResult(true, $status, $elapsedMs);
    }
}