<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>GET 파라미터</h3>";
echo "route: <b>" . htmlspecialchars($_GET['route'] ?? '(없음)') . "</b><br>";
echo "board_slug: <b>" . htmlspecialchars($_GET['board_slug'] ?? '(없음)') . "</b><br>";
echo "전체 GET: <pre>" . htmlspecialchars(print_r($_GET, true)) . "</pre>";

echo "<h3>REQUEST_URI</h3>";
echo htmlspecialchars($_SERVER['REQUEST_URI']) . "<br>";

echo "<h3>라우팅 시뮬레이션</h3>";
$route = isset($_GET['route']) ? trim($_GET['route'], '/') : '';
$parts = explode('/', $route);
echo "route값: <b>{$route}</b><br>";
echo "seg0: <b>" . ($parts[0] ?? '') . "</b><br>";
echo "seg1: <b>" . ($parts[1] ?? '') . "</b><br>";
echo "seg2: <b>" . ($parts[2] ?? '') . "</b><br>";

echo "<h3>DB 게시판 확인</h3>";
define('ROOT', __DIR__);
require_once __DIR__ . '/config/config.php';
$db = DB::getInstance();
$boards = $db->fetchAll("SELECT id, slug, name, is_active FROM boards");
echo "등록된 게시판:<br>";
foreach($boards as $b) {
    echo "- id={$b['id']} slug=<b>{$b['slug']}</b> name={$b['name']} active={$b['is_active']}<br>";
}

// free 게시판 직접 조회
$free = $db->fetch("SELECT * FROM boards WHERE slug = 'free'");
echo "<br>'free' 슬러그 조회: " . ($free ? "<b style='color:green'>찾음 (id={$free['id']})</b>" : "<b style='color:red'>없음!</b>") . "<br>";
