<?php
/**
 * board/image_upload.php
 * TOAST UI Editor addImageBlobHook 전용 이미지 업로드 API
 * 응답: JSON { url: "이미지 URL" } 또는 { error: "메시지" }
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once ROOT . '/config/config.php';

// JSON 응답 헬퍼
function jsonOk($url) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('url' => $url), JSON_UNESCAPED_UNICODE);
    exit;
}
function jsonError($msg, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('error' => $msg), JSON_UNESCAPED_UNICODE);
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('잘못된 요청입니다.', 405);
}

// 로그인 확인 (비회원 이미지 업로드 허용 여부)
// 비회원도 글쓰기 가능한 게시판이 있으므로 세션만 확인
// CSRF 는 Ajax 요청이라 헤더로 확인
$csrfHeader = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
$csrfSession = isset($_SESSION['_csrf']) ? $_SESSION['_csrf'] : '';
if (empty($csrfHeader) || !hash_equals($csrfSession, $csrfHeader)) {
    jsonError('보안 토큰이 유효하지 않습니다.', 403);
}

// 파일 확인
if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errCode = isset($_FILES['image']['error']) ? $_FILES['image']['error'] : -1;
    $errMap  = array(
        UPLOAD_ERR_INI_SIZE   => '파일이 너무 큽니다 (서버 제한).',
        UPLOAD_ERR_FORM_SIZE  => '파일이 너무 큽니다.',
        UPLOAD_ERR_PARTIAL    => '파일이 일부만 업로드되었습니다.',
        UPLOAD_ERR_NO_FILE    => '파일이 선택되지 않았습니다.',
        UPLOAD_ERR_NO_TMP_DIR => '임시 디렉토리가 없습니다.',
        UPLOAD_ERR_CANT_WRITE => '파일 쓰기에 실패했습니다.',
    );
    jsonError(isset($errMap[$errCode]) ? $errMap[$errCode] : '업로드 오류가 발생했습니다.');
}

$file = $_FILES['image'];

// 이미지 타입 검증
$allowedMime = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
$allowedExt  = array('jpg', 'jpeg', 'png', 'gif', 'webp');

$mime = mime_content_type($file['tmp_name']);
if (!in_array($mime, $allowedMime)) {
    jsonError('이미지 파일만 업로드할 수 있습니다. (jpg, png, gif, webp)');
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt)) {
    jsonError('허용되지 않는 파일 형식입니다.');
}

// 파일 크기 제한 (10MB)
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    jsonError('이미지 크기는 10MB 이하여야 합니다.');
}

// 저장 디렉토리 (연도별)
$uploadDir = UPLOAD_PATH . date('Y') . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 고유 파일명 생성
$savedName = 'img_' . uniqid('', true) . '.' . $ext;
$savedPath = $uploadDir . $savedName;

if (!move_uploaded_file($file['tmp_name'], $savedPath)) {
    jsonError('파일 저장에 실패했습니다.', 500);
}

// 가로 1000px 초과 시 자동 리사이즈
resize_if_needed($savedPath);

// URL 반환
$url = SITE_URL . '/uploads/' . date('Y') . '/' . $savedName;
jsonOk($url);
