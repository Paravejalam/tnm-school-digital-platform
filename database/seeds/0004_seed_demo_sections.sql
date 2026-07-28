-- =============================================================================
-- Seeder: 0004_seed_demo_sections.sql
-- Inserts: Standard sections (A, B) for each class grade
-- Authority: .github/AGENT.md
-- Order: Run AFTER 0003_seed_demo_classes.sql (requires academic_class rows)
-- =============================================================================

USE `tnm_school_platform`;

INSERT IGNORE INTO `sections` (`id`, `name`, `capacity`, `class_id`, `status`) VALUES
(1,  'A', 40, 1,  'active'),
(2,  'B', 40, 1,  'active'),
(3,  'A', 40, 2,  'active'),
(4,  'B', 40, 2,  'active'),
(5,  'A', 40, 3,  'active'),
(6,  'B', 40, 3,  'active'),
(7,  'A', 40, 4,  'active'),
(8,  'B', 40, 4,  'active'),
(9,  'A', 40, 5,  'active'),
(10, 'B', 40, 5,  'active'),
(11, 'A', 40, 6,  'active'),
(12, 'B', 40, 6,  'active'),
(13, 'A', 40, 7,  'active'),
(14, 'B', 40, 7,  'active'),
(15, 'A', 40, 8,  'active'),
(16, 'B', 40, 8,  'active'),
(17, 'A', 40, 9,  'active'),
(18, 'B', 40, 9,  'active'),
(19, 'A', 40, 10, 'active'),
(20, 'B', 40, 10, 'active'),
(21, 'A', 40, 11, 'active'),
(22, 'B', 40, 11, 'active'),
(23, 'A', 40, 12, 'active'),
(24, 'B', 40, 12, 'active');
