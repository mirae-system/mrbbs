<?php
/**
 * board/index.php — 게시판 목록 (일반 / 갤러리 / Q&A)
 * PHP 7.3 / 카페24 호환
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once ROOT . '/config/config.php';

$db        = DB::getInstance();
$boardSlug = isset($_GET['board_slug']) ? $_GET['board_slug'] : '';
$board     = $db->fetch(
    "SELECT * FROM boards WHERE slug = ? AND is_active = 1",
    array($boardSlug)
);

if (!$board) {
    http_response_code(404);
    require ROOT . '/pages/404.php';
    exit;
}

if (!Auth::canRead($board)) {
    flash('이 게시판을 볼 권한이 없습니다.', 'warning');
    redirect(SITE_URL);
}

// 검색 / 페이징
$keyword  = trim(isset($_GET['q'])   ? $_GET['q']   : '');
$searchBy = isset($_GET['by']) && in_array($_GET['by'], array('title','content','author'))
            ? $_GET['by'] : 'title';
$page     = max(1, (int)(isset($_GET['page']) ? $_GET['page'] : 1));
$perPage  = (int)$board['posts_per_page'];

$where  = "p.board_id = ? AND p.status = 'active'";
$params = array($board['id']);

if ($keyword !== '') {
    if ($searchBy === 'content')    $where .= " AND p.content LIKE ?";
    elseif ($searchBy === 'author') $where .= " AND p.author_name LIKE ?";
    else                            $where .= " AND p.title LIKE ?";
    $params[] = '%' . $keyword . '%';
}

$total = $db->count(
    "SELECT COUNT(*) FROM posts p WHERE $where AND p.is_notice = 0",
    $params
);
$pag = paginate($total, $perPage, $page);

// 공지글 (첫 페이지만)
$notices = array();
if ($page === 1) {
    $notices = $db->fetchAll(
        "SELECT p.*, m.name as member_name FROM posts p
         LEFT JOIN members m ON p.member_id = m.id
         WHERE p.board_id = ? AND p.is_notice = 1 AND p.status = 'active'
         ORDER BY p.created_at DESC LIMIT 5",
        array($board['id'])
    );
}

// 일반 게시글 (LIMIT 직접 삽입 — MariaDB 10.0 호환)
$limitVal  = (int)$perPage;
$offsetVal = (int)$pag['offset'];
$posts = $db->fetchAll(
    "SELECT p.*, m.name as member_name FROM posts p
     LEFT JOIN members m ON p.member_id = m.id
     WHERE $where AND p.is_notice = 0
     ORDER BY p.created_at DESC, p.id DESC
     LIMIT {$limitVal} OFFSET {$offsetVal}",
    $params
);

// SEO
$pageTitle       = $board['name'];
$pageDescription = $board['description']
    ? $board['description']
    : $board['name'] . ' 게시판입니다.';
$canonicalUrl    = SITE_URL . '/board/' . $board['slug']
    . ($page > 1 ? '?page=' . $page : '');

// Q&A 구조화 데이터
if ($board['type'] === 'qna' && $page === 1 && $posts) {
    $faqItems = array();
    foreach (array_slice($posts, 0, 10) as $p) {
        $faqItems[] = array(
            '@type'          => 'Question',
            'name'           => $p['title'],
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => excerpt($p['content'], 200)
            )
        );
    }
    $schemaJson = json_encode(
        array(
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $faqItems
        ),
        JSON_UNESCAPED_UNICODE
    );
}

require ROOT . '/includes/header.php';
?>

<div class="container my-4">
  <div class="page-header">
    <h1><?= e($board['name']) ?></h1>
    <?php if ($board['description']): ?>
    <p><?= e($board['description']) ?></p>
    <?php endif; ?>
  </div>

  <?php if ($board['type'] === 'gallery'): ?>
    <?php include __DIR__ . '/tpl_gallery.php'; ?>
  <?php else: ?>
    <?php include __DIR__ . '/tpl_list.php'; ?>
  <?php endif; ?>
</div>

<?php require ROOT . '/includes/footer.php'; ?>
