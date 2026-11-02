<?php
/**
 * 서비스 소개(랜딩) 페이지.
 *
 * @var bool $loggedIn 세션 로그인 여부
 */
$ctaHref  = $loggedIn ? '/admin' : '/admin/login';
$ctaLabel = $loggedIn ? '콘솔로 이동' : '로그인';
$features = [
    ['title' => '노드락 라이선스', 'body' => 'Ed25519 서명 파일을 배포하면 클라이언트가 공개키로 오프라인 검증합니다. 호스트ID 바인딩으로 무단 복제를 차단합니다.'],
    ['title' => '플로팅 라이선스', 'body' => '관리키만 배포하고 온라인으로 활성화·유효성 확인·사용량 차감을 처리합니다. 이중 활성화는 자동 차단됩니다.'],
    ['title' => '역할별 콘솔', 'body' => '운영자·대행사·고객이 각자의 소유권 범위 안에서 발급, 조회, 연장, 정지, 재발급을 수행합니다.'],
    ['title' => '자동화·알림', 'body' => '만료 알림, 자동 종료, 부정사용 감지를 배치로 처리하고 Slack으로 통보합니다.'],
    ['title' => 'AI 업무 지원', 'body' => '수집 로그 분류·요약, 이상 사용 탐지, 고객 문의 답변 초안까지. 최종 확정은 항상 사람이 합니다.'],
    ['title' => '감사 추적', 'body' => '모든 발급·상태 변경 이력을 기록해 언제든 누가 무엇을 했는지 되짚을 수 있습니다.'],
];
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AILicet · 라이선스 인증·관리 서비스</title>
    <meta name="description" content="AILicet은 온·오프라인을 아우르는 라이선스 발급·인증·관리 서비스입니다.">
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
    <link rel="alternate icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('apple-touch-icon.png') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/aicura.css') ?>">
    <style>
        .landing-nav { display:flex; align-items:center; justify-content:space-between; max-width:1080px; margin:0 auto; padding:20px 24px; }
        .landing-brand { display:flex; align-items:center; gap:10px; font-size:20px; font-weight:700; color:#fff; }
        .landing-hero { background:linear-gradient(135deg, var(--color-primary-dark), var(--color-primary) 60%, var(--color-secondary)); color:#fff; }
        .landing-hero__body { max-width:1080px; margin:0 auto; padding:72px 24px 96px; }
        .landing-hero h1 { font-size:clamp(28px, 5vw, 44px); line-height:1.25; margin:0 0 16px; }
        .landing-hero p { font-size:17px; max-width:560px; margin:0 0 32px; opacity:.92; }
        .landing-hero .btn--cta { background:#fff; color:var(--color-primary-dark); padding:12px 28px; font-weight:600; }
        .landing-hero .btn--cta:hover { background:var(--color-primary-soft); color:var(--color-primary-dark); }
        .landing-section { max-width:1080px; margin:0 auto; padding:64px 24px; }
        .landing-section h2 { font-size:24px; margin:0 0 8px; }
        .landing-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px; margin-top:28px; }
        .landing-grid .card__body h3 { margin:0 0 8px; font-size:16px; color:var(--color-primary); }
        .landing-grid .card__body p { margin:0; font-size:14px; color:var(--color-text-muted); line-height:1.6; }
        .landing-foot { text-align:center; padding:28px 24px; color:var(--color-text-muted); font-size:13px; border-top:1px solid var(--color-border); }
    </style>
</head>
<body>
<header class="landing-hero">
    <nav class="landing-nav">
        <span class="landing-brand">
            <img src="<?= base_url('assets/img/ailicet-mark-on-dark.svg') ?>" width="32" height="32" alt=""> AILicet
        </span>
        <a class="btn btn--cta" href="<?= esc($ctaHref, 'attr') ?>"><?= esc($ctaLabel) ?></a>
    </nav>
    <div class="landing-hero__body">
        <h1>Permission, verified.<br>라이선스 인증·관리를 한곳에서.</h1>
        <p>AILicet은 AIvance 제품군을 위한 라이선스 발급·인증·관리 서비스입니다. 오프라인 노드락부터 온라인 플로팅까지 하나의 콘솔에서 운영합니다.</p>
        <a class="btn btn--cta" href="<?= esc($ctaHref, 'attr') ?>"><?= esc($ctaLabel) ?></a>
    </div>
</header>

<main class="landing-section">
    <h2>주요 기능</h2>
    <p class="muted">발급부터 검증, 운영 자동화까지 라이선스 수명주기 전체를 다룹니다.</p>
    <div class="landing-grid">
        <?php foreach ($features as $feature): ?>
            <div class="card">
                <div class="card__body">
                    <h3><?= esc($feature['title']) ?></h3>
                    <p><?= esc($feature['body']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<footer class="landing-foot">© AIvance · AILicet</footer>
</body>
</html>
