
<footer class="bg-light border-top mt-5 py-4">
  <div class="container">
    <div class="row">
      <div class="col-md-6">
        <p class="text-muted small mb-1">
          <strong><?= e(defined('SITE_NAME_DB') ? SITE_NAME_DB : SITE_NAME) ?></strong>
        </p>
        <!-- * 직접 수정: 회사 정보를 입력하세요 -->
        <p class="text-muted small mb-0">
          대표: 홍길동 &nbsp;|&nbsp; 주소: 서울시 강남구 &nbsp;|&nbsp; 전화: 02-0000-0000<br>
          사업자등록번호: 000-00-00000 &nbsp;|&nbsp; 이메일: info@yourdomain.com
        </p>
      </div>
      <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <p class="text-muted small mb-1">
          <a href="<?= SITE_URL ?>/privacy" class="text-muted me-3">개인정보처리방침</a>
          <a href="<?= SITE_URL ?>/terms"   class="text-muted">이용약관</a>
        </p>
        <p class="text-muted small mb-0">
          &copy; <?= date('Y') ?> <?= e(defined('SITE_NAME_DB') ? SITE_NAME_DB : SITE_NAME) ?>. All rights reserved.
        </p>
      </div>
    </div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- 사이트 공통 JS -->
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>

</body>
</html>
