<?php

use App\Libraries\Seo\JsonLdBuilder;
use App\Libraries\Seo\LandingContent;

/**
 * 서비스 소개(랜딩) 페이지.
 *
 * @var bool $loggedIn 세션 로그인 여부
 */
$ctaHref  = $loggedIn ? '/admin' : '/admin/login';
$ctaLabel = $loggedIn ? '콘솔로 이동' : '로그인';
$features  = LandingContent::features();
$faqs      = LandingContent::faqs();
$jsonLd    = (new JsonLdBuilder())->landingJson();
$canonical = base_url('/');
$ogImage   = base_url('apple-touch-icon.png');
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc(LandingContent::TITLE) ?></title>
    <meta name="description" content="<?= esc(LandingContent::DESCRIPTION, 'attr') ?>">
    <link rel="canonical" href="<?= esc($canonical, 'attr') ?>">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="AILicet">
    <meta property="og:locale" content="ko_KR">
    <meta property="og:title" content="<?= esc(LandingContent::TITLE, 'attr') ?>">
    <meta property="og:description" content="<?= esc(LandingContent::DESCRIPTION, 'attr') ?>">
    <meta property="og:url" content="<?= esc($canonical, 'attr') ?>">
    <meta property="og:image" content="<?= esc($ogImage, 'attr') ?>">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= esc(LandingContent::TITLE, 'attr') ?>">
    <meta name="twitter:description" content="<?= esc(LandingContent::DESCRIPTION, 'attr') ?>">
    <meta name="twitter:image" content="<?= esc($ogImage, 'attr') ?>">
    <script type="application/ld+json"><?= $jsonLd ?></script>
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
        .landing-faq details { border-bottom:1px solid var(--color-border); padding:14px 0; }
        .landing-faq summary { cursor:pointer; font-weight:600; }
        .landing-faq p { margin:8px 0 0; font-size:14px; color:var(--color-text-muted); line-height:1.6; }
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
    <section class="landing-faq" id="faq" style="margin-top:56px">
        <h2>자주 묻는 질문</h2>
        <?php foreach ($faqs as $faq): ?>
            <details>
                <summary><?= esc($faq['q']) ?></summary>
                <p><?= esc($faq['a']) ?></p>
            </details>
        <?php endforeach; ?>
    </section>
</main>

<footer class="landing-foot">© AIvance · AILicet</footer>
</body>
</html>
