<?php
/**
 * includes/nav.php
 * * 이 파일을 직접 수정하여 네비게이션 메뉴를 관리하세요.
 * 게시판 메뉴는 DB에서 자동 로드됩니다.
 */
$db = DB::getInstance();
$activeBoards = $db->fetchAll(
    "SELECT slug, name, type FROM boards WHERE is_active = 1 ORDER BY sort_order ASC"
);

// 현재 경로 확인 (active 클래스용)
$currentRoute = isset($_GET['route']) ? trim($_GET['route'], '/') : '';
$routeParts   = explode('/', $currentRoute);
$currentSeg0  = isset($routeParts[0]) ? $routeParts[0] : '';
$currentSeg1  = isset($routeParts[1]) ? $routeParts[1] : '';
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
  <div class="container">

    <!-- 브랜드 로고 -->
    <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>">
      <?= e(defined('SITE_NAME_DB') ? SITE_NAME_DB : SITE_NAME) ?>
    </a>

    <!-- 모바일 토글 -->
    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-controls="mainNav" aria-expanded="false" aria-label="메뉴 열기">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">

        <!-- * 직접 수정: 회사소개 드롭다운 -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= in_array($currentSeg0, array('greeting','history','business')) ? 'active' : '' ?>"
             href="#" data-bs-toggle="dropdown">회사소개</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item <?= $currentSeg0 === 'greeting' ? 'active' : '' ?>"
                   href="<?= SITE_URL ?>/greeting">인사말</a></li>
            <li><a class="dropdown-item <?= $currentSeg0 === 'history' ? 'active' : '' ?>"
                   href="<?= SITE_URL ?>/history">연혁</a></li>
            <li><a class="dropdown-item <?= $currentSeg0 === 'business' ? 'active' : '' ?>"
                   href="<?= SITE_URL ?>/business">사업소개</a></li>
          </ul>
        </li>

        <!-- * 직접 수정: 메뉴 추가 시 여기에 li 태그를 추가하세요 -->
        <!--
        <li class="nav-item">
          <a class="nav-link" href="<?= SITE_URL ?>/contact">오시는길</a>
        </li>
        -->

        <!-- DB에서 자동 로드: 게시판 메뉴 -->
        <?php if ($activeBoards): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= $currentSeg0 === 'board' ? 'active' : '' ?>"
             href="#" data-bs-toggle="dropdown">게시판</a>
          <ul class="dropdown-menu">
            <?php foreach ($activeBoards as $b): ?>
            <?php
              // 게시판 종류별 아이콘
              if ($b['type'] === 'gallery')    $icon = '[갤러리]';
              elseif ($b['type'] === 'qna')    $icon = '[Q&A]';
              else                             $icon = '';
              $isActive = ($currentSeg0 === 'board' && $currentSeg1 === $b['slug']);
            ?>
            <li>
              <a class="dropdown-item <?= $isActive ? 'active' : '' ?>"
                 href="<?= SITE_URL ?>/board/<?= e($b['slug']) ?>">
                <?= $icon ?> <?= e($b['name']) ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </li>
        <?php endif; ?>

      </ul>

      <!-- 로그인/로그아웃 영역 -->
      <div class="d-flex align-items-center gap-2">
        <?php if (Auth::isAdmin()): ?>
          <a href="<?= SITE_URL ?>/admin/" class="btn btn-sm btn-dark">관리자</a>
          <a href="<?= SITE_URL ?>/auth/logout.php" class="btn btn-sm btn-outline-secondary">로그아웃</a>
        <?php elseif (Auth::isMember()): ?>
          <?php $m = Auth::getMember(); ?>
          <span class="text-muted small"><?= e($m['name']) ?>님</span>
          <a href="<?= SITE_URL ?>/auth/logout.php" class="btn btn-sm btn-outline-secondary">로그아웃</a>
        <?php else: ?>
          <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-sm btn-outline-primary">로그인</a>
        <?php endif; ?>
      </div>

    </div>
  </div>
</nav>
