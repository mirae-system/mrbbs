<?php
/**
 * admin/board_manage.php — 게시판 추가/수정/삭제/순서 관리 (PHP 7.3)
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/config/config.php';
Auth::requireAdmin();

$db     = DB::getInstance();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id     = (int)(isset($_GET['id']) ? $_GET['id'] : 0);

// ── POST 처리 ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('보안 오류.', 'danger');
        redirect(SITE_URL . '/admin/board_manage.php');
    }

    $act = isset($_POST['act']) ? $_POST['act'] : '';

    // 게시판 추가/수정
    if (in_array($act, array('create', 'update'))) {
        $slug      = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim(isset($_POST['slug']) ? $_POST['slug'] : '')));
        $name      = trim(isset($_POST['name']) ? $_POST['name'] : '');
        $type_raw  = isset($_POST['type']) ? $_POST['type'] : 'general';
        $type      = in_array($type_raw, array('general','gallery','qna')) ? $type_raw : 'general';
        $wa_raw    = isset($_POST['write_auth']) ? $_POST['write_auth'] : 'all';
        $ra_raw    = isset($_POST['read_auth'])  ? $_POST['read_auth']  : 'all';
        $write_auth= in_array($wa_raw, array('all','member','admin')) ? $wa_raw : 'all';
        $read_auth = in_array($ra_raw, array('all','member','admin'))  ? $ra_raw  : 'all';

        $data = array(
            'slug'           => $slug,
            'name'           => $name,
            'type'           => $type,
            'description'    => trim(isset($_POST['description'])    ? $_POST['description']    : ''),
            'sort_order'     => (int)(isset($_POST['sort_order'])    ? $_POST['sort_order']     : 0),
            'use_comment'    => isset($_POST['use_comment'])         ? 1 : 0,
            'use_file'       => isset($_POST['use_file'])            ? 1 : 0,
            'file_max_size'  => max(1, (int)(isset($_POST['file_max_size'])  ? $_POST['file_max_size']  : 10)),
            'file_max_count' => max(1, (int)(isset($_POST['file_max_count']) ? $_POST['file_max_count'] : 5)),
            'use_turnstile'  => isset($_POST['use_turnstile'])       ? 1 : 0,
            'use_secret'     => isset($_POST['use_secret'])          ? 1 : 0,
            'write_auth'     => $write_auth,
            'read_auth'      => $read_auth,
            'posts_per_page' => max(5, (int)(isset($_POST['posts_per_page']) ? $_POST['posts_per_page'] : 15)),
            'is_active'      => isset($_POST['is_active'])           ? 1 : 0,
        );

        if (!$data['slug'] || !$data['name']) {
            flash('슬러그와 이름은 필수입니다.', 'warning');
            redirect(SITE_URL . '/admin/board_manage.php');
        }

        if ($act === 'create') {
            $db->insert(
                "INSERT INTO boards (slug,name,type,description,sort_order,use_comment,use_file,
                 file_max_size,file_max_count,use_turnstile,use_secret,write_auth,read_auth,posts_per_page,is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                array_values($data)
            );
            flash('게시판이 추가되었습니다.', 'success');
        } else {
            $boardId = (int)(isset($_POST['board_id']) ? $_POST['board_id'] : 0);
            $vals    = array_values($data);
            $vals[]  = $boardId;
            $db->execute(
                "UPDATE boards SET slug=?,name=?,type=?,description=?,sort_order=?,use_comment=?,
                 use_file=?,file_max_size=?,file_max_count=?,use_turnstile=?,use_secret=?,
                 write_auth=?,read_auth=?,posts_per_page=?,is_active=? WHERE id=?",
                $vals
            );
            flash('게시판이 수정되었습니다.', 'success');
        }
        redirect(SITE_URL . '/admin/board_manage.php');
    }

    // 삭제
    if ($act === 'delete') {
        $boardId = (int)(isset($_POST['board_id']) ? $_POST['board_id'] : 0);
        $postCnt = $db->count(
            "SELECT COUNT(*) FROM posts WHERE board_id = ? AND status='active'",
            array($boardId)
        );
        if ($postCnt > 0) {
            flash('게시글이 있는 게시판은 삭제할 수 없습니다. 먼저 게시글을 삭제하거나 비활성화하세요.', 'warning');
        } else {
            $db->execute("DELETE FROM boards WHERE id = ?", array($boardId));
            flash('게시판이 삭제되었습니다.', 'success');
        }
        redirect(SITE_URL . '/admin/board_manage.php');
    }

    // 순서 저장 (AJAX)
    if ($act === 'reorder') {
        $orders = isset($_POST['orders']) ? $_POST['orders'] : array();
        foreach ($orders as $bid => $ord) {
            $db->execute(
                "UPDATE boards SET sort_order = ? WHERE id = ?",
                array((int)$ord, (int)$bid)
            );
        }
        json_response(array('ok' => true));
    }
}

// ── GET 처리 ─────────────────────────────────────────────────
$boards    = $db->fetchAll("SELECT * FROM boards ORDER BY sort_order ASC, id ASC");
$editBoard = ($action === 'edit' && $id)
    ? $db->fetch("SELECT * FROM boards WHERE id = ?", array($id))
    : null;

$adminPageTitle = '게시판 관리';
require __DIR__ . '/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0">게시판 관리</h5>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#boardModal"
          onclick="openModal(null)">
    <i class="bi bi-plus-circle me-1"></i>게시판 추가
  </button>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <table class="table table-hover mb-0 small align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:40px" class="text-center">순서</th>
          <th>슬러그</th><th>이름</th><th>종류</th>
          <th class="text-center">댓글</th>
          <th class="text-center">파일</th>
          <th class="text-center">Turnstile</th>
          <th>쓰기권한</th><th>상태</th>
          <th style="width:130px">관리</th>
        </tr>
      </thead>
      <tbody id="boardSortable">
        <?php foreach ($boards as $b): ?>
        <tr data-id="<?= $b['id'] ?>">
          <td class="text-muted text-center" style="cursor:grab">⠿</td>
          <td><code><?= e($b['slug']) ?></code></td>
          <td><?= e($b['name']) ?></td>
          <td>
            <?php
              if ($b['type'] === 'gallery')  echo '[갤러리]';
              elseif ($b['type'] === 'qna')  echo '[Q&A]';
              else                           echo '[일반]';
            ?>
          </td>
          <td class="text-center"><?= $b['use_comment']   ? '사용' : '-' ?></td>
          <td class="text-center"><?= $b['use_file']      ? '사용' : '-' ?></td>
          <td class="text-center"><?= $b['use_turnstile'] ? '사용' : '-' ?></td>
          <td><?= e($b['write_auth']) ?></td>
          <td>
            <span class="badge bg-<?= $b['is_active'] ? 'success' : 'secondary' ?>">
              <?= $b['is_active'] ? '활성' : '비활성' ?>
            </span>
          </td>
          <td>
            <button class="btn btn-sm btn-outline-primary py-0 me-1"
                    onclick="openModal(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)">수정</button>
            <form method="post" class="d-inline"
                  onsubmit="return confirm('삭제하시겠습니까?')">
              <?= csrf_field() ?>
              <input type="hidden" name="act"      value="delete">
              <input type="hidden" name="board_id" value="<?= $b['id'] ?>">
              <button class="btn btn-sm btn-outline-danger py-0">삭제</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$boards): ?>
        <tr><td colspan="10" class="text-center text-muted py-3">게시판이 없습니다.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<p class="text-muted small mt-2">⠿ 아이콘을 드래그하여 순서를 변경할 수 있습니다.</p>

<!-- 게시판 추가/수정 Modal -->
<div class="modal fade" id="boardModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" id="boardForm">
        <?= csrf_field() ?>
        <input type="hidden" name="act"      id="formAct"     value="create">
        <input type="hidden" name="board_id" id="formBoardId" value="">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="modalTitle">게시판 추가</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-sm-4">
              <label class="form-label small fw-bold">슬러그 (영문) <span class="text-danger">*</span></label>
              <input type="text" name="slug" id="fSlug" class="form-control form-control-sm"
                     placeholder="free" pattern="[a-z0-9_-]+" required>
              <div class="form-text">URL에 사용 (영소문자·숫자·-_만)</div>
            </div>
            <div class="col-sm-4">
              <label class="form-label small fw-bold">게시판 이름 <span class="text-danger">*</span></label>
              <input type="text" name="name" id="fName" class="form-control form-control-sm" required>
            </div>
            <div class="col-sm-4">
              <label class="form-label small fw-bold">종류</label>
              <select name="type" id="fType" class="form-select form-select-sm">
                <option value="general">[일반] 게시판</option>
                <option value="gallery">[갤러리] 게시판</option>
                <option value="qna">[Q&A] 게시판</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label small fw-bold">설명</label>
              <input type="text" name="description" id="fDesc" class="form-control form-control-sm">
            </div>
            <div class="col-sm-3">
              <label class="form-label small fw-bold">페이지당 게시글</label>
              <input type="number" name="posts_per_page" id="fPPP"
                     class="form-control form-control-sm" value="15" min="5" max="100">
            </div>
            <div class="col-sm-3">
              <label class="form-label small fw-bold">정렬 순서</label>
              <input type="number" name="sort_order" id="fSort"
                     class="form-control form-control-sm" value="0">
            </div>
            <div class="col-sm-3">
              <label class="form-label small fw-bold">쓰기 권한</label>
              <select name="write_auth" id="fWriteAuth" class="form-select form-select-sm">
                <option value="all">모든 사람</option>
                <option value="member">회원만</option>
                <option value="admin">관리자만</option>
              </select>
            </div>
            <div class="col-sm-3">
              <label class="form-label small fw-bold">읽기 권한</label>
              <select name="read_auth" id="fReadAuth" class="form-select form-select-sm">
                <option value="all">모든 사람</option>
                <option value="member">회원만</option>
                <option value="admin">관리자만</option>
              </select>
            </div>
            <div class="col-12">
              <div class="d-flex flex-wrap gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="use_comment" id="fComment" checked>
                  <label class="form-check-label small" for="fComment">댓글 허용</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="use_file" id="fFile" checked>
                  <label class="form-check-label small" for="fFile">파일 업로드 허용</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="use_turnstile" id="fTurnstile" checked>
                  <label class="form-check-label small" for="fTurnstile">Turnstile 스팸차단</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="use_secret" id="fSecret">
                  <label class="form-check-label small" for="fSecret">비밀글 허용</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="is_active" id="fActive" checked>
                  <label class="form-check-label small" for="fActive">활성화</label>
                </div>
              </div>
            </div>
            <div class="col-sm-4">
              <label class="form-label small">최대 파일 크기 (MB)</label>
              <input type="number" name="file_max_size" id="fFSize"
                     class="form-control form-control-sm" value="10" min="1" max="100">
            </div>
            <div class="col-sm-4">
              <label class="form-label small">최대 파일 개수</label>
              <input type="number" name="file_max_count" id="fFCount"
                     class="form-control form-control-sm" value="5" min="1" max="20">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">취소</button>
          <button type="submit" class="btn btn-primary btn-sm" id="modalSubmitBtn">추가</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openModal(board) {
  var isEdit = board !== null;
  document.getElementById('modalTitle').textContent     = isEdit ? '게시판 수정' : '게시판 추가';
  document.getElementById('formAct').value              = isEdit ? 'update' : 'create';
  document.getElementById('formBoardId').value          = isEdit ? board.id : '';
  document.getElementById('modalSubmitBtn').textContent = isEdit ? '수정' : '추가';

  document.getElementById('fSlug').value  = isEdit ? (board.slug        || '') : '';
  document.getElementById('fName').value  = isEdit ? (board.name        || '') : '';
  document.getElementById('fDesc').value  = isEdit ? (board.description || '') : '';
  document.getElementById('fPPP').value   = isEdit ? (board.posts_per_page || 15) : 15;
  document.getElementById('fSort').value  = isEdit ? (board.sort_order  || 0)  : 0;
  document.getElementById('fType').value  = isEdit ? (board.type        || 'general') : 'general';
  document.getElementById('fWriteAuth').value = isEdit ? (board.write_auth || 'all') : 'all';
  document.getElementById('fReadAuth').value  = isEdit ? (board.read_auth  || 'all') : 'all';
  document.getElementById('fFSize').value  = isEdit ? (board.file_max_size  || 10) : 10;
  document.getElementById('fFCount').value = isEdit ? (board.file_max_count || 5)  : 5;

  document.getElementById('fComment').checked   = isEdit ? (board.use_comment   == 1) : true;
  document.getElementById('fFile').checked      = isEdit ? (board.use_file      == 1) : true;
  document.getElementById('fTurnstile').checked = isEdit ? (board.use_turnstile == 1) : true;
  document.getElementById('fSecret').checked    = isEdit ? (board.use_secret    == 1) : false;
  document.getElementById('fActive').checked    = isEdit ? (board.is_active     == 1) : true;

  document.getElementById('fSlug').readOnly = isEdit;

  var modal = new bootstrap.Modal(document.getElementById('boardModal'));
  modal.show();
}

// 드래그 정렬
(function() {
  var tbody = document.getElementById('boardSortable');
  var dragging = null;
  var rows = tbody.querySelectorAll('tr');
  rows.forEach(function(tr) {
    tr.draggable = true;
    tr.addEventListener('dragstart', function() { dragging = tr; tr.style.opacity = '0.5'; });
    tr.addEventListener('dragend',   function() {
      dragging = null; tr.style.opacity = '';
      saveOrder();
    });
    tr.addEventListener('dragover', function(e) {
      e.preventDefault();
      var allRows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
      var after = null;
      allRows.forEach(function(child) {
        if (child === dragging) return;
        var box    = child.getBoundingClientRect();
        var offset = e.clientY - box.top - box.height / 2;
        if (offset < 0) { after = child; }
      });
      tbody.insertBefore(dragging, after);
    });
  });

  function saveOrder() {
    var orders = {};
    tbody.querySelectorAll('tr[data-id]').forEach(function(tr, i) {
      orders[tr.dataset.id] = i;
    });
    var fd = new FormData();
    fd.append('act', 'reorder');
    fd.append('_csrf', '<?= csrf_token() ?>');
    Object.keys(orders).forEach(function(k) { fd.append('orders['+k+']', orders[k]); });
    fetch('', { method: 'POST', body: fd });
  }
})();
</script>

<?php require __DIR__ . '/layout_end.php'; ?>
