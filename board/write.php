<?php
/**
 * board/write.php — 게시글 작성 (TOAST UI Editor 포함)
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once ROOT . '/config/config.php';

$db        = DB::getInstance();
$boardSlug = isset($_GET['board_slug']) ? $_GET['board_slug'] : '';
$board     = $db->fetch("SELECT * FROM boards WHERE slug = ? AND is_active = 1", array($boardSlug));

if (!$board) { http_response_code(404); require ROOT.'/pages/404.php'; exit; }
if (!Auth::canWrite($board)) {
    flash('글쓰기 권한이 없습니다.', 'warning');
    redirect(SITE_URL . '/board/' . $boardSlug);
}

// ── POST 저장 처리 ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('보안 오류가 발생했습니다.', 'danger');
        redirect(SITE_URL . '/write/' . $boardSlug);
    }

    if ($board['use_turnstile'] && !Turnstile::verifyRequest()) {
        flash('스팸 방지 검증에 실패했습니다. 다시 시도해주세요.', 'danger');
        redirect(SITE_URL . '/write/' . $boardSlug);
    }

    $title    = trim(isset($_POST['title'])   ? $_POST['title']   : '');
    $content  = isset($_POST['content'])      ? $_POST['content'] : '';
    $isSecret = (isset($_POST['is_secret']) && $board['use_secret']) ? 1 : 0;
    $isNotice = (Auth::isAdmin() && isset($_POST['is_notice']))      ? 1 : 0;

    $authorName = '';
    $authorPass = null;
    if (Auth::isMember()) {
        $m = Auth::getMember();
        $authorName = $m['name'];
    } elseif (Auth::isAdmin()) {
        $a = Auth::getAdmin();
        $authorName = $a['name'];
    } else {
        $authorName = trim(isset($_POST['author_name']) ? $_POST['author_name'] : '');
        $rawPass    = isset($_POST['author_pass'])      ? $_POST['author_pass'] : '';
        if (!$authorName || !$rawPass) {
            flash('이름과 비밀번호를 입력해주세요.', 'warning');
            redirect(SITE_URL . '/write/' . $boardSlug);
        }
        $authorPass = password_hash($rawPass, PASSWORD_BCRYPT);
    }

    if (!$title) {
        flash('제목을 입력해주세요.', 'warning');
        redirect(SITE_URL . '/write/' . $boardSlug);
    }

    // TOAST UI Editor 는 HTML을 출력하므로 허용 태그 범위 확대
    $content = strip_tags($content,
        '<p><br><strong><em><u><s><ul><ol><li><h1><h2><h3><h4><blockquote><a><img>' .
        '<table><thead><tbody><tr><th><td><code><pre><hr><del><ins><span><div>'
    );

    $slug     = make_slug($title);
    $memberId = Auth::isMember() ? Auth::getMember()['id'] : null;
    $adminId  = Auth::isAdmin()  ? Auth::getAdmin()['id']  : null;

    $db->beginTransaction();
    try {
        $postId = $db->insert(
            "INSERT INTO posts
             (board_id,member_id,admin_id,title,slug,content,author_name,password,is_secret,is_notice,ip)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            array($board['id'], $memberId, $adminId, $title, $slug, $content,
                  $authorName, $authorPass, $isSecret, $isNotice, get_ip())
        );

        if ($board['use_file'] && !empty($_FILES['files']['name'][0])) {
            handleFileUpload((int)$postId, $board, $_FILES['files'], $db);
        }
        $db->commit();
        flash('게시글이 등록되었습니다.', 'success');
        redirect(SITE_URL . '/board/' . $boardSlug . '/' . $postId . '/' . $slug);
    } catch (Exception $e) {
        $db->rollBack();
        flash('저장 중 오류가 발생했습니다.', 'danger');
        redirect(SITE_URL . '/write/' . $boardSlug);
    }
}

// ── GET: 폼 표시 ─────────────────────────────────────────────
$pageTitle    = $board['name'] . ' 글쓰기';
$useTurnstile = $board['use_turnstile'] && Turnstile::isEnabled();
$exts         = array_map(function($e) { return '.' . $e; }, ALLOWED_EXTENSIONS);
require ROOT . '/includes/header.php';
?>

<!-- TOAST UI Editor CSS -->
<link rel="stylesheet"
      href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css">

<div class="container my-4" style="max-width:900px">
  <div class="page-header">
    <h1><?= e($board['name']) ?> 글쓰기</h1>
  </div>

  <form method="post" enctype="multipart/form-data" id="writeForm">
    <?= csrf_field() ?>

    <!-- 비회원 정보 -->
    <?php if (!Auth::isLoggedIn()): ?>
    <div class="row g-2 mb-3">
      <div class="col-sm-4">
        <label class="form-label small">이름 <span class="text-danger">*</span></label>
        <input type="text" name="author_name" class="form-control form-control-sm" required>
      </div>
      <div class="col-sm-4">
        <label class="form-label small">비밀번호 <span class="text-danger">*</span></label>
        <input type="password" name="author_pass" class="form-control form-control-sm" required>
      </div>
    </div>
    <?php endif; ?>

    <!-- 제목 -->
    <div class="mb-3">
      <label class="form-label small fw-bold">제목 <span class="text-danger">*</span></label>
      <input type="text" name="title" class="form-control"
             placeholder="제목을 입력하세요." required>
    </div>

    <!-- TOAST UI Editor -->
    <div class="mb-3">
      <label class="form-label small fw-bold">내용 <span class="text-danger">*</span></label>
      <div id="toastEditor"></div>
      <!-- 실제 전송되는 hidden input -->
      <input type="hidden" name="content" id="editorContent">
    </div>

    <!-- 옵션 -->
    <div class="d-flex gap-3 mb-3">
      <?php if (Auth::isAdmin()): ?>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_notice" id="chkNotice">
        <label class="form-check-label small" for="chkNotice">공지글로 등록</label>
      </div>
      <?php endif; ?>
      <?php if ($board['use_secret']): ?>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_secret" id="chkSecret">
        <label class="form-check-label small" for="chkSecret">비밀글</label>
      </div>
      <?php endif; ?>
    </div>

    <!-- 파일 업로드 -->
    <?php if ($board['use_file']): ?>
    <div class="mb-3">
      <label class="form-label small fw-bold">
        첨부파일 (최대 <?= $board['file_max_count'] ?>개 / 각 <?= $board['file_max_size'] ?>MB)
      </label>
      <div id="fileInputList">
        <div class="d-flex align-items-center gap-2 mb-1 file-input-row">
          <input type="file" name="files[]"
                 class="form-control form-control-sm file-input-item"
                 accept="<?= implode(',', $exts) ?>">
        </div>
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary mt-1"
              id="addFileBtn"
              onclick="addFileInput()">
        <i class="bi bi-plus-circle me-1"></i>파일 추가
      </button>
      <div class="form-text mt-1">
        허용 확장자: <?= implode(', ', ALLOWED_EXTENSIONS) ?>
        &nbsp;|&nbsp; 최대 <?= $board['file_max_count'] ?>개 / 각 <?= $board['file_max_size'] ?>MB
      </div>
      <div id="fileError" class="text-danger small mt-1" style="display:none"></div>
    </div>
    <script>
    var FILE_MAX_COUNT = <?= $board['file_max_count'] ?>;
    var FILE_MAX_SIZE  = <?= $board['file_max_size'] ?> * 1024 * 1024;
    var FILE_MAX_SIZE_MB = <?= $board['file_max_size'] ?>;

    function addFileInput() {
      var list  = document.getElementById('fileInputList');
      var rows  = list.querySelectorAll('.file-input-row');
      if (rows.length >= FILE_MAX_COUNT) {
        alert('파일은 최대 ' + FILE_MAX_COUNT + '개까지 첨부할 수 있습니다.');
        return;
      }
      var row = document.createElement('div');
      row.className = 'd-flex align-items-center gap-2 mb-1 file-input-row';
      row.innerHTML =
        '<input type="file" name="files[]" class="form-control form-control-sm file-input-item"' +
        ' accept="<?= implode(',', $exts) ?>">' +
        '<button type="button" class="btn btn-sm btn-outline-danger py-0 flex-shrink-0"' +
        ' onclick="removeFileInput(this)"><i class="bi bi-x"></i></button>';
      list.appendChild(row);

      // 최대 개수 도달 시 추가 버튼 숨김
      if (list.querySelectorAll('.file-input-row').length >= FILE_MAX_COUNT) {
        document.getElementById('addFileBtn').style.display = 'none';
      }
    }

    function removeFileInput(btn) {
      var row  = btn.closest('.file-input-row');
      row.parentNode.removeChild(row);
      document.getElementById('addFileBtn').style.display = 'inline-block';
      document.getElementById('fileError').style.display = 'none';
    }

    function validateFiles(input) {
      var errEl = document.getElementById('fileError');
      errEl.style.display = 'none';
      errEl.textContent   = '';
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

    // 파일 input 변경 시 각각 검증
    document.getElementById('fileInputList').addEventListener('change', function(e) {
      if (e.target.classList.contains('file-input-item')) {
        validateFiles(e.target);
      }
    });

    // 폼 제출 시 전체 검증
    document.getElementById('writeForm').addEventListener('submit', function(e) {
      var inputs  = document.querySelectorAll('.file-input-item');
      var total   = 0;
      var errEl   = document.getElementById('fileError');
      errEl.style.display = 'none';

      for (var i = 0; i < inputs.length; i++) {
        if (!inputs[i].files.length) continue;
        total++;
        var file = inputs[i].files[0];
        if (file.size > FILE_MAX_SIZE) {
          errEl.textContent = '"' + file.name + '" 파일이 용량 제한(' + FILE_MAX_SIZE_MB +
            'MB)을 초과했습니다.';
          errEl.style.display = 'block';
          e.preventDefault(); return;
        }
      }
      if (total > FILE_MAX_COUNT) {
        errEl.textContent = '파일은 최대 ' + FILE_MAX_COUNT + '개까지 첨부할 수 있습니다.';
        errEl.style.display = 'block';
        e.preventDefault(); return;
      }
    }, false);
    </script>
    <?php endif; ?>

    <!-- Turnstile -->
    <?php if ($useTurnstile): ?>
    <div class="mb-3"><?= Turnstile::widget() ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between">
      <a href="<?= SITE_URL ?>/board/<?= e($boardSlug) ?>"
         class="btn btn-outline-secondary">취소</a>
      <button type="submit" class="btn btn-primary px-4"
              onclick="return submitForm()">등록하기</button>
    </div>
  </form>
</div>

<!-- TOAST UI Editor JS -->
<script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>
<script>
// TOAST UI Editor 초기화
var editor = new toastui.Editor({
  el: document.getElementById('toastEditor'),
  height: '500px',
  initialEditType: 'wysiwyg',
  previewStyle: 'vertical',
  language: 'ko-KR',
  placeholder: '내용을 입력하세요.',
  toolbarItems: [
    ['heading', 'bold', 'italic', 'strike'],
    ['hr', 'quote'],
    ['ul', 'ol', 'task', 'indent', 'outdent'],
    ['table', 'link'],
    ['code', 'codeblock'],
    ['image'],
  ],
  hooks: {
    // 이미지 업로드 훅 — base64 대신 서버 업로드
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

// 폼 제출 시 에디터 내용을 hidden input에 저장
document.getElementById('writeForm').addEventListener('submit', function(e) {
  var html = editor.getHTML();
  if (!html || html === '<p><br></p>' || html.trim() === '') {
    e.preventDefault();
    alert('내용을 입력해주세요.');
    return;
  }
  document.getElementById('editorContent').value = html;
}, true);
</script>

<?php require ROOT . '/includes/footer.php'; ?>
