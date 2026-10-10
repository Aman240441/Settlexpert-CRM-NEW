-- ============================================================================
-- SETTLEXPERT CRM — MYSQL / MARIADB PRODUCTION SCHEMA
-- ============================================================================

CREATE DATABASE IF NOT EXISTS settlexpert_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE settlexpert_crm;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Departments
CREATE TABLE IF NOT EXISTS departments (
  id VARCHAR(64) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  code VARCHAR(64) NOT NULL UNIQUE,
  description TEXT,
  head_manager_id VARCHAR(64),
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Manager Types
CREATE TABLE IF NOT EXISTS manager_types (
  id VARCHAR(64) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  code VARCHAR(64) NOT NULL UNIQUE,
  description TEXT,
  permissions_json TEXT,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Users (Admin, Managers, Employees, Advocates)
CREATE TABLE IF NOT EXISTS users (
  id VARCHAR(64) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  phone VARCHAR(50),
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'manager', 'employee', 'advocate') NOT NULL,
  manager_id VARCHAR(64),
  department_id VARCHAR(64),
  team_id VARCHAR(64),
  manager_type_id VARCHAR(64),
  emp_or_mgr_id VARCHAR(100) UNIQUE,
  profile_image TEXT,
  joining_date VARCHAR(50),
  id_type VARCHAR(50),
  id_front TEXT,
  id_back TEXT,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  failed_login_attempts INT DEFAULT 0,
  locked_until DATETIME NULL,
  last_failed_login_at DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_email (email),
  INDEX idx_users_role (role),
  INDEX idx_users_manager (manager_id),
  INDEX idx_users_department (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Teams
CREATE TABLE IF NOT EXISTS teams (
  id VARCHAR(64) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  code VARCHAR(64) NOT NULL UNIQUE,
  department_id VARCHAR(64),
  manager_id VARCHAR(64),
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. User Permissions
CREATE TABLE IF NOT EXISTS user_permissions (
  id VARCHAR(64) PRIMARY KEY,
  user_id VARCHAR(64) NOT NULL,
  module VARCHAR(100) NOT NULL,
  can_view TINYINT(1) NOT NULL DEFAULT 1,
  can_create TINYINT(1) NOT NULL DEFAULT 0,
  can_edit TINYINT(1) NOT NULL DEFAULT 0,
  can_delete TINYINT(1) NOT NULL DEFAULT 0,
  can_assign TINYINT(1) NOT NULL DEFAULT 0,
  can_verify TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_user_module (user_id, module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Advocates
CREATE TABLE IF NOT EXISTS advocates (
  id VARCHAR(64) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  advocate_id VARCHAR(100) NOT NULL UNIQUE,
  mobile VARCHAR(50) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  registration_number VARCHAR(100) NOT NULL UNIQUE,
  specialization VARCHAR(255) NOT NULL,
  address TEXT,
  profile_image TEXT,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Fee Plans
CREATE TABLE IF NOT EXISTS fee_plans (
  id VARCHAR(64) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  duration VARCHAR(100) NOT NULL,
  default_fee DECIMAL(12, 2) NOT NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Audit Logs
CREATE TABLE IF NOT EXISTS audit_logs (
  id VARCHAR(64) PRIMARY KEY,
  user_id VARCHAR(64),
  user_name VARCHAR(255),
  role VARCHAR(50),
  action VARCHAR(255) NOT NULL,
  module VARCHAR(100) NOT NULL,
  record_id VARCHAR(64),
  details_json TEXT,
  ip_address VARCHAR(100),
  user_agent VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Leads
CREATE TABLE IF NOT EXISTS leads (
  id VARCHAR(64) PRIMARY KEY,
  lead_number VARCHAR(100) UNIQUE NOT NULL,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  email VARCHAR(255),
  city VARCHAR(100),
  state VARCHAR(100),
  total_debt DECIMAL(12, 2) DEFAULT 0,
  monthly_income DECIMAL(12, 2) DEFAULT 0,
  service_needed VARCHAR(255),
  paying_emis VARCHAR(50) DEFAULT 'Yes',
  harassment_calls VARCHAR(50) DEFAULT 'No',
  employment_status VARCHAR(100) DEFAULT 'Employed',
  employment_type VARCHAR(100) DEFAULT 'Salaried',
  settlement_needed VARCHAR(50) DEFAULT 'Yes',
  consultation_timing VARCHAR(100),
  credit_card_dues DECIMAL(12, 2) DEFAULT 0,
  personal_loan_dues DECIMAL(12, 2) DEFAULT 0,
  service_fee DECIMAL(12, 2) DEFAULT 0,
  bank_name VARCHAR(255),
  status ENUM('new', 'contacted', 'interested', 'in_progress', 'converted', 'dropped', 'unassigned') NOT NULL DEFAULT 'new',
  manager_id VARCHAR(64),
  employee_id VARCHAR(64),
  department_id VARCHAR(64),
  created_by VARCHAR(64),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_leads_status (status),
  INDEX idx_leads_employee (employee_id),
  INDEX idx_leads_manager (manager_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Follow-ups
CREATE TABLE IF NOT EXISTS follow_ups (
  id VARCHAR(64) PRIMARY KEY,
  lead_id VARCHAR(64) NOT NULL,
  user_id VARCHAR(64),
  user_name VARCHAR(255),
  call_status VARCHAR(100) NOT NULL,
  interested_level VARCHAR(100),
  final_status VARCHAR(100) NOT NULL,
  remark TEXT,
  next_follow_up_date VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_follow_ups_lead (lead_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Clients
CREATE TABLE IF NOT EXISTS clients (
  id VARCHAR(64) PRIMARY KEY,
  client_number VARCHAR(100) UNIQUE NOT NULL,
  lead_id VARCHAR(64),
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255),
  phone VARCHAR(50) NOT NULL,
  city VARCHAR(100),
  employment_status VARCHAR(100) DEFAULT 'Employed',
  employment_type VARCHAR(100) DEFAULT 'Salaried',
  total_debt DECIMAL(12, 2) DEFAULT 0,
  monthly_income DECIMAL(12, 2) DEFAULT 0,
  credit_card_dues DECIMAL(12, 2) DEFAULT 0,
  personal_loan_dues DECIMAL(12, 2) DEFAULT 0,
  loan_type VARCHAR(255) DEFAULT 'Credit Card & Personal Loan',
  paying_emis VARCHAR(50) DEFAULT 'Yes',
  harassment_calls VARCHAR(50) DEFAULT 'No',
  settlement_needed VARCHAR(50) DEFAULT 'Yes',
  consultation_timing VARCHAR(100),
  settlement_target DECIMAL(12, 2) DEFAULT 0,
  sx_fee DECIMAL(12, 2) DEFAULT 0,
  fees_date VARCHAR(50),
  fees_status VARCHAR(50) DEFAULT 'Pending',
  pending_amount DECIMAL(12, 2) DEFAULT 0,
  total_received DECIMAL(12, 2) DEFAULT 0,
  status ENUM('active', 'inactive', 'dropped', 'closed') NOT NULL DEFAULT 'active',
  case_status ENUM('active', 'closed', 'on_hold', 'dropped') DEFAULT 'active',
  notes TEXT,
  employee_id VARCHAR(64),
  manager_id VARCHAR(64),
  advocate_id VARCHAR(64),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_clients_employee (employee_id),
  INDEX idx_clients_manager (manager_id),
  INDEX idx_clients_advocate (advocate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Lenders
CREATE TABLE IF NOT EXISTS lenders (
  id VARCHAR(64) PRIMARY KEY,
  client_id VARCHAR(64) NOT NULL,
  bank_name VARCHAR(255) NOT NULL,
  loan_type VARCHAR(100),
  balance DECIMAL(12, 2) DEFAULT 0,
  default_date VARCHAR(50),
  status VARCHAR(100) DEFAULT 'Defaulted',
  lender_email VARCHAR(255),
  pdf_url TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lenders_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Agreements
CREATE TABLE IF NOT EXISTS agreements (
  id VARCHAR(64) PRIMARY KEY,
  agreement_number VARCHAR(100) UNIQUE NOT NULL,
  client_id VARCHAR(64),
  fee_plan_id VARCHAR(64),
  name VARCHAR(255),
  phone VARCHAR(50),
  email VARCHAR(255),
  address TEXT,
  pin_number VARCHAR(20),
  dob VARCHAR(50),
  start_date VARCHAR(50),
  end_date VARCHAR(50),
  total_fee DECIMAL(12, 2) NOT NULL,
  monthly_fee DECIMAL(12, 2) DEFAULT 0,
  resolution_duration VARCHAR(100),
  prepared_by VARCHAR(255),
  status ENUM('draft', 'sent', 'signed', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
  agreement_body LONGTEXT,
  created_by VARCHAR(64),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_agreements_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Monthly Payment Records
CREATE TABLE IF NOT EXISTS monthly_payment_records (
  id VARCHAR(64) PRIMARY KEY,
  client_id VARCHAR(64) NOT NULL,
  agreement_id VARCHAR(64),
  month_number INT NOT NULL,
  due_date VARCHAR(50) NOT NULL,
  expected_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
  received_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
  payment_date VARCHAR(50),
  payment_status ENUM('Pending', 'Partially Paid', 'Paid', 'Overdue') NOT NULL DEFAULT 'Pending',
  remarks TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_mpr_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. Monthly Payment History
CREATE TABLE IF NOT EXISTS monthly_payment_history (
  id VARCHAR(64) PRIMARY KEY,
  monthly_record_id VARCHAR(64) NOT NULL,
  client_id VARCHAR(64) NOT NULL,
  agreement_id VARCHAR(64),
  month_number INT NOT NULL,
  payment_date VARCHAR(50) NOT NULL,
  amount_received DECIMAL(12, 2) NOT NULL,
  cumulative_received DECIMAL(12, 2) NOT NULL,
  payment_status VARCHAR(100) NOT NULL,
  remarks TEXT,
  updated_by_id VARCHAR(64),
  updated_by_name VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_mph_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. Payments (Receipts)
CREATE TABLE IF NOT EXISTS payments (
  id VARCHAR(64) PRIMARY KEY,
  receipt_number VARCHAR(100) UNIQUE,
  client_id VARCHAR(64),
  agreement_id VARCHAR(64),
  amount DECIMAL(12, 2) NOT NULL,
  payment_method VARCHAR(100) NOT NULL,
  transaction_id VARCHAR(255),
  status ENUM('pending_verification', 'verified', 'rejected') NOT NULL DEFAULT 'pending_verification',
  verified_by VARCHAR(255),
  verified_at DATETIME NULL,
  payment_date VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_payments_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. Tasks
CREATE TABLE IF NOT EXISTS tasks (
  id VARCHAR(64) PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  module VARCHAR(100),
  priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
  due_date VARCHAR(50),
  status ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  assigned_to VARCHAR(64),
  created_by VARCHAR(64),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 18. Advocate Assignment History
CREATE TABLE IF NOT EXISTS advocate_assignment_history (
  id VARCHAR(64) PRIMARY KEY,
  client_id VARCHAR(64) NOT NULL,
  previous_advocate_id VARCHAR(64),
  previous_advocate_name VARCHAR(255),
  new_advocate_id VARCHAR(64),
  new_advocate_name VARCHAR(255),
  assigned_by_id VARCHAR(64),
  assigned_by_name VARCHAR(255),
  assigned_by_role VARCHAR(50),
  notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 19. Payment Due Notifications
CREATE TABLE IF NOT EXISTS payment_due_notifications (
  id VARCHAR(64) PRIMARY KEY,
  client_id VARCHAR(64) NOT NULL,
  agreement_id VARCHAR(64),
  monthly_record_id VARCHAR(64),
  month_number INT NOT NULL,
  notification_type ENUM('PAYMENT_DUE_TODAY', 'PARTIAL_PAYMENT', 'PAYMENT_OVERDUE', 'PAYMENT_RECEIVED') NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  due_date VARCHAR(50) NOT NULL,
  expected_amount DECIMAL(12, 2) NOT NULL DEFAULT 8000,
  received_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
  pending_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
  days_overdue INT NOT NULL DEFAULT 0,
  status ENUM('active', 'resolved') NOT NULL DEFAULT 'active',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  employee_id VARCHAR(64),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_client_notification (client_id, month_number, due_date, notification_type),
  INDEX idx_pdn_client (client_id),
  INDEX idx_pdn_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 20. Lead Imports & Distributions
CREATE TABLE IF NOT EXISTS lead_imports (
  id VARCHAR(64) PRIMARY KEY,
  import_number VARCHAR(100) UNIQUE NOT NULL,
  file_name VARCHAR(255),
  uploaded_by VARCHAR(64),
  uploaded_by_name VARCHAR(255),
  uploaded_by_role VARCHAR(50),
  total_rows INT DEFAULT 0,
  valid_leads INT DEFAULT 0,
  duplicate_leads INT DEFAULT 0,
  invalid_rows INT DEFAULT 0,
  imported_leads INT DEFAULT 0,
  distribution_status VARCHAR(50) DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lead_distributions (
  id VARCHAR(64) PRIMARY KEY,
  distribution_number VARCHAR(100) UNIQUE NOT NULL,
  import_id VARCHAR(64),
  distributed_by VARCHAR(64),
  distributed_by_name VARCHAR(255),
  distributed_by_role VARCHAR(50),
  distribution_mode VARCHAR(100) NOT NULL,
  assignment_method VARCHAR(100) NOT NULL,
  total_leads INT NOT NULL,
  employee_count INT NOT NULL,
  summary_json TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 21. Staff Profiles, KYC & Advocate Details
CREATE TABLE IF NOT EXISTS staff_extended_profiles (
  user_id VARCHAR(64) PRIMARY KEY,
  date_of_birth VARCHAR(50),
  gender VARCHAR(20),
  father_name VARCHAR(255),
  mother_name VARCHAR(255),
  alt_phone VARCHAR(50),
  whatsapp_number VARCHAR(50),
  current_address TEXT,
  permanent_address TEXT,
  city VARCHAR(100),
  state VARCHAR(100),
  pin_code VARCHAR(20),
  designation VARCHAR(100),
  employment_status VARCHAR(50) DEFAULT 'Active',
  staff_type VARCHAR(50) DEFAULT 'Employee',
  reporting_manager_id VARCHAR(64),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS staff_kyc (
  id VARCHAR(64) PRIMARY KEY,
  user_id VARCHAR(64) NOT NULL UNIQUE,
  aadhaar_number VARCHAR(50),
  aadhaar_front_doc TEXT,
  aadhaar_back_doc TEXT,
  kyc_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
  verified_by VARCHAR(255),
  verified_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS staff_advocate_details (
  user_id VARCHAR(64) PRIMARY KEY,
  registration_number VARCHAR(100),
  bar_council_state VARCHAR(100),
  specialization VARCHAR(255),
  years_experience INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 25. Login Rate Limit Table (PHP Sliding-Window Rate Limiter)
CREATE TABLE IF NOT EXISTS login_rate_limit (
  ip_address VARCHAR(100) PRIMARY KEY,
  attempts INT NOT NULL DEFAULT 1,
  reset_at BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
