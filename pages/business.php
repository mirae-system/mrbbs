<?php
/**
 * pages/business.php — 사업소개 페이지 (PHP 7.3 호환)
 * * $businesses 배열을 직접 수정하세요.
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once ROOT . '/config/config.php';

$pageTitle       = '사업소개';
$pageDescription = '주요 사업 분야와 서비스를 소개합니다.';
require ROOT . '/includes/header.php';
?>

<div class="container my-5">
  <div class="page-header">
    <h1>사업소개</h1>
    <p>Business</p>
  </div>

  <?php
  /* * 아래 배열을 직접 수정하세요 * */
  $businesses = array(
    array('icon'=>'bi-laptop',        'title'=>'IT 솔루션',      'desc'=>'기업 맞춤형 소프트웨어 개발 및 시스템 구축 서비스를 제공합니다.'),
    array('icon'=>'bi-cloud-upload',  'title'=>'클라우드 서비스', 'desc'=>'안정적이고 확장 가능한 클라우드 인프라 구성 및 운영을 지원합니다.'),
    array('icon'=>'bi-bar-chart',     'title'=>'데이터 분석',     'desc'=>'빅데이터 분석을 통해 비즈니스 인사이트를 제공합니다.'),
    array('icon'=>'bi-shield-check',  'title'=>'보안 컨설팅',     'desc'=>'기업 정보 보안 진단 및 전략 수립 서비스를 제공합니다.'),
  );
  ?>

  <div class="row g-4 mb-5">
    <?php foreach ($businesses as $b): ?>
    <div class="col-sm-6 col-lg-3">
      <div class="card h-100 border-0 shadow-sm text-center p-3">
        <div class="card-body">
          <div class="mb-3">
            <i class="bi <?= $b['icon'] ?> text-primary" style="font-size:2.5rem"></i>
          </div>
          <h5 class="fw-bold"><?= e($b['title']) ?></h5>
          <p class="text-muted small"><?= e($b['desc']) ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bg-light rounded-3 p-4 p-md-5">
    <h3 class="fw-bold text-center mb-4">핵심 가치</h3>
    <div class="row g-3 text-center">
      <div class="col-4">
        <div class="fs-2"></div>
        <div class="fw-bold">고객 중심</div>
        <div class="text-muted small">고객 만족을 최우선으로</div>
      </div>
      <div class="col-4">
        <div class="fs-2"></div>
        <div class="fw-bold">혁신</div>
        <div class="text-muted small">끊임없는 기술 혁신</div>
      </div>
      <div class="col-4">
        <div class="fs-2"></div>
        <div class="fw-bold">신뢰</div>
        <div class="text-muted small">투명한 파트너십</div>
      </div>
    </div>
  </div>
</div>

<?php require ROOT . '/includes/footer.php'; ?>
