<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\FloatingRepository;
use App\Support\Jwt;

/**
 * 플로팅 라이센스 온라인 인증 유스케이스.
 *
 * 활성화(호스트 점유·이중활성화 차단·캐시토큰) → 유효성(잔여 카운트/크레딧) → 분석 차감(중복 방지).
 */
final class FloatingAuthService
{
    private const string TOKEN_SCOPE = 'floating_activation';

    /** 시작만 하고 종료되지 않은 분석이 카운트제 한도를 점유(예약)하는 최대 시간(초). */
    private const int RESERVATION_TTL = 3600;

    public function __construct(
        private readonly FloatingRepository $repo,
        private readonly Jwt $jwt,
    ) {
    }

    /**
     * 활성화 — 호스트 등록 + 이중 활성화 차단 + 캐시 토큰 발급.
     *
     * @return array{activated:bool, reason:string, token?:string, check_term?:int, activate_term?:int}
     */
    public function activation(string $licenseKey, string $hostId): array
    {
        $license = $this->resolve($licenseKey);
        if ($license === null) {
            return ['activated' => false, 'reason' => 'INVALID_LICENSE_KEY'];
        }
        if (($reason = $this->invalidReason($license)) !== null) {
            return ['activated' => false, 'reason' => $reason];
        }

        $licenseId    = (int) $license['id'];
        $activateTerm = (int) ($license['activate_term'] ?? 24);
        $checkTerm    = (int) ($license['check_term'] ?? 30);

        // 점유 검사 + 기록을 라이선스 락 아래 한 트랜잭션으로 — 서로 다른 호스트의 동시 활성화는 하나만 성공
        $occupied = $this->repo->transactional(function () use ($licenseId, $hostId, $activateTerm): bool {
            $this->repo->lockLicense($licenseId);

            $now    = gmdate('Y-m-d H:i:s');
            $active = $this->repo->activeActivation($licenseId, $now);
            if ($active !== null && (string) $active['host_id'] !== $hostId) {
                return true; // 이중 활성화 차단
            }

            $expiresAt = gmdate('Y-m-d H:i:s', time() + $activateTerm * 3600);
            $existing  = $this->repo->latestActivationByHost($licenseId, $hostId);
            if ($existing !== null) {
                $this->repo->renewActivation((int) $existing['id'], $now, $expiresAt); // 행 누적 방지
            } else {
                $this->repo->insertActivation($licenseId, $hostId, $now, $expiresAt);
            }

            return false;
        });
        if ($occupied) {
            return ['activated' => false, 'reason' => 'ALREADY_ACTIVE'];
        }

        $token = $this->jwt->encode([
            'scope' => self::TOKEN_SCOPE,
            'lic'   => $licenseId,
            'host'  => $hostId,
        ], max(60, $checkTerm * 60));

        return ['activated' => true, 'reason' => 'OK', 'token' => $token, 'check_term' => $checkTerm, 'activate_term' => $activateTerm];
    }

    /**
     * 유효성 + 잔여 카운트/크레딧.
     *
     * @return array{valid:bool, reason:string, remaining:array<string,int>, status:?string, expire_date:?string}
     */
    public function effectiveness(string $licenseKey): array
    {
        $license = $this->resolve($licenseKey);
        if ($license === null) {
            return $this->invalid('INVALID_LICENSE_KEY');
        }
        if (($reason = $this->invalidReason($license)) !== null) {
            return $this->invalid($reason, $license);
        }

        $remaining = $this->remaining($license);
        $reason    = 'OK';
        $valid     = true;
        foreach ($remaining as $left) {
            if ($left <= 0) {
                $valid  = false;
                $reason = 'USAGE_EXCEEDED';
            }
        }

        return [
            'valid'       => $valid,
            'reason'      => $reason,
            'remaining'   => $remaining,
            'status'      => (string) $license['status'],
            'expire_date' => $license['expire_date'] !== null ? (string) $license['expire_date'] : null,
        ];
    }

    /**
     * 분석 시작 — analysis_key 발급.
     *
     * @return array{started:bool, reason:string, analysis_key?:string}
     */
    public function analysisStart(string $licenseKey, string $hostId): array
    {
        $license = $this->resolve($licenseKey);
        if ($license === null) {
            return ['started' => false, 'reason' => 'INVALID_LICENSE_KEY'];
        }
        if (($reason = $this->invalidReason($license)) !== null) {
            return ['started' => false, 'reason' => $reason];
        }

        $licenseId   = (int) $license['id'];
        $analysisKey = bin2hex(random_bytes(16));

        // 한도 검사 + 시작 기록을 라이선스 락 아래 한 트랜잭션으로 — 동시 시작이 한도를 넘지 못한다
        $exceeded = $this->repo->transactional(function () use ($license, $licenseId, $analysisKey, $hostId): bool {
            $this->repo->lockLicense($licenseId);

            $since = gmdate('Y-m-d H:i:s', time() - self::RESERVATION_TTL);
            foreach ($this->remaining($license, $since) as $left) {
                if ($left <= 0) {
                    return true;
                }
            }
            $this->repo->insertAnalysis($licenseId, $analysisKey, $hostId, gmdate('Y-m-d H:i:s'));

            return false;
        });
        if ($exceeded) {
            return ['started' => false, 'reason' => 'USAGE_EXCEEDED'];
        }

        return ['started' => true, 'reason' => 'OK', 'analysis_key' => $analysisKey];
    }

    /**
     * 분석 종료 — 성공 시 사용량 차감(중복 방지: analysis_key 이미 종료면 무시).
     *
     * @return array{deducted:bool, reason:string, amount:int}
     */
    public function analysisEnd(string $analysisKey, bool $success, int $amount): array
    {
        $row = $this->repo->findAnalysis($analysisKey);
        if ($row === null) {
            return ['deducted' => false, 'reason' => 'NOT_FOUND', 'amount' => 0];
        }
        if ((string) $row['status'] !== 'started') {
            return ['deducted' => false, 'reason' => 'ALREADY_PROCESSED', 'amount' => 0]; // 멱등
        }

        $now = gmdate('Y-m-d H:i:s');
        if (! $success) {
            $this->repo->completeAnalysis((int) $row['id'], 'failed', 0, $now);

            return ['deducted' => false, 'reason' => 'FAILED_NO_DEDUCT', 'amount' => 0];
        }

        $amount = max(1, $amount); // 카운트제=1, 크레딧제=요청량
        $this->repo->completeAnalysis((int) $row['id'], 'completed', $amount, $now);

        return ['deducted' => true, 'reason' => 'OK', 'amount' => $amount];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolve(string $licenseKey): ?array
    {
        $id = $this->repo->licenseIdByKey($licenseKey);

        return $id === null ? null : $this->repo->find($id);
    }

    /**
     * @param array<string, mixed> $license
     */
    private function invalidReason(array $license): ?string
    {
        if ((string) $license['status'] !== 'active') {
            return 'INACTIVE';
        }
        $expire = $license['expire_date'] !== null ? (string) $license['expire_date'] : null;
        if ($expire !== null && $expire < gmdate('Y-m-d')) {
            return 'EXPIRED';
        }

        return null;
    }

    /**
     * 잔여 사용량(설정된 한도만).
     *
     * $inFlightSince 를 주면 그 시각 이후 시작되고 아직 끝나지 않은 분석을 카운트제 사용량에 포함(예약)한다.
     * 크레딧제는 종료 시점에야 차감량이 정해지므로 예약하지 않는다(시작 시 잔여 > 0 만 확인).
     *
     * @param array<string, mixed> $license
     *
     * @return array<string, int>
     */
    private function remaining(array $license, ?string $inFlightSince = null): array
    {
        $config = json_decode((string) ($license['config'] ?? '{}'), true);
        $limits = is_array($config) && isset($config['limits']) && is_array($config['limits']) ? $config['limits'] : [];
        $id     = (int) $license['id'];

        $remaining = [];
        if (isset($limits['count'])) {
            $used = $this->repo->usedCount($id);
            if ($inFlightSince !== null) {
                $used += $this->repo->inFlightCount($id, $inFlightSince);
            }
            $remaining['count'] = max(0, (int) $limits['count'] - $used);
        }
        if (isset($limits['credit'])) {
            $remaining['credit'] = max(0, (int) $limits['credit'] - $this->repo->usedCredit($id));
        }

        return $remaining;
    }

    /**
     * @param array<string, mixed>|null $license
     *
     * @return array{valid:bool, reason:string, remaining:array<string,int>, status:?string, expire_date:?string}
     */
    private function invalid(string $reason, ?array $license = null): array
    {
        return [
            'valid'       => false,
            'reason'      => $reason,
            'remaining'   => [],
            'status'      => $license !== null ? (string) $license['status'] : null,
            'expire_date' => $license !== null && $license['expire_date'] !== null ? (string) $license['expire_date'] : null,
        ];
    }
}
