<?php
/**
 * includes/header.php
 * 공통 헤더 — SEO 메타태그 포함
 *
 * 사용 전 페이지에서 아래 변수를 설정하세요:
 *   $pageTitle       = '페이지 제목';
 *   $pageDescription = '페이지 설명 (150자 이내)';
 *   $pageKeywords    = '키워드1, 키워드2';
 *   $ogImage         = '이미지 URL';
 *   $canonicalUrl    = '정규 URL';
 */
$siteName    = defined('SITE_NAME_DB')        ? SITE_NAME_DB        : SITE_NAME;
$siteDesc    = defined('SITE_DESCRIPTION_DB') ? SITE_DESCRIPTION_DB : '';
$pageTitle       = isset($pageTitle)       ? $pageTitle       : $siteName;
$pageDescription = isset($pageDescription) ? $pageDescription : $siteDesc;
$pageKeywords    = isset($pageKeywords)    ? $pageKeywords    : '';
$ogImage         = isset($ogImage)         ? $ogImage         : SITE_URL . '/assets/img/og-default.svg';
$canonicalUrl    = isset($canonicalUrl)    ? $canonicalUrl    : current_url();
$fullTitle       = ($pageTitle === $siteName) ? $siteName : $pageTitle . ' | ' . $siteName;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <!-- SEO 기본 -->
  <title><?= e($fullTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">
  <?php if ($pageKeywords): ?>
  <meta name="keywords" content="<?= e($pageKeywords) ?>">
  <?php endif; ?>
  <link rel="canonical" href="<?= e($canonicalUrl) ?>">

  <!-- Open Graph -->
  <meta property="og:type"        content="website">
  <meta property="og:url"         content="<?= e($canonicalUrl) ?>">
  <meta property="og:title"       content="<?= e($fullTitle) ?>">
  <meta property="og:description" content="<?= e($pageDescription) ?>">
  <meta property="og:image"       content="<?= e($ogImage) ?>">
  <meta property="og:site_name"   content="<?= e($siteName) ?>">
  <meta property="og:locale"      content="ko_KR">

  <!-- Twitter Card -->
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="<?= e($fullTitle) ?>">
  <meta name="twitter:description" content="<?= e($pageDescription) ?>">
  <meta name="twitter:image"       content="<?= e($ogImage) ?>">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- 사이트 공통 CSS -->
  <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
  <!-- TOAST UI Editor 뷰어 CSS (본문 스타일) -->
  <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor-only.min.css">

  <!-- Cloudflare Turnstile (필요 시 개별 페이지에서 포함) -->
  <?php if (isset($useTurnstile) && $useTurnstile): ?>
  <?= Turnstile::script() ?>
  <?php endif; ?>

  <!-- 구조화 데이터 (페이지별 설정) -->
  <?php if (isset($schemaJson)): ?>
  <script type="application/ld+json"><?= $schemaJson ?></script>
  <?php endif; ?>
</head>
<body>

<?php require_once ROOT . '/includes/nav.php'; ?>

<!-- 플래시 메시지 -->
<?php $flash = get_flash(); if ($flash): ?>
<div class="container mt-3">
  <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= e($flash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>
