<?php
/**
 * pages/greeting.php — 인사말 페이지
 * * 이 파일을 직접 수정하여 내용을 관리하세요.
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once dirname(__DIR__) . '/config/config.php';

$pageTitle       = '인사말';
$pageDescription = '대표 인사말 및 회사 소개입니다.';
require ROOT . '/includes/header.php';
?>

<div class="container my-5" style="max-width:820px">
  <div class="page-header">
    <h1>인사말</h1>
    <p>Greeting</p>
  </div>

  <!-- * 여기서부터 직접 수정하세요 * -->
  <div class="row align-items-center g-4 mb-5">
    <div class="col-md-5 text-center">
      <!-- 대표 사진 또는 이미지 -->
      <div class="bg-light rounded-3 d-flex align-items-center justify-content-center"
           style="height:280px; border:2px dashed #dee2e6">
        <span class="text-muted small">대표 사진<br>(이미지로 교체하세요)</span>
      </div>
    </div>
    <div class="col-md-7">
      <h2 class="fs-4 fw-bold mb-3">안녕하세요,<br>대표이사 <strong>홍길동</strong>입니다.</h2>
      <p class="text-muted lh-lg">
        저희 <strong><?= e(defined('SITE_NAME_DB') ? SITE_NAME_DB : SITE_NAME) ?></strong>을 방문해 주셔서 감사합니다.
        회사 소개 내용을 여기에 작성하세요. pages/greeting.php 파일을 직접 열어 수정하시면 됩니다.
      </p>
      <p class="text-muted lh-lg">
        저희는 고객 여러분의 신뢰를 최우선으로 생각하며, 최고의 서비스를 제공하기 위해 최선을 다하고 있습니다.
        앞으로도 많은 관심과 성원 부탁드립니다.
      </p>
      <p class="fw-bold mt-4"><?= e(defined('SITE_NAME_DB') ? SITE_NAME_DB : SITE_NAME) ?> 대표이사 홍길동 드림</p>
    </div>
  </div>
  <!-- * 여기까지 * -->

</div>

<?php require ROOT . '/includes/footer.php'; ?>
