<?php
/**
 * admin/index.php — 관리자 대시보드 (PHP 7.3 호환)
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/config/config.php';
Auth::requireAdmin();

$db = DB::getInstance();

$stats = array(
    'boards'  => $db->count("SELECT COUNT(*) FROM boards WHERE is_active = 1"),
    'posts'   => $db->count("SELECT COUNT(*) FROM posts WHERE status = 'active'"),
    'members' => $db->count("SELECT COUNT(*) FROM members WHERE is_banned = 0"),
    'today'   => $db->count("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = CURDATE() AND status='active'"),
);

$recentPosts = $db->fetchAll(
    "SELECT p.id, p.title, p.author_name, p.created_at, b.name as board_name, b.slug as board_slug
     FROM posts p JOIN boards b ON p.board_id = b.id
     WHERE p.status = 'active'
     ORDER BY p.created_at DESC LIMIT 5"
);

$adminPageTitle = '대시보드';
require __DIR__ . '/layout.php';
?>

<h5 class="fw-bold mb-4"> 대시보드</h5>

<div class="row g-3 mb-4">
  <?php
  $cards = array(
      array('label'=>'활성 게시판', 'value'=>$stats['boards'],  'icon'=>'bi-layout-text-sidebar', 'color'=>'primary'),
      array('label'=>'전체 게시글', 'value'=>$stats['posts'],   'icon'=>'bi-file-text',           'color'=>'success'),
      array('label'=>'가입 회원',   'value'=>$stats['members'], 'icon'=>'bi-people',              'color'=>'info'),
      array('label'=>'오늘 게시글', 'value'=>$stats['today'],   'icon'=>'bi-calendar-check',      'color'=>'warning'),
  );
  foreach ($cards as $c):
  ?>
  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white bg-<?= $c['color'] ?>"
             style="width:48px;height:48px;font-size:1.3rem">
          <i class="bi <?= $c['icon'] ?>"></i>
        </div>
        <div>
          <div class="fw-bold fs-4"><?= number_format($c['value']) ?></div>
          <div class="text-muted small"><?= $c['label'] ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-bold border-bottom py-3">최근 게시글</div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0 small">
      <thead class="table-light">
        <tr><th>게시판</th><th>제목</th><th>작성자</th><th>작성일</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($recentPosts as $p): ?>
        <tr>
          <td class="text-muted"><?= e($p['board_name']) ?></td>
          <td><?= e(mb_substr($p['title'], 0, 30, 'UTF-8')) ?></td>
          <td><?= e($p['author_name']) ?></td>
          <td><?= format_date($p['created_at'], 'Y.m.d H:i') ?></td>
          <td>
            <a href="<?= SITE_URL ?>/board/<?= e($p['board_slug']) ?>/<?= $p['id'] ?>"
               class="btn btn-sm btn-outline-secondary py-0" target="_blank">보기</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$recentPosts): ?>
        <tr><td colspan="5" class="text-center text-muted py-3">게시글이 없습니다.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="alert alert-warning small py-2">
  <strong>보안 필수사항:</strong>
  초기 비밀번호(admin1234)를 반드시 변경하세요!
  <a href="<?= SITE_URL ?>/admin/change_password.php" class="btn btn-sm btn-warning ms-2 py-0">비밀번호 변경</a>
</div>

<div class="d-flex gap-2 mt-3 flex-wrap">
  <a href="<?= SITE_URL ?>/admin/board_manage.php" class="btn btn-outline-primary btn-sm">
    <i class="bi bi-plus-circle me-1"></i>게시판 추가
  </a>
  <a href="<?= SITE_URL ?>/admin/settings.php" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-gear me-1"></i>사이트 설정
  </a>
  <a href="<?= SITE_URL ?>/admin/members.php" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-people me-1"></i>회원 관리
  </a>
</div>

<?php require __DIR__ . '/layout_end.php'; ?>
