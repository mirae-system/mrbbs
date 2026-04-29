<?php
/**
 * admin/change_password.php — 관리자 비밀번호 변경
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/config/config.php';
Auth::requireAdmin();

$db    = DB::getInstance();
$admin = Auth::getAdmin();
$error = '';
$done  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = '보안 오류가 발생했습니다.';
    } else {
        $current = $_POST['current_password'] ?? '';
        $new1    = $_POST['new_password']     ?? '';
        $new2    = $_POST['new_password2']    ?? '';

        $row = $db->fetch("SELECT password FROM admins WHERE id = ?", array($admin['id']));

        if (!password_verify($current, $row['password'])) {
            $error = '현재 비밀번호가 올바르지 않습니다.';
        } elseif (strlen($new1) < 8) {
            $error = '새 비밀번호는 8자 이상이어야 합니다.';
        } elseif ($new1 !== $new2) {
            $error = '새 비밀번호가 일치하지 않습니다.';
        } else {
            $hash = password_hash($new1, PASSWORD_BCRYPT, array('cost' => 12));
            $db->execute("UPDATE admins SET password = ? WHERE id = ?", array($hash, $admin['id']));
            $done = true;
        }
    }
}

$adminPageTitle = '비밀번호 변경';
require __DIR__ . '/layout.php';
?>

<h5 class="fw-bold mb-4"> 비밀번호 변경</h5>

<div class="card border-0 shadow-sm" style="max-width:480px">
  <div class="card-body p-4">
    <?php if ($done): ?>
      <div class="alert alert-success">비밀번호가 변경되었습니다.</div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="alert alert-warning small py-2 mb-3">
      <strong>주의: 초기 비밀번호(admin1234)를 반드시 변경하세요!</strong>
    </div>

    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label small fw-bold">현재 비밀번호</label>
        <input type="password" name="current_password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label small fw-bold">새 비밀번호 (8자 이상)</label>
        <input type="password" name="new_password" class="form-control" minlength="8" required>
      </div>
      <div class="mb-4">
        <label class="form-label small fw-bold">새 비밀번호 확인</label>
        <input type="password" name="new_password2" class="form-control" minlength="8" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">변경하기</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/layout_end.php'; ?>
