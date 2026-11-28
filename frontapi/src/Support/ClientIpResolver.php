<?php

declare(strict_types=1);

namespace App\Support;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 신뢰 프록시(Apache 등)를 고려한 클라이언트 IP 해석.
 *
 * - 소켓 peer(REMOTE_ADDR)가 신뢰 프록시가 아니면 X-Forwarded-For 를 무시한다(위조 방지).
 * - 신뢰 프록시면 XFF 를 오른쪽→왼쪽으로 훑어 신뢰 프록시가 아닌 첫 주소를 클라이언트로 본다.
 *   (프록시는 자신이 본 peer 를 오른쪽에 append 하므로 왼쪽 값은 클라이언트가 임의로 넣을 수 있다.)
 */
final class ClientIpResolver
{
    /** @var list<string> */
    private array $trusted;

    public function __construct(Config $config)
    {
        $this->trusted = $config->trustedProxies;
    }

    public function resolve(ServerRequestInterface $request): string
    {
        $peer = (string) ($request->getServerParams()['REMOTE_ADDR'] ?? '');
        if ($peer === '') {
            return 'unknown';
        }
        if (! $this->isTrusted($peer)) {
            return $peer;
        }

        $forwarded = $request->getHeaderLine('X-Forwarded-For');
        if ($forwarded === '') {
            return $peer;
        }

        $hops = array_map('trim', explode(',', $forwarded));
        for ($i = count($hops) - 1; $i >= 0; $i--) {
            $hop = $hops[$i];
            if (filter_var($hop, FILTER_VALIDATE_IP) === false) {
                return $peer; // 형식이 깨진 헤더는 신뢰하지 않는다
            }
            if (! $this->isTrusted($hop)) {
                return $hop;
            }
        }

        return $peer;
    }

    private function isTrusted(string $ip): bool
    {
        foreach ($this->trusted as $entry) {
            if (self::matches($ip, $entry)) {
                return true;
            }
        }

        return false;
    }

    /** 단일 IP 또는 CIDR 매칭(IPv4/IPv6). */
    private static function matches(string $ip, string $entry): bool
    {
        [$net, $bits] = array_pad(explode('/', $entry, 2), 2, null);
        $a = @inet_pton($ip);
        $b = @inet_pton((string) $net);
        if ($a === false || $b === false || strlen($a) !== strlen($b)) {
            return false;
        }

        $max  = strlen($a) * 8;
        $bits = $bits === null ? $max : (int) $bits;
        if ($bits < 0 || $bits > $max) {
            return false;
        }

        $full = intdiv($bits, 8);
        if ($full > 0 && substr($a, 0, $full) !== substr($b, 0, $full)) {
            return false;
        }
        $rest = $bits % 8;
        if ($rest === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $rest)) & 0xFF;

        return (ord($a[$full]) & $mask) === (ord($b[$full]) & $mask);
    }
}
