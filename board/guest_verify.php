<?php
/**
 * board/guest_verify.php — 비회원 게시글 비밀번호 확인
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once ROOT . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(SITE_URL); }
if (!csrf_verify()) { flash('보안 오류.', 'danger'); redirect(SITE_URL); }

$db        = DB::getInstance();
$postId    = (int)(isset($_POST['post_id'])    ? $_POST['post_id']    : 0);
$boardSlug = isset($_POST['board_slug'])        ? $_POST['board_slug'] : '';
$password  = isset($_POST['password'])          ? $_POST['password']   : '';

$post = $db->fetch(
    "SELECT id, slug, password, board_id FROM posts
     WHERE id = ? AND status = 'active'",
    array($postId)
);

$backUrl = SITE_URL . '/board/' . $boardSlug . '/' . $postId . '/' . ($post['slug'] ?? '');

if (!$post) {
    flash('게시글을 찾을 수 없습니다.', 'warning');
    redirect(SITE_URL . '/board/' . $boardSlug);
}

// 비밀번호 검증
if (!$post['password'] || !password_verify($password, $post['password'])) {
    flash('비밀번호가 올바르지 않습니다.', 'danger');
    redirect($backUrl);
}

// 인증 성공 — 세션에 저장 (30분 유효)
$_SESSION['guest_verified_' . $postId]      = true;
$_SESSION['guest_verified_time_' . $postId] = time();

redirect($backUrl);
