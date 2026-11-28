<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * analysis_logs (license_id, status) 복합 인덱스.
 *
 * frontapi 의 사용량 조회(COUNT / SUM ... WHERE license_id AND status)와 진행 중 분석 예약 조회가
 * 로그가 쌓여도 느려지지 않도록 한다. 스키마의 단일 출처는 루트 CI4 마이그레이션이다.
 */
final class AddUsageIndexToAnalysisLogs extends Migration
{
    public function up(): void
    {
        $this->db->query('CREATE INDEX idx_analysis_logs_license_status ON analysis_logs (license_id, status)');
    }

    public function down(): void
    {
        $this->db->query('DROP INDEX idx_analysis_logs_license_status ON analysis_logs');
    }
}
