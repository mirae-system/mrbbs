<?php
/**
 * core/Auth.php
 * 관리자 / 회원 인증 클래스 — PHP 7.3 호환
 */
class Auth
{
    // ── 관리자 ───────────────────────────────────────────────

    public static function adminLogin($username, $password) {
        $db    = DB::getInstance();
        $admin = $db->fetch("SELECT * FROM admins WHERE username = ?", array($username));
        if (!$admin) return false;
        if (!password_verify($password, $admin['password'])) return false;

        $_SESSION[ADMIN_SESSION_KEY] = array(
            'id'       => $admin['id'],
            'username' => $admin['username'],
            'name'     => $admin['name'],
            'email'    => $admin['email'],
        );
        $db->execute("UPDATE admins SET last_login = NOW() WHERE id = ?", array($admin['id']));
        session_regenerate_id(true);
        return true;
    }

    public static function isAdmin() {
        return !empty($_SESSION[ADMIN_SESSION_KEY]);
    }

    public static function getAdmin() {
        return isset($_SESSION[ADMIN_SESSION_KEY]) ? $_SESSION[ADMIN_SESSION_KEY] : null;
    }

    public static function requireAdmin() {
        if (!self::isAdmin()) {
            flash('관리자 로그인이 필요합니다.', 'warning');
            redirect(SITE_URL . '/admin/login.php');
        }
    }

    public static function adminLogout() {
        unset($_SESSION[ADMIN_SESSION_KEY]);
    }

    // ── 소셜 회원 ────────────────────────────────────────────

    public static function memberLoginOrCreate($provider, $providerId, $info = array()) {
        $db = DB::getInstance();
        $member = $db->fetch(
            "SELECT * FROM members WHERE provider = ? AND provider_id = ?",
            array($provider, $providerId)
        );
        if (!$member) {
            $id = $db->insert(
                "INSERT INTO members (provider, provider_id, email, name, avatar) VALUES (?,?,?,?,?)",
                array($provider, $providerId, isset($info['email']) ? $info['email'] : null,
                      isset($info['name']) ? $info['name'] : null,
                      isset($info['avatar']) ? $info['avatar'] : null)
            );
            $member = $db->fetch("SELECT * FROM members WHERE id = ?", array($id));
        } else {
            $db->execute("UPDATE members SET last_login = NOW() WHERE id = ?", array($member['id']));
        }

        if ($member['is_banned']) {
            flash('차단된 계정입니다.', 'danger');
            redirect(SITE_URL);
        }

        $_SESSION[MEMBER_SESSION_KEY] = array(
            'id'       => $member['id'],
            'provider' => $member['provider'],
            'name'     => $member['name'],
            'email'    => $member['email'],
            'avatar'   => $member['avatar'],
        );
        session_regenerate_id(true);
        return $member;
    }

    public static function isMember() {
        return !empty($_SESSION[MEMBER_SESSION_KEY]);
    }

    public static function getMember() {
        return isset($_SESSION[MEMBER_SESSION_KEY]) ? $_SESSION[MEMBER_SESSION_KEY] : null;
    }

    public static function requireMember() {
        if (!self::isMember()) {
            flash('로그인이 필요합니다.', 'warning');
            redirect(SITE_URL . '/auth/login.php');
        }
    }

    public static function memberLogout() {
        unset($_SESSION[MEMBER_SESSION_KEY]);
    }

    // ── 공통 ─────────────────────────────────────────────────

    public static function isLoggedIn() {
        return self::isAdmin() || self::isMember();
    }

    public static function canWrite($board) {
        $auth = $board['write_auth'];
        if ($auth === 'all')    return true;
        if ($auth === 'member') return self::isMember() || self::isAdmin();
        if ($auth === 'admin')  return self::isAdmin();
        return false;
    }

    public static function canRead($board) {
        $auth = $board['read_auth'];
        if ($auth === 'all')    return true;
        if ($auth === 'member') return self::isMember() || self::isAdmin();
        if ($auth === 'admin')  return self::isAdmin();
        return false;
    }
}
