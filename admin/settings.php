<?php
/**
 * admin/settings.php — 사이트 설정 (PHP 7.3 호환)
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/config/config.php';
Auth::requireAdmin();

$db = DB::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash('보안 오류.', 'danger'); redirect(SITE_URL.'/admin/settings.php'); }

    $fields = array(
        'site_name', 'site_description', 'site_url', 'admin_email',
        'turnstile_site_key', 'turnstile_secret',
        'google_client_id', 'google_client_secret',
        'naver_client_id',  'naver_client_secret',
    );
    foreach ($fields as $key) {
        $val = trim(isset($_POST[$key]) ? $_POST[$key] : '');
        $db->execute("REPLACE INTO settings (skey, svalue) VALUES (?, ?)", array($key, $val));
    }
    flash('설정이 저장되었습니다.', 'success');
    redirect(SITE_URL . '/admin/settings.php');
}

$raw      = $db->fetchAll("SELECT skey, svalue FROM settings");
$settings = array();
foreach ($raw as $r) { $settings[$r['skey']] = $r['svalue']; }

$adminPageTitle = '사이트 설정';
require __DIR__ . '/layout.php';
?>

<h5 class="fw-bold mb-4"> 사이트 설정</h5>

<form method="post">
  <?= csrf_field() ?>

  <!-- 기본 정보 -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-bold border-bottom py-3">기본 정보</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-sm-6">
          <label class="form-label small fw-bold">사이트 이름</label>
          <input type="text" name="site_name" class="form-control form-control-sm"
                 value="<?= e(isset($settings['site_name']) ? $settings['site_name'] : '') ?>">
        </div>
        <div class="col-sm-6">
          <label class="form-label small fw-bold">사이트 URL</label>
          <input type="text" name="site_url" class="form-control form-control-sm"
                 value="<?= e(isset($settings['site_url']) ? $settings['site_url'] : '') ?>">
        </div>
        <div class="col-12">
          <label class="form-label small fw-bold">사이트 설명 (SEO)</label>
          <input type="text" name="site_description" class="form-control form-control-sm"
                 value="<?= e(isset($settings['site_description']) ? $settings['site_description'] : '') ?>">
        </div>
        <div class="col-sm-6">
          <label class="form-label small fw-bold">관리자 이메일</label>
          <input type="email" name="admin_email" class="form-control form-control-sm"
                 value="<?= e(isset($settings['admin_email']) ? $settings['admin_email'] : '') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- Cloudflare Turnstile -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
      <span class="fw-bold"> Cloudflare Turnstile (스팸 차단)</span>
      <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank"
         class="btn btn-sm btn-outline-warning py-0 px-2">키 발급하기 →</a>
    </div>
    <div class="card-body">
      <div class="alert alert-info small py-2 mb-3">
        <strong>발급 방법:</strong> Cloudflare 대시보드 로그인 →
        왼쪽 메뉴 <strong>Turnstile</strong> → <strong>Add Site</strong> →
        도메인 입력 → Widget type: <strong>Managed</strong> →
        <strong>Site Key</strong> / <strong>Secret Key</strong> 복사
      </div>
      <div class="row g-3">
        <div class="col-sm-6">
          <label class="form-label small fw-bold">Site Key (공개키 — 화면에 표시)</label>
          <input type="text" name="turnstile_site_key" class="form-control form-control-sm font-monospace"
                 value="<?= e(isset($settings['turnstile_site_key']) ? $settings['turnstile_site_key'] : '') ?>"
                 placeholder="0x4AAAAAAA...">
        </div>
        <div class="col-sm-6">
          <label class="form-label small fw-bold">Secret Key (비밀키 — 서버 전용)</label>
          <input type="text" name="turnstile_secret" class="form-control form-control-sm font-monospace"
                 value="<?= e(isset($settings['turnstile_secret']) ? $settings['turnstile_secret'] : '') ?>"
                 placeholder="0x4AAAAAAA...">
        </div>
      </div>
    </div>
  </div>

  <!-- Google OAuth -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
      <span class="fw-bold">Google OAuth 로그인</span>
      <a href="https://console.cloud.google.com/apis/credentials" target="_blank"
         class="btn btn-sm btn-outline-primary py-0 px-2">Google Console →</a>
    </div>
    <div class="card-body">
      <div class="alert alert-info small py-2 mb-3">
        <strong>설정 순서:</strong><br>
        1. Google Cloud Console → <strong>API 및 서비스</strong> → <strong>사용자 인증 정보</strong><br>
        2. <strong>+ 사용자 인증 정보 만들기</strong> → OAuth 2.0 클라이언트 ID<br>
        3. 애플리케이션 유형: <strong>웹 애플리케이션</strong><br>
        4. 승인된 리디렉션 URI: <code><?= e(SITE_URL) ?>/auth/google_callback.php</code>
      </div>
      <div class="row g-3">
        <div class="col-sm-6">
          <label class="form-label small fw-bold">Client ID</label>
          <input type="text" name="google_client_id" class="form-control form-control-sm font-monospace"
                 value="<?= e(isset($settings['google_client_id']) ? $settings['google_client_id'] : '') ?>"
                 placeholder="000000000-xxx.apps.googleusercontent.com">
        </div>
        <div class="col-sm-6">
          <label class="form-label small fw-bold">Client Secret</label>
          <input type="text" name="google_client_secret" class="form-control form-control-sm font-monospace"
                 value="<?= e(isset($settings['google_client_secret']) ? $settings['google_client_secret'] : '') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- Naver OAuth -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
      <span class="fw-bold">네이버 OAuth 로그인</span>
      <a href="https://developers.naver.com/apps/#/register" target="_blank"
         class="btn btn-sm btn-outline-success py-0 px-2">네이버 개발자 →</a>
    </div>
    <div class="card-body">
      <div class="alert alert-info small py-2 mb-3">
        <strong>설정 순서:</strong><br>
        1. 네이버 개발자센터 → <strong>Application 등록</strong><br>
        2. 사용 API: <strong>네아로(네이버 아이디로 로그인)</strong> 선택<br>
        3. 서비스 환경: <strong>PC웹</strong> → 서비스 URL: <code><?= e(SITE_URL) ?></code><br>
        4. Callback URL: <code><?= e(SITE_URL) ?>/auth/naver_callback.php</code>
      </div>
      <div class="row g-3">
        <div class="col-sm-6">
          <label class="form-label small fw-bold">Client ID</label>
          <input type="text" name="naver_client_id" class="form-control form-control-sm font-monospace"
                 value="<?= e(isset($settings['naver_client_id']) ? $settings['naver_client_id'] : '') ?>">
        </div>
        <div class="col-sm-6">
          <label class="form-label small fw-bold">Client Secret</label>
          <input type="text" name="naver_client_secret" class="form-control form-control-sm font-monospace"
                 value="<?= e(isset($settings['naver_client_secret']) ? $settings['naver_client_secret'] : '') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="text-end">
    <button type="submit" class="btn btn-primary px-5">설정 저장</button>
  </div>
</form>

<?php require __DIR__ . '/layout_end.php'; ?>
