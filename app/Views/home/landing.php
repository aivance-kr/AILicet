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

$steps = [
    ['title' => '상품·모듈 등록', 'body' => '운영자가 상품 코드, 모듈, 기간 정책, 사용량 한도를 정의합니다.'],
    ['title' => '라이선스 발급', 'body' => '노드락은 호스트ID에 바인딩된 서명 파일을, 플로팅은 관리키를 발급합니다.'],
    ['title' => '클라이언트 인증', 'body' => '노드락은 내장 공개키로 오프라인 검증, 플로팅은 frontapi로 활성화·유효성 확인을 수행합니다.'],
    ['title' => '사용량·감사 기록', 'body' => '분석 시작·종료 시 사용량을 차감하고 모든 요청과 상태 변경을 로그로 남깁니다.'],
    ['title' => '만료·부정사용 대응', 'body' => '배치가 만료 알림과 자동 종료, 부정사용 감지를 처리하고 Slack으로 알립니다.'],
];
$plans = [
    ['name' => '노드락', 'tag' => '오프라인 설치형', 'items' => ['Ed25519 서명 파일 발급', '호스트ID 바인딩', '오프라인 검증', '재발급 지원']],
    ['name' => '플로팅', 'tag' => '온라인 동시 사용형', 'items' => ['관리키 기반 활성화', '유효성·사용량 실시간 확인', '이중 활성화 차단', '카운트·크레딧 차감'], 'featured' => true],
    ['name' => '엔터프라이즈', 'tag' => '맞춤 구성', 'items' => ['노드락·플로팅 혼합 운영', '대행사 계층 구조', '전용 지원 및 SLA', '연동 개발 지원']],
];
$tools = [
    ['title' => '라이선스 콘솔', 'body' => '운영자·대행사·고객 포털에서 발급, 연장, 정지, 재발급, 이력 조회를 수행합니다.'],
    ['title' => 'frontapi (REST)', 'body' => '활성화, 유효성, 사용량 차감, 로그 수집 엔드포인트를 JWT Bearer로 제공합니다.'],
    ['title' => 'Swagger 문서', 'body' => '/api/docs 에서 OpenAPI 스펙을 확인하고 바로 호출해 볼 수 있습니다.'],
    ['title' => '키 생성 CLI', 'body' => 'php spark license:keygen 으로 서명 키 쌍을 생성합니다.'],
    ['title' => '일일 배치', 'body' => '만료 알림, 자동 종료, 부정사용 감지를 일 단위로 실행합니다.'],
    ['title' => 'AI 로그 도구', 'body' => '로그 분류, 감사 로그 설명, 고객 문의 답변 초안을 생성합니다.'],
];
$sdks = [
    ['lang' => 'PHP', 'status' => '제공', 'body' => 'LicenseVerifier 참조 구현. 공개키로 NLicense.lic 서명을 검증합니다.'],
    ['lang' => 'Python', 'status' => '제공', 'body' => 'PyNaCl 기반. pip로 설치해 verify_license_file() 한 번으로 검증합니다.'],
    ['lang' => 'Java', 'status' => '제공', 'body' => 'JDK 17 내장 Ed25519 사용. 외부 암호 라이브러리가 필요 없습니다.'],
    ['lang' => 'C++', 'status' => '제공', 'body' => 'libsodium 기반. CMake 라이브러리로 앱에 정적 연결합니다.'],
    ['lang' => 'C#', 'status' => '제공', 'body' => '.NET 8, BouncyCastle 기반 순수 관리 코드 구현입니다.'],
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
        .landing-section--alt { max-width:none; background:var(--color-surface); border-top:1px solid var(--color-border); border-bottom:1px solid var(--color-border); }
        .landing-section--alt > .landing-inner { max-width:1080px; margin:0 auto; padding:64px 24px; }
        .landing-steps { counter-reset:step; display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:16px; margin-top:28px; }
        .landing-step { position:relative; padding:20px 16px 16px; border:1px solid var(--color-border); border-radius:var(--radius-sm, 8px); background:var(--color-bg); }
        .landing-step::before { counter-increment:step; content:counter(step); display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:50%; background:var(--color-primary); color:#fff; font-weight:700; font-size:14px; margin-bottom:10px; }
        .landing-step h3 { margin:0 0 6px; font-size:15px; }
        .landing-step p { margin:0; font-size:13px; line-height:1.6; color:var(--color-text-muted); }
        .landing-plan { display:flex; flex-direction:column; }
        .landing-plan.is-featured { border:2px solid var(--color-secondary); }
        .landing-plan .tag { color:var(--color-text-muted); font-size:13px; margin:0 0 12px; }
        .landing-plan .price { font-size:22px; font-weight:700; margin:0 0 14px; }
        .landing-plan ul { margin:0 0 16px; padding-left:18px; font-size:14px; line-height:1.8; color:var(--color-text-muted); }
        .landing-sdk { display:flex; flex-wrap:wrap; gap:12px; margin-top:28px; }
        .landing-sdk .card { flex:1 1 180px; }
        .landing-sdk .status { font-size:12px; font-weight:600; color:var(--color-text-soft); }
        .landing-sdk .status.is-live { color:var(--color-success); }
        .landing-code { margin-top:20px; padding:16px; background:#0F1F1A; color:#D7EFE6; border-radius:8px; font-size:13px; line-height:1.6; overflow-x:auto; }
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

<main>
    <section class="landing-section" id="features">
        <h2>주요 기능</h2>
        <p class="muted">발급부터 검증, 운영 자동화까지 라이선스 수명주기 전체를 다룹니다.</p>
        <div class="landing-grid">
            <?php foreach ($features as $feature): ?>
                <div class="card"><div class="card__body">
                    <h3><?= esc($feature['title']) ?></h3>
                    <p><?= esc($feature['body']) ?></p>
                </div></div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="landing-section--alt" id="process">
        <div class="landing-inner">
            <h2>라이선스 프로세스</h2>
            <p class="muted">상품 등록에서 부정사용 대응까지, 다섯 단계로 운영됩니다.</p>
            <div class="landing-steps">
                <?php foreach ($steps as $step): ?>
                    <div class="landing-step">
                        <h3><?= esc($step['title']) ?></h3>
                        <p><?= esc($step['body']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="landing-section" id="pricing">
        <h2>가격정책</h2>
        <p class="muted">라이선스 유형별로 구성합니다. 상세 요금은 문의해 주세요.</p>
        <div class="landing-grid">
            <?php foreach ($plans as $plan): ?>
                <div class="card landing-plan<?= ! empty($plan['featured']) ? ' is-featured' : '' ?>"><div class="card__body">
                    <h3><?= esc($plan['name']) ?></h3>
                    <p class="tag"><?= esc($plan['tag']) ?></p>
                    <p class="price">문의</p>
                    <ul>
                        <?php foreach ($plan['items'] as $item): ?>
                            <li><?= esc($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div></div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="landing-section--alt" id="tools">
        <div class="landing-inner">
            <h2>제공 도구</h2>
            <p class="muted">콘솔, API, CLI, 배치까지 운영에 필요한 도구를 함께 제공합니다.</p>
            <div class="landing-grid">
                <?php foreach ($tools as $tool): ?>
                    <div class="card"><div class="card__body">
                        <h3><?= esc($tool['title']) ?></h3>
                        <p><?= esc($tool['body']) ?></p>
                    </div></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="landing-section" id="sdk">
        <h2>SDK</h2>
        <p class="muted">클라이언트 프로그램에 내장해 라이선스 파일을 검증합니다. 모든 SDK는 같은 파일 포맷과 검증 순서를 따릅니다.</p>
        <div class="landing-sdk">
            <?php foreach ($sdks as $sdk): ?>
                <div class="card"><div class="card__body">
                    <h3><?= esc($sdk['lang']) ?></h3>
                    <span class="status<?= $sdk['status'] === '제공' ? ' is-live' : '' ?>"><?= esc($sdk['status']) ?></span>
                    <?php if ($sdk['body'] !== ''): ?>
                        <p><?= esc($sdk['body']) ?></p>
                    <?php endif; ?>
                </div></div>
            <?php endforeach; ?>
        </div>
        <pre class="landing-code"><code>$verifier = new LicenseVerifier($publicKeyBase64);
$payload  = $verifier->verify(file_get_contents('NLicense.lic'));
// 위조·변조 시 null, 통과 시 페이로드(host_id, expire_date, modules ...)</code></pre>
    </section>
</main>

<footer class="landing-foot">© AIvance · AILicet</footer>
</body>
</html>
