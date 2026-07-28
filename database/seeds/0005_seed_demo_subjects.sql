-- =============================================================================
-- Seeder: 0005_seed_demo_subjects.sql
-- Inserts: Core school subjects catalogue
-- Authority: .github/AGENT.md
-- Order: Run AFTER migrations complete (no FK dependencies)
-- =============================================================================

USE `tnm_school_platform`;

INSERT IGNORE INTO `subjects` (`id`, `name`, `code`, `description`, `status`) VALUES
(1,  'English',            'ENG',    'English language and literature',                              'active'),
(2,  'Hindi',              'HND',    'Hindi language and literature',                                 'active'),
(3,  'Mathematics',        'MATH',   'Core mathematics — arithmetic, algebra, geometry',              'active'),
(4,  'Science',            'SCI',    'General science — physics, chemistry, biology foundation',      'active'),
(5,  'Social Studies',     'SOC',    'History, geography, civics and social awareness',               'active'),
(6,  'Computer Science',   'CMP',    'Computer fundamentals, programming and digital literacy',       'active'),
(7,  'Sanskrit',           'SKT',    'Sanskrit language basics',                                      'active'),
(8,  'Environmental Studies', 'EVS','Environmental awareness and nature studies',                    'active'),
(9,  'Art & Craft',        'ART',    'Drawing, painting, craft and creative expression',              'active'),
(10, 'Physical Education', 'PED',    'Physical fitness, sports and games',                            'active'),
(11, 'Music',              'MUS',    'Vocal and instrumental music',                                  'active'),
(12, 'Moral Science',      'MRL',    'Moral values, ethics and life skills',                          'active'),
(13, 'General Knowledge',  'GK',     'Current affairs and general awareness',                         'active'),
(14, 'Physics',            'PHY',    'Senior secondary physics (Class 11-12)',                        'active'),
(15, 'Chemistry',          'CHM',    'Senior secondary chemistry (Class 11-12)',                      'active'),
(16, 'Biology',            'BIO',    'Senior secondary biology (Class 11-12)',                        'active'),
(17, 'Accountancy',        'ACC',    'Senior secondary commerce — accountancy (Class 11-12)',         'active'),
(18, 'Business Studies',   'BST',    'Senior secondary commerce — business studies (Class 11-12)',    'active'),
(19, 'Economics',          'ECO',    'Senior secondary economics (Class 11-12)',                      'active'),
(20, 'Political Science',  'POL',    'Senior secondary humanities — political science (Class 11-12)', 'active');
