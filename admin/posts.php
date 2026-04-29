<?php
/**
 * admin/posts.php — 게시글 관리 (PHP 7.3 호환)
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/config/config.php';
Auth::requireAdmin();

$db = DB::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $act     = isset($_POST['act'])      ? $_POST['act']      : '';
    $postIds = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();

    if ($postIds) {
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        if ($act === 'delete') {
            $db->execute("UPDATE posts SET status = 'deleted' WHERE id IN ($placeholders)", $postIds);
            flash(count($postIds) . '개 게시글을 삭제했습니다.', 'success');
        } elseif ($act === 'blind') {
            $db->execute("UPDATE posts SET status = 'blind' WHERE id IN ($placeholders)", $postIds);
            flash(count($postIds) . '개 게시글을 블라인드 처리했습니다.', 'success');
        } elseif ($act === 'restore') {
            $db->execute("UPDATE posts SET status = 'active' WHERE id IN ($placeholders)", $postIds);
            flash(count($postIds) . '개 게시글을 복원했습니다.', 'success');
        }
    }
    $boardId = isset($_POST['board_id']) ? (int)$_POST['board_id'] : 0;
    $q       = isset($_POST['q'])        ? $_POST['q']        : '';
    redirect(SITE_URL . '/admin/posts.php?board_id=' . $boardId . '&q=' . urlencode($q));
}

$boardId = (int)(isset($_GET['board_id']) ? $_GET['board_id'] : 0);
$status  = isset($_GET['status']) ? $_GET['status'] : 'active';
$keyword = trim(isset($_GET['q']) ? $_GET['q'] : '');
$page    = max(1, (int)(isset($_GET['page']) ? $_GET['page'] : 1));

$where  = "p.status = ?";
$params = array($status);
if ($boardId) { $where .= " AND p.board_id = ?"; $params[] = $boardId; }
if ($keyword) {
    $where .= " AND (p.title LIKE ? OR p.author_name LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

$total = $db->count("SELECT COUNT(*) FROM posts p WHERE $where", $params);
$pag   = paginate($total, 20, $page);

$limitVal  = 20;
$offsetVal = (int)$pag['offset'];
$posts = $db->fetchAll(
    "SELECT p.*, b.name as board_name, b.slug as board_slug
     FROM posts p JOIN boards b ON p.board_id = b.id
     WHERE $where ORDER BY p.id DESC LIMIT {$limitVal} OFFSET {$offsetVal}",
    $params
);
$allBoards = $db->fetchAll("SELECT id, name FROM boards ORDER BY sort_order");

$adminPageTitle = '게시글 관리';
require __DIR__ . '/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0"> 게시글 관리</h5>
</div>

<form class="d-flex flex-wrap gap-2 mb-3 align-items-center" method="get">
  <select name="board_id" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
    <option value="">전체 게시판</option>
    <?php foreach ($allBoards as $b): ?>
    <option value="<?= $b['id'] ?>" <?= $boardId == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
    <option value="active"  <?= $status === 'active'  ? 'selected' : '' ?>>활성</option>
    <option value="deleted" <?= $status === 'deleted' ? 'selected' : '' ?>>삭제됨</option>
    <option value="blind"   <?= $status === 'blind'   ? 'selected' : '' ?>>블라인드</option>
  </select>
  <input type="text" name="q" class="form-control form-control-sm" style="width:180px"
         value="<?= e($keyword) ?>" placeholder="제목/작성자 검색">
  <button class="btn btn-sm btn-outline-secondary">검색</button>
</form>

<form method="post" id="postForm">
  <?= csrf_field() ?>
  <input type="hidden" name="board_id" value="<?= $boardId ?>">
  <input type="hidden" name="q"        value="<?= e($keyword) ?>">

  <div class="mb-2 d-flex gap-2 align-items-center flex-wrap">
    <button type="submit" name="act" value="blind"   class="btn btn-sm btn-outline-warning"
            onclick="return confirmBulk('블라인드')">선택 블라인드</button>
    <button type="submit" name="act" value="restore" class="btn btn-sm btn-outline-success"
            onclick="return confirmBulk('복원')">선택 복원</button>
    <button type="submit" name="act" value="delete"  class="btn btn-sm btn-outline-danger"
            onclick="return confirmBulk('삭제')">선택 삭제</button>
    <span class="text-muted small">총 <?= number_format($total) ?>개</span>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <table class="table table-hover mb-0 small align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:36px"><input type="checkbox" id="chkAll"></th>
            <th>게시판</th><th>제목</th><th>작성자</th>
            <th>조회</th><th>날짜</th><th>상태</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($posts as $p): ?>
          <tr>
            <td><input type="checkbox" name="post_ids[]" value="<?= $p['id'] ?>"></td>
            <td class="text-muted"><?= e($p['board_name']) ?></td>
            <td style="max-width:260px" class="text-truncate">
              <?= $p['is_notice'] ? '<span class="badge-notice">공지</span>' : '' ?>
              <?= e(mb_substr($p['title'], 0, 35, 'UTF-8')) ?>
            </td>
            <td><?= e($p['author_name']) ?></td>
            <td><?= number_format($p['views']) ?></td>
            <td><?= format_date($p['created_at'], 'Y.m.d') ?></td>
            <td>
              <span class="badge bg-<?= status_color($p['status']) ?>">
                <?= status_label($p['status']) ?>
              </span>
            </td>
            <td>
              <a href="<?= SITE_URL ?>/board/<?= e($p['board_slug']) ?>/<?= $p['id'] ?>"
                 class="btn btn-sm btn-outline-secondary py-0" target="_blank">보기</a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$posts): ?>
          <tr><td colspan="8" class="text-center text-muted py-3">게시글이 없습니다.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>

<?php if ($pag['totalPages'] > 1): ?>
<nav class="mt-3">
  <ul class="pagination pagination-sm justify-content-center">
    <?php for ($i = $pag['startPage']; $i <= $pag['endPage']; $i++): ?>
    <li class="page-item <?= $i === $pag['current'] ? 'active' : '' ?>">
      <a class="page-link"
         href="?page=<?= $i ?>&board_id=<?= $boardId ?>&status=<?= e($status) ?>&q=<?= urlencode($keyword) ?>">
        <?= $i ?>
      </a>
    </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<script>
document.getElementById('chkAll').addEventListener('change', function() {
  document.querySelectorAll('input[name="post_ids[]"]').forEach(function(c){ c.checked = this.checked; }, this);
});
function confirmBulk(action) {
  var checked = document.querySelectorAll('input[name="post_ids[]"]:checked').length;
  if (!checked) { alert('게시글을 선택하세요.'); return false; }
  return confirm(checked + '개 게시글을 ' + action + '하시겠습니까?');
}
</script>

<?php require __DIR__ . '/layout_end.php'; ?>
