<?php
/**
 * board/edit.php — 게시글 수정 (TOAST UI Editor 포함)
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once ROOT . '/config/config.php';

$db        = DB::getInstance();
$boardSlug = isset($_GET['board_slug']) ? $_GET['board_slug'] : '';
$postId    = (int)(isset($_GET['post_id']) ? $_GET['post_id'] : 0);
$board     = $db->fetch("SELECT * FROM boards WHERE slug = ? AND is_active = 1", array($boardSlug));
$post      = $db->fetch("SELECT * FROM posts WHERE id = ? AND status = 'active'", array($postId));

if (!$board || !$post) {
    http_response_code(404);
    require ROOT . '/pages/404.php';
    exit;
}

$isGuest = !$post['member_id'] && !$post['admin_id'];

// 권한 확인
if (Auth::isAdmin()) {
    // OK
} elseif (Auth::isMember() && Auth::getMember()['id'] == $post['member_id']) {
    // OK
} elseif ($isGuest) {
    $verified   = isset($_SESSION['guest_verified_' . $postId]) && $_SESSION['guest_verified_' . $postId];
    $verifyTime = isset($_SESSION['guest_verified_time_' . $postId]) ? $_SESSION['guest_verified_time_' . $postId] : 0;
    if (!$verified || (time() - $verifyTime) > 1800) {
        flash('비밀번호 확인이 필요합니다.', 'warning');
        redirect(SITE_URL . '/board/' . $boardSlug . '/' . $postId . '/' . $post['slug']);
    }
} else {
    flash('수정 권한이 없습니다.', 'danger');
    redirect(SITE_URL . '/board/' . $boardSlug);
}

// ── POST 저장 ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('보안 오류.', 'danger');
        redirect(SITE_URL . '/edit/' . $boardSlug . '/' . $postId);
    }

    $title    = trim(isset($_POST['title'])   ? $_POST['title']   : '');
    $content  = isset($_POST['content'])      ? $_POST['content'] : '';
    $isSecret = (isset($_POST['is_secret']) && $board['use_secret']) ? 1 : 0;
    $isNotice = (Auth::isAdmin() && isset($_POST['is_notice']))      ? 1 : 0;

    if (!$title) {
        flash('제목을 입력해주세요.', 'warning');
        redirect(SITE_URL . '/edit/' . $boardSlug . '/' . $postId);
    }

    $content = strip_tags($content,
        '<p><br><strong><em><u><s><ul><ol><li><h1><h2><h3><h4><blockquote><a><img>' .
        '<table><thead><tbody><tr><th><td><code><pre><hr><del><ins><span><div>'
    );
    $slug = make_slug($title);

    // 에디터에서 제거된 서버 이미지 파일 삭제
    $oldContent = $post['content'];
    delete_removed_images($oldContent, $content);

    // 관리자: 날짜 및 조회수 직접 수정
    if (Auth::isAdmin()) {
        $manualDate  = isset($_POST['manual_date'])  ? trim($_POST['manual_date'])  : '';
        $manualViews = isset($_POST['manual_views']) ? (int)$_POST['manual_views'] : -1;

        // 날짜 입력값 검증 (YYYY-MM-DD HH:MM:SS 또는 YYYY-MM-DD)
        $newDate = null;
        if ($manualDate !== '') {
            // 날짜 형식 맞추기
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $manualDate)) {
                $manualDate .= ' 00:00:00';
            }
            if (strtotime($manualDate) !== false) {
                $newDate = date('Y-m-d H:i:s', strtotime($manualDate));
            }
        }

        $setDate  = $newDate ? $newDate : null;
        $setViews = $manualViews >= 0 ? $manualViews : null;

        $sql = "UPDATE posts SET title=?, slug=?, content=?, is_secret=?, is_notice=?";
        $params = array($title, $slug, $content, $isSecret, $isNotice);

        if ($setDate !== null) {
            $sql .= ", created_at=?";
            $params[] = $setDate;
        }
        if ($setViews !== null) {
            $sql .= ", views=?";
            $params[] = $setViews;
        }

        $sql .= " WHERE id=?";
        $params[] = $postId;
        $db->execute($sql, $params);

    } else {
        $db->execute(
            "UPDATE posts SET title=?, slug=?, content=?, is_secret=?, is_notice=?, updated_at=NOW() WHERE id=?",
            array($title, $slug, $content, $isSecret, $isNotice, $postId)
        );
    }

    if ($board['use_file'] && !empty($_FILES['files']['name'][0])) {
        handleFileUpload($postId, $board, $_FILES['files'], $db);
    }

    flash('수정되었습니다.', 'success');
    redirect(SITE_URL . '/board/' . $boardSlug . '/' . $postId . '/' . $slug);
}

// ── GET: 폼 표시 ─────────────────────────────────────────────
$files        = $db->fetchAll("SELECT * FROM files WHERE post_id = ?", array($postId));
$pageTitle    = '게시글 수정';
$useTurnstile = false;
require ROOT . '/includes/header.php';
?>

<!-- TOAST UI Editor CSS -->
<link rel="stylesheet"
      href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css">

<div class="container my-4" style="max-width:900px">
  <div class="page-header"><h1>게시글 수정</h1></div>

  <form method="post" enctype="multipart/form-data" id="editForm">
    <?= csrf_field() ?>

    <div class="mb-3">
      <label class="form-label small fw-bold">제목</label>
      <input type="text" name="title" class="form-control"
             value="<?= e($post['title']) ?>" required>
    </div>

    <!-- TOAST UI Editor -->
    <div class="mb-3">
      <label class="form-label small fw-bold">내용</label>
      <div id="toastEditor"></div>
      <input type="hidden" name="content" id="editorContent">
    </div>

    <!-- 옵션 -->
    <div class="d-flex gap-3 mb-3">
      <?php if (Auth::isAdmin()): ?>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_notice" id="chkNotice"
               <?= $post['is_notice'] ? 'checked' : '' ?>>
        <label class="form-check-label small" for="chkNotice">공지글</label>
      </div>
      <?php endif; ?>
      <?php if ($board['use_secret']): ?>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_secret" id="chkSecret"
               <?= $post['is_secret'] ? 'checked' : '' ?>>
        <label class="form-check-label small" for="chkSecret">비밀글</label>
      </div>
      <?php endif; ?>
    </div>

    <!-- 관리자 전용: 날짜 및 조회수 수정 -->
    <?php if (Auth::isAdmin()): ?>
    <div class="card border-warning mb-3">
      <div class="card-header bg-warning bg-opacity-10 py-2 small fw-bold">
        관리자 전용 — 날짜 및 조회수 수정
      </div>
      <div class="card-body py-3">
        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label small fw-bold">작성일시</label>
            <input type="datetime-local" name="manual_date"
                   class="form-control form-control-sm"
                   value="<?= date('Y-m-d\TH:i', strtotime($post['created_at'])) ?>">
            <div class="form-text">수정 시 목록 정렬 순서도 변경됩니다.</div>
          </div>
          <div class="col-sm-3">
            <label class="form-label small fw-bold">조회수</label>
            <input type="number" name="manual_views"
                   class="form-control form-control-sm"
                   value="<?= $post['views'] ?>" min="0">
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- 기존 첨부파일 -->
    <?php if ($files): ?>
    <div class="mb-3 p-3 bg-light rounded">
      <p class="small fw-bold mb-2">기존 첨부파일</p>
      <?php foreach ($files as $f): ?>
      <div class="d-flex align-items-center gap-2 mb-1 small">
        <span><?= e($f['original_name']) ?> (<?= format_bytes($f['file_size']) ?>)</span>
        <a href="<?= SITE_URL ?>/board/file_delete.php?id=<?= $f['id'] ?>&post_id=<?= $postId ?>&board_slug=<?= e($boardSlug) ?>"
           class="text-danger"
           onclick="return confirm('삭제하시겠습니까?')">삭제</a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($board['use_file']): ?>
    <div class="mb-3">
      <label class="form-label small fw-bold">
        파일 추가 (최대 <?= $board['file_max_count'] ?>개 / 각 <?= $board['file_max_size'] ?>MB)
      </label>
      <div id="fileInputList">
        <div class="d-flex align-items-center gap-2 mb-1 file-input-row">
          <input type="file" name="files[]"
                 class="form-control form-control-sm file-input-item"
                 accept="<?= implode(',', array_map(function($e){ return '.'.$e; }, ALLOWED_EXTENSIONS)) ?>">
        </div>
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary mt-1"
              id="addFileBtn" onclick="addFileInput()">
        <i class="bi bi-plus-circle me-1"></i>파일 추가
      </button>
      <div class="form-text mt-1">
        허용 확장자: <?= implode(', ', ALLOWED_EXTENSIONS) ?>
        &nbsp;|&nbsp; 최대 <?= $board['file_max_count'] ?>개 / 각 <?= $board['file_max_size'] ?>MB
      </div>
      <div id="fileError" class="text-danger small mt-1" style="display:none"></div>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between">
      <a href="<?= SITE_URL ?>/board/<?= e($boardSlug) ?>/<?= $postId ?>/<?= e($post['slug']) ?>"
         class="btn btn-outline-secondary">취소</a>
      <button type="submit" class="btn btn-primary px-4">수정 완료</button>
    </div>
  </form>
</div>

<!-- TOAST UI Editor JS -->
<script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>
<script>
// 기존 내용으로 에디터 초기화
var editor = new toastui.Editor({
  el: document.getElementById('toastEditor'),
  height: '500px',
  initialEditType: 'wysiwyg',
  previewStyle: 'vertical',
  language: 'ko-KR',
  initialValue: <?= json_encode($post['content'], JSON_UNESCAPED_UNICODE) ?>,
  toolbarItems: [
    ['heading', 'bold', 'italic', 'strike'],
    ['hr', 'quote'],
    ['ul', 'ol', 'task', 'indent', 'outdent'],
    ['table', 'link'],
    ['code', 'codeblock'],
    ['image'],
  ],
  hooks: {
    addImageBlobHook: function(blob, callback) {
      var formData = new FormData();
      formData.append('image', blob, blob.name || 'image.png');

      fetch('<?= SITE_URL ?>/board/image_upload.php', {
        method: 'POST',
        headers: {
          'X-CSRF-Token': '<?= csrf_token() ?>'
        },
        body: formData
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.url) {
          callback(data.url, blob.name || '이미지');
        } else {
          alert('이미지 업로드 실패: ' + (data.error || '알 수 없는 오류'));
          callback('', '');
        }
      })
      .catch(function(err) {
        alert('이미지 업로드 중 오류가 발생했습니다.');
        callback('', '');
      });
    }
  }
});

var FILE_MAX_COUNT  = <?= $board['file_max_count'] ?>;
var FILE_MAX_SIZE   = <?= $board['file_max_size'] ?> * 1024 * 1024;
var FILE_MAX_SIZE_MB = <?= $board['file_max_size'] ?>;
var FILE_ACCEPT     = '<?= implode(',', array_map(function($e){ return '.'.$e; }, ALLOWED_EXTENSIONS)) ?>';

function addFileInput() {
  var list = document.getElementById('fileInputList');
  var rows = list.querySelectorAll('.file-input-row');
  if (rows.length >= FILE_MAX_COUNT) {
    alert('파일은 최대 ' + FILE_MAX_COUNT + '개까지 첨부할 수 있습니다.');
    return;
  }
  var row = document.createElement('div');
  row.className = 'd-flex align-items-center gap-2 mb-1 file-input-row';
  row.innerHTML =
    '<input type="file" name="files[]" class="form-control form-control-sm file-input-item"' +
    ' accept="' + FILE_ACCEPT + '">' +
    '<button type="button" class="btn btn-sm btn-outline-danger py-0 flex-shrink-0"' +
    ' onclick="removeFileInput(this)"><i class="bi bi-x"></i></button>';
  list.appendChild(row);
  if (list.querySelectorAll('.file-input-row').length >= FILE_MAX_COUNT) {
    document.getElementById('addFileBtn').style.display = 'none';
  }
}

function removeFileInput(btn) {
  btn.closest('.file-input-row').remove();
  document.getElementById('addFileBtn').style.display = 'inline-block';
  document.getElementById('fileError').style.display = 'none';
}

function validateFileItem(input) {
  var errEl = document.getElementById('fileError');
  errEl.style.display = 'none';
  if (!input.files.length) return true;
  var file = input.files[0];
  if (file.size > FILE_MAX_SIZE) {
    errEl.textContent = '"' + file.name + '" 파일이 용량 제한(' + FILE_MAX_SIZE_MB +
      'MB)을 초과했습니다. (파일 크기: ' + (file.size/1024/1024).toFixed(1) + 'MB)';
    errEl.style.display = 'block';
    input.value = '';
    return false;
  }
  return true;
}

document.getElementById('fileInputList').addEventListener('change', function(e) {
  if (e.target.classList.contains('file-input-item')) validateFileItem(e.target);
});

document.getElementById('editForm').addEventListener('submit', function(e) {
  var html = editor.getHTML();
  if (!html || html === '<p><br></p>' || html.trim() === '') {
    e.preventDefault();
    alert('내용을 입력해주세요.');
    return;
  }
  var inputs = document.querySelectorAll('.file-input-item');
  var total = 0;
  var errEl = document.getElementById('fileError');
  errEl.style.display = 'none';
  for (var i = 0; i < inputs.length; i++) {
    if (!inputs[i].files.length) continue;
    total++;
    if (inputs[i].files[0].size > FILE_MAX_SIZE) {
      errEl.textContent = '"' + inputs[i].files[0].name + '" 파일이 용량 제한(' + FILE_MAX_SIZE_MB + 'MB)을 초과했습니다.';
      errEl.style.display = 'block';
      e.preventDefault(); return;
    }
  }
  if (total > FILE_MAX_COUNT) {
    errEl.textContent = '파일은 최대 ' + FILE_MAX_COUNT + '개까지 첨부할 수 있습니다.';
    errEl.style.display = 'block';
    e.preventDefault(); return;
  }
  document.getElementById('editorContent').value = html;
});
</script>

<?php require ROOT . '/includes/footer.php'; ?>
