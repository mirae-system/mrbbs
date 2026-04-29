<?php
/**
 * admin/login.php — 관리자 로그인 (PHP 7.3 / 카페24 호환)
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/config/config.php';

if (Auth::isAdmin()) {
    redirect(SITE_URL . '/admin/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── CSRF 검증 ───────────────────────────────────────────
    if (!csrf_verify()) {
        // 실패 원인 구분
        $sessToken = isset($_SESSION['_csrf'])  ? $_SESSION['_csrf']  : '';
        $postToken = isset($_POST['_csrf'])     ? $_POST['_csrf']     : '';

        if (empty($sessToken)) {
            // 세션이 아예 유지되지 않는 경우
            $error = '세션이 유지되지 않습니다. 쿠키가 허용되어 있는지 확인하거나, HTTPS 주소로 접속해 주세요.';
        } elseif (empty($postToken)) {
            $error = 'CSRF 토큰이 전송되지 않았습니다. 페이지를 새로고침 후 다시 시도하세요.';
        } else {
            $error = '보안 토큰이 일치하지 않습니다. 페이지를 새로고침 후 다시 시도하세요.';
        }

    } else {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password']      : '';

        if (Auth::adminLogin($username, $password)) {
            // 로그인 성공 후 CSRF 토큰 갱신
            unset($_SESSION['_csrf']);
            flash('관리자로 로그인되었습니다.', 'success');
            redirect(SITE_URL . '/admin/');
        } else {
            $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>관리자 로그인 | <?= e(defined('SITE_NAME_DB') ? SITE_NAME_DB : SITE_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <meta name="robots" content="noindex,nofollow">
</head>
<body class="bg-light">
<div class="container">
  <div class="row justify-content-center mt-5">
    <div class="col-md-4 col-sm-8">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <h5 class="fw-bold text-center mb-4"> 관리자 로그인</h5>

          <?php if ($error): ?>
          <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
          <?php endif; ?>

          <form method="post" action="">
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label small fw-bold">아이디</label>
              <input type="text" name="username" class="form-control"
                     value="<?= isset($_POST['username']) ? e($_POST['username']) : '' ?>"
                     autofocus autocomplete="username" required>
            </div>
            <div class="mb-4">
              <label class="form-label small fw-bold">비밀번호</label>
              <input type="password" name="password" class="form-control"
                     autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">로그인</button>
          </form>

          <div class="text-center mt-3">
            <a href="<?= SITE_URL ?>" class="text-muted small">← 사이트로 돌아가기</a>
          </div>
        </div>
      </div>

      <!-- 세션/쿠키 안내 -->
      <div class="text-center mt-3">
        <p class="text-muted small">
          로그인이 안 되면 브라우저 쿠키 허용 여부를 확인하세요.<br>
          <strong>HTTPS</strong> 주소로 접속하는 것을 권장합니다.
        </p>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
