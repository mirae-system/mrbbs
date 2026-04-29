-- ============================================
-- PHP 게시판 솔루션 DB 스키마
-- MariaDB 10.0.x / UTF-8
-- ============================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 관리자 계정 테이블
CREATE TABLE IF NOT EXISTS `admins` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)  NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(200) NOT NULL,
  `last_login` DATETIME     DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 기본 관리자 계정 2개 삽입
-- 비밀번호: admin1234 (운영 전 반드시 변경!)
INSERT INTO `admins` (`username`, `password`, `name`, `email`) VALUES
('admin1', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '관리자1', 'admin1@yourdomain.com'),
('admin2', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '관리자2', 'admin2@yourdomain.com');

-- 소셜 회원 테이블
CREATE TABLE IF NOT EXISTS `members` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider`    ENUM('google','naver') NOT NULL,
  `provider_id` VARCHAR(200) NOT NULL,
  `email`       VARCHAR(200) DEFAULT NULL,
  `name`        VARCHAR(100) DEFAULT NULL,
  `avatar`      VARCHAR(500) DEFAULT NULL,
  `is_banned`   TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login`  DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider` (`provider`, `provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 게시판 설정 테이블
CREATE TABLE IF NOT EXISTS `boards` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`            VARCHAR(100) NOT NULL UNIQUE COMMENT 'URL용 영문 슬러그',
  `name`            VARCHAR(100) NOT NULL COMMENT '게시판 이름',
  `type`            ENUM('general','gallery','qna') NOT NULL DEFAULT 'general',
  `description`     VARCHAR(500) DEFAULT NULL,
  `sort_order`      INT          NOT NULL DEFAULT 0,
  `use_comment`     TINYINT(1)   NOT NULL DEFAULT 1,
  `use_file`        TINYINT(1)   NOT NULL DEFAULT 1,
  `file_max_size`   INT          NOT NULL DEFAULT 10 COMMENT 'MB',
  `file_max_count`  INT          NOT NULL DEFAULT 5,
  `use_turnstile`   TINYINT(1)   NOT NULL DEFAULT 1,
  `use_secret`      TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '비밀글 허용',
  `write_auth`      ENUM('all','member','admin') NOT NULL DEFAULT 'all',
  `read_auth`       ENUM('all','member','admin') NOT NULL DEFAULT 'all',
  `posts_per_page`  INT          NOT NULL DEFAULT 15,
  `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 기본 게시판 3개
INSERT INTO `boards` (`slug`,`name`,`type`,`description`,`sort_order`,`use_turnstile`) VALUES
('free',    '자유게시판',   'general', '자유롭게 글을 작성하세요.',      1, 1),
('gallery', '갤러리',       'gallery', '사진과 이미지를 공유하세요.',     2, 1),
('qna',     'Q&A',          'qna',     '궁금한 점을 질문하고 답변받으세요.', 3, 1);

-- 게시글 테이블
CREATE TABLE IF NOT EXISTS `posts` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `board_id`    INT UNSIGNED NOT NULL,
  `member_id`   INT UNSIGNED DEFAULT NULL COMMENT 'NULL=비회원',
  `admin_id`    INT UNSIGNED DEFAULT NULL COMMENT '관리자 작성',
  `title`       VARCHAR(300) NOT NULL,
  `slug`        VARCHAR(400) NOT NULL COMMENT 'SEO URL 슬러그',
  `content`     MEDIUMTEXT   NOT NULL,
  `author_name` VARCHAR(100) NOT NULL,
  `author_email`VARCHAR(200) DEFAULT NULL,
  `password`    VARCHAR(255) DEFAULT NULL COMMENT '비회원 비밀번호',
  `is_secret`   TINYINT(1)   NOT NULL DEFAULT 0,
  `is_notice`   TINYINT(1)   NOT NULL DEFAULT 0,
  `is_answered` TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Q&A 답변여부',
  `views`       INT UNSIGNED NOT NULL DEFAULT 0,
  `thumbnail`   VARCHAR(500) DEFAULT NULL COMMENT '갤러리 대표이미지',
  `ip`          VARCHAR(45)  DEFAULT NULL,
  `status`      ENUM('active','deleted','blind') NOT NULL DEFAULT 'active',
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_board` (`board_id`, `status`, `created_at`),
  KEY `idx_slug`  (`slug`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 댓글 테이블
CREATE TABLE IF NOT EXISTS `comments` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id`     INT UNSIGNED NOT NULL,
  `member_id`   INT UNSIGNED DEFAULT NULL,
  `admin_id`    INT UNSIGNED DEFAULT NULL,
  `parent_id`   INT UNSIGNED DEFAULT NULL COMMENT '대댓글',
  `content`     TEXT         NOT NULL,
  `author_name` VARCHAR(100) NOT NULL,
  `is_answer`   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Q&A 공식답변',
  `ip`          VARCHAR(45)  DEFAULT NULL,
  `status`      ENUM('active','deleted') NOT NULL DEFAULT 'active',
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post` (`post_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 첨부파일 테이블
CREATE TABLE IF NOT EXISTS `files` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id`     INT UNSIGNED NOT NULL,
  `original_name` VARCHAR(300) NOT NULL,
  `saved_name`  VARCHAR(300) NOT NULL,
  `file_path`   VARCHAR(500) NOT NULL,
  `file_size`   INT UNSIGNED NOT NULL,
  `mime_type`   VARCHAR(100) DEFAULT NULL,
  `is_image`    TINYINT(1)   NOT NULL DEFAULT 0,
  `thumb_path`  VARCHAR(500) DEFAULT NULL,
  `download_cnt`INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 사이트 설정 테이블 (key-value)
CREATE TABLE IF NOT EXISTS `settings` (
  `skey`   VARCHAR(100) NOT NULL,
  `svalue` TEXT         DEFAULT NULL,
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`skey`, `svalue`) VALUES
('site_name',          '내 사이트'),
('site_description',   '사이트 설명을 입력하세요.'),
('site_url',           'https://yourdomain.com'),
('turnstile_site_key', ''),
('turnstile_secret',   ''),
('google_client_id',   ''),
('google_client_secret',''),
('naver_client_id',    ''),
('naver_client_secret',''),
('upload_path',        'uploads/'),
('admin_email',        'admin@yourdomain.com');
