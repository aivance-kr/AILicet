<?php

declare(strict_types=1);

namespace Tests;

use App\Support\ClientIpResolver;
use App\Support\Config;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * 신뢰 프록시 기반 클라이언트 IP 해석 — X-Forwarded-For 위조 방지.
 *
 * @internal
 */
final class ClientIpResolverTest extends TestCase
{
    /**
     * @param list<string> $trusted
     */
    private function resolve(string $peer, ?string $xff, array $trusted = ['127.0.0.1', '::1']): string
    {
        $env = ['TRUSTED_PROXIES' => implode(',', $trusted)];
        $req = (new Psr17Factory())->createServerRequest('GET', '/x', ['REMOTE_ADDR' => $peer]);
        if ($xff !== null) {
            $req = $req->withHeader('X-Forwarded-For', $xff);
        }

        return (new ClientIpResolver(Config::fromEnv($env)))->resolve($req);
    }

    public function testUntrustedPeerIgnoresForwardedFor(): void
    {
        $this->assertSame('203.0.113.9', $this->resolve('203.0.113.9', '1.2.3.4'));
    }

    public function testTrustedProxyUsesForwardedFor(): void
    {
        $this->assertSame('198.51.100.7', $this->resolve('127.0.0.1', '198.51.100.7'));
    }

    public function testSpoofedLeftmostEntryIsIgnoredBehindTrustedProxy(): void
    {
        // 클라이언트가 "6.6.6.6" 을 심고, Apache 가 실제 peer(198.51.100.7)를 append 한 경우
        $this->assertSame('198.51.100.7', $this->resolve('127.0.0.1', '6.6.6.6, 198.51.100.7'));
    }

    public function testChainOfTrustedProxiesIsSkipped(): void
    {
        $this->assertSame('198.51.100.7', $this->resolve('10.0.0.5', '198.51.100.7, 10.0.0.9', ['10.0.0.0/8']));
    }

    public function testMalformedHeaderFallsBackToPeer(): void
    {
        $this->assertSame('127.0.0.1', $this->resolve('127.0.0.1', 'not-an-ip'));
    }

    public function testNoHeaderFromTrustedProxyUsesPeer(): void
    {
        $this->assertSame('127.0.0.1', $this->resolve('127.0.0.1', null));
    }

    public function testMissingPeerIsUnknown(): void
    {
        $req = (new Psr17Factory())->createServerRequest('GET', '/x');
        $this->assertSame('unknown', (new ClientIpResolver(Config::fromEnv([])))->resolve($req));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function cidrCases(): iterable
    {
        yield 'v4 inside /24'  => ['192.168.1.77', '192.168.1.0/24', true];
        yield 'v4 outside /24' => ['192.168.2.77', '192.168.1.0/24', false];
        yield 'v4 /20 inside'  => ['10.0.15.1', '10.0.0.0/20', true];
        yield 'v4 /20 outside' => ['10.0.16.1', '10.0.0.0/20', false];
        yield 'v6 loopback'    => ['::1', '::1', true];
        yield 'v6 /64 inside'  => ['2001:db8::5', '2001:db8::/64', true];
        yield 'v6 /64 outside' => ['2001:db9::5', '2001:db8::/64', false];
        yield 'family mismatch' => ['::1', '127.0.0.1', false];
    }

    #[DataProvider('cidrCases')]
    public function testCidrMatching(string $peer, string $entry, bool $trusted): void
    {
        $result = $this->resolve($peer, '198.51.100.7', [$entry]);

        $this->assertSame($trusted ? '198.51.100.7' : $peer, $result);
    }
}
