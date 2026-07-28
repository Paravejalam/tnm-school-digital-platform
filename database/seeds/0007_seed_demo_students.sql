-- =============================================================================
-- Seeder: 0007_seed_demo_students.sql
-- Inserts: Sample student enrollment records across classes and sections
-- Authority: .github/AGENT.md
-- Order: Run AFTER 0004_seed_demo_sections.sql (requires class_id, section_id)
-- =============================================================================

USE `tnm_school_platform`;

INSERT IGNORE INTO `students` (`id`, `admission_number`, `roll_number`, `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `gender`, `academic_session_id`, `class_id`, `section_id`, `class_name`, `section`, `status`) VALUES
-- Class 10 Section A (class_id=10, section_id=19)
(1,  'ADM2026001', '10-A-01', 'Arjun',     'Mehta',    NULL, NULL, '2010-05-12', 'male',    1, 10, 19, 'Class 10', 'A', 'active'),
(2,  'ADM2026002', '10-A-02', 'Ananya',    'Reddy',    NULL, NULL, '2010-08-23', 'female',  1, 10, 19, 'Class 10', 'A', 'active'),
(3,  'ADM2026003', '10-A-03', 'Rohan',     'Nair',     NULL, NULL, '2010-11-07', 'male',    1, 10, 19, 'Class 10', 'A', 'active'),
(4,  'ADM2026004', '10-A-04', 'Priyanka',  'Chopra',   NULL, NULL, '2010-02-15', 'female',  1, 10, 19, 'Class 10', 'A', 'active'),
-- Class 10 Section B (class_id=10, section_id=20)
(5,  'ADM2026005', '10-B-01', 'Vikash',    'Kumar',    NULL, NULL, '2010-07-30', 'male',    1, 10, 20, 'Class 10', 'B', 'active'),
(6,  'ADM2026006', '10-B-02', 'Sneha',     'Iyer',     NULL, NULL, '2010-04-18', 'female',  1, 10, 20, 'Class 10', 'B', 'active'),
(7,  'ADM2026007', '10-B-03', 'Manish',    'Thakur',   NULL, NULL, '2010-09-05', 'male',    1, 10, 20, 'Class 10', 'B', 'active'),
(8,  'ADM2026008', '10-B-04', 'Divya',     'Rao',      NULL, NULL, '2010-12-22', 'female',  1, 10, 20, 'Class 10', 'B', 'active'),
-- Class 9 Section A (class_id=9, section_id=17)
(9,  'ADM2026009', '9-A-01',  'Karan',     'Bose',     NULL, NULL, '2011-03-10', 'male',    1, 9,  17, 'Class 9',  'A', 'active'),
(10, 'ADM2026010', '9-A-02',  'Meera',     'Kapoor',   NULL, NULL, '2011-06-25', 'female',  1, 9,  17, 'Class 9',  'A', 'active'),
(11, 'ADM2026011', '9-A-03',  'Deepak',    'Saxena',   NULL, NULL, '2011-01-14', 'male',    1, 9,  17, 'Class 9',  'A', 'active'),
(12, 'ADM2026012', '9-A-04',  'Ritu',      'Agrawal',  NULL, NULL, '2011-08-19', 'female',  1, 9,  17, 'Class 9',  'A', 'active'),
-- Class 8 Section A (class_id=8, section_id=15)
(13, 'ADM2026013', '8-A-01',  'Sahil',     'Malik',    NULL, NULL, '2012-04-05', 'male',    1, 8,  15, 'Class 8',  'A', 'active'),
(14, 'ADM2026014', '8-A-02',  'Tanya',     'Sen',      NULL, NULL, '2012-09-30', 'female',  1, 8,  15, 'Class 8',  'A', 'active'),
(15, 'ADM2026015', '8-A-03',  'Aditya',    'Pillai',   NULL, NULL, '2012-07-11', 'male',    1, 8,  15, 'Class 8',  'A', 'active'),
(16, 'ADM2026016', '8-A-04',  'Pooja',     'Dubey',    NULL, NULL, '2012-02-28', 'female',  1, 8,  15, 'Class 8',  'A', 'active'),
-- Class 7 Section A (class_id=7, section_id=13)
(17, 'ADM2026017', '7-A-01',  'Rahul',     'Tiwari',   NULL, NULL, '2013-05-20', 'male',    1, 7,  13, 'Class 7',  'A', 'active'),
(18, 'ADM2026018', '7-A-02',  'Shreya',    'Bhatt',    NULL, NULL, '2013-10-08', 'female',  1, 7,  13, 'Class 7',  'A', 'active'),
(19, 'ADM2026019', '7-A-03',  'Nitin',     'Chauhan',  NULL, NULL, '2013-01-17', 'male',    1, 7,  13, 'Class 7',  'A', 'active'),
(20, 'ADM2026020', '7-A-04',  'Swati',     'Shukla',   NULL, NULL, '2013-12-03', 'female',  1, 7,  13, 'Class 7',  'A', 'active'),
-- Class 5 Section A (class_id=5, section_id=9)
(21, 'ADM2026021', '5-A-01',  'Mohit',     'Rathore',  NULL, NULL, '2015-06-14', 'male',    1, 5,  9,  'Class 5',  'A', 'active'),
(22, 'ADM2026022', '5-A-02',  'Ishita',    'Goswami',  NULL, NULL, '2015-03-22', 'female',  1, 5,  9,  'Class 5',  'A', 'active'),
(23, 'ADM2026023', '5-A-03',  'Prakash',   'Jha',      NULL, NULL, '2015-11-09', 'male',    1, 5,  9,  'Class 5',  'A', 'active'),
(24, 'ADM2026024', '5-A-04',  'Lakshmi',   'Menon',    NULL, NULL, '2015-08-30', 'female',  1, 5,  9,  'Class 5',  'A', 'active'),
-- Class 3 Section A (class_id=3, section_id=5)
(25, 'ADM2026025', '3-A-01',  'Gaurav',    'Soni',     NULL, NULL, '2017-04-16', 'male',    1, 3,  5,  'Class 3',  'A', 'active'),
(26, 'ADM2026026', '3-A-02',  'Khushi',    'Nagpal',   NULL, NULL, '2017-07-25', 'female',  1, 3,  5,  'Class 3',  'A', 'active'),
(27, 'ADM2026027', '3-A-03',  'Harsh',     'Garg',     NULL, NULL, '2017-01-08', 'male',    1, 3,  5,  'Class 3',  'A', 'active'),
(28, 'ADM2026028', '3-A-04',  'Jaya',      'Srivastav',NULL, NULL, '2017-09-12', 'female',  1, 3,  5,  'Class 3',  'A', 'active'),
-- Class 1 Section A (class_id=1, section_id=1)
(29, 'ADM2026029', '1-A-01',  'Aarav',     'Sharma',   NULL, NULL, '2019-05-02', 'male',    1, 1,  1,  'Class 1',  'A', 'active'),
(30, 'ADM2026030', '1-A-02',  'Aanya',     'Gupta',    NULL, NULL, '2019-08-19', 'female',  1, 1,  1,  'Class 1',  'A', 'active');
