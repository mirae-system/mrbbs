<?php
/**
 * admin/members.php — 회원 관리 (PHP 7.3)
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/config/config.php';
Auth::requireAdmin();

$db = DB::getInstance();

// 차단/해제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $act      = isset($_POST['act'])  ? $_POST['act']      : '';
    $memberId = (int)(isset($_POST['mid']) ? $_POST['mid'] : 0);

    if ($act === 'ban') {
        $db->execute("UPDATE members SET is_banned = 1 WHERE id = ?", array($memberId));
        flash('차단했습니다.', 'success');
    } elseif ($act === 'unban') {
        $db->execute("UPDATE members SET is_banned = 0 WHERE id = ?", array($memberId));
        flash('차단 해제했습니다.', 'success');
    }
    redirect(SITE_URL . '/admin/members.php');
}

$page    = max(1, (int)(isset($_GET['page']) ? $_GET['page'] : 1));
$keyword = trim(isset($_GET['q']) ? $_GET['q'] : '');

$where  = '';
$params = array();
if ($keyword !== '') {
    $where    = "WHERE (name LIKE ? OR email LIKE ?)";
    $params[] = '%' . $keyword . '%';
    $params[] = '%' . $keyword . '%';
}

$total   = $db->count("SELECT COUNT(*) FROM members $where", $params);
$pag     = paginate($total, 20, $page);

$limitVal  = 20;
$offsetVal = (int)$pag['offset'];
$members = $db->fetchAll(
    "SELECT * FROM members $where ORDER BY created_at DESC LIMIT {$limitVal} OFFSET {$offsetVal}",
    $params
);

$adminPageTitle = '회원 관리';
require __DIR__ . '/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0"> 회원 관리
    <span class="text-muted small fw-normal">(총 <?= number_format($total) ?>명)</span>
  </h5>
  <form class="d-flex gap-1" method="get">
    <input type="text" name="q" class="form-control form-control-sm"
           value="<?= e($keyword) ?>" placeholder="이름/이메일 검색">
    <button class="btn btn-sm btn-outline-secondary">검색</button>
  </form>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <table class="table table-hover mb-0 small align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:50px">ID</th>
          <th style="width:90px">제공자</th>
          <th>이름</th>
          <th>이메일</th>
          <th style="width:100px">가입일</th>
          <th style="width:100px">최근 로그인</th>
          <th style="width:70px" class="text-center">상태</th>
          <th style="width:90px">관리</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($members as $m): ?>
        <tr class="<?= $m['is_banned'] ? 'table-danger' : '' ?>">
          <td class="text-muted"><?= $m['id'] ?></td>
          <td>
            <?= $m['provider'] === 'google' ? 'Google' : 'Naver' ?>
          </td>
          <td>
            <?php if ($m['avatar']): ?>
            <img src="<?= e($m['avatar']) ?>" class="rounded-circle me-1"
                 width="22" height="22" alt="" onerror="this.style.display='none'">
            <?php endif; ?>
            <?= e($m['name'] ? $m['name'] : '-') ?>
          </td>
          <td><?= e($m['email'] ? $m['email'] : '-') ?></td>
          <td><?= format_date($m['created_at'], 'Y.m.d') ?></td>
          <td><?= $m['last_login'] ? format_date($m['last_login'], 'Y.m.d') : '-' ?></td>
          <td class="text-center">
            <span class="badge bg-<?= $m['is_banned'] ? 'danger' : 'success' ?>">
              <?= $m['is_banned'] ? '차단' : '정상' ?>
            </span>
          </td>
          <td>
            <form method="post" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="mid" value="<?= $m['id'] ?>">
              <?php if ($m['is_banned']): ?>
                <input type="hidden" name="act" value="unban">
                <button class="btn btn-sm btn-outline-success py-0">해제</button>
              <?php else: ?>
                <input type="hidden" name="act" value="ban">
                <button class="btn btn-sm btn-outline-danger py-0"
                        onclick="return confirm('차단하시겠습니까?')">차단</button>
              <?php endif; ?>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$members): ?>
        <tr>
          <td colspan="8" class="text-center text-muted py-4">
            <i class="bi bi-people d-block mb-2" style="font-size:2rem"></i>
            가입된 회원이 없습니다.
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($pag['totalPages'] > 1): ?>
<nav class="mt-3">
  <ul class="pagination justify-content-center pagination-sm">
    <?php if ($pag['startPage'] > 1): ?>
    <li class="page-item">
      <a class="page-link"
         href="?page=<?= $pag['startPage']-1 ?>&q=<?= urlencode($keyword) ?>">«</a>
    </li>
    <?php endif; ?>
    <?php for ($i = $pag['startPage']; $i <= $pag['endPage']; $i++): ?>
    <li class="page-item <?= $i === $pag['current'] ? 'active' : '' ?>">
      <a class="page-link"
         href="?page=<?= $i ?>&q=<?= urlencode($keyword) ?>"><?= $i ?></a>
    </li>
    <?php endfor; ?>
    <?php if ($pag['endPage'] < $pag['totalPages']): ?>
    <li class="page-item">
      <a class="page-link"
         href="?page=<?= $pag['endPage']+1 ?>&q=<?= urlencode($keyword) ?>">»</a>
    </li>
    <?php endif; ?>
  </ul>
</nav>
<?php endif; ?>

<?php require __DIR__ . '/layout_end.php'; ?>
