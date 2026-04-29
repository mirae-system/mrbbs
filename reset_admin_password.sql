-- ============================================
-- 관리자 비밀번호 재설정 SQL
-- 이미 install.sql 을 실행한 경우 이 파일만 실행하세요.
-- 비밀번호: admin1234
-- ============================================

UPDATE `admins` SET `password` = '$2y$10$wnowVt/Jx/cL0YmrEX3CTu6boxJydeNRV.8mNkB71mQvPDuraeh.e' WHERE `username` = 'admin1';
UPDATE `admins` SET `password` = '$2y$10$VIQ/9p8..0uEyv/6./HtaeRCA1BAjGZKHwdZbKmLeOZCEEQwQmOye' WHERE `username` = 'admin2';

-- 적용 확인
SELECT id, username, name, email, last_login FROM admins;
