<?php
/**
 * board/delete.php — 게시글 삭제 처리 (PHP 7.3)
 * 첨부파일 실물 + DB 함께 삭제
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once ROOT . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(SITE_URL); }
if (!csrf_verify()) { flash('보안 오류.', 'danger'); redirect(SITE_URL); }

$db        = DB::getInstance();
$postId    = (int)(isset($_POST['post_id'])    ? $_POST['post_id']    : 0);
$boardSlug = isset($_POST['board_slug'])        ? $_POST['board_slug'] : '';
$backUrl   = SITE_URL . '/board/' . $boardSlug;

$post = $db->fetch("SELECT * FROM posts WHERE id = ? AND status = 'active'", array($postId));
if (!$post) {
    flash('게시글을 찾을 수 없습니다.', 'warning');
    redirect($backUrl);
}

$isGuest = !$post['member_id'] && !$post['admin_id'];

// ── 권한 확인 ────────────────────────────────────────────────
if (Auth::isAdmin()) {
    // 관리자
} elseif (Auth::isMember() && Auth::getMember()['id'] == $post['member_id']) {
    // 본인 회원
} elseif ($isGuest) {
    $verified   = isset($_SESSION['guest_verified_' . $postId]) && $_SESSION['guest_verified_' . $postId];
    $verifyTime = isset($_SESSION['guest_verified_time_' . $postId]) ? $_SESSION['guest_verified_time_' . $postId] : 0;
    if (!$verified || (time() - $verifyTime) > 1800) {
        flash('비밀번호 확인이 필요합니다.', 'warning');
        redirect(SITE_URL . '/board/' . $boardSlug . '/' . $postId . '/' . $post['slug']);
    }
} else {
    flash('삭제 권한이 없습니다.', 'danger');
    redirect($backUrl);
}

// ── 본문 내 에디터 이미지 삭제 ─────────────────────────────
// image_upload.php 로 업로드된 인라인 이미지 삭제
delete_removed_images($post['content'], '');

// ── 첨부파일 실물 삭제 ───────────────────────────────────────
$files = $db->fetchAll("SELECT * FROM files WHERE post_id = ?", array($postId));

foreach ($files as $f) {
    // 원본 파일 — UPLOAD_PATH 기준으로 경로 조합
    if (!empty($f['file_path'])) {
        // file_path = 'uploads/2026/파일명' 형태
        // UPLOAD_PATH = BASE_PATH . '/uploads/'
        // 실제 경로 = BASE_PATH . '/' . file_path
        $fullPath = rtrim(BASE_PATH, '/') . '/' . ltrim($f['file_path'], '/');

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        } else {
            // 대안 경로: UPLOAD_PATH 직접 사용
            // file_path 에서 'uploads/' 를 제거한 나머지
            $rel = preg_replace('#^uploads/#', '', $f['file_path']);
            $altPath = rtrim(UPLOAD_PATH, '/') . '/' . $rel;
            if (file_exists($altPath)) {
                @unlink($altPath);
            }
        }
    }

    // 썸네일
    if (!empty($f['thumb_path'])) {
        $thumbFull = rtrim(BASE_PATH, '/') . '/' . ltrim($f['thumb_path'], '/');
        if (file_exists($thumbFull)) {
            @unlink($thumbFull);
        } else {
            $rel = preg_replace('#^uploads/#', '', $f['thumb_path']);
            $altPath = rtrim(UPLOAD_PATH, '/') . '/' . $rel;
            if (file_exists($altPath)) {
                @unlink($altPath);
            }
        }
    }
}

// ── DB 삭제 처리 ─────────────────────────────────────────────
$db->beginTransaction();
try {
    $db->execute("DELETE FROM files    WHERE post_id = ?", array($postId));
    $db->execute("UPDATE comments SET status = 'deleted' WHERE post_id = ?", array($postId));
    $db->execute("UPDATE posts    SET status = 'deleted' WHERE id = ?",      array($postId));
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    flash('삭제 중 오류가 발생했습니다.', 'danger');
    redirect($backUrl);
}

// 비회원 인증 세션 제거
unset($_SESSION['guest_verified_' . $postId]);
unset($_SESSION['guest_verified_time_' . $postId]);

flash('게시글이 삭제되었습니다.', 'success');
redirect($backUrl);
