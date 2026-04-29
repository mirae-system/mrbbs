<?php
/**
 * pages/home.php — 메인 홈페이지 (PHP 7.3 호환)
 * * 이 파일을 직접 수정하여 홈 화면을 관리하세요.
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once ROOT . '/config/config.php';

$db = DB::getInstance();

$boards     = $db->fetchAll("SELECT * FROM boards WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 4");
$boardPosts = array();
foreach ($boards as $b) {
    $boardPosts[$b['id']] = array(
        'board' => $b,
        'posts' => $db->fetchAll(
            "SELECT id, title, slug, author_name, created_at FROM posts
             WHERE board_id = ? AND status = 'active' AND is_notice = 0
             ORDER BY created_at DESC LIMIT 5",
            array($b['id'])
        ),
    );
}

$siteName        = defined('SITE_NAME_DB') ? SITE_NAME_DB : SITE_NAME;
$pageTitle       = $siteName;
$pageDescription = defined('SITE_DESCRIPTION_DB') ? SITE_DESCRIPTION_DB : $siteName . '에 오신 것을 환영합니다.';
require ROOT . '/includes/header.php';
?>

<!-- * 히어로 섹션 — 직접 수정하세요 * -->
<section class="py-5 text-center bg-light border-bottom">
  <div class="container py-3">
    <h1 class="display-5 fw-bold mb-3"><?= e($siteName) ?>에 오신 것을 환영합니다</h1>
    <p class="lead text-muted mb-4">
      여기에 사이트 소개 문구를 입력하세요. pages/home.php 파일을 열어 직접 수정하면 됩니다.
    </p>
    <div class="d-flex justify-content-center gap-3 flex-wrap">
      <a href="<?= SITE_URL ?>/greeting" class="btn btn-primary">회사 소개</a>
      <a href="<?= SITE_URL ?>/board/free" class="btn btn-outline-secondary">게시판 보기</a>
    </div>
  </div>
</section>

<!-- 최근 게시글 섹션 -->
<?php if ($boardPosts): ?>
<div class="container my-5">
  <h2 class="fs-5 fw-bold mb-4 text-center">최근 게시글</h2>
  <div class="row g-4">
    <?php foreach ($boardPosts as $item): ?>
    <div class="col-md-6 col-xl-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2">
          <span class="fw-bold small">
            <?php
              $t = $item['board']['type'];
              if ($t === 'gallery')    echo '';
              elseif ($t === 'qna')    echo '';
              else                     echo '';
            ?>
            <?= e($item['board']['name']) ?>
          </span>
          <a href="<?= SITE_URL ?>/board/<?= e($item['board']['slug']) ?>"
             class="text-muted small">더보기 →</a>
        </div>
        <ul class="list-group list-group-flush">
          <?php if ($item['posts']): ?>
            <?php foreach ($item['posts'] as $p): ?>
            <li class="list-group-item px-3 py-2">
              <a href="<?= SITE_URL ?>/board/<?= e($item['board']['slug']) ?>/<?= $p['id'] ?>/<?= e($p['slug']) ?>"
                 class="text-reset text-decoration-none d-flex justify-content-between align-items-center">
                <span class="text-truncate small" style="max-width:160px"><?= e($p['title']) ?></span>
                <span class="text-muted" style="font-size:.72rem"><?= time_ago($p['created_at']) ?></span>
              </a>
            </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="list-group-item text-muted small text-center py-3">게시글이 없습니다.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- * 추가 섹션 — 직접 수정하거나 삭제하세요 * -->
<section class="bg-light border-top py-5">
  <div class="container text-center">
    <h2 class="fs-5 fw-bold mb-3">문의하기</h2>
    <p class="text-muted small mb-3">궁금한 점이 있으시면 Q&amp;A 게시판을 이용해 주세요.</p>
    <a href="<?= SITE_URL ?>/board/qna" class="btn btn-outline-primary btn-sm">Q&amp;A 바로가기</a>
  </div>
</section>

<?php require ROOT . '/includes/footer.php'; ?>
