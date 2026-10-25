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
        $this->resolveSafe($url);
    }

    /**
     * Valida a URL e devolve onde conectar.
     *
     * 'ips' traz os endereços JÁ VALIDADOS para o cliente HTTP fixar na conexão
     * (impede DNS rebinding). Fica vazio quando não há o que fixar: o host já é
     * um IP literal, ou as checagens estão liberadas por configuração.
     *
     * @return array{host: string, port: int, ips: list<string>}
     */
    public function resolveSafe(string $url): array
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new UnsafeUrlException('URL inválida.');
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new UnsafeUrlException('Apenas URLs http e https são permitidas.');
        }

        $host = trim($parts['host'], '[]');
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        if (config('pulso.allow_private_hosts')) {
            return ['host' => $host, 'port' => $port, 'ips' => []];
        }

        $isLiteral = filter_var($host, FILTER_VALIDATE_IP) !== false;

        $ips = $isLiteral ? [$host] : $this->resolve($host);

        if ($ips === []) {
            throw new UnsafeUrlException("Não foi possível resolver o host {$host}.");
        }

        foreach ($ips as $ip) {
            if (! $this->isPublic($ip)) {
                throw new UnsafeUrlException("O host {$host} aponta para um endereço não permitido ({$ip}).");
            }
        }

        return ['host' => $host, 'port' => $port, 'ips' => $isLiteral ? [] : $ips];
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
        // IPv4 embutido em IPv6 (::ffff:127.0.0.1): avalia o IPv4 de dentro.
        if (preg_match('/^::ffff:(\d{1,3}(?:\.\d{1,3}){3})$/i', $ip, $matches)) {
            $ip = $matches[1];
        }

        $valid = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($valid === false) {
            return false;
        }

        // 100.64.0.0/10 (CGNAT): o PHP não trata como privada, mas é rede interna.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $long = ip2long($ip);

            if ($long >= ip2long('100.64.0.0') && $long <= ip2long('100.127.255.255')) {
                return false;
            }
        }

        return true;
    }
}