<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ★ 아래 3줄을 실제 DB 정보로 수정하세요
$host = 'localhost';
$name = 'adm_sav273';   // 실제 DB명
$user = 'adm_sav273';   // 실제 DB 아이디
$pass = 'sej141127kr!';   // 실제 DB 비밀번호

echo "DB 연결 시도중...<br>";
try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$name};charset=utf8mb4",
        $user, $pass,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    echo "DB 연결 성공!<br>";
    $rows = $pdo->query("SELECT slug, name FROM boards")->fetchAll(PDO::FETCH_ASSOC);
    echo "boards: " . count($rows) . "개<br>";
    foreach($rows as $r) echo "- {$r['slug']}: {$r['name']}<br>";
} catch(PDOException $e) {
    echo "DB 오류: " . $e->getMessage();
}
