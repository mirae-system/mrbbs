<?php
/**
 * board/view.php — 게시글 보기 (비밀글 기능 포함)
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once ROOT . '/config/config.php';

$db        = DB::getInstance();
$boardSlug = isset($_GET['board_slug']) ? $_GET['board_slug'] : '';
$postId    = isset($_GET['post_id'])    ? (int)$_GET['post_id'] : 0;

$board = $db->fetch("SELECT * FROM boards WHERE slug = ? AND is_active = 1", array($boardSlug));
$post  = $board ? $db->fetch(
    "SELECT p.*, m.name as member_name
     FROM posts p LEFT JOIN members m ON p.member_id = m.id
     WHERE p.id = ? AND p.board_id = ? AND p.status = 'active'",
    array($postId, $board['id'])
) : null;

if (!$board || !$post) {
    http_response_code(404);
    require ROOT . '/pages/404.php';
    exit;
}
if (!Auth::canRead($board)) {
    flash('열람 권한이 없습니다.', 'warning');
    redirect(SITE_URL . '/board/' . $boardSlug);
}

$isMember = Auth::isMember();
$isAdmin  = Auth::isAdmin();
$memberId = $isMember ? (int)Auth::getMember()['id'] : 0;
$adminId  = $isAdmin  ? (int)Auth::getAdmin()['id']  : 0;

$isOwnerMember = $isMember && $memberId == $post['member_id'];
$isOwnerAdmin  = $isAdmin;
$isGuestPost   = !$post['member_id'] && !$post['admin_id']; // 비회원 작성글

// ── 비밀글 접근 제어 ─────────────────────────────────────────
$canView = true;
$needPassword = false;

if ($post['is_secret']) {
    if ($isAdmin) {
        // 관리자: 항상 열람 가능
        $canView = true;
    } elseif ($isOwnerMember) {
        // 본인 회원 글: 열람 가능
        $canView = true;
    } elseif ($isMember && !$isGuestPost) {
        // 다른 회원이 쓴 비밀글: 열람 불가
        $canView = false;
    } elseif ($isGuestPost) {
        // 비회원이 쓴 비밀글: 비밀번호 확인 필요
        $secretVerified  = isset($_SESSION['secret_verified_' . $postId]) && $_SESSION['secret_verified_' . $postId];
        $secretVerifyTime= isset($_SESSION['secret_verified_time_' . $postId]) ? $_SESSION['secret_verified_time_' . $postId] : 0;
        $secretExpired   = (time() - $secretVerifyTime) > 3600; // 60분

        if ($secretVerified && !$secretExpired) {
            $canView = true;
        } else {
            $canView      = false;
            $needPassword = true;
        }
    } else {
        // 비로그인 상태에서 회원 작성 비밀글: 열람 불가
        $canView = false;
    }
}

// 비밀글 접근 불가 처리
if (!$canView) {
    $pageTitle    = '비밀글';
    $useTurnstile = false;
    require ROOT . '/includes/header.php';
    ?>
    <div class="container my-5">
      <div class="row justify-content-center">
        <div class="col-md-5">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-center">
              <div class="mb-3" style="font-size:3rem">&#128274;</div>
              <h5 class="fw-bold mb-1">비밀글입니다</h5>

              <?php if ($needPassword): ?>
              <p class="text-muted small mb-4">작성 시 입력한 비밀번호를 입력하세요.</p>
              <?php if ($flash = get_flash()): ?>
              <div class="alert alert-<?= e($flash['type']) ?> py-2 small"><?= e($flash['msg']) ?></div>
              <?php endif; ?>
              <form method="post" action="<?= SITE_URL ?>/board/secret_verify.php">
                <?= csrf_field() ?>
                <input type="hidden" name="post_id"    value="<?= $postId ?>">
                <input type="hidden" name="board_slug" value="<?= e($boardSlug) ?>">
                <div class="input-group mb-3">
                  <input type="password" name="password"
                         class="form-control" placeholder="비밀번호" required autofocus>
                  <button class="btn btn-primary">확인</button>
                </div>
              </form>

              <?php else: ?>
              <p class="text-muted small mb-4">작성자 본인 또는 관리자만 열람할 수 있습니다.</p>
              <?php if (!$isMember): ?>
              <a href="<?= SITE_URL ?>/auth/login.php?return=<?= urlencode(current_url()) ?>"
                 class="btn btn-outline-primary btn-sm mb-3">로그인하기</a>
              <?php endif; ?>
              <?php endif; ?>

              <div class="mt-2">
                <a href="<?= SITE_URL ?>/board/<?= e($boardSlug) ?>"
                   class="btn btn-outline-secondary btn-sm">목록으로</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
    require ROOT . '/includes/footer.php';
    exit;
}

// ── 정상 열람 ────────────────────────────────────────────────

// 비회원 수정/삭제 세션 확인
$guestVerified  = isset($_SESSION['guest_verified_' . $postId]) && $_SESSION['guest_verified_' . $postId];
$guestVerifyTime= isset($_SESSION['guest_verified_time_' . $postId]) ? $_SESSION['guest_verified_time_' . $postId] : 0;
$guestExpired   = (time() - $guestVerifyTime) > 1800;
$guestVerifiedOk= $guestVerified && !$guestExpired;

// 조회수 증가
$db->execute("UPDATE posts SET views = views + 1 WHERE id = ?", array($postId));
$post['views']++;

// 첨부파일
$files = $db->fetchAll("SELECT * FROM files WHERE post_id = ?", array($postId));

// 댓글
$comments = $db->fetchAll(
    "SELECT c.*, m.name as member_name
     FROM comments c LEFT JOIN members m ON c.member_id = m.id
     WHERE c.post_id = ? AND c.status = 'active'
     ORDER BY c.created_at ASC",
    array($postId)
);

// 이전/다음 글
// 이전글: 현재보다 오래된 글 (created_at 기준)
$prevPost = $db->fetch(
    "SELECT id, title, slug FROM posts
     WHERE board_id = ? AND created_at < ? AND status='active'
     ORDER BY created_at DESC LIMIT 1",
    array($board['id'], $post['created_at'])
);
// 다음글: 현재보다 최신 글 (created_at 기준)
$nextPost = $db->fetch(
    "SELECT id, title, slug FROM posts
     WHERE board_id = ? AND created_at > ? AND status='active'
     ORDER BY created_at ASC LIMIT 1",
    array($board['id'], $post['created_at'])
);

// SEO
$pageTitle       = $post['is_secret'] ? '비밀글' : $post['title'];
$pageDescription = $post['is_secret'] ? '' : excerpt($post['content'], 150);
$canonicalUrl    = SITE_URL . '/board/' . $boardSlug . '/' . $postId . '/' . $post['slug'];
$ogImage         = $post['thumbnail']
    ? SITE_URL . '/' . $post['thumbnail']
    : SITE_URL . '/assets/img/og-default.svg';

if ($board['type'] === 'qna' && !$post['is_secret']) {
    $answerText = '';
    foreach ($comments as $c) {
        if ($c['is_answer']) { $answerText = excerpt($c['content'], 300); break; }
    }
    $schemaJson = json_encode(array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array(array(
            '@type'          => 'Question',
            'name'           => $post['title'],
            'acceptedAnswer' => array('@type' => 'Answer', 'text' => $answerText ?: excerpt($post['content'], 300))
        ))
    ), JSON_UNESCAPED_UNICODE);
}

$useTurnstile = false;
require ROOT . '/includes/header.php';
?>

<div class="container my-4">

  <!-- 브레드크럼 -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
      <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">홈</a></li>
      <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/board/<?= e($boardSlug) ?>"><?= e($board['name']) ?></a></li>
      <li class="breadcrumb-item active"><?= e(mb_substr($post['title'], 0, 20, 'UTF-8')) ?>...</li>
    </ol>
  </nav>

  <!-- 게시글 헤더 -->
  <div class="mb-3">
    <?php if ($post['is_notice']): ?>
      <span class="badge bg-primary me-1">공지</span>
    <?php endif; ?>
    <?php if ($post['is_secret']): ?>
      <span class="badge bg-secondary me-1">&#128274; 비밀글</span>
    <?php endif; ?>
    <?php if ($board['type'] === 'qna'): ?>
      <span class="<?= $post['is_answered'] ? 'qna-answered' : 'qna-unanswered' ?>">
        <?= $post['is_answered'] ? '답변완료' : '답변대기' ?>
      </span>
    <?php endif; ?>
    <h1 class="post-title mt-1"><?= e($post['title']) ?></h1>
    <div class="post-meta d-flex flex-wrap gap-3 mt-2">
      <span><i class="bi bi-person me-1"></i><?= e($post['author_name']) ?></span>
      <span><i class="bi bi-clock me-1"></i><?= format_date($post['created_at'], 'Y.m.d H:i') ?></span>
      <span><i class="bi bi-eye me-1"></i><?= number_format($post['views']) ?></span>
    </div>
  </div>

  <!-- 본문 -->
  <div class="post-content toastui-editor-contents"><?= $post['content'] ?></div>

  <!-- 첨부파일 -->
  <?php if ($files): ?>
  <div class="mt-3 p-3 bg-light rounded">
    <p class="small fw-bold mb-2"><i class="bi bi-paperclip me-1"></i>첨부파일 (<?= count($files) ?>)</p>
    <ul class="file-list">
      <?php foreach ($files as $f): ?>
      <li>
        <i class="bi bi-file-earmark<?= $f['is_image'] ? '-image' : '' ?> text-muted"></i>
        <a href="<?= SITE_URL ?>/board/download.php?id=<?= $f['id'] ?>" class="text-primary">
          <?= e($f['original_name']) ?>
        </a>
        <span class="text-muted small">(<?= format_bytes($f['file_size']) ?>)</span>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <!-- 게시글 관리 버튼 -->
  <div class="d-flex justify-content-between mt-4">
    <a href="<?= SITE_URL ?>/board/<?= e($boardSlug) ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-list me-1"></i>목록
    </a>

    <div class="d-flex gap-2">
      <?php if ($isOwnerMember || $isOwnerAdmin): ?>
        <a href="<?= SITE_URL ?>/edit/<?= e($boardSlug) ?>/<?= $postId ?>"
           class="btn btn-sm btn-outline-primary">수정</a>
        <form method="post" action="<?= SITE_URL ?>/board/delete.php"
              onsubmit="return confirm('게시글을 삭제하시겠습니까?')">
          <?= csrf_field() ?>
          <input type="hidden" name="post_id"    value="<?= $postId ?>">
          <input type="hidden" name="board_slug" value="<?= e($boardSlug) ?>">
          <button class="btn btn-sm btn-outline-danger">삭제</button>
        </form>

      <?php elseif ($isGuestPost): ?>
        <?php if ($guestVerifiedOk): ?>
          <a href="<?= SITE_URL ?>/edit/<?= e($boardSlug) ?>/<?= $postId ?>"
             class="btn btn-sm btn-outline-primary">수정</a>
          <form method="post" action="<?= SITE_URL ?>/board/delete.php"
                onsubmit="return confirm('게시글을 삭제하시겠습니까?')">
            <?= csrf_field() ?>
            <input type="hidden" name="post_id"    value="<?= $postId ?>">
            <input type="hidden" name="board_slug" value="<?= e($boardSlug) ?>">
            <button class="btn btn-sm btn-outline-danger">삭제</button>
          </form>
        <?php else: ?>
          <button class="btn btn-sm btn-outline-secondary"
                  onclick="document.getElementById('guestAuthBox').style.display='block';this.style.display='none'">
            수정/삭제
          </button>
          <div id="guestAuthBox" style="display:none">
            <form method="post" action="<?= SITE_URL ?>/board/guest_verify.php"
                  class="d-flex gap-2 align-items-center">
              <?= csrf_field() ?>
              <input type="hidden" name="post_id"    value="<?= $postId ?>">
              <input type="hidden" name="board_slug" value="<?= e($boardSlug) ?>">
              <input type="password" name="password"
                     class="form-control form-control-sm"
                     placeholder="작성 시 비밀번호"
                     style="width:160px" required>
              <button class="btn btn-sm btn-outline-primary">확인</button>
            </form>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- 이전/다음 글 -->
  <div class="mt-3 border rounded">
    <?php if ($nextPost): ?>
    <div class="d-flex align-items-center p-2 border-bottom small">
      <span class="text-muted me-3" style="min-width:40px">다음</span>
      <a href="<?= SITE_URL ?>/board/<?= e($boardSlug) ?>/<?= $nextPost['id'] ?>/<?= e($nextPost['slug']) ?>"
         class="text-reset text-truncate"><?= e($nextPost['title']) ?></a>
    </div>
    <?php endif; ?>
    <?php if ($prevPost): ?>
    <div class="d-flex align-items-center p-2 small">
      <span class="text-muted me-3" style="min-width:40px">이전</span>
      <a href="<?= SITE_URL ?>/board/<?= e($boardSlug) ?>/<?= $prevPost['id'] ?>/<?= e($prevPost['slug']) ?>"
         class="text-reset text-truncate"><?= e($prevPost['title']) ?></a>
    </div>
    <?php endif; ?>
  </div>

  <!-- 댓글 섹션 -->
  <?php if ($board['use_comment']): ?>
  <div class="mt-5" id="comments">
    <h5 class="mb-3 fw-bold">댓글 <span class="text-muted fw-normal"><?= count($comments) ?></span></h5>

    <?php if ($comments): ?>
    <ul class="comment-list mb-4">
      <?php foreach ($comments as $c): ?>
      <?php
        $cIsMine = ($memberId && $memberId == $c['member_id'])
                || ($adminId  && $adminId  == $c['admin_id'])
                || $isAdmin;
      ?>
      <li class="comment-item" id="comment-<?= $c['id'] ?>">
        <?php if ($c['is_answer']): ?><div class="qna-answer-box"><div class="answer-label">공식 답변</div><?php endif; ?>

        <div class="d-flex justify-content-between align-items-center">
          <div>
            <span class="comment-author"><?= e($c['member_name'] ?: $c['author_name']) ?></span>
            <span class="comment-date"><?= time_ago($c['created_at']) ?></span>
            <?php if ($c['updated_at']): ?>
            <span class="text-muted" style="font-size:.75rem">(수정됨)</span>
            <?php endif; ?>
          </div>
          <?php if ($cIsMine): ?>
          <div class="d-flex gap-1">
            <button class="btn btn-sm btn-link text-muted py-0 px-1" style="font-size:.8rem"
                    onclick="toggleEditComment(<?= $c['id'] ?>)">수정</button>
            <form method="post" action="<?= SITE_URL ?>/board/comment_delete.php" class="d-inline"
                  onsubmit="return confirm('댓글을 삭제하시겠습니까?')">
              <?= csrf_field() ?>
              <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
              <input type="hidden" name="post_id"    value="<?= $postId ?>">
              <input type="hidden" name="board_slug" value="<?= e($boardSlug) ?>">
              <button class="btn btn-sm btn-link text-danger py-0 px-1" style="font-size:.8rem">삭제</button>
            </form>
          </div>
          <?php endif; ?>
        </div>

        <div class="comment-body mt-1" id="comment-body-<?= $c['id'] ?>">
          <?= nl2br(e($c['content'])) ?>
        </div>

        <?php if ($cIsMine): ?>
        <div id="comment-edit-<?= $c['id'] ?>" style="display:none" class="mt-2">
          <form method="post" action="<?= SITE_URL ?>/board/comment_update.php">
            <?= csrf_field() ?>
            <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
            <input type="hidden" name="post_id"    value="<?= $postId ?>">
            <input type="hidden" name="board_slug" value="<?= e($boardSlug) ?>">
            <textarea name="content" class="form-control form-control-sm mb-2"
                      rows="3"><?= e($c['content']) ?></textarea>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-sm btn-primary">저장</button>
              <button type="button" class="btn btn-sm btn-outline-secondary"
                      onclick="toggleEditComment(<?= $c['id'] ?>)">취소</button>
            </div>
          </form>
        </div>
        <?php endif; ?>

        <?php if ($c['is_answer']): ?></div><?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="text-muted small mb-4">첫 번째 댓글을 작성해보세요.</p>
    <?php endif; ?>

    <!-- 댓글 작성 -->
    <?php if ($isMember || $isAdmin): ?>
    <form method="post" action="<?= SITE_URL ?>/board/comment_save.php">
      <?= csrf_field() ?>
      <input type="hidden" name="post_id"    value="<?= $postId ?>">
      <input type="hidden" name="board_slug" value="<?= e($boardSlug) ?>">
      <?php if ($isAdmin && $board['type'] === 'qna'): ?>
      <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="is_answer" id="chkAnswer">
        <label class="form-check-label small text-primary fw-bold" for="chkAnswer">공식 답변으로 등록</label>
      </div>
      <?php endif; ?>
      <div class="mb-2">
        <textarea name="content" rows="3" class="form-control form-control-sm"
                  placeholder="댓글을 작성하세요." required></textarea>
      </div>
      <div class="text-end">
        <button type="submit" class="btn btn-sm btn-primary">댓글 작성</button>
      </div>
    </form>
    <?php else: ?>
    <div class="alert alert-light border text-center py-3">
      <p class="mb-2 text-muted small">댓글은 로그인 후 작성할 수 있습니다.</p>
      <a href="<?= SITE_URL ?>/auth/login.php?return=<?= urlencode(current_url()) ?>"
         class="btn btn-sm btn-outline-primary">로그인하고 댓글 달기</a>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</div>

<script>
function toggleEditComment(id) {
  var body = document.getElementById('comment-body-' + id);
  var form = document.getElementById('comment-edit-' + id);
  if (form.style.display === 'none') {
    body.style.display = 'none';
    form.style.display = 'block';
  } else {
    body.style.display = 'block';
    form.style.display = 'none';
  }
}
</script>

<?php require ROOT . '/includes/footer.php'; ?>
