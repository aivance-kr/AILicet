<?php

declare(strict_types=1);

namespace App\Support;

use Predis\Client as Redis;
use Throwable;

/**
 * Redis 고정 윈도우 Rate Limiter.
 *
 * 키별로 window 초 동안 max 회까지 허용한다.
 *
 * 정책(의도된 fail-open): Redis 장애 시 요청을 허용한다. 레이트 리밋은 남용 완화용 보조 방어선이고
 * 라이선스 인증 자체(JWT·라이선스 검증)는 별도로 수행되므로, Redis 장애가 전체 클라이언트의
 * 라이선스 확인을 막는 것(fail-closed)보다 가용성을 우선한다. 장애 구간에는 제한이 풀리므로
 * Redis 모니터링·알림으로 보완한다. fail-closed 가 필요해지면 이 catch 의 반환값만 바꾸면 된다.
 */
final class RedisRateLimiter implements RateLimiter
{
    public function __construct(
        private readonly Redis $redis,
        private readonly int $max,
        private readonly int $window,
    ) {
    }

    public function hit(string $key): bool
    {
        try {
            $redisKey = 'ratelimit:' . $key;
            $count    = (int) $this->redis->incr($redisKey);
            if ($count === 1) {
                $this->redis->expire($redisKey, $this->window);
            }

            return $count <= $this->max;
        } catch (Throwable) {
            return true; // fail-open: Redis 장애가 서비스 전체를 막지 않도록
        }
    }
}
