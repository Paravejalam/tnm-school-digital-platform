-- Initial database bootstrap placeholder
-- Business tables will be introduced in later sprints.

CREATE DATABASE IF NOT EXISTS `tnm_school_platform` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `app_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_app_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
