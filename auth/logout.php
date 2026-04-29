<?php
/**
 * auth/logout.php — 로그아웃
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once dirname(__DIR__) . '/config/config.php';
Auth::adminLogout();
Auth::memberLogout();
flash('로그아웃 되었습니다.', 'success');
redirect(SITE_URL);
