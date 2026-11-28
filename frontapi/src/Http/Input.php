<?php

declare(strict_types=1);

namespace App\Http;

use App\Middleware\ClientIpMiddleware;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 요청 본문(JSON) 파싱 헬퍼.
 */
final class Input
{
    /**
     * @return array<string, mixed>
     */
    public static function json(ServerRequestInterface $request): array
    {
        $raw = (string) $request->getBody();
        if ($raw === '') {
            return [];
        }
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    /**
     * 클라이언트 IP — ClientIpMiddleware 가 신뢰 프록시 규칙으로 해석한 값.
     * 속성이 없으면(미들웨어 미경유) 헤더는 신뢰하지 않고 소켓 peer 만 쓴다.
     */
    public static function clientIp(ServerRequestInterface $request): string
    {
        $ip = $request->getAttribute(ClientIpMiddleware::ATTRIBUTE);
        if (is_string($ip) && $ip !== '') {
            return $ip;
        }

        return (string) ($request->getServerParams()['REMOTE_ADDR'] ?? 'unknown');
    }
}
