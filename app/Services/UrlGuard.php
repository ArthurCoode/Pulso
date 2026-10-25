<?php

namespace App\Services;

use App\Exceptions\UnsafeUrlException;

class UrlGuard
{
    /**
     * Lança UnsafeUrlException se a URL não for segura para ser requisitada.
     */
    public function assertSafe(string $url): void
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new UnsafeUrlException('URL inválida.');
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            throw new UnsafeUrlException('Apenas URLs http e https são permitidas.');
        }

        if (config('pulso.allow_private_hosts')) {
            return;
        }

        $host = trim($parts['host'], '[]');

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : $this->resolve($host);

        if ($ips === []) {
            throw new UnsafeUrlException("Não foi possível resolver o host {$host}.");
        }

        foreach ($ips as $ip) {
            if (! $this->isPublic($ip)) {
                throw new UnsafeUrlException("O host {$host} aponta para um endereço não permitido ({$ip}).");
            }
        }
    }

    /**
     * @return list<string>
     */
    private function resolve(string $host): array
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];

        $ips = [];
        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($ip !== null) {
                $ips[] = $ip;
            }
        }

        // Fallback para o resolvedor do sistema (arquivo hosts, etc.).
        if ($ips === []) {
            $ips = gethostbynamel($host) ?: [];
        }

        return array_values(array_unique($ips));
    }

    private function isPublic(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}