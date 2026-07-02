-- =============================================================================
-- Migration: 0018_create_audit_logs_table
-- Table    : audit_logs
-- Module   : System
-- Depends  : users
-- Authority: .github/AGENT.md (Section 24.9 - Audit Logging)
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NULL     DEFAULT NULL,
    `action`      VARCHAR(100)    NOT NULL COMMENT 'e.g. created, updated, deleted, login, logout',
    `entity_type` VARCHAR(100)    NOT NULL COMMENT 'e.g. Student, Teacher, User',
    `entity_id`   BIGINT UNSIGNED NULL     DEFAULT NULL,
    `old_values`  JSON            NULL     DEFAULT NULL,
    `new_values`  JSON            NULL     DEFAULT NULL,
    `ip_address`  VARCHAR(45)     NULL     DEFAULT NULL COMMENT 'IPv4 or IPv6',
    `user_agent`  VARCHAR(512)    NULL     DEFAULT NULL,
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_logs_user_id` (`user_id`),
    KEY `idx_audit_logs_entity` (`entity_type`, `entity_id`),
    KEY `idx_audit_logs_action` (`action`),
    KEY `idx_audit_logs_created_at` (`created_at`),
    CONSTRAINT `fk_audit_logs_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Immutable audit trail for all significant actions.';