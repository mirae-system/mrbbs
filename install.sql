-- ============================================
-- PHP 게시판 솔루션 DB 스키마
-- MariaDB 10.0.x / UTF-8mb4 호환
-- ※ 767바이트 인덱스 제한 대응 버전
-- ============================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ── MariaDB 10.0.x 인덱스 제한 해제 ─────────────────────────
-- innodb_large_prefix 가 OFF 인 경우 아래 2줄로 세션 단위 활성화
-- (카페24 공유호스팅은 GLOBAL 변경 불가 → 컬럼 길이로 해결)
-- SET SESSION innodb_strict_mode = OFF;

-- ── 관리자 계정 테이블 ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admins` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)     NOT NULL,
  `password`   VARCHAR(255)    NOT NULL COMMENT 'bcrypt hash',
  `name`       VARCHAR(100)    NOT NULL,
  `email`      VARCHAR(200)    NOT NULL,
  `last_login` DATETIME        DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 기본 관리자 계정 2개 (비밀번호: admin1234 — 반드시 변경!)
INSERT INTO `admins` (`username`, `password`, `name`, `email`) VALUES
('admin1', '$2y$10$wnowVt/Jx/cL0YmrEX3CTu6boxJydeNRV.8mNkB71mQvPDuraeh.e', '관리자1', 'admin1@yourdomain.com'),
('admin2', '$2y$10$VIQ/9p8..0uEyv/6./HtaeRCA1BAjGZKHwdZbKmLeOZCEEQwQmOye', '관리자2', 'admin2@yourdomain.com');

-- ── 소셜 회원 테이블 ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `members` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `provider`    ENUM('google','naver') NOT NULL,
  `provider_id` VARCHAR(191)    NOT NULL,
  `email`       VARCHAR(191)    DEFAULT NULL,
  `name`        VARCHAR(100)    DEFAULT NULL,
  `avatar`      VARCHAR(500)    DEFAULT NULL,
  `is_banned`   TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login`  DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider` (`provider`, `provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 게시판 설정 테이블 ───────────────────────────────────────
-- slug: VARCHAR(100) → UNIQUE 인덱스 = 100 * 4 = 400바이트 < 767 OK
CREATE TABLE IF NOT EXISTS `boards` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `slug`            VARCHAR(100)    NOT NULL COMMENT 'URL용 영문 슬러그 (영숫자만)',
  `name`            VARCHAR(100)    NOT NULL COMMENT '게시판 이름',
  `type`            ENUM('general','gallery','qna') NOT NULL DEFAULT 'general',
  `description`     VARCHAR(500)    DEFAULT NULL,
  `sort_order`      INT             NOT NULL DEFAULT 0,
  `use_comment`     TINYINT(1)      NOT NULL DEFAULT 1,
  `use_file`        TINYINT(1)      NOT NULL DEFAULT 1,
  `file_max_size`   INT             NOT NULL DEFAULT 10 COMMENT 'MB',
  `file_max_count`  INT             NOT NULL DEFAULT 5,
  `use_turnstile`   TINYINT(1)      NOT NULL DEFAULT 1,
  `use_secret`      TINYINT(1)      NOT NULL DEFAULT 0,
  `write_auth`      ENUM('all','member','admin') NOT NULL DEFAULT 'all',
  `read_auth`       ENUM('all','member','admin') NOT NULL DEFAULT 'all',
  `posts_per_page`  INT             NOT NULL DEFAULT 15,
  `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 기본 게시판 3개
INSERT INTO `boards` (`slug`,`name`,`type`,`description`,`sort_order`,`use_turnstile`) VALUES
('free',    '자유게시판', 'general', '자유롭게 글을 작성하세요.',         1, 1),
('gallery', '갤러리',     'gallery', '사진과 이미지를 공유하세요.',        2, 1),
('qna',     'Q&A',        'qna',     '궁금한 점을 질문하고 답변받으세요.', 3, 1);

-- ── 게시글 테이블 ────────────────────────────────────────────
-- slug: VARCHAR(191) → UNIQUE 없이 일반 KEY(191) 사용 → 767바이트 이내
CREATE TABLE IF NOT EXISTS `posts` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `board_id`     INT UNSIGNED    NOT NULL,
  `member_id`    INT UNSIGNED    DEFAULT NULL,
  `admin_id`     INT UNSIGNED    DEFAULT NULL,
  `title`        VARCHAR(300)    NOT NULL,
  `slug`         VARCHAR(191)    NOT NULL COMMENT 'SEO URL 슬러그',
  `content`      MEDIUMTEXT      NOT NULL,
  `author_name`  VARCHAR(100)    NOT NULL,
  `author_email` VARCHAR(191)    DEFAULT NULL,
  `password`     VARCHAR(255)    DEFAULT NULL COMMENT '비회원 비밀번호',
  `is_secret`    TINYINT(1)      NOT NULL DEFAULT 0,
  `is_notice`    TINYINT(1)      NOT NULL DEFAULT 0,
  `is_answered`  TINYINT(1)      NOT NULL DEFAULT 0,
  `views`        INT UNSIGNED    NOT NULL DEFAULT 0,
  `thumbnail`    VARCHAR(500)    DEFAULT NULL,
  `ip`           VARCHAR(45)     DEFAULT NULL,
  `status`       ENUM('active','deleted','blind') NOT NULL DEFAULT 'active',
  `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME        DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_board_status` (`board_id`, `status`, `created_at`),
  KEY `idx_slug`         (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 댓글 테이블 ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `comments` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `post_id`     INT UNSIGNED    NOT NULL,
  `member_id`   INT UNSIGNED    DEFAULT NULL,
  `admin_id`    INT UNSIGNED    DEFAULT NULL,
  `parent_id`   INT UNSIGNED    DEFAULT NULL,
  `content`     TEXT            NOT NULL,
  `author_name` VARCHAR(100)    NOT NULL,
  `is_answer`   TINYINT(1)      NOT NULL DEFAULT 0,
  `ip`          VARCHAR(45)     DEFAULT NULL,
  `status`      ENUM('active','deleted') NOT NULL DEFAULT 'active',
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post` (`post_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 첨부파일 테이블 ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `files` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `post_id`       INT UNSIGNED    NOT NULL,
  `original_name` VARCHAR(255)    NOT NULL,
  `saved_name`    VARCHAR(255)    NOT NULL,
  `file_path`     VARCHAR(500)    DEFAULT NULL,
  `file_size`     INT UNSIGNED    NOT NULL DEFAULT 0,
  `mime_type`     VARCHAR(100)    DEFAULT NULL,
  `is_image`      TINYINT(1)      NOT NULL DEFAULT 0,
  `thumb_path`    VARCHAR(500)    DEFAULT NULL,
  `download_cnt`  INT UNSIGNED    NOT NULL DEFAULT 0,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 사이트 설정 테이블 ───────────────────────────────────────
-- skey: VARCHAR(100) → 100 * 4 = 400바이트 < 767 OK
CREATE TABLE IF NOT EXISTS `settings` (
  `skey`   VARCHAR(100)    NOT NULL,
  `svalue` TEXT            DEFAULT NULL,
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`skey`, `svalue`) VALUES
('site_name',           '내 사이트'),
('site_description',    '사이트 설명을 입력하세요.'),
('site_url',            'https://yourdomain.com'),
('turnstile_site_key',  ''),
('turnstile_secret',    ''),
('google_client_id',    ''),
('google_client_secret',''),
('naver_client_id',     ''),
('naver_client_secret', ''),
('upload_path',         'uploads/'),
('admin_email',         'admin@yourdomain.com');
