<?php
/**
 * pages/404.php — 404 에러 페이지
 */
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
    require_once ROOT . '/config/config.php';
}
$pageTitle    = '페이지를 찾을 수 없습니다';
$useTurnstile = false;
require ROOT . '/includes/header.php';
?>
<div class="container text-center my-5 py-5">
  <div style="font-size:5rem"></div>
  <h1 class="fw-bold mt-3">404</h1>
  <p class="text-muted">요청하신 페이지를 찾을 수 없습니다.</p>
  <a href="<?= SITE_URL ?>" class="btn btn-primary mt-2">홈으로 돌아가기</a>
</div>
<?php require ROOT . '/includes/footer.php'; ?>
