<?php
/**
 * board/comment_update.php — 댓글 수정 (본인만)
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once ROOT . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(SITE_URL); }
if (!csrf_verify()) { flash('보안 오류.', 'danger'); redirect(SITE_URL); }

// 로그인 확인
if (!Auth::isMember() && !Auth::isAdmin()) {
    flash('로그인이 필요합니다.', 'warning');
    redirect(SITE_URL . '/auth/login.php');
}

$db        = DB::getInstance();
$commentId = (int)(isset($_POST['comment_id']) ? $_POST['comment_id'] : 0);
$postId    = (int)(isset($_POST['post_id'])    ? $_POST['post_id']    : 0);
$boardSlug = isset($_POST['board_slug'])        ? $_POST['board_slug'] : '';
$content   = trim(isset($_POST['content'])     ? $_POST['content']    : '');

// 댓글 조회
$comment = $db->fetch(
    "SELECT c.*, p.slug as post_slug FROM comments c
     JOIN posts p ON c.post_id = p.id
     WHERE c.id = ? AND c.post_id = ? AND c.status = 'active'",
    array($commentId, $postId)
);

$backUrl = SITE_URL . '/board/' . $boardSlug . '/' . $postId . '/' . ($comment['post_slug'] ?? '');

if (!$comment) {
    flash('댓글을 찾을 수 없습니다.', 'warning');
    redirect(SITE_URL . '/board/' . $boardSlug);
}

// 본인 확인
$currentMemberId = Auth::isMember() ? (int)Auth::getMember()['id'] : 0;
$currentAdminId  = Auth::isAdmin()  ? (int)Auth::getAdmin()['id']  : 0;

// 본인 댓글이거나 관리자이면 수정 가능
$isMine = ($currentMemberId && $currentMemberId == $comment['member_id'])
       || ($currentAdminId  && $currentAdminId  == $comment['admin_id'])
       || Auth::isAdmin();

if (!$isMine) {
    flash('본인 댓글만 수정할 수 있습니다.', 'danger');
    redirect($backUrl . '#comments');
}

if (!$content) {
    flash('댓글 내용을 입력해주세요.', 'warning');
    redirect($backUrl . '#comments');
}

$content = strip_tags($content);
$db->execute(
    "UPDATE comments SET content = ?, updated_at = NOW() WHERE id = ?",
    array($content, $commentId)
);

flash('댓글이 수정되었습니다.', 'success');
redirect($backUrl . '#comment-' . $commentId);
