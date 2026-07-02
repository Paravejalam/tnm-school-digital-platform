-- =============================================================================
-- Migration: 0002_create_permissions_table
-- Table    : permissions
-- Module   : Auth / RBAC
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `permissions` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150)    NOT NULL,
    `slug`        VARCHAR(150)    NOT NULL,
    `module`      VARCHAR(100)    NOT NULL COMMENT 'Domain module this permission belongs to',
    `description` TEXT            NULL,
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_permissions_slug` (`slug`),
    KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Granular permissions for RBAC.';
