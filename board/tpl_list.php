<?php /* board/tpl_list.php — 일반 + Q&A 목록 템플릿 */ ?>

<!-- 검색 + 쓰기 버튼 -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form class="d-flex search-form" method="get" action="<?= SITE_URL ?>/board/<?= e($board['slug']) ?>">
    <select name="by" class="form-select form-select-sm me-1" style="width:auto">
      <option value="title"   <?= $searchBy==='title'   ? 'selected' : '' ?>>제목</option>
      <option value="content" <?= $searchBy==='content' ? 'selected' : '' ?>>내용</option>
      <option value="author"  <?= $searchBy==='author'  ? 'selected' : '' ?>>작성자</option>
    </select>
    <input type="text" name="q" class="form-control form-control-sm" value="<?= e($keyword) ?>" placeholder="검색어">
    <button class="btn btn-sm btn-outline-secondary ms-1" type="submit"><i class="bi bi-search"></i></button>
  </form>
  <?php if (Auth::canWrite($board)): ?>
  <a href="<?= SITE_URL ?>/write/<?= e($board['slug']) ?>" class="btn btn-sm btn-primary">
    <i class="bi bi-pencil-square me-1"></i>글쓰기
  </a>
  <?php endif; ?>
</div>

<p class="text-muted small">총 <strong><?= number_format($total) ?></strong>개의 게시글</p>

<table class="table board-table">
  <thead>
    <tr>
      <th style="width:60px" class="text-center">번호</th>
      <?php if ($board['type'] === 'qna'): ?>
      <th style="width:70px" class="text-center">상태</th>
      <?php endif; ?>
      <th>제목</th>
      <th style="width:100px" class="text-center d-none-mobile">작성자</th>
      <th style="width:100px" class="text-center d-none-mobile">날짜</th>
      <th style="width:60px"  class="text-center d-none-mobile">조회</th>
    </tr>
  </thead>
  <tbody>
    <!-- 공지 -->
    <?php foreach ($notices as $n): ?>
    <tr class="table-light">
      <td class="text-center"><span class="badge-notice">공지</span></td>
      <?php if ($board['type'] === 'qna'): ?><td></td><?php endif; ?>
      <td class="title-cell">
        <?php if ($n['is_secret']): ?>
          <i class="bi bi-lock-fill text-muted me-1"></i>
        <?php endif; ?>
        <a href="<?= SITE_URL ?>/board/<?= e($board['slug']) ?>/<?= $n['id'] ?>/<?= e($n['slug']) ?>">
          <?= e($n['title']) ?>
        </a>
      </td>
      <td class="text-center d-none-mobile text-muted small"><?= e($n['author_name']) ?></td>
      <td class="text-center d-none-mobile text-muted small"><?= format_date($n['created_at']) ?></td>
      <td class="text-center d-none-mobile text-muted small"><?= number_format($n['views']) ?></td>
    </tr>
    <?php endforeach; ?>

    <!-- 일반 글 -->
    <?php if ($posts): ?>
      <?php $num = $total - $pag['offset']; ?>
      <?php foreach ($posts as $p): ?>
      <?php if ($p['is_notice']) { $num++; continue; } ?>
      <tr>
        <td class="text-center text-muted small"><?= $num-- ?></td>
        <?php if ($board['type'] === 'qna'): ?>
        <td class="text-center">
          <?php if ($p['is_answered']): ?>
            <span class="qna-answered">답변</span>
          <?php else: ?>
            <span class="qna-unanswered">대기</span>
          <?php endif; ?>
        </td>
        <?php endif; ?>
        <td class="title-cell">
          <?php if ($p['is_secret']): ?>
            <i class="bi bi-lock-fill text-muted me-1"></i>
          <?php endif; ?>
          <a href="<?= SITE_URL ?>/board/<?= e($board['slug']) ?>/<?= $p['id'] ?>/<?= e($p['slug']) ?>">
            <?= e($p['title']) ?>
          </a>
          <?php if (strtotime($p['created_at']) > time() - 86400): ?>
            <span class="badge-new">N</span>
          <?php endif; ?>
        </td>
        <td class="text-center d-none-mobile text-muted small"><?= e($p['author_name']) ?></td>
        <td class="text-center d-none-mobile text-muted small"><?= time_ago($p['created_at']) ?></td>
        <td class="text-center d-none-mobile text-muted small"><?= number_format($p['views']) ?></td>
      </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="<?= $board['type']==='qna' ? 6 : 5 ?>" class="no-data">
          <i class="bi bi-inbox"></i>등록된 게시글이 없습니다.
        </td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>

<!-- 페이지네이션 -->
<?php if ($pag['totalPages'] > 1): ?>
<nav class="mt-3">
  <ul class="pagination justify-content-center">
    <?php if ($pag['startPage'] > 1): ?>
    <li class="page-item">
      <a class="page-link" href="?page=<?= $pag['startPage']-1 ?>&q=<?= urlencode($keyword) ?>&by=<?= e($searchBy) ?>">«</a>
    </li>
    <?php endif; ?>
    <?php for ($i = $pag['startPage']; $i <= $pag['endPage']; $i++): ?>
    <li class="page-item <?= $i === $pag['current'] ? 'active' : '' ?>">
      <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($keyword) ?>&by=<?= e($searchBy) ?>"><?= $i ?></a>
    </li>
    <?php endfor; ?>
    <?php if ($pag['endPage'] < $pag['totalPages']): ?>
    <li class="page-item">
      <a class="page-link" href="?page=<?= $pag['endPage']+1 ?>&q=<?= urlencode($keyword) ?>&by=<?= e($searchBy) ?>">»</a>
    </li>
    <?php endif; ?>
  </ul>
</nav>
<?php endif; ?>
