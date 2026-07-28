-- =============================================================================
-- Seeder: 0003_seed_demo_classes.sql
-- Inserts: Standard class grades 1-12 for the 2026-2027 academic session
-- Authority: .github/AGENT.md
-- Order: Run AFTER 0002_seed_admin_user.sql (requires academic_session id=1)
-- =============================================================================

USE `tnm_school_platform`;

INSERT IGNORE INTO `academic_classes` (`id`, `class_name`, `grade_level`, `academic_session_id`, `status`) VALUES
(1,  'Class 1',  1,  1, 'active'),
(2,  'Class 2',  2,  1, 'active'),
(3,  'Class 3',  3,  1, 'active'),
(4,  'Class 4',  4,  1, 'active'),
(5,  'Class 5',  5,  1, 'active'),
(6,  'Class 6',  6,  1, 'active'),
(7,  'Class 7',  7,  1, 'active'),
(8,  'Class 8',  8,  1, 'active'),
(9,  'Class 9',  9,  1, 'active'),
(10, 'Class 10', 10, 1, 'active'),
(11, 'Class 11', 11, 1, 'active'),
(12, 'Class 12', 12, 1, 'active');
