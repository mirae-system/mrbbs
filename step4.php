<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. PHP OK<br>";

// 세션 시작 테스트
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "2. 세션 OK (ID: " . session_id() . ")<br>";

// 상수 정의
define('DEBUG_MODE', true);
define('DB_HOST',    'localhost');
define('DB_NAME',    'your_dbname');  // ★ 실제 DB명
define('DB_USER',    'your_dbuser');  // ★ 실제 아이디
define('DB_PASS',    'your_dbpass');  // ★ 실제 비밀번호
define('DB_CHARSET', 'utf8mb4');
define('ROOT',       __DIR__);
define('BASE_PATH',  __DIR__);
define('SITE_URL',   'http://sav273.web25.kr');
define('SITE_NAME',  'test');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL',  SITE_URL . '/uploads/');
define('THUMB_WIDTH',  400);
define('THUMB_HEIGHT', 300);
define('ALLOWED_EXTENSIONS', array('jpg','jpeg','png'));
define('IMAGE_EXTENSIONS',   array('jpg','jpeg','png'));
define('SECRET_KEY', 'test');
define('SESSION_NAME', 'BSESS');
define('SESSION_LIFETIME', 7200);
define('ADMIN_SESSION_KEY', '_admin_auth');
define('MEMBER_SESSION_KEY', '_member_auth');
define('TURNSTILE_SITE_KEY', '');
define('TURNSTILE_SECRET', '');
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');
define('GOOGLE_REDIRECT_URI', '');
define('NAVER_CLIENT_ID', '');
define('NAVER_CLIENT_SECRET', '');
define('NAVER_REDIRECT_URI', '');

echo "3. 상수 정의 OK<br>";

require_once __DIR__ . '/config/db.php';
echo "4. db.php OK<br>";

require_once __DIR__ . '/core/helpers.php';
echo "5. helpers.php OK<br>";

require_once __DIR__ . '/core/Auth.php';
echo "6. Auth.php OK<br>";

require_once __DIR__ . '/core/Turnstile.php';
echo "7. Turnstile.php OK<br>";

require_once __DIR__ . '/core/ApiClient.php';
echo "8. ApiClient.php OK<br>";

// DB 연결
try {
    $db = DB::getInstance();
    echo "9. DB 연결 OK<br>";
    $boards = $db->fetchAll("SELECT id, slug, name, is_active FROM boards");
    echo "10. boards 조회 OK - " . count($boards) . "개<br>";
    foreach($boards as $b) {
        echo "&nbsp;&nbsp;- [{$b['id']}] {$b['slug']} / {$b['name']} (active={$b['is_active']})<br>";
    }
} catch(Exception $e) {
    echo "DB 오류: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<br><b>여기까지 OK이면 config.php 자체를 테스트:</b><br>";
