<?php
/**
 * auth/login.php — 소셜 로그인 선택 페이지
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once dirname(__DIR__) . '/config/config.php';

if (Auth::isLoggedIn()) redirect(SITE_URL);

// 로그인 후 돌아올 URL 저장
if (!empty($_GET['return'])) {
    $_SESSION['login_return'] = filter_var($_GET['return'], FILTER_SANITIZE_URL);
}

$pageTitle    = '로그인';
$useTurnstile = false;
require ROOT . '/includes/header.php';
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <h4 class="text-center fw-bold mb-1">로그인</h4>
          <p class="text-center text-muted small mb-4">소셜 계정으로 간편하게 로그인하세요.</p>

          <!-- Google 로그인 -->
          <a href="<?= SITE_URL ?>/auth/google.php"
             class="btn btn-outline-dark w-100 mb-3 d-flex align-items-center justify-content-center gap-2">
            <svg width="18" height="18" viewBox="0 0 48 48">
              <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
              <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
              <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
              <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            Google로 로그인
          </a>

          <!-- 네이버 로그인 -->
          <a href="<?= SITE_URL ?>/auth/naver.php"
             class="btn w-100 d-flex align-items-center justify-content-center gap-2 text-white"
             style="background:#03C75A; border-color:#03C75A">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="white">
              <path d="M16.273 12.845L7.376 0H0v24h7.727V11.155L16.624 24H24V0h-7.727z"/>
            </svg>
            네이버로 로그인
          </a>

          <hr class="my-4">
          <p class="text-center text-muted small">
            로그인 없이도 게시글 열람은 가능합니다.<br>
            글 작성 시에는 로그인 또는 이름/비밀번호를 입력하세요.
          </p>
        </div>
      </div>

      <!-- 관리자 로그인 링크 -->
      <div class="text-center mt-3">
        <a href="<?= SITE_URL ?>/admin/login.php" class="text-muted small">관리자 로그인</a>
      </div>
    </div>
  </div>
</div>

<?php require ROOT . '/includes/footer.php'; ?>
