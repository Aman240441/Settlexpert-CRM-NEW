-- ==============================================================================
-- SettlExpert CRM - Target MySQL 8.0+ Database Schema
-- Canonical Table Count: Exactly 30 Tables
-- Engine: InnoDB | Character Set: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. departments
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `departments` (
    `id` VARCHAR(64) NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `code` VARCHAR(64) NOT NULL,
    `description` TEXT NULL,
    `head_manager_id` VARCHAR(64) NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_departments_name` (`name`),
    UNIQUE KEY `uq_departments_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. manager_types
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `manager_types` (
    `id` VARCHAR(64) NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `code` VARCHAR(64) NOT NULL,
    `description` TEXT NULL,
    `permissions_json` TEXT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_manager_types_name` (`name`),
    UNIQUE KEY `uq_manager_types_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. fee_plans
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fee_plans` (
    `id` VARCHAR(64) NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `duration` VARCHAR(64) NOT NULL,
    `default_fee` DECIMAL(14,2) NOT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. business_counters (Concurrency-safe atomic numbering via SELECT ... FOR UPDATE)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `business_counters` (
    `counter_name` VARCHAR(64) NOT NULL,
    `current_val` INT UNSIGNED NOT NULL DEFAULT 0,
    `prefix` VARCHAR(32) NOT NULL,
    `padding` TINYINT UNSIGNED NOT NULL DEFAULT 4,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`counter_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `business_counters` (`counter_name`, `current_val`, `prefix`, `padding`) VALUES
('lead', 0, 'LEAD', 4),
('client', 0, 'CL', 4),
('agreement', 0, 'AGR', 4),
('receipt', 0, 'REC', 3),
('legal_notice', 0, 'LN', 4),
('demand_notice', 0, 'DN', 4),
('complaint', 0, 'CMP', 4),
('lead_import', 0, 'IMP', 4),
('lead_distribution', 0, 'DIST', 4)
ON DUPLICATE KEY UPDATE `counter_name` = `counter_name`;

-- ------------------------------------------------------------------------------
-- 5. login_rate_limit (Persistent IP lockout and attempt tracking)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_rate_limit` (
    `ip_address` VARCHAR(45) NOT NULL,
    `attempts` INT UNSIGNED NOT NULL DEFAULT 1,
    `locked_until` DATETIME NULL,
    `last_attempt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. audit_logs
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` VARCHAR(64) NOT NULL,
    `user_id` VARCHAR(64) NULL,
    `user_name` VARCHAR(191) NULL,
    `role` VARCHAR(64) NULL,
    `action` VARCHAR(128) NOT NULL,
    `module` VARCHAR(128) NOT NULL,
    `record_id` VARCHAR(64) NULL,
    `details_json` LONGTEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_audit_logs_user` (`user_id`),
    INDEX `idx_audit_logs_module` (`module`),
    INDEX `idx_audit_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. teams
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `teams` (
    `id` VARCHAR(64) NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `department_id` VARCHAR(64) NULL,
    `manager_id` VARCHAR(64) NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_teams_department` (`department_id`),
    CONSTRAINT `fk_teams_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 8. users
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` VARCHAR(64) NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `phone` VARCHAR(32) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(32) NOT NULL,
    `manager_id` VARCHAR(64) NULL,
    `department_id` VARCHAR(64) NULL,
    `team_id` VARCHAR(64) NULL,
    `manager_type_id` VARCHAR(64) NULL,
    `emp_or_mgr_id` VARCHAR(64) NULL,
    `profile_image` LONGTEXT NULL,
    `joining_date` VARCHAR(32) NULL,
    `id_type` VARCHAR(64) NULL,
    `id_front` LONGTEXT NULL,
    `id_back` LONGTEXT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `failed_login_attempts` INT DEFAULT 0,
    `locked_until` DATETIME NULL,
    `last_failed_login_at` DATETIME NULL,
    `last_login_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    UNIQUE KEY `uq_users_emp_or_mgr_id` (`emp_or_mgr_id`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_manager` (`manager_id`),
    INDEX `idx_users_dept` (`department_id`),
    INDEX `idx_users_team` (`team_id`),
    INDEX `idx_users_manager_type` (`manager_type_id`),
    CONSTRAINT `fk_users_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_users_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_users_manager_type` FOREIGN KEY (`manager_type_id`) REFERENCES `manager_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 9. user_permissions
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_permissions` (
    `id` VARCHAR(64) NOT NULL,
    `user_id` VARCHAR(64) NOT NULL,
    `module` VARCHAR(64) NOT NULL,
    `can_view` TINYINT NOT NULL DEFAULT 1,
    `can_create` TINYINT NOT NULL DEFAULT 0,
    `can_edit` TINYINT NOT NULL DEFAULT 0,
    `can_delete` TINYINT NOT NULL DEFAULT 0,
    `can_assign` TINYINT NOT NULL DEFAULT 0,
    `can_verify` TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_permissions_user_module` (`user_id`, `module`),
    CONSTRAINT `fk_user_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 10. advocates
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `advocates` (
    `id` VARCHAR(64) NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `advocate_id` VARCHAR(64) NOT NULL,
    `mobile` VARCHAR(32) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `registration_number` VARCHAR(128) NOT NULL,
    `specialization` VARCHAR(191) NOT NULL,
    `address` TEXT NULL,
    `profile_image` LONGTEXT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_advocates_advocate_id` (`advocate_id`),
    UNIQUE KEY `uq_advocates_email` (`email`),
    UNIQUE KEY `uq_advocates_registration_number` (`registration_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 11. staff_extended_profiles
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_extended_profiles` (
    `user_id` VARCHAR(64) NOT NULL,
    `date_of_birth` VARCHAR(32) NULL,
    `gender` VARCHAR(32) NULL,
    `father_name` VARCHAR(191) NULL,
    `mother_name` VARCHAR(191) NULL,
    `alt_phone` VARCHAR(32) NULL,
    `whatsapp_number` VARCHAR(32) NULL,
    `current_address` TEXT NULL,
    `permanent_address` TEXT NULL,
    `city` VARCHAR(128) NULL,
    `state` VARCHAR(128) NULL,
    `pin_code` VARCHAR(32) NULL,
    `designation` VARCHAR(128) NULL,
    `employment_status` VARCHAR(64) DEFAULT 'Active',
    `staff_type` VARCHAR(64) DEFAULT 'Employee',
    `reporting_manager_id` VARCHAR(64) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_staff_ext_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_staff_ext_reporting_mgr` FOREIGN KEY (`reporting_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 12. staff_kyc
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_kyc` (
    `id` VARCHAR(64) NOT NULL,
    `user_id` VARCHAR(64) NOT NULL,
    `aadhaar_number` VARCHAR(32) NULL,
    `aadhaar_front_doc` LONGTEXT NULL,
    `aadhaar_back_doc` LONGTEXT NULL,
    `kyc_status` VARCHAR(32) DEFAULT 'pending',
    `verified_by` VARCHAR(64) NULL,
    `verified_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_staff_kyc_user` (`user_id`),
    CONSTRAINT `fk_staff_kyc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 13. staff_advocate_details
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_advocate_details` (
    `user_id` VARCHAR(64) NOT NULL,
    `registration_number` VARCHAR(128) NULL,
    `bar_council_state` VARCHAR(128) NULL,
    `specialization` VARCHAR(191) NULL,
    `years_experience` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_staff_advocate_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 14. tasks
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` VARCHAR(64) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `module` VARCHAR(64) NULL,
    `priority` VARCHAR(32) DEFAULT 'medium',
    `due_date` VARCHAR(32) NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
    `assigned_to` VARCHAR(64) NULL,
    `created_by` VARCHAR(64) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_tasks_assigned_to` (`assigned_to`),
    INDEX `idx_tasks_status` (`status`),
    CONSTRAINT `fk_tasks_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 15. lead_imports
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lead_imports` (
    `id` VARCHAR(64) NOT NULL,
    `import_number` VARCHAR(64) NOT NULL,
    `file_name` VARCHAR(255) NULL,
    `uploaded_by` VARCHAR(64) NULL,
    `uploaded_by_name` VARCHAR(191) NULL,
    `uploaded_by_role` VARCHAR(64) NULL,
    `total_rows` INT DEFAULT 0,
    `valid_leads` INT DEFAULT 0,
    `duplicate_leads` INT DEFAULT 0,
    `invalid_rows` INT DEFAULT 0,
    `imported_leads` INT DEFAULT 0,
    `distribution_status` VARCHAR(32) DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_lead_imports_number` (`import_number`),
    INDEX `idx_lead_imports_uploaded_by` (`uploaded_by`),
    CONSTRAINT `fk_lead_imports_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 16. lead_distributions
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lead_distributions` (
    `id` VARCHAR(64) NOT NULL,
    `distribution_number` VARCHAR(64) NOT NULL,
    `import_id` VARCHAR(64) NULL,
    `distributed_by` VARCHAR(64) NULL,
    `distributed_by_name` VARCHAR(191) NULL,
    `distributed_by_role` VARCHAR(64) NULL,
    `distribution_mode` VARCHAR(64) NOT NULL,
    `assignment_method` VARCHAR(64) NOT NULL,
    `total_leads` INT NOT NULL,
    `employee_count` INT NOT NULL,
    `summary_json` LONGTEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_lead_dist_number` (`distribution_number`),
    INDEX `idx_lead_dist_import` (`import_id`),
    INDEX `idx_lead_dist_distributed_by` (`distributed_by`),
    CONSTRAINT `fk_lead_dist_import` FOREIGN KEY (`import_id`) REFERENCES `lead_imports` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_lead_dist_distributed_by` FOREIGN KEY (`distributed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 17. leads
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leads` (
    `id` VARCHAR(64) NOT NULL,
    `lead_number` VARCHAR(64) NULL,
    `name` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NULL,
    `phone` VARCHAR(32) NOT NULL,
    `loan_amount` DECIMAL(14,2) DEFAULT 0,
    `bank_name` VARCHAR(191) NULL,
    `status` VARCHAR(64) NOT NULL DEFAULT 'new',
    `manager_id` VARCHAR(64) NULL,
    `employee_id` VARCHAR(64) NULL,
    `department_id` VARCHAR(64) NULL,
    `created_by` VARCHAR(64) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `city` VARCHAR(128) NULL,
    `total_debt` DECIMAL(14,2) DEFAULT 0,
    `monthly_income` DECIMAL(14,2) DEFAULT 0,
    `service_needed` VARCHAR(191) NULL,
    `paying_emis` VARCHAR(32) DEFAULT 'Yes',
    `harassment_calls` VARCHAR(32) DEFAULT 'No',
    `employment_status` VARCHAR(64) DEFAULT 'Employed',
    `employment_type` VARCHAR(64) DEFAULT 'Salaried',
    `settlement_needed` VARCHAR(32) DEFAULT 'Yes',
    `consultation_timing` VARCHAR(128) NULL,
    `credit_card_dues` DECIMAL(14,2) DEFAULT 0,
    `personal_loan_dues` DECIMAL(14,2) DEFAULT 0,
    `service_fee` DECIMAL(14,2) DEFAULT 0,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `address` TEXT NULL,
    `state` VARCHAR(128) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_leads_lead_number` (`lead_number`),
    INDEX `idx_leads_employee` (`employee_id`),
    INDEX `idx_leads_manager` (`manager_id`),
    INDEX `idx_leads_department` (`department_id`),
    INDEX `idx_leads_status` (`status`),
    INDEX `idx_leads_phone` (`phone`),
    CONSTRAINT `fk_leads_employee` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_leads_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_leads_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 18. follow_ups
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `follow_ups` (
    `id` VARCHAR(64) NOT NULL,
    `lead_id` VARCHAR(64) NOT NULL,
    `user_id` VARCHAR(64) NULL,
    `user_name` VARCHAR(191) NULL,
    `call_status` VARCHAR(64) NOT NULL,
    `interested_level` VARCHAR(64) NULL,
    `final_status` VARCHAR(64) NOT NULL,
    `remark` TEXT NULL,
    `next_follow_up_date` VARCHAR(32) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_follow_ups_lead` (`lead_id`),
    INDEX `idx_follow_ups_user` (`user_id`),
    CONSTRAINT `fk_follow_ups_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_follow_ups_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 19. lead_assignment_history
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lead_assignment_history` (
    `id` VARCHAR(64) NOT NULL,
    `lead_id` VARCHAR(64) NOT NULL,
    `distribution_id` VARCHAR(64) NULL,
    `previous_employee_id` VARCHAR(64) NULL,
    `previous_employee_name` VARCHAR(191) NULL,
    `assigned_employee_id` VARCHAR(64) NULL,
    `assigned_employee_name` VARCHAR(191) NULL,
    `assigned_by` VARCHAR(64) NULL,
    `assigned_by_name` VARCHAR(191) NULL,
    `assigned_by_role` VARCHAR(64) NULL,
    `reassignment_reason` TEXT NULL,
    `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_lead_assign_hist_lead` (`lead_id`),
    INDEX `idx_lead_assign_hist_dist` (`distribution_id`),
    INDEX `idx_lead_assign_hist_assigned_emp` (`assigned_employee_id`),
    CONSTRAINT `fk_lead_assign_hist_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_lead_assign_hist_dist` FOREIGN KEY (`distribution_id`) REFERENCES `lead_distributions` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_lead_assign_hist_prev_emp` FOREIGN KEY (`previous_employee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_lead_assign_hist_curr_emp` FOREIGN KEY (`assigned_employee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_lead_assign_hist_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 20. clients
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clients` (
    `id` VARCHAR(64) NOT NULL,
    `client_number` VARCHAR(64) NULL,
    `name` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NULL,
    `phone` VARCHAR(32) NOT NULL,
    `total_debt` DECIMAL(14,2) DEFAULT 0,
    `settlement_target` DECIMAL(14,2) DEFAULT 0,
    `fee_plan_id` VARCHAR(64) NULL,
    `status` VARCHAR(64) NOT NULL DEFAULT 'active',
    `manager_id` VARCHAR(64) NULL,
    `employee_id` VARCHAR(64) NULL,
    `advocate_id` VARCHAR(64) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `lead_id` VARCHAR(64) NULL,
    `city` VARCHAR(128) NULL,
    `employment_status` VARCHAR(64) DEFAULT 'Employed',
    `employment_type` VARCHAR(64) DEFAULT 'Salaried',
    `monthly_income` DECIMAL(14,2) DEFAULT 0,
    `credit_card_dues` DECIMAL(14,2) DEFAULT 0,
    `personal_loan_dues` DECIMAL(14,2) DEFAULT 0,
    `loan_type` VARCHAR(128) DEFAULT 'Credit Card & Personal Loan',
    `paying_emis` VARCHAR(32) DEFAULT 'Yes',
    `harassment_calls` VARCHAR(32) DEFAULT 'No',
    `settlement_needed` VARCHAR(32) DEFAULT 'Yes',
    `consultation_timing` VARCHAR(128) NULL,
    `sx_fee` DECIMAL(14,2) DEFAULT 0,
    `fees_date` VARCHAR(32) NULL,
    `fees_status` VARCHAR(64) DEFAULT 'Pending',
    `case_status` VARCHAR(64) DEFAULT 'active',
    `notes` TEXT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `pending_amount` DECIMAL(14,2) DEFAULT 0,
    `total_received` DECIMAL(14,2) DEFAULT 0,
    `address` TEXT NULL,
    `state` VARCHAR(128) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_clients_client_number` (`client_number`),
    INDEX `idx_clients_employee` (`employee_id`),
    INDEX `idx_clients_manager` (`manager_id`),
    INDEX `idx_clients_advocate` (`advocate_id`),
    INDEX `idx_clients_lead` (`lead_id`),
    INDEX `idx_clients_fee_plan` (`fee_plan_id`),
    INDEX `idx_clients_status` (`status`),
    CONSTRAINT `fk_clients_employee` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_clients_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_clients_advocate` FOREIGN KEY (`advocate_id`) REFERENCES `advocates` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_clients_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_clients_fee_plan` FOREIGN KEY (`fee_plan_id`) REFERENCES `fee_plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 21. lenders
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lenders` (
    `id` VARCHAR(64) NOT NULL,
    `client_id` VARCHAR(64) NOT NULL,
    `bank_name` VARCHAR(191) NOT NULL,
    `loan_type` VARCHAR(128) NULL,
    `balance` DECIMAL(14,2) DEFAULT 0,
    `default_date` VARCHAR(32) NULL,
    `status` VARCHAR(64) DEFAULT 'Defaulted',
    `lender_email` VARCHAR(191) NULL,
    `pdf_url` LONGTEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_lenders_client` (`client_id`),
    CONSTRAINT `fk_lenders_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 22. agreements
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agreements` (
    `id` VARCHAR(64) NOT NULL,
    `agreement_number` VARCHAR(64) NULL,
    `client_id` VARCHAR(64) NULL,
    `fee_plan_id` VARCHAR(64) NULL,
    `total_fee` DECIMAL(14,2) NOT NULL,
    `status` VARCHAR(64) NOT NULL DEFAULT 'pending',
    `start_date` VARCHAR(32) NULL,
    `end_date` VARCHAR(32) NULL,
    `created_by` VARCHAR(64) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `name` VARCHAR(191) NULL,
    `phone` VARCHAR(32) NULL,
    `email` VARCHAR(191) NULL,
    `address` TEXT NULL,
    `pin_number` VARCHAR(32) NULL,
    `dob` VARCHAR(32) NULL,
    `monthly_fee` DECIMAL(14,2) DEFAULT 0,
    `resolution_duration` VARCHAR(64) NULL,
    `prepared_by` VARCHAR(191) NULL,
    `agreement_body` LONGTEXT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `monthly_income` DECIMAL(14,2) NULL,
    `executed_date` VARCHAR(32) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_agreements_number` (`agreement_number`),
    INDEX `idx_agreements_client` (`client_id`),
    INDEX `idx_agreements_fee_plan` (`fee_plan_id`),
    CONSTRAINT `fk_agreements_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_agreements_fee_plan` FOREIGN KEY (`fee_plan_id`) REFERENCES `fee_plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 23. advocate_assignment_history
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `advocate_assignment_history` (
    `id` VARCHAR(64) NOT NULL,
    `client_id` VARCHAR(64) NOT NULL,
    `previous_advocate_id` VARCHAR(64) NULL,
    `previous_advocate_name` VARCHAR(191) NULL,
    `new_advocate_id` VARCHAR(64) NULL,
    `new_advocate_name` VARCHAR(191) NULL,
    `assigned_by_id` VARCHAR(64) NULL,
    `assigned_by_name` VARCHAR(191) NULL,
    `assigned_by_role` VARCHAR(64) NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_adv_assign_hist_client` (`client_id`),
    CONSTRAINT `fk_adv_assign_hist_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 24. legal_notices
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `legal_notices` (
    `id` VARCHAR(64) NOT NULL,
    `notice_number` VARCHAR(64) NOT NULL,
    `client_id` VARCHAR(64) NULL,
    `client_name` VARCHAR(191) NULL,
    `bank_name` VARCHAR(191) NOT NULL,
    `loan_account_no` VARCHAR(128) NULL,
    `notice_type` VARCHAR(128) NOT NULL DEFAULT 'Anti-Harassment Notice',
    `notice_subject` VARCHAR(255) NULL,
    `notice_content` LONGTEXT NULL,
    `notice_date` VARCHAR(32) NOT NULL,
    `dispatch_date` VARCHAR(32) NULL,
    `speed_post_number` VARCHAR(128) NULL,
    `tracking_url` TEXT NULL,
    `status` VARCHAR(64) NOT NULL DEFAULT 'Draft',
    `advocate_id` VARCHAR(64) NULL,
    `advocate_name` VARCHAR(191) NULL,
    `created_by` VARCHAR(64) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_legal_notices_number` (`notice_number`),
    INDEX `idx_legal_notices_client` (`client_id`),
    INDEX `idx_legal_notices_advocate` (`advocate_id`),
    CONSTRAINT `fk_legal_notices_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_legal_notices_advocate` FOREIGN KEY (`advocate_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 25. demand_notices
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `demand_notices` (
    `id` VARCHAR(64) NOT NULL,
    `demand_number` VARCHAR(64) NOT NULL,
    `client_id` VARCHAR(64) NULL,
    `client_name` VARCHAR(191) NULL,
    `bank_name` VARCHAR(191) NOT NULL,
    `loan_account_no` VARCHAR(128) NULL,
    `demand_type` VARCHAR(128) NOT NULL DEFAULT 'Incoming Loan Recall Demand',
    `demand_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `settlement_offer_amount` DECIMAL(14,2) DEFAULT 0,
    `notice_date` VARCHAR(32) NOT NULL,
    `reply_due_date` VARCHAR(32) NULL,
    `status` VARCHAR(64) NOT NULL DEFAULT 'Pending Review',
    `remarks` TEXT NULL,
    `file_attachment` LONGTEXT NULL,
    `advocate_id` VARCHAR(64) NULL,
    `advocate_name` VARCHAR(191) NULL,
    `created_by` VARCHAR(64) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_demand_notices_number` (`demand_number`),
    INDEX `idx_demand_notices_client` (`client_id`),
    INDEX `idx_demand_notices_advocate` (`advocate_id`),
    CONSTRAINT `fk_demand_notices_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_demand_notices_advocate` FOREIGN KEY (`advocate_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 26. client_complaints
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `client_complaints` (
    `id` VARCHAR(64) NOT NULL,
    `complaint_number` VARCHAR(64) NOT NULL,
    `client_id` VARCHAR(64) NULL,
    `client_name` VARCHAR(191) NOT NULL,
    `complaint_type` VARCHAR(128) NOT NULL DEFAULT 'RBI Complaint (Twitter / X)',
    `bank_name` VARCHAR(191) NOT NULL,
    `loan_account_no` VARCHAR(128) NULL,
    `tweet_url` TEXT NULL,
    `complaint_ref_no` VARCHAR(128) NULL,
    `screenshot_url` LONGTEXT NULL,
    `file_name` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `status` VARCHAR(64) NOT NULL DEFAULT 'Filed',
    `created_by` VARCHAR(64) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_client_complaints_number` (`complaint_number`),
    INDEX `idx_client_complaints_client` (`client_id`),
    CONSTRAINT `fk_client_complaints_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 27. monthly_payment_records
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `monthly_payment_records` (
    `id` VARCHAR(64) NOT NULL,
    `client_id` VARCHAR(64) NOT NULL,
    `agreement_id` VARCHAR(64) NULL,
    `month_number` INT NOT NULL,
    `due_date` VARCHAR(32) NOT NULL,
    `expected_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `received_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `payment_date` VARCHAR(32) NULL,
    `payment_status` VARCHAR(64) NOT NULL DEFAULT 'Pending',
    `remarks` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_monthly_records_client` (`client_id`),
    INDEX `idx_monthly_records_agreement` (`agreement_id`),
    INDEX `idx_monthly_records_due` (`due_date`),
    INDEX `idx_monthly_records_status` (`payment_status`),
    CONSTRAINT `fk_monthly_records_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_monthly_records_agreement` FOREIGN KEY (`agreement_id`) REFERENCES `agreements` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 28. payments
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
    `id` VARCHAR(64) NOT NULL,
    `receipt_number` VARCHAR(64) NULL,
    `client_id` VARCHAR(64) NULL,
    `agreement_id` VARCHAR(64) NULL,
    `amount` DECIMAL(14,2) NOT NULL,
    `payment_method` VARCHAR(64) NOT NULL,
    `transaction_id` VARCHAR(128) NULL,
    `status` VARCHAR(64) NOT NULL DEFAULT 'pending_verification',
    `verified_by` VARCHAR(64) NULL,
    `verified_at` DATETIME NULL,
    `payment_date` VARCHAR(32) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payments_receipt_number` (`receipt_number`),
    INDEX `idx_payments_client` (`client_id`),
    INDEX `idx_payments_agreement` (`agreement_id`),
    INDEX `idx_payments_status` (`status`),
    CONSTRAINT `fk_payments_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_payments_agreement` FOREIGN KEY (`agreement_id`) REFERENCES `agreements` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 29. monthly_payment_history
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `monthly_payment_history` (
    `id` VARCHAR(64) NOT NULL,
    `monthly_record_id` VARCHAR(64) NOT NULL,
    `client_id` VARCHAR(64) NOT NULL,
    `agreement_id` VARCHAR(64) NULL,
    `month_number` INT NOT NULL,
    `payment_date` VARCHAR(32) NOT NULL,
    `amount_received` DECIMAL(14,2) NOT NULL,
    `cumulative_received` DECIMAL(14,2) NOT NULL,
    `payment_status` VARCHAR(64) NOT NULL,
    `remarks` TEXT NULL,
    `updated_by_id` VARCHAR(64) NULL,
    `updated_by_name` VARCHAR(191) NULL,
    `updated_by_role` VARCHAR(64) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_mph_record` (`monthly_record_id`),
    INDEX `idx_mph_client` (`client_id`),
    INDEX `idx_mph_agreement` (`agreement_id`),
    CONSTRAINT `fk_mph_record` FOREIGN KEY (`monthly_record_id`) REFERENCES `monthly_payment_records` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mph_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mph_agreement` FOREIGN KEY (`agreement_id`) REFERENCES `agreements` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 30. payment_due_notifications
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_due_notifications` (
    `id` VARCHAR(64) NOT NULL,
    `client_id` VARCHAR(64) NOT NULL,
    `agreement_id` VARCHAR(64) NULL,
    `monthly_record_id` VARCHAR(64) NULL,
    `month_number` INT NOT NULL,
    `notification_type` VARCHAR(64) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `due_date` VARCHAR(32) NOT NULL,
    `expected_amount` DECIMAL(14,2) NOT NULL DEFAULT 8000,
    `received_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `pending_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `days_overdue` INT NOT NULL DEFAULT 0,
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `is_read` TINYINT NOT NULL DEFAULT 0,
    `employee_id` VARCHAR(64) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_notif_client_month_due_type` (`client_id`, `month_number`, `due_date`, `notification_type`),
    INDEX `idx_notif_client` (`client_id`),
    INDEX `idx_notif_monthly_record` (`monthly_record_id`),
    INDEX `idx_notif_employee` (`employee_id`),
    INDEX `idx_notif_status` (`status`),
    INDEX `idx_notif_is_read` (`is_read`),
    CONSTRAINT `fk_notif_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notif_agreement` FOREIGN KEY (`agreement_id`) REFERENCES `agreements` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_notif_monthly_record` FOREIGN KEY (`monthly_record_id`) REFERENCES `monthly_payment_records` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notif_employee` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
