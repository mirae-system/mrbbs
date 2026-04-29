<?php /* board/tpl_gallery.php — 갤러리 목록 템플릿 */ ?>

<!-- 검색 + 쓰기 -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form class="d-flex search-form" method="get" action="<?= SITE_URL ?>/board/<?= e($board['slug']) ?>">
    <input type="text" name="q" class="form-control form-control-sm" value="<?= e($keyword) ?>" placeholder="검색어">
    <button class="btn btn-sm btn-outline-secondary ms-1" type="submit"><i class="bi bi-search"></i></button>
  </form>
  <?php if (Auth::canWrite($board)): ?>
  <a href="<?= SITE_URL ?>/write/<?= e($board['slug']) ?>" class="btn btn-sm btn-primary">
    <i class="bi bi-image me-1"></i>사진 올리기
  </a>
  <?php endif; ?>
</div>

<?php if ($posts): ?>
<div class="gallery-grid">
  <?php foreach ($posts as $p): ?>
  <a href="<?= SITE_URL ?>/board/<?= e($board['slug']) ?>/<?= $p['id'] ?>/<?= e($p['slug']) ?>"
     class="gallery-item text-decoration-none text-reset d-block">
    <?php if ($p['thumbnail']): ?>
      <img src="<?= e(SITE_URL . '/' . $p['thumbnail']) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
    <?php else: ?>
      <div class="d-flex align-items-center justify-content-center bg-light" style="height:180px">
        <i class="bi bi-image text-secondary" style="font-size:2rem"></i>
      </div>
    <?php endif; ?>
    <div class="gallery-item-body">
      <div class="gallery-item-title"><?= e($p['title']) ?></div>
      <div class="gallery-item-meta mt-1">
        <span><?= e($p['author_name']) ?></span>
        <span class="ms-2"><?= time_ago($p['created_at']) ?></span>
        <span class="ms-2"><i class="bi bi-eye"></i> <?= number_format($p['views']) ?></span>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="no-data">
  <i class="bi bi-images"></i>
  등록된 사진이 없습니다.
</div>
<?php endif; ?>

<!-- 페이지네이션 -->
<?php if ($pag['totalPages'] > 1): ?>
<nav class="mt-4">
  <ul class="pagination justify-content-center">
    <?php if ($pag['startPage'] > 1): ?>
    <li class="page-item">
      <a class="page-link" href="?page=<?= $pag['startPage']-1 ?>&q=<?= urlencode($keyword) ?>">«</a>
    </li>
    <?php endif; ?>
    <?php for ($i = $pag['startPage']; $i <= $pag['endPage']; $i++): ?>
    <li class="page-item <?= $i === $pag['current'] ? 'active' : '' ?>">
      <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($keyword) ?>"><?= $i ?></a>
    </li>
    <?php endfor; ?>
    <?php if ($pag['endPage'] < $pag['totalPages']): ?>
    <li class="page-item">
      <a class="page-link" href="?page=<?= $pag['endPage']+1 ?>&q=<?= urlencode($keyword) ?>">»</a>
    </li>
    <?php endif; ?>
  </ul>
</nav>
<?php endif; ?>
