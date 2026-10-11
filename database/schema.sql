-- =============================================================================
--  WordsMem — database schema / 数据库表结构
-- =============================================================================
--  Load into an empty database / 在空数据库中执行：
--
--      mysql -u root -p wordsmem < database/schema.sql
--
--  Then import a word list / 然后导入一份词表：
--
--      php scripts/import_words.php data/demo-wordlist.csv "Demo list" demo
--
--  No word data ships with this schema. Bring your own list — see
--  docs/INSTALL.md and data/demo-wordlist.csv for the expected format.
--  本文件不含任何词库数据。词表请自行准备，格式见 docs/INSTALL.md 和
--  data/demo-wordlist.csv。
--
--  utf8mb4 / utf8mb4_unicode_ci throughout — works on MySQL 5.7+, MySQL 8
--  and MariaDB 10.3+.
--  全库 utf8mb4 / utf8mb4_unicode_ci，MySQL 5.7+、MySQL 8、MariaDB 10.3+ 通用。
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Word banks and words / 词库与单词
-- A word bank is one importable list. Words belong to exactly one bank;
-- the same headword may appear in several banks.
-- 一个「词库」就是一份可导入的词表。单词归属于某一个词库，同一个词可以
-- 出现在多个词库里。
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `word_banks` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `word_banks_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `words` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `word_bank_id` bigint unsigned NOT NULL,
  `rank` int unsigned DEFAULT NULL,
  `headword` varchar(255) NOT NULL,
  `us_phone` varchar(255) DEFAULT NULL,
  `uk_phone` varchar(255) DEFAULT NULL,
  `translation_cn` text,
  `definition_en` text,
  `example_en` text,
  `example_cn` text,
  `root_note` text,
  `synonyms` json DEFAULT NULL,
  `phrases` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `words_word_bank_id_headword_unique` (`word_bank_id`,`headword`),
  CONSTRAINT `words_word_bank_id_foreign` FOREIGN KEY (`word_bank_id`) REFERENCES `word_banks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Accounts / 账号
-- Passwords are bcrypt hashes written by password_hash().
-- 密码是 password_hash() 生成的 bcrypt 哈希。
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Per-user learning state and preferences / 每个用户的学习状态与偏好
-- user_word_states is the spaced-repetition record: one row per user per
-- word, holding the SM-2 ease factor, interval and next due date.
-- user_word_states 是间隔重复的核心记录：每个用户每个单词一行，存放
-- SM-2 的难度系数、间隔天数和下次到期时间。
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_settings` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `word_bank_id` bigint unsigned DEFAULT NULL,
  `daily_new_words` int unsigned NOT NULL DEFAULT '15',
  `daily_review_limit` int unsigned NOT NULL DEFAULT '100',
  `auto_reveal` tinyint(1) NOT NULL DEFAULT '1',
  `show_us_phone` tinyint(1) NOT NULL DEFAULT '1',
  `show_uk_phone` tinyint(1) NOT NULL DEFAULT '0',
  `show_definition_en` tinyint(1) NOT NULL DEFAULT '1',
  `show_example` tinyint(1) NOT NULL DEFAULT '1',
  `show_root_note` tinyint(1) NOT NULL DEFAULT '1',
  `show_synonyms` tinyint(1) NOT NULL DEFAULT '1',
  `show_phrases` tinyint(1) NOT NULL DEFAULT '0',
  `show_similar_words` tinyint(1) NOT NULL DEFAULT '1',
  `show_semantic_tags` tinyint(1) NOT NULL DEFAULT '1',
  `auto_play_audio` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `fk_us_bank` (`word_bank_id`),
  CONSTRAINT `fk_us_bank` FOREIGN KEY (`word_bank_id`) REFERENCES `word_banks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_us_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_word_states` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `word_id` bigint unsigned NOT NULL,
  `status` enum('new','learning','review','suspended') NOT NULL DEFAULT 'new',
  `ease_factor` float NOT NULL DEFAULT '2.5',
  `interval_days` int unsigned NOT NULL DEFAULT '0',
  `repetitions` int unsigned NOT NULL DEFAULT '0',
  `lapses` int unsigned NOT NULL DEFAULT '0',
  `due_at` datetime DEFAULT NULL,
  `last_reviewed_at` datetime DEFAULT NULL,
  `last_grade` tinyint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_word` (`user_id`,`word_id`),
  KEY `idx_user_status_due` (`user_id`,`status`,`due_at`),
  KEY `fk_uws_word` (`word_id`),
  CONSTRAINT `fk_uws_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uws_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Semantic grouping: subject categories and roots/affixes / 语义分组：学科分类与词根词缀
-- Both are derived from word content by the scripts in scripts/, not
-- entered by hand. Re-run those scripts after importing a new bank.
-- 这两组数据由 scripts/ 下的脚本从单词内容中自动生成，不是手工录入的。
-- 导入新词库后重新跑一遍脚本即可。
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `categories` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `word_categories` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `word_id` bigint unsigned NOT NULL,
  `category_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_word_cat` (`word_id`,`category_id`),
  KEY `fk_wc_cat` (`category_id`),
  CONSTRAINT `fk_wc_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wc_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `word_roots` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `word_id` bigint unsigned NOT NULL,
  `root_text` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_root_text` (`root_text`),
  KEY `idx_word` (`word_id`),
  CONSTRAINT `fk_wr_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
