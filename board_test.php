<?php
// board_test.php — board/index.php 오류 진단용 (확인 후 삭제)
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT', __DIR__);

echo "<h3>Step 1: config 로드</h3>";
try {
    require_once ROOT . '/config/config.php';
    echo "config OK<br>";
} catch (Throwable $e) {
    die("config 오류: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}

echo "<h3>Step 2: DB 연결</h3>";
try {
    $db = DB::getInstance();
    echo "DB OK<br>";
} catch (Throwable $e) {
    die("DB 오류: " . $e->getMessage());
}

echo "<h3>Step 3: boards 테이블 조회</h3>";
try {
    $board = $db->fetch("SELECT * FROM boards WHERE slug = 'free' AND is_active = 1");
    if ($board) {
        echo "board 조회 OK: " . htmlspecialchars($board['name']) . "<br>";
        echo "type: " . $board['type'] . "<br>";
    } else {
        echo "<b>WARNING: 'free' 게시판이 DB에 없습니다!</b><br>";
        $all = $db->fetchAll("SELECT id, slug, name, is_active FROM boards");
        echo "등록된 게시판: <pre>" . print_r($all, true) . "</pre>";
    }
} catch (Throwable $e) {
    die("boards 조회 오류: " . $e->getMessage());
}

echo "<h3>Step 4: posts 테이블 조회</h3>";
try {
    if ($board) {
        $total = $db->count("SELECT COUNT(*) FROM posts p WHERE p.board_id = ? AND p.status = 'active' AND p.is_notice = 0", array($board['id']));
        echo "posts count OK: $total 개<br>";
    }
} catch (Throwable $e) {
    die("posts 조회 오류: " . $e->getMessage());
}

echo "<h3>Step 5: helpers 함수 확인</h3>";
echo "paginate: " . (function_exists('paginate') ? 'OK' : 'MISSING') . "<br>";
echo "e: " . (function_exists('e') ? 'OK' : 'MISSING') . "<br>";
echo "time_ago: " . (function_exists('time_ago') ? 'OK' : 'MISSING') . "<br>";
echo "Auth: " . (class_exists('Auth') ? 'OK' : 'MISSING') . "<br>";

echo "<h3>Step 6: header include 테스트</h3>";
\$pageTitle = '테스트';
\$useTurnstile = false;
try {
    ob_start();
    require ROOT . '/includes/header.php';
    $out = ob_get_clean();
    echo "header OK (" . strlen($out) . " bytes)<br>";
} catch (Throwable $e) {
    ob_end_clean();
    die("header 오류: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}

echo "<br><b>모든 단계 통과! board/index.php 직접 실행 테스트:</b><br>";

echo "<h3>Step 7: board/index.php include</h3>";
\$_GET['board_slug'] = 'free';
\$_GET['route'] = 'board/free';
try {
    ob_start();
    require ROOT . '/board/index.php';
    $out = ob_get_clean();
    echo "board/index.php OK (" . strlen($out) . " bytes)<br>";
    echo substr($out, 0, 500) . "...";
} catch (Throwable $e) {
    ob_end_clean();
    die("<b>board/index.php 오류:</b> " . $e->getMessage() . "<br>파일: " . $e->getFile() . "<br>라인: " . $e->getLine() . "<br><pre>" . $e->getTraceAsString() . "</pre>");
}
