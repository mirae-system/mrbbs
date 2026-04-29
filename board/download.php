<?php
/**
 * board/download.php — 파일 다운로드 (PHP 7.3)
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once ROOT . '/config/config.php';

$db     = DB::getInstance();
$fileId = (int)(isset($_GET['id']) ? $_GET['id'] : 0);

$file = $db->fetch("SELECT * FROM files WHERE id = ?", array($fileId));
if (!$file) {
    http_response_code(404);
    exit('파일을 찾을 수 없습니다.');
}

// 경로 해석 (BASE_PATH 기준 우선, 실패 시 UPLOAD_PATH 기준)
$fullPath = rtrim(BASE_PATH, '/') . '/' . ltrim($file['file_path'], '/');
if (!file_exists($fullPath)) {
    $rel      = preg_replace('#^uploads/#', '', $file['file_path']);
    $fullPath = rtrim(UPLOAD_PATH, '/') . '/' . $rel;
}

if (!file_exists($fullPath)) {
    http_response_code(404);
    exit('파일이 존재하지 않습니다.');
}

$db->execute("UPDATE files SET download_cnt = download_cnt + 1 WHERE id = ?", array($fileId));

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . rawurlencode($file['original_name']) . '"');
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: no-cache');
readfile($fullPath);
exit;
