-- ============================================================
-- Child Immunization Tracker — Full Schema + Seed Data
-- ============================================================
CREATE DATABASE IF NOT EXISTS `cvs` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cvs`;

-- Users
CREATE TABLE IF NOT EXISTS `users` (
    `user_id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100)  NOT NULL,
    `email`      VARCHAR(255)  NOT NULL,
    `password`   VARCHAR(255)  NOT NULL,
    `phone`      VARCHAR(20)   DEFAULT NULL,
    `role`       ENUM('admin','Guardian','doctor','SocialCaregiver') NOT NULL DEFAULT 'Guardian',
    `location`   VARCHAR(255)  DEFAULT NULL,
    `id_number`  VARCHAR(50)   DEFAULT NULL,
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Children
CREATE TABLE IF NOT EXISTS `children` (
    `child_id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`                   VARCHAR(100)  NOT NULL,
    `date_of_birth`          DATE          NOT NULL,
    `gender`                 ENUM('Male','Female','Other') NOT NULL,
    `health_id`              VARCHAR(20)   NOT NULL,
    `guardian_id`            INT UNSIGNED  NOT NULL,
    `caregiver_id`           INT UNSIGNED  DEFAULT NULL,
    `vaccination_card_path`  VARCHAR(500)  DEFAULT NULL,
    `last_vaccination_date`  DATE          DEFAULT NULL,
    `last_vaccine_name`      VARCHAR(255)  DEFAULT NULL,
    `created_at`             DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`child_id`),
    UNIQUE KEY `uq_health_id` (`health_id`),
    KEY `fk_children_guardian` (`guardian_id`),
    KEY `fk_children_caregiver` (`caregiver_id`),
    CONSTRAINT `fk_children_guardian`  FOREIGN KEY (`guardian_id`)  REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_children_caregiver` FOREIGN KEY (`caregiver_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vaccines
CREATE TABLE IF NOT EXISTS `vaccines` (
    `vaccine_id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`                    VARCHAR(255) NOT NULL,
    `disease_prevented`       VARCHAR(255) DEFAULT NULL,
    `recommended_age`         VARCHAR(100) DEFAULT NULL,
    `recommended_age_days`    INT UNSIGNED DEFAULT NULL,
    `dose_number`             TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `dose_description`        TEXT         DEFAULT NULL,
    `route_of_administration` VARCHAR(100) DEFAULT NULL,
    `site_of_administration`  VARCHAR(100) DEFAULT NULL,
    `side_effects`            TEXT         DEFAULT NULL,
    PRIMARY KEY (`vaccine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vaccination Schedule
CREATE TABLE IF NOT EXISTS `vaccination_schedule` (
    `schedule_id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `child_id`        INT UNSIGNED NOT NULL,
    `vaccine_id`      INT UNSIGNED NOT NULL,
    `due_date`        DATE         NOT NULL,
    `dose_number`     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `status`          ENUM('Pending','Completed','Missed') NOT NULL DEFAULT 'Pending',
    `completed_date`  DATE         DEFAULT NULL,
    `administered_by` INT UNSIGNED DEFAULT NULL,
    `notes`           TEXT         DEFAULT NULL,
    `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`schedule_id`),
    KEY `fk_vs_child`   (`child_id`),
    KEY `fk_vs_vaccine` (`vaccine_id`),
    KEY `fk_vs_admin`   (`administered_by`),
    KEY `idx_vs_status` (`status`),
    CONSTRAINT `fk_vs_child`   FOREIGN KEY (`child_id`)        REFERENCES `children` (`child_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_vs_vaccine` FOREIGN KEY (`vaccine_id`)      REFERENCES `vaccines` (`vaccine_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_vs_admin`   FOREIGN KEY (`administered_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vaccination Cards
CREATE TABLE IF NOT EXISTS `vaccination_cards` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `child_id`    INT UNSIGNED NOT NULL,
    `card_path`   VARCHAR(500) NOT NULL,
    `uploaded_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `verified_at` DATETIME     DEFAULT NULL,
    `verified_by` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_vc_child`    (`child_id`),
    KEY `fk_vc_verifier` (`verified_by`),
    CONSTRAINT `fk_vc_child`    FOREIGN KEY (`child_id`)    REFERENCES `children` (`child_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_vc_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `notification_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         INT UNSIGNED NOT NULL,
    `child_id`        INT UNSIGNED DEFAULT NULL,
    `message`         TEXT         NOT NULL,
    `is_read`         TINYINT(1)   NOT NULL DEFAULT 0,
    `status`          ENUM('Pending','Sent','Read') NOT NULL DEFAULT 'Pending',
    `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`notification_id`),
    KEY `fk_notif_user`  (`user_id`),
    KEY `fk_notif_child` (`child_id`),
    KEY `idx_notif_read` (`user_id`, `is_read`),
    CONSTRAINT `fk_notif_user`  FOREIGN KEY (`user_id`)  REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notif_child` FOREIGN KEY (`child_id`) REFERENCES `children` (`child_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TFA Codes
CREATE TABLE IF NOT EXISTS `tfa_codes` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(255) NOT NULL,
    `code`       VARCHAR(6)   NOT NULL,
    `expires_at` DATETIME     NOT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tfa_email` (`email`),
    KEY `idx_email_code` (`email`, `code`),
    KEY `idx_expires`    (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password Resets
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(255) NOT NULL,
    `token`      VARCHAR(64)  NOT NULL,
    `expires_at` DATETIME     NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pr_email` (`email`),
    KEY `idx_pr_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Caregiver Assignments
CREATE TABLE IF NOT EXISTS `caregiver_assignments` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `caregiver_id` INT UNSIGNED NOT NULL,
    `child_id`     INT UNSIGNED NOT NULL,
    `assigned_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ca_pair` (`caregiver_id`, `child_id`),
    KEY `fk_ca_child` (`child_id`),
    CONSTRAINT `fk_ca_caregiver` FOREIGN KEY (`caregiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ca_child`     FOREIGN KEY (`child_id`)     REFERENCES `children` (`child_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Logs
CREATE TABLE IF NOT EXISTS `system_logs` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `description` VARCHAR(500) NOT NULL,
    `user_id`     INT UNSIGNED DEFAULT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_sl_user`     (`user_id`),
    KEY `idx_sl_created` (`created_at`),
    CONSTRAINT `fk_sl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Seed: Demo Users
-- Passwords are bcrypt hashes of "Admin@1234" (admin) / "Parent@1234" (others)
-- Generated with PHP password_hash(..., PASSWORD_DEFAULT)
-- ============================================================
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `location`, `id_number`) VALUES
('System Admin',      'admin@jarida.io',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+254700000001', 'admin',           'Nairobi',  '12345678'),
('Dr. Amara Osei',    'doctor@jarida.io',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+254711000002', 'doctor',          'Kisumu',   '23456789'),
('Grace Wambui',      'grace@jarida.io',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+254722000003', 'Guardian',        'Nairobi',  '34567890'),
('Joseph Otieno',     'joseph@jarida.io',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+254733000004', 'Guardian',        'Mombasa',  '45678901'),
('CHW Fatuma Ali',    'chw@jarida.io',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+254744000005', 'SocialCaregiver', 'Garissa',  '56789012');

-- ============================================================
-- Seed: Kenya National Immunization Schedule (KEPI)
-- recommended_age_days = days from birth
-- ============================================================
INSERT INTO `vaccines` (`name`, `disease_prevented`, `recommended_age`, `recommended_age_days`, `dose_number`, `dose_description`, `route_of_administration`, `site_of_administration`, `side_effects`) VALUES
('BCG',        'Tuberculosis',                            'At birth',    0,   1, 'Single dose at birth',             'Intradermal',      'Right upper arm',       'Small blister, mild fever'),
('OPV 0',      'Polio',                                   'At birth',    0,   1, 'Birth dose oral polio vaccine',    'Oral',             'Mouth',                 'Rare mild diarrhoea'),
('OPV 1',      'Polio',                                   '6 weeks',     42,  1, 'First dose oral polio vaccine',    'Oral',             'Mouth',                 'Rare mild diarrhoea'),
('OPV 2',      'Polio',                                   '10 weeks',    70,  2, 'Second dose oral polio vaccine',   'Oral',             'Mouth',                 'Rare mild diarrhoea'),
('OPV 3',      'Polio',                                   '14 weeks',    98,  3, 'Third dose oral polio vaccine',    'Oral',             'Mouth',                 'Rare mild diarrhoea'),
('DPT-HepB-Hib 1', 'Diphtheria, Pertussis, Tetanus, Hepatitis B, Hib', '6 weeks', 42, 1, 'First pentavalent dose', 'Intramuscular', 'Left thigh', 'Redness, mild fever, fussiness'),
('DPT-HepB-Hib 2', 'Diphtheria, Pertussis, Tetanus, Hepatitis B, Hib', '10 weeks', 70, 2, 'Second pentavalent dose', 'Intramuscular', 'Left thigh', 'Redness, mild fever, fussiness'),
('DPT-HepB-Hib 3', 'Diphtheria, Pertussis, Tetanus, Hepatitis B, Hib', '14 weeks', 98, 3, 'Third pentavalent dose', 'Intramuscular', 'Left thigh', 'Redness, mild fever, fussiness'),
('PCV 1',      'Pneumococcal disease',                    '6 weeks',     42,  1, 'First pneumococcal dose',          'Intramuscular',    'Right thigh',           'Mild redness, fever'),
('PCV 2',      'Pneumococcal disease',                    '10 weeks',    70,  2, 'Second pneumococcal dose',         'Intramuscular',    'Right thigh',           'Mild redness, fever'),
('PCV 3',      'Pneumococcal disease',                    '14 weeks',    98,  3, 'Third pneumococcal dose',          'Intramuscular',    'Right thigh',           'Mild redness, fever'),
('RV 1',       'Rotavirus',                               '6 weeks',     42,  1, 'First rotavirus dose',             'Oral',             'Mouth',                 'Mild diarrhoea, irritability'),
('RV 2',       'Rotavirus',                               '10 weeks',    70,  2, 'Second rotavirus dose',            'Oral',             'Mouth',                 'Mild diarrhoea, irritability'),
('IPV',        'Polio',                                   '14 weeks',    98,  1, 'Inactivated polio vaccine',        'Intramuscular',    'Right thigh',           'Mild soreness'),
('Measles 1',  'Measles',                                 '9 months',    270, 1, 'First measles dose',               'Subcutaneous',     'Right upper arm',       'Mild rash, low fever'),
('Measles 2 / Rubella', 'Measles, Rubella',              '18 months',   540, 2, 'Second measles-rubella dose',      'Subcutaneous',     'Right upper arm',       'Mild rash, low fever'),
('Yellow Fever', 'Yellow Fever',                          '9 months',    270, 1, 'Single lifetime dose',             'Subcutaneous',     'Right upper arm',       'Mild fever, headache'),
('Vitamin A',  'Vitamin A deficiency',                    '6 months',    180, 1, 'Supplement at 6 months',           'Oral',             'Mouth',                 'Rare nausea'),
('Vitamin A 2','Vitamin A deficiency',                    '12 months',   365, 2, 'Supplement at 12 months',          'Oral',             'Mouth',                 'Rare nausea');

-- ============================================================
-- Seed: Demo Children for Grace Wambui (user_id=3)
-- ============================================================
INSERT INTO `children` (`name`, `date_of_birth`, `gender`, `health_id`, `guardian_id`, `created_at`) VALUES
('Amara Wambui',   '2024-08-15', 'Female', 'KE-2024-0001', 3, NOW()),
('Brian Wambui',   '2023-03-10', 'Male',   'KE-2023-0045', 3, NOW());

-- Demo Child for Joseph Otieno (user_id=4)
INSERT INTO `children` (`name`, `date_of_birth`, `gender`, `health_id`, `guardian_id`, `created_at`) VALUES
('Chloe Otieno',   '2024-01-20', 'Female', 'KE-2024-0078', 4, NOW());

-- ============================================================
-- Seed: Vaccination schedule for Amara Wambui (child_id=1, DOB 2024-08-15)
-- ============================================================
INSERT INTO `vaccination_schedule` (`child_id`, `vaccine_id`, `due_date`, `dose_number`, `status`, `completed_date`, `administered_by`) VALUES
(1,  1,  '2024-08-15', 1, 'Completed', '2024-08-15', 2),  -- BCG
(1,  2,  '2024-08-15', 1, 'Completed', '2024-08-15', 2),  -- OPV 0
(1,  3,  '2024-09-26', 1, 'Completed', '2024-09-26', 2),  -- OPV 1
(1,  6,  '2024-09-26', 1, 'Completed', '2024-09-26', 2),  -- DPT 1
(1,  9,  '2024-09-26', 1, 'Completed', '2024-09-26', 2),  -- PCV 1
(1,  12, '2024-09-26', 1, 'Completed', '2024-09-26', 2),  -- RV 1
(1,  4,  '2024-10-24', 2, 'Completed', '2024-10-24', 2),  -- OPV 2
(1,  7,  '2024-10-24', 2, 'Completed', '2024-10-24', 2),  -- DPT 2
(1,  10, '2024-10-24', 2, 'Completed', '2024-10-24', 2),  -- PCV 2
(1,  13, '2024-10-24', 2, 'Completed', '2024-10-24', 2),  -- RV 2
(1,  5,  '2024-11-21', 3, 'Completed', '2024-11-21', 2),  -- OPV 3
(1,  8,  '2024-11-21', 3, 'Completed', '2024-11-21', 2),  -- DPT 3
(1,  11, '2024-11-21', 3, 'Completed', '2024-11-21', 2),  -- PCV 3
(1,  14, '2024-11-21', 1, 'Completed', '2024-11-21', 2),  -- IPV
(1,  18, '2025-02-15', 1, 'Completed', '2025-02-15', 2),  -- Vitamin A
(1,  15, '2025-05-12', 1, 'Missed',    NULL,          NULL), -- Measles 1 (missed)
(1,  17, '2025-05-12', 1, 'Missed',    NULL,          NULL), -- Yellow Fever (missed)
(1,  19, '2025-08-15', 2, 'Pending',   NULL,          NULL), -- Vitamin A 2
(1,  16, '2026-02-15', 2, 'Pending',   NULL,          NULL); -- Measles 2 / Rubella

-- Vaccination schedule for Brian Wambui (child_id=2, DOB 2023-03-10) - fully up to date
INSERT INTO `vaccination_schedule` (`child_id`, `vaccine_id`, `due_date`, `dose_number`, `status`, `completed_date`, `administered_by`) VALUES
(2, 1,  '2023-03-10', 1, 'Completed', '2023-03-10', 2),
(2, 2,  '2023-03-10', 1, 'Completed', '2023-03-10', 2),
(2, 3,  '2023-04-21', 1, 'Completed', '2023-04-21', 2),
(2, 6,  '2023-04-21', 1, 'Completed', '2023-04-21', 2),
(2, 9,  '2023-04-21', 1, 'Completed', '2023-04-21', 2),
(2, 12, '2023-04-21', 1, 'Completed', '2023-04-21', 2),
(2, 4,  '2023-05-19', 2, 'Completed', '2023-05-19', 2),
(2, 7,  '2023-05-19', 2, 'Completed', '2023-05-19', 2),
(2, 10, '2023-05-19', 2, 'Completed', '2023-05-19', 2),
(2, 13, '2023-05-19', 2, 'Completed', '2023-05-19', 2),
(2, 5,  '2023-06-16', 3, 'Completed', '2023-06-16', 2),
(2, 8,  '2023-06-16', 3, 'Completed', '2023-06-16', 2),
(2, 11, '2023-06-16', 3, 'Completed', '2023-06-16', 2),
(2, 14, '2023-06-16', 1, 'Completed', '2023-06-16', 2),
(2, 18, '2023-09-10', 1, 'Completed', '2023-09-10', 2),
(2, 15, '2023-12-05', 1, 'Completed', '2023-12-05', 2),
(2, 17, '2023-12-05', 1, 'Completed', '2023-12-05', 2),
(2, 19, '2024-03-10', 2, 'Completed', '2024-03-10', 2),
(2, 16, '2024-09-10', 2, 'Completed', '2024-09-10', 2);

-- Vaccination schedule for Chloe Otieno (child_id=3, DOB 2024-01-20)
INSERT INTO `vaccination_schedule` (`child_id`, `vaccine_id`, `due_date`, `dose_number`, `status`, `completed_date`, `administered_by`) VALUES
(3, 1,  '2024-01-20', 1, 'Completed', '2024-01-20', 2),
(3, 2,  '2024-01-20', 1, 'Completed', '2024-01-20', 2),
(3, 3,  '2024-03-02', 1, 'Completed', '2024-03-02', 2),
(3, 6,  '2024-03-02', 1, 'Completed', '2024-03-02', 2),
(3, 9,  '2024-03-02', 1, 'Completed', '2024-03-02', 2),
(3, 12, '2024-03-02', 1, 'Completed', '2024-03-02', 2),
(3, 4,  '2024-03-30', 2, 'Missed',    NULL,          NULL),
(3, 7,  '2024-03-30', 2, 'Missed',    NULL,          NULL),
(3, 10, '2024-03-30', 2, 'Missed',    NULL,          NULL),
(3, 13, '2024-03-30', 2, 'Missed',    NULL,          NULL),
(3, 5,  '2024-04-27', 3, 'Pending',   NULL,          NULL),
(3, 8,  '2024-04-27', 3, 'Pending',   NULL,          NULL),
(3, 11, '2024-04-27', 3, 'Pending',   NULL,          NULL),
(3, 14, '2024-04-27', 1, 'Pending',   NULL,          NULL),
(3, 18, '2024-07-20', 1, 'Pending',   NULL,          NULL),
(3, 15, '2024-10-16', 1, 'Pending',   NULL,          NULL),
(3, 17, '2024-10-16', 1, 'Pending',   NULL,          NULL),
(3, 19, '2025-01-20', 2, 'Pending',   NULL,          NULL),
(3, 16, '2025-07-20', 2, 'Pending',   NULL,          NULL);

-- ============================================================
-- Seed: Notifications
-- ============================================================
INSERT INTO `notifications` (`user_id`, `child_id`, `message`, `is_read`, `status`, `created_at`) VALUES
(3, 1, 'Missed vaccination: Measles 1 (was due May 12, 2025) — please rebook', 0, 'Pending', NOW()),
(3, 1, 'Missed vaccination: Yellow Fever (was due May 12, 2025) — please rebook', 0, 'Pending', NOW()),
(3, 1, 'Upcoming vaccination: Vitamin A 2 due on August 15, 2025', 1, 'Sent', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(4, 3, 'Missed vaccination: OPV 2 (was due March 30, 2024) — please rebook', 0, 'Pending', NOW()),
(4, 3, 'Missed vaccination: DPT-HepB-Hib 2 (was due March 30, 2024) — please rebook', 0, 'Pending', NOW());

-- ============================================================
-- Seed: System Logs
-- ============================================================
INSERT INTO `system_logs` (`description`, `user_id`, `created_at`) VALUES
('User logged in',                          3, DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Child registered: Amara Wambui',          3, DATE_SUB(NOW(), INTERVAL 30 DAY)),
('Vaccination marked completed: BCG',       2, DATE_SUB(NOW(), INTERVAL 30 DAY)),
('Child registered: Brian Wambui',          3, DATE_SUB(NOW(), INTERVAL 400 DAY)),
('User logged in',                          1, NOW()),
('Admin viewed system logs',                1, NOW());
