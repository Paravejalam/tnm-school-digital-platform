-- =============================================================================
-- Migration: 0006_create_auth_tokens_table
-- Table    : auth_tokens
-- Module   : Auth
-- Depends  : users
-- SQL ref  : TokenRepository.php - storeToken, revokeToken
-- Authority: .github/AGENT.md
-- SECURITY : Tokens stored as opaque strings (hashed preferred in production).
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `auth_tokens` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `token`      VARCHAR(512)    NOT NULL,
    `revoked`    TINYINT(1)      NOT NULL DEFAULT 0,
    `expires_at` TIMESTAMP       NULL     DEFAULT NULL,
    `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_auth_tokens_user_id` (`user_id`),
    KEY `idx_auth_tokens_revoked` (`revoked`),
    KEY `idx_auth_tokens_expires_at` (`expires_at`),
    CONSTRAINT `fk_auth_tokens_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='JWT refresh token blacklist and revocation tracking.';
