<?php

namespace App\Services;

use App\Exceptions\UnsafeUrlException;
use App\Models\Monitor;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MonitorChecker
{
    private const MAX_REDIRECTS = 3;

    private const REDIRECT_CODES = [301, 302, 303, 307, 308];

    public function __construct(private UrlGuard $guard)
    {
    }

    public function check(Monitor $monitor): CheckResult
    {
        $startedAt = hrtime(true);

        try {
            $response = $this->request($monitor, $startedAt);
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

    /**
     * Faz a requisição seguindo os redirecionamentos manualmente: cada destino
     * é validado e tem o IP fixado, e o tempo total respeita o timeout do monitor.
     */
    private function request(Monitor $monitor, int $startedAt): Response
    {
        $url = $monitor->url;

        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $elapsed = (hrtime(true) - $startedAt) / 1_000_000_000;
            $remaining = $monitor->timeout - $elapsed;

            if ($remaining <= 0) {
                throw new RuntimeException('Tempo limite excedido.');
            }

            $target = $this->guard->resolveSafe($url);

            $options = ['allow_redirects' => false];

            if ($target['ips'] !== []) {
                // Sem cURL não há como fixar o IP: melhor falhar do que ficar desprotegido.
                if (! defined('CURLOPT_RESOLVE')) {
                    throw new UnsafeUrlException('A extensão cURL do PHP é necessária para a proteção contra DNS rebinding.');
                }

                $options['curl'] = [constant('CURLOPT_RESOLVE') => [$this->resolveEntry($target)]];
            }

            $response = Http::withHeaders(['User-Agent' => config('pulso.user_agent')])
                ->timeout(max(1, (int) ceil($remaining)))
                ->withOptions($options)
                ->send($monitor->method, $url);

            // Se o monitor espera justamente este status (ex.: 301), não segue.
            if ($response->status() === $monitor->expected_status
                || ! in_array($response->status(), self::REDIRECT_CODES, true)) {
                return $response;
            }

            $location = $response->header('Location');

            if ($location === '') {
                return $response;
            }

            // Resolve Location relativo ("/login") em relação à URL atual.
            $url = (string) UriResolver::resolve(new Uri($url), new Uri($location));
        }

        throw new RuntimeException('Redirecionamentos demais (máximo '.self::MAX_REDIRECTS.').');
    }

    /**
     * Formato do cURL: "host:porta:ip1,ip2" (IPv6 entre colchetes).
     *
     * @param  array{host: string, port: int, ips: list<string>}  $target
     */
    private function resolveEntry(array $target): string
    {
        $ips = array_map(
            fn (string $ip) => str_contains($ip, ':') ? "[{$ip}]" : $ip,
            $target['ips']
        );

        return $target['host'].':'.$target['port'].':'.implode(',', $ips);
    }
}