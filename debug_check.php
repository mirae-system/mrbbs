<?php
/**
 * debug_check.php — 설치 환경 진단 파일
 * WARNING  확인 후 반드시 삭제하세요! (보안 위험)
 */

// 모든 오류 표시
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<style>body{font-family:monospace;padding:20px;} .ok{color:green} .fail{color:red} .warn{color:orange} h2{border-bottom:1px solid #ccc;padding-bottom:5px}</style>';
echo '<h1> 서버 환경 진단</h1>';

// ── 1. PHP 버전 ──────────────────────────────────────────────
echo '<h2>1. PHP 버전</h2>';
$phpVer = phpversion();
$ok = version_compare($phpVer, '7.3', '>=');
echo '<p class="' . ($ok ? 'ok' : 'fail') . '">PHP 버전: ' . $phpVer . ' ' . ($ok ? 'OK' : 'FAIL 7.3 이상 필요') . '</p>';

// ── 2. 필수 PHP 확장 ─────────────────────────────────────────
echo '<h2>2. PHP 확장 모듈</h2>';
$required = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'session', 'gd', 'openssl', 'fileinfo'];
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    echo '<p class="' . ($loaded ? 'ok' : 'fail') . '">' . $ext . ': ' . ($loaded ? 'OK 활성화' : 'FAIL 없음') . '</p>';
}

// ── 3. DB 연결 테스트 ────────────────────────────────────────
echo '<h2>3. 데이터베이스 연결</h2>';

// config.php 에서 설정값 직접 읽기 시도
$configFile = __DIR__ . '/config/config.php';
if (!file_exists($configFile)) {
    echo '<p class="fail">FAIL config/config.php 파일이 없습니다.</p>';
} else {
    // config.php 를 직접 파싱 (include 없이 상수값 추출)
    $configContent = file_get_contents($configFile);
    preg_match("/define\('DB_HOST',\s*'([^']+)'\)/", $configContent, $m1);
    preg_match("/define\('DB_NAME',\s*'([^']+)'\)/", $configContent, $m2);
    preg_match("/define\('DB_USER',\s*'([^']+)'\)/", $configContent, $m3);
    preg_match("/define\('DB_PASS',\s*'([^']+)'\)/", $configContent, $m4);

    $host = $m1[1] ?? '';
    $name = $m2[1] ?? '';
    $user = $m3[1] ?? '';
    $pass = $m4[1] ?? '';

    echo '<p>HOST: <b>' . htmlspecialchars($host) . '</b></p>';
    echo '<p>DB  : <b>' . htmlspecialchars($name) . '</b></p>';
    echo '<p>USER: <b>' . htmlspecialchars($user) . '</b></p>';

    if ($host === 'localhost' && $name === 'your_dbname') {
        echo '<p class="fail">FAIL config/config.php 의 DB 설정이 아직 기본값입니다! 실제 값으로 변경하세요.</p>';
    } else {
        try {
            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            echo '<p class="ok">OK DB 연결 성공!</p>';

            // 테이블 존재 확인
            $tables = ['admins','members','boards','posts','comments','files','settings'];
            echo '<p>테이블 확인:</p><ul>';
            foreach ($tables as $t) {
                $exists = $pdo->query("SHOW TABLES LIKE '$t'")->rowCount() > 0;
                echo '<li class="' . ($exists ? 'ok' : 'fail') . '">' . $t . ': ' . ($exists ? 'OK' : 'FAIL 없음 — install.sql 을 실행하세요') . '</li>';
            }
            echo '</ul>';
        } catch (PDOException $e) {
            echo '<p class="fail">FAIL DB 연결 실패: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p class="warn">→ config/config.php 의 DB_HOST / DB_NAME / DB_USER / DB_PASS 를 확인하세요.</p>';
        }
    }
}

// ── 4. 파일/디렉토리 존재 확인 ───────────────────────────────
echo '<h2>4. 필수 파일 존재 확인</h2>';
$files = [
    '.htaccess',
    'index.php',
    'config/config.php',
    'config/db.php',
    'core/Auth.php',
    'core/helpers.php',
    'core/Turnstile.php',
    'core/ApiClient.php',
    'includes/header.php',
    'includes/nav.php',
    'includes/footer.php',
];
foreach ($files as $f) {
    $exists = file_exists(__DIR__ . '/' . $f);
    echo '<p class="' . ($exists ? 'ok' : 'fail') . '">' . $f . ': ' . ($exists ? 'OK' : 'FAIL 없음') . '</p>';
}

// ── 5. 디렉토리 권한 ─────────────────────────────────────────
echo '<h2>5. 디렉토리 권한</h2>';
$dirs = ['uploads'];
foreach ($dirs as $d) {
    $path    = __DIR__ . '/' . $d;
    $exists  = is_dir($path);
    $writable= $exists && is_writable($path);
    echo '<p class="' . ($writable ? 'ok' : ($exists ? 'warn' : 'fail')) . '">'
       . $d . '/: '
       . ($writable ? 'OK 쓰기 가능' : ($exists ? 'WARNING 쓰기 불가 (chmod 755)' : 'FAIL 디렉토리 없음'))
       . '</p>';
}

// ── 6. .htaccess RewriteEngine 확인 ──────────────────────────
echo '<h2>6. .htaccess 확인</h2>';
$htaccess = __DIR__ . '/.htaccess';
if (file_exists($htaccess)) {
    $content = file_get_contents($htaccess);
    $hasRewrite = strpos($content, 'RewriteEngine') !== false;
    echo '<p class="' . ($hasRewrite ? 'ok' : 'fail') . '">'
       . 'RewriteEngine: ' . ($hasRewrite ? 'OK 설정됨' : 'FAIL 없음') . '</p>';
} else {
    echo '<p class="fail">FAIL .htaccess 파일이 없습니다.</p>';
}

// ── 7. session 확인 ──────────────────────────────────────────
echo '<h2>7. 세션 테스트</h2>';
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['_test'] = 'ok';
echo '<p class="' . ($_SESSION['_test'] === 'ok' ? 'ok' : 'fail') . '">세션: '
   . ($_SESSION['_test'] === 'ok' ? 'OK 정상' : 'FAIL 오류') . '</p>';

// ── 8. config.php require 테스트 ─────────────────────────────
echo '<h2>8. config.php 로드 테스트</h2>';
if (file_exists(__DIR__ . '/config/config.php')) {
    try {
        // 세션이 이미 시작됐으므로 오류 억제
        @define('ROOT', __DIR__);
        ob_start();
        @require_once __DIR__ . '/config/config.php';
        ob_end_clean();
        echo '<p class="ok">OK config.php 로드 성공</p>';
    } catch (Throwable $e) {
        ob_end_clean();
        echo '<p class="fail">FAIL config.php 오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p class="warn">파일: ' . $e->getFile() . ' 라인: ' . $e->getLine() . '</p>';
    }
}


// ── 9. CSRF / 세션 쿠키 상세 진단 ──────────────────────────
echo '<h2>9. CSRF & 세션 쿠키 진단</h2>';

// HTTPS 감지
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

echo '<p>현재 프로토콜: <b>' . ($isHttps ? 'HTTPS OK' : 'HTTP (SSL 없음 — 정상, secure=false 로 세션 동작)') . '</b></p>';

// 세션 ID 확인
echo '<p>세션 ID: <b>' . (session_id() ?: '없음 FAIL') . '</b></p>';
echo '<p>세션 상태: <b>' . (session_status() === PHP_SESSION_ACTIVE ? '활성 OK' : '비활성 FAIL') . '</b></p>';

// CSRF 토큰 생성/확인
if (!isset($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(16));
}
echo '<p>CSRF 토큰 세션 저장: <b class="ok">OK ' . substr($_SESSION['_csrf'], 0, 8) . '...</b></p>';

// 쿠키 파라미터 확인
$cp = session_get_cookie_params();
echo '<p>세션 쿠키 설정:</p><ul>';
echo '<li>secure: <b class="' . ($cp['secure'] ? ($isHttps ? 'ok' : 'warn') : 'ok') . '">'
   . ($cp['secure'] ? 'true' . ($isHttps ? ' (HTTPS OK)' : ' WARNING HTTP에서는 쿠키 전송 안 됨!') : 'false OK') . '</b></li>';
echo '<li>httponly: <b>' . ($cp['httponly'] ? 'true OK' : 'false') . '</b></li>';
echo '<li>lifetime: <b>' . $cp['lifetime'] . '초</b></li>';
echo '<li>path: <b>' . $cp['path'] . '</b></li>';
echo '</ul>';

// 세션 저장 경로 쓰기 확인
$savePath = session_save_path() ?: sys_get_temp_dir();
$writable = is_writable($savePath);
echo '<p>세션 저장 경로: <b>' . htmlspecialchars($savePath) . '</b> '
   . ($writable ? '<span class="ok">OK 쓰기 가능</span>' : '<span class="fail">FAIL 쓰기 불가</span>') . '</p>';

echo '<hr><p class="warn">WARNING 이 파일(debug_check.php)은 확인 후 즉시 삭제하세요!</p>';

