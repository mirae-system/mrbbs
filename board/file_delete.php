<?php
/**
 * board/file_delete.php — 첨부파일 개별 삭제 (PHP 7.3)
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once ROOT . '/config/config.php';

$db        = DB::getInstance();
$fileId    = (int)(isset($_GET['id'])         ? $_GET['id']         : 0);
$postId    = (int)(isset($_GET['post_id'])     ? $_GET['post_id']    : 0);
$boardSlug = isset($_GET['board_slug'])         ? $_GET['board_slug'] : '';

$file = $db->fetch(
    "SELECT f.*, p.member_id, p.admin_id FROM files f
     JOIN posts p ON f.post_id = p.id
     WHERE f.id = ? AND f.post_id = ?",
    array($fileId, $postId)
);

if (!$file) {
    flash('파일을 찾을 수 없습니다.', 'warning');
    redirect(SITE_URL . '/board/' . $boardSlug);
}

$isAuthor = Auth::isAdmin() ||
            (Auth::isMember() && Auth::getMember()['id'] == $file['member_id']);
if (!$isAuthor) {
    flash('삭제 권한이 없습니다.', 'danger');
    redirect(SITE_URL . '/board/' . $boardSlug);
}

// ── 파일 실물 삭제 ───────────────────────────────────────────
function deletePhysicalFile($path) {
    if (empty($path)) return;
    // BASE_PATH 기준
    $full = rtrim(BASE_PATH, '/') . '/' . ltrim($path, '/');
    if (file_exists($full)) { @unlink($full); return; }
    // UPLOAD_PATH 기준 (uploads/ 제거 후)
    $rel = preg_replace('#^uploads/#', '', $path);
    $alt = rtrim(UPLOAD_PATH, '/') . '/' . $rel;
    if (file_exists($alt)) { @unlink($alt); }
}

deletePhysicalFile($file['file_path']);
deletePhysicalFile($file['thumb_path']);

// ── DB 삭제 ──────────────────────────────────────────────────
$db->execute("DELETE FROM files WHERE id = ?", array($fileId));

// 게시글 썸네일 갱신
$remaining = $db->fetch(
    "SELECT thumb_path FROM files WHERE post_id = ? AND is_image = 1 ORDER BY id ASC LIMIT 1",
    array($postId)
);
$db->execute(
    "UPDATE posts SET thumbnail = ? WHERE id = ?",
    array($remaining ? $remaining['thumb_path'] : null, $postId)
);

flash('파일이 삭제되었습니다.', 'success');
redirect(SITE_URL . '/edit/' . $boardSlug . '/' . $postId);
