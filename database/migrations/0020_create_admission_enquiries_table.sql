-- =============================================================================
-- Migration: 0020_create_admission_enquiries_table
-- Table    : admission_enquiries
-- Module   : AdmissionEnquiry
-- Depends  : system_settings
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `admission_enquiries` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `enquiry_number`        VARCHAR(50)     NOT NULL,
    `student_name`          VARCHAR(150)    NOT NULL,
    `date_of_birth`         DATE            NULL,
    `gender`                VARCHAR(20)     NULL,
    `parent_name`           VARCHAR(150)    NOT NULL,
    `parent_phone`          VARCHAR(30)     NOT NULL,
    `parent_email`          VARCHAR(255)    NULL,
    `applying_for_class`    VARCHAR(50)     NOT NULL,
    `previous_school`       VARCHAR(255)    NULL,
    `address`               TEXT            NULL,
    `city`                  VARCHAR(100)    NULL,
    `state`                 VARCHAR(100)    NULL,
    `pincode`                VARCHAR(20)     NULL,
    `message`               TEXT            NULL,
    `status`                VARCHAR(30)     NOT NULL DEFAULT 'new',
    `source`                VARCHAR(50)     NOT NULL DEFAULT 'website',
    `created_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`            TIMESTAMP       NULL DEFAULT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_admission_enquiries_number` (`enquiry_number`),
    KEY `idx_admission_enquiries_status` (`status`),
    KEY `idx_admission_enquiries_phone` (`parent_phone`),
    KEY `idx_admission_enquiries_class` (`applying_for_class`),
    KEY `idx_admission_enquiries_created_at` (`created_at`),
    KEY `idx_admission_enquiries_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Admission enquiries submitted through the school website and other channels.';
