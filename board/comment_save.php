<?php
/**
 * board/comment_save.php — 댓글 저장 (회원 전용)
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once ROOT . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(SITE_URL); }
if (!csrf_verify()) { flash('보안 오류.', 'danger'); redirect(SITE_URL); }

// 로그인 확인 (회원 전용)
if (!Auth::isMember() && !Auth::isAdmin()) {
    flash('댓글은 로그인 후 작성할 수 있습니다.', 'warning');
    redirect(SITE_URL . '/auth/login.php');
}

$db        = DB::getInstance();
$postId    = (int)(isset($_POST['post_id'])    ? $_POST['post_id']    : 0);
$boardSlug = isset($_POST['board_slug'])        ? $_POST['board_slug'] : '';
$content   = trim(isset($_POST['content'])     ? $_POST['content']    : '');

$post = $db->fetch(
    "SELECT p.*, b.use_comment, b.slug AS board_slug_col
     FROM posts p JOIN boards b ON p.board_id = b.id
     WHERE p.id = ? AND p.status = 'active'",
    array($postId)
);

if (!$post || !$post['use_comment']) {
    flash('댓글을 작성할 수 없습니다.', 'warning');
    redirect(SITE_URL);
}

$backUrl = SITE_URL . '/board/' . $boardSlug . '/' . $postId . '/' . $post['slug'];

if (!$content) {
    flash('댓글 내용을 입력해주세요.', 'warning');
    redirect($backUrl);
}

// 작성자 정보
$authorName = '';
$memberId   = null;
$adminId    = null;
$isAnswer   = 0;

if (Auth::isAdmin()) {
    $a          = Auth::getAdmin();
    $authorName = $a['name'];
    $adminId    = $a['id'];
    $isAnswer   = isset($_POST['is_answer']) ? 1 : 0;
} else {
    $m          = Auth::getMember();
    $authorName = $m['name'];
    $memberId   = $m['id'];
}

$content = strip_tags($content);
$db->insert(
    "INSERT INTO comments (post_id, member_id, admin_id, content, author_name, is_answer, ip)
     VALUES (?,?,?,?,?,?,?)",
    array($postId, $memberId, $adminId, $content, $authorName, $isAnswer, get_ip())
);

if ($isAnswer) {
    $db->execute("UPDATE posts SET is_answered = 1 WHERE id = ?", array($postId));
}

flash('댓글이 등록되었습니다.', 'success');
redirect($backUrl . '#comments');
