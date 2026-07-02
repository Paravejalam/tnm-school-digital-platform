-- =============================================================================
-- Migration: 0019_create_system_settings_table
-- Table    : system_settings
-- Module   : System
-- Extends  : database/schema/0001-bootstrap.sql (app_settings)
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `system_settings` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key`         VARCHAR(150)    NOT NULL,
    `value`       TEXT            NULL,
    `type`        ENUM('string','integer','boolean','json') NOT NULL DEFAULT 'string',
    `module`      VARCHAR(100)    NULL     DEFAULT NULL COMMENT 'Module that owns this setting',
    `description` TEXT            NULL,
    `is_public`   TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 = safe to expose to frontend',
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_system_settings_key` (`key`),
    KEY `idx_system_settings_module` (`module`),
    KEY `idx_system_settings_is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Application-wide configuration settings.';