<?php
/**
 * pages/history.php — 연혁 페이지 (PHP 7.3 호환)
 * * $history 배열을 직접 수정하여 연혁을 관리하세요.
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once ROOT . '/config/config.php';

$pageTitle       = '연혁';
$pageDescription = '회사의 발자취와 성장 역사입니다.';
require ROOT . '/includes/header.php';
?>

<div class="container my-5" style="max-width:760px">
  <div class="page-header">
    <h1>연혁</h1>
    <p>History</p>
  </div>

  <?php
  /* * 아래 배열을 직접 수정하세요 * */
  $history = array(
    2024 => array(
      '12월' => '사옥 이전',
      '06월' => '매출 100억 달성',
      '01월' => '신규 서비스 런칭',
    ),
    2023 => array(
      '09월' => '기업부설연구소 설립',
      '03월' => '벤처기업 인증',
    ),
    2022 => array(
      '06월' => '직원 50명 돌파',
      '01월' => '시리즈 A 투자 유치',
    ),
    2020 => array(
      '03월' => '법인 설립',
    ),
  );
  ?>

  <div class="position-relative" style="padding-left:2rem;border-left:3px solid #0d6efd">
    <?php foreach ($history as $year => $events): ?>
    <div class="mb-4">
      <div class="position-relative mb-3">
        <span class="badge bg-primary fw-bold" style="font-size:.85rem"><?= $year ?></span>
      </div>
      <?php foreach ($events as $month => $event): ?>
      <div class="d-flex gap-3 mb-2 align-items-start ms-2">
        <span class="text-muted small fw-bold" style="min-width:36px"><?= e($month) ?></span>
        <span class="small"><?= e($event) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require ROOT . '/includes/footer.php'; ?>
