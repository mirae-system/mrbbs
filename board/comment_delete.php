<?php
/**
 * board/comment_delete.php — 댓글 삭제 (본인만)
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

// 댓글 조회
$comment = $db->fetch(
    "SELECT c.*, p.slug as post_slug, p.is_answered
     FROM comments c JOIN posts p ON c.post_id = p.id
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

// 본인 댓글이거나 관리자이면 삭제 가능
$isMine = ($currentMemberId && $currentMemberId == $comment['member_id'])
       || ($currentAdminId  && $currentAdminId  == $comment['admin_id'])
       || Auth::isAdmin();

if (!$isMine) {
    flash('본인 댓글만 삭제할 수 있습니다.', 'danger');
    redirect($backUrl . '#comments');
}

// 소프트 삭제
$db->execute("UPDATE comments SET status = 'deleted' WHERE id = ?", array($commentId));

// Q&A 공식 답변이었으면 answered 상태 되돌리기
if ($comment['is_answer']) {
    $remaining = $db->count(
        "SELECT COUNT(*) FROM comments WHERE post_id = ? AND is_answer = 1 AND status = 'active'",
        array($postId)
    );
    if ($remaining === 0) {
        $db->execute("UPDATE posts SET is_answered = 0 WHERE id = ?", array($postId));
    }
}

flash('댓글이 삭제되었습니다.', 'success');
redirect($backUrl . '#comments');
