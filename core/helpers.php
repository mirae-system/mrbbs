<?php
/**
 * core/helpers.php
 * 전역 헬퍼 함수 모음 — PHP 7.3 호환
 */

// ── 출력 보안 ────────────────────────────────────────────────
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// ── CSRF 토큰 ────────────────────────────────────────────────
function csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify() {
    if (session_status() !== PHP_SESSION_ACTIVE) return false;
    $token     = isset($_POST['_csrf'])        ? $_POST['_csrf']        : '';
    $sessToken = isset($_SESSION['_csrf'])     ? $_SESSION['_csrf']     : '';
    if (empty($sessToken) || empty($token))   return false;
    return hash_equals($sessToken, $token);
}

// ── SEO 슬러그 생성 ─────────────────────────────────────────
function make_slug($str, $maxLen = 200) {
    $str = mb_strtolower(trim($str), 'UTF-8');
    $str = preg_replace('/[^\w\x{AC00}-\x{D7A3}\x{1100}-\x{11FF}]+/u', '-', $str);
    $str = trim($str, '-');
    return mb_substr($str, 0, $maxLen, 'UTF-8');
}

// ── 페이징 계산 ──────────────────────────────────────────────
function paginate($total, $perPage, $current) {
    $totalPages = max(1, (int)ceil($total / $perPage));
    $current    = max(1, min($current, $totalPages));
    $offset     = ($current - 1) * $perPage;
    $block      = 10;
    $startPage  = (int)(floor(($current - 1) / $block) * $block) + 1;
    $endPage    = min($startPage + $block - 1, $totalPages);
    return compact('total','perPage','current','totalPages','offset','startPage','endPage');
}

// ── 파일 크기 포맷 ───────────────────────────────────────────
function format_bytes($bytes) {
    $bytes = (int)$bytes;
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

// ── 날짜 포맷 ────────────────────────────────────────────────
function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return '방금 전';
    if ($diff < 3600)   return (int)($diff/60) . '분 전';
    if ($diff < 86400)  return (int)($diff/3600) . '시간 전';
    if ($diff < 604800) return (int)($diff/86400) . '일 전';
    return date('Y.m.d', strtotime($datetime));
}

function format_date($datetime, $fmt = 'Y.m.d') {
    return date($fmt, strtotime($datetime));
}

// ── IP 추출 ──────────────────────────────────────────────────
function get_ip() {
    foreach (array('HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR') as $key) {
        if (!empty($_SERVER[$key])) {
            return explode(',', $_SERVER[$key])[0];
        }
    }
    return '0.0.0.0';
}

// ── 리다이렉트 ──────────────────────────────────────────────
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// ── 플래시 메시지 ────────────────────────────────────────────
function flash($msg, $type = 'success') {
    $_SESSION['_flash'] = array('msg' => $msg, 'type' => $type);
}

function get_flash() {
    if (!empty($_SESSION['_flash'])) {
        $f = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $f;
    }
    return null;
}

// ── 썸네일 생성 (GD) ────────────────────────────────────────
function create_thumbnail($srcPath, $destPath, $width = THUMB_WIDTH, $height = THUMB_HEIGHT) {
    if (!function_exists('imagecreatefromjpeg')) return false;
    $info = @getimagesize($srcPath);
    if (!$info) return false;

    $sw   = $info[0];
    $sh   = $info[1];
    $type = $info[2];

    if ($type === IMAGETYPE_JPEG)      $src = imagecreatefromjpeg($srcPath);
    elseif ($type === IMAGETYPE_PNG)   $src = imagecreatefrompng($srcPath);
    elseif ($type === IMAGETYPE_GIF)   $src = imagecreatefromgif($srcPath);
    elseif ($type === IMAGETYPE_WEBP)  $src = imagecreatefromwebp($srcPath);
    else return false;

    if (!$src) return false;

    $ratio = min($width / $sw, $height / $sh);
    $dw = (int)($sw * $ratio);
    $dh = (int)($sh * $ratio);

    $dst = imagecreatetruecolor($dw, $dh);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $dw, $dh, $sw, $sh);
    imagejpeg($dst, $destPath, 85);
    imagedestroy($src);
    imagedestroy($dst);
    return true;
}

// ── meta description 자동 추출 ──────────────────────────────
function excerpt($html, $len = 150) {
    $text = strip_tags($html);
    $text = preg_replace('/\s+/', ' ', $text);
    return mb_substr(trim($text), 0, $len, 'UTF-8');
}

// ── 현재 URL ────────────────────────────────────────────────
function current_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// ── JSON 응답 (API용) ────────────────────────────────────────
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── board type 아이콘 헬퍼 ──────────────────────────────────
function board_type_icon($type) {
    if ($type === 'gallery') return '갤러리';
    if ($type === 'qna')     return 'Q&A';
    return '일반';
}

// ── post status 배지 색상 ───────────────────────────────────
function status_color($status) {
    if ($status === 'active')  return 'success';
    if ($status === 'deleted') return 'secondary';
    return 'warning';
}

function status_label($status) {
    if ($status === 'active')  return '활성';
    if ($status === 'deleted') return '삭제';
    if ($status === 'blind')   return '블라인드';
    return $status;
}

// ── 이미지 리사이즈 함수 ────────────────────────────────────
/**
 * 이미지 가로가 $maxWidth 초과 시 자동 리사이즈
 * 원본 파일을 덮어씁니다.
 * ★ 최대 가로 변경: config.php 의 IMAGE_MAX_WIDTH 값을 수정하세요.
 *
 * @param string $filepath  실제 파일 경로
 * @param int    $maxWidth  최대 가로 픽셀 (기본: IMAGE_MAX_WIDTH 상수)
 * @return bool  리사이즈 실행 여부
 */
function resize_if_needed($filepath, $maxWidth = 0) {
    // 상수 기본값 적용
    if ($maxWidth <= 0) {
        $maxWidth = defined('IMAGE_MAX_WIDTH') ? IMAGE_MAX_WIDTH : 1000;
    }

    // GD 확장 없으면 건너뜀
    if (!function_exists('imagecreatefromjpeg')) return false;

    // 이미지 정보 확인
    $info = @getimagesize($filepath);
    if (!$info) return false;

    $origWidth  = $info[0];
    $origHeight = $info[1];
    $type       = $info[2];

    // 지원 타입 확인
    $supported = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP);
    if (!in_array($type, $supported)) return false;

    // 가로가 최대값 이하면 리사이즈 불필요
    if ($origWidth <= $maxWidth) return false;

    // 비율 유지 크기 계산
    $ratio     = $maxWidth / $origWidth;
    $newWidth  = $maxWidth;
    $newHeight = (int)round($origHeight * $ratio);

    // 원본 이미지 로드
    switch ($type) {
        case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg($filepath); break;
        case IMAGETYPE_PNG:  $src = @imagecreatefrompng($filepath);  break;
        case IMAGETYPE_GIF:  $src = @imagecreatefromgif($filepath);  break;
        case IMAGETYPE_WEBP: $src = @imagecreatefromwebp($filepath); break;
        default: return false;
    }
    if (!$src) return false;

    // 새 캔버스 생성
    $dst = imagecreatetruecolor($newWidth, $newHeight);
    if (!$dst) { imagedestroy($src); return false; }

    // PNG/WebP 투명도 유지
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // 리사이즈 (고품질)
    imagecopyresampled(
        $dst, $src,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $origWidth, $origHeight
    );

    // 원본 파일 덮어쓰기 저장
    $saved = false;
    switch ($type) {
        case IMAGETYPE_JPEG: $saved = imagejpeg($dst, $filepath, 85); break;
        case IMAGETYPE_PNG:  $saved = imagepng($dst,  $filepath, 6);  break;
        case IMAGETYPE_GIF:  $saved = imagegif($dst,  $filepath);     break;
        case IMAGETYPE_WEBP: $saved = imagewebp($dst, $filepath, 85); break;
    }

    // 메모리 해제
    imagedestroy($src);
    imagedestroy($dst);

    return $saved;
}

// ── 파일 업로드 공통 함수 ────────────────────────────────────
function handleFileUpload($postId, $board, $files, $db) {
    $maxSize  = $board['file_max_size'] * 1024 * 1024;
    $maxCount = $board['file_max_count'];
    $uploadDir = UPLOAD_PATH . date('Y') . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $count      = 0;
    $firstImage = null;

    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        if ($count >= $maxCount) break;
        if ($files['size'][$i] > $maxSize) continue;

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXTENSIONS)) continue;

        $savedName = uniqid('f_', true) . '.' . $ext;
        $savedPath = $uploadDir . $savedName;
        $relPath   = 'uploads/' . date('Y') . '/' . $savedName;

        if (!move_uploaded_file($files['tmp_name'][$i], $savedPath)) continue;

        $isImage   = in_array($ext, IMAGE_EXTENSIONS);
        $thumbPath = null;

        if ($isImage) {
            // 가로 1000px 초과 시 자동 리사이즈 (원본 파일 덮어쓰기)
            resize_if_needed($savedPath);

            $thumbName = 'thumb_' . $savedName;
            $thumbFull = $uploadDir . $thumbName;
            if (create_thumbnail($savedPath, $thumbFull)) {
                $thumbPath = 'uploads/' . date('Y') . '/' . $thumbName;
                if (!$firstImage) $firstImage = $thumbPath;
            }
        }

        // 리사이즈 후 실제 파일 크기 (원본 크기 아님)
        $actualSize = file_exists($savedPath) ? filesize($savedPath) : $files['size'][$i];

        $db->execute(
            "INSERT INTO files (post_id, original_name, saved_name, file_path, file_size, mime_type, is_image, thumb_path) VALUES (?,?,?,?,?,?,?,?)",
            array($postId, $name, $savedName, $relPath, $actualSize, $files['type'][$i], $isImage ? 1 : 0, $thumbPath)
        );
        $count++;
    }

    if ($firstImage) {
        $db->execute("UPDATE posts SET thumbnail = ? WHERE id = ?", array($firstImage, $postId));
    }
}

// ── 에디터 이미지 관리 함수 ──────────────────────────────────

/**
 * HTML 본문에서 img src URL 목록 추출
 * @param string $html
 * @return array URL 배열
 */
function extract_image_urls($html) {
    $urls = array();
    if (empty($html)) return $urls;
    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
    if (!empty($matches[1])) {
        $urls = array_unique($matches[1]);
    }
    return array_values($urls);
}

/**
 * SITE_URL/uploads/ 로 시작하는 URL 만 필터링 (자체 서버 이미지)
 * @param array $urls
 * @return array
 */
function filter_local_image_urls($urls) {
    $result = array();
    foreach ($urls as $url) {
        if (strpos($url, SITE_URL . '/uploads/') === 0) {
            $result[] = $url;
        }
    }
    return $result;
}

/**
 * URL → 실제 파일 경로 변환
 * http://domain.com/uploads/2026/img_xxx.png → /서버경로/uploads/2026/img_xxx.png
 * @param string $url
 * @return string|null
 */
function url_to_filepath($url) {
    $base = SITE_URL . '/uploads/';
    if (strpos($url, $base) !== 0) return null;
    $rel = substr($url, strlen($base)); // 2026/img_xxx.png
    return rtrim(UPLOAD_PATH, '/') . '/' . $rel;
}

/**
 * 이전 HTML → 새 HTML 비교 후 제거된 서버 이미지 파일 삭제
 * @param string $oldHtml  수정 전 본문
 * @param string $newHtml  수정 후 본문
 */
function delete_removed_images($oldHtml, $newHtml) {
    $oldUrls = filter_local_image_urls(extract_image_urls($oldHtml));
    $newUrls = filter_local_image_urls(extract_image_urls($newHtml));

    // 이전에는 있었는데 새 본문에 없는 URL = 삭제된 이미지
    $removedUrls = array_diff($oldUrls, $newUrls);

    foreach ($removedUrls as $url) {
        $filepath = url_to_filepath($url);
        if ($filepath && file_exists($filepath)) {
            @unlink($filepath);
        }
    }
}
