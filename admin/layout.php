<?php
/**
 * admin/layout.php — 관리자 공통 레이아웃
 * 각 관리자 페이지에서 include하여 사용
 */
$adminMenu = array(
    array('icon'=>'bi-speedometer2',         'label'=>'대시보드',     'url'=>'/admin/'),
    array('icon'=>'bi-layout-text-sidebar',  'label'=>'게시판 관리', 'url'=>'/admin/board_manage.php'),
    array('icon'=>'bi-people',               'label'=>'회원 관리',     'url'=>'/admin/members.php'),
    array('icon'=>'bi-file-text',            'label'=>'게시글 관리',   'url'=>'/admin/posts.php'),
    array('icon'=>'bi-gear',                 'label'=>'사이트 설정',   'url'=>'/admin/settings.php'),
    array('icon'=>'bi-key',                  'label'=>'비밀번호 변경', 'url'=>'/admin/change_password.php'),
);
$currentAdminPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($adminPageTitle ?? '관리자') ?> | <?= e(SITE_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
  <meta name="robots" content="noindex,nofollow">
</head>
<body>

<!-- 상단 바 -->
<nav class="navbar navbar-dark bg-dark px-3 py-2" style="height:56px">
  <a class="navbar-brand fw-bold small" href="<?= SITE_URL ?>/admin/">
     <?= e(defined('SITE_NAME_DB') ? SITE_NAME_DB : SITE_NAME) ?> 관리자
  </a>
  <div class="d-flex align-items-center gap-3">
    <span class="text-white-50 small"><?= e(Auth::getAdmin()['name']) ?>님</span>
    <a href="<?= SITE_URL ?>/auth/logout.php" class="btn btn-sm btn-outline-light">로그아웃</a>
    <a href="<?= SITE_URL ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-box-arrow-up-right me-1"></i>사이트 보기
    </a>
  </div>
</nav>

<div class="d-flex" style="min-height:calc(100vh - 56px)">
  <!-- 사이드바 -->
  <div class="admin-sidebar" style="width:220px;min-width:220px">
    <nav class="nav flex-column p-2 pt-3">
      <?php foreach ($adminMenu as $m): ?>
      <a class="nav-link d-flex align-items-center gap-2 mb-1
                 <?= (strpos($currentAdminPath, $m['url']) === 0) || ($m['url']==='/admin/' && $currentAdminPath==='/admin/') ? 'active' : '' ?>"
         href="<?= SITE_URL . $m['url'] ?>">
        <i class="bi <?= $m['icon'] ?>"></i> <?= $m['label'] ?>
      </a>
      <?php endforeach; ?>
    </nav>
  </div>

  <!-- 본문 -->
  <div class="flex-grow-1 p-4" style="background:#f8f9fa">
    <?php $flash = get_flash(); if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show py-2 small">
      <?= e($flash['msg']) ?>
      <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
