-- =====================================================
-- Dental Medical Management System - Database Initialization Script
-- Generated from Laravel Migrations
-- Date: 2025-12-24
-- =====================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS `dental_medical_management`
DEFAULT CHARACTER SET utf8mb4
DEFAULT COLLATE utf8mb4_unicode_ci;

USE `dental_medical_management`;

-- Set session variables for foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- Core System Tables
-- =====================================================

-- Table: users
-- Description: System users including doctors, administrators, and staff
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `surname` VARCHAR(255) NOT NULL,
  `othername` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `phone_no` VARCHAR(255) NOT NULL,
  `alternative_no` VARCHAR(255) NULL,
  `photo` VARCHAR(255) NULL,
  `nin` VARCHAR(255) NULL,
  `email_verified_at` TIMESTAMP NULL,
  `password` VARCHAR(255) NOT NULL,
  `last_seen` TIMESTAMP NULL,
  `remember_token` VARCHAR(100) NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: password_resets
-- Description: Password reset tokens
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  INDEX `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: failed_jobs
-- Description: Failed queue jobs
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: roles
-- Description: User roles (Admin, Doctor, Receptionist, etc.)
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: branches
-- Description: Clinic/Hospital branches
DROP TABLE IF EXISTS `branches`;
CREATE TABLE `branches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `is_active` ENUM('true', 'false') NOT NULL DEFAULT 'true',
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insurance and Patient Management
-- =====================================================

-- Table: insurance_companies
-- Description: Insurance companies for patient coverage
DROP TABLE IF EXISTS `insurance_companies`;
CREATE TABLE `insurance_companies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NULL,
  `phone_no` VARCHAR(255) NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: patients
-- Description: Patient records
DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_no` VARCHAR(255) NULL,
  `surname` VARCHAR(255) NOT NULL,
  `othername` VARCHAR(255) NOT NULL,
  `gender` ENUM('Male', 'Female') NULL,
  `age` VARCHAR(255) NULL,
  `email` VARCHAR(255) NULL,
  `phone_no` VARCHAR(255) NULL,
  `alternative_no` VARCHAR(255) NULL,
  `address` VARCHAR(255) NULL,
  `nin` VARCHAR(255) NULL,
  `photo` VARCHAR(255) NULL,
  `profession` VARCHAR(255) NULL,
  `next_of_kin` VARCHAR(255) NULL,
  `next_of_kin_no` VARCHAR(255) NULL,
  `next_of_kin_address` TEXT NULL,
  `has_insurance` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
  `insurance_company_id` BIGINT UNSIGNED NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Appointment Management
-- =====================================================

-- Table: appointments
-- Description: Patient appointments
DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `appointment_no` BIGINT UNSIGNED NULL UNIQUE,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `start_time` VARCHAR(255) NULL,
  `notes` LONGTEXT NULL,
  `status` ENUM('Waiting', 'Treatment Complete', 'Treatment Incomplete', 'Rejected') NOT NULL DEFAULT 'Waiting',
  `visit_information` ENUM('Single Treatment', 'Review Treatment') NULL,
  `doctor_id` BIGINT UNSIGNED NULL,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: book_appointments
-- Description: Online appointment booking requests
DROP TABLE IF EXISTS `book_appointments`;
CREATE TABLE `book_appointments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(255) NOT NULL,
  `phone_number` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NULL,
  `message` LONGTEXT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: online_bookings
-- Description: Online booking system for appointments
DROP TABLE IF EXISTS `online_bookings`;
CREATE TABLE `online_bookings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(255) NOT NULL,
  `phone_no` VARCHAR(255) NULL,
  `email` VARCHAR(255) NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `start_time` VARCHAR(255) NOT NULL,
  `message` LONGTEXT NULL,
  `visit_history` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
  `status` ENUM('Accepted', 'Rejected', 'Waiting') NOT NULL DEFAULT 'Waiting',
  `insurance_company_id` BIGINT UNSIGNED NULL,
  `branch_id` BIGINT UNSIGNED NULL,
  `sort_by` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: appointment_histories
-- Description: History of appointment changes and rescheduling
DROP TABLE IF EXISTS `appointment_histories`;
CREATE TABLE `appointment_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `start_time` VARCHAR(255) NOT NULL,
  `status` ENUM('Created', 'Rescheduled') NOT NULL DEFAULT 'Created',
  `message` LONGTEXT NULL,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Medical Services and Billing
-- =====================================================

-- Table: medical_services
-- Description: Available medical services and procedures
DROP TABLE IF EXISTS `medical_services`;
CREATE TABLE `medical_services` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: invoices
-- Description: Patient invoices for services rendered
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_no` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `status` ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: invoice_items
-- Description: Line items for invoices
DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE `invoice_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `qty` DOUBLE NULL,
  `amount` DOUBLE NOT NULL,
  `invoice_id` BIGINT UNSIGNED NOT NULL,
  `medical_service_id` BIGINT UNSIGNED NOT NULL,
  `doctor_id` BIGINT UNSIGNED NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: self_accounts
-- Description: Customer accounts for prepayment
DROP TABLE IF EXISTS `self_accounts`;
CREATE TABLE `self_accounts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_no` VARCHAR(255) NOT NULL UNIQUE,
  `account_holder` VARCHAR(255) NOT NULL,
  `holder_phone_no` VARCHAR(255) NULL,
  `holder_email` VARCHAR(255) NULL,
  `holder_address` VARCHAR(255) NULL,
  `is_active` ENUM('true', 'false') NOT NULL DEFAULT 'true',
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: self_account_deposits
-- Description: Deposits to customer accounts
DROP TABLE IF EXISTS `self_account_deposits`;
CREATE TABLE `self_account_deposits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `amount` DOUBLE NOT NULL,
  `payment_method` ENUM('Cash', 'Mobile Money', 'Cheque') NULL,
  `payment_date` DATE NULL,
  `self_account_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: invoice_payments
-- Description: Payments received for invoices
DROP TABLE IF EXISTS `invoice_payments`;
CREATE TABLE `invoice_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `amount` DOUBLE NOT NULL,
  `payment_date` DATE NULL,
  `payment_method` ENUM('Cash', 'Online Wallet', 'Insurance', 'Mobile Money', 'Cheque') NULL,
  `cheque_no` VARCHAR(255) NULL COMMENT 'if the client make a payment with a cheque',
  `account_name` VARCHAR(255) NULL COMMENT 'cheque account holder',
  `bank_name` VARCHAR(255) NULL COMMENT 'name of the bank',
  `invoice_id` BIGINT UNSIGNED NOT NULL,
  `insurance_company_id` BIGINT UNSIGNED NULL,
  `self_account_id` BIGINT UNSIGNED NULL,
  `branch_id` BIGINT UNSIGNED NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: quotations
-- Description: Price quotations for patients
DROP TABLE IF EXISTS `quotations`;
CREATE TABLE `quotations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quotation_no` VARCHAR(255) NULL,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: quotation_items
-- Description: Line items for quotations
DROP TABLE IF EXISTS `quotation_items`;
CREATE TABLE `quotation_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `qty` DOUBLE NULL,
  `amount` DOUBLE NOT NULL,
  `quotation_id` BIGINT UNSIGNED NOT NULL,
  `medical_service_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Medical Records
-- =====================================================

-- Table: surgeries
-- Description: Patient surgery history
DROP TABLE IF EXISTS `surgeries`;
CREATE TABLE `surgeries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `surgery` VARCHAR(255) NOT NULL,
  `surgery_date` DATE NOT NULL,
  `description` LONGTEXT NULL,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: chronic_diseases
-- Description: Patient chronic disease records
DROP TABLE IF EXISTS `chronic_diseases`;
CREATE TABLE `chronic_diseases` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `disease` LONGTEXT NULL,
  `status` ENUM('Active', 'Treated') NULL,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: allergies
-- Description: Patient allergy records
DROP TABLE IF EXISTS `allergies`;
CREATE TABLE `allergies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `drug` VARCHAR(255) NOT NULL,
  `body_reaction` VARCHAR(255) NULL,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: treatments
-- Description: Treatment records for appointments
DROP TABLE IF EXISTS `treatments`;
CREATE TABLE `treatments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `clinical_notes` LONGTEXT NULL,
  `treatment` LONGTEXT NULL,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: prescriptions
-- Description: Prescription records
DROP TABLE IF EXISTS `prescriptions`;
CREATE TABLE `prescriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `drug` VARCHAR(255) NOT NULL,
  `qty` VARCHAR(255) NOT NULL,
  `directions` TEXT NOT NULL,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: medical_cards
-- Description: Patient medical cards and X-rays
DROP TABLE IF EXISTS `medical_cards`;
CREATE TABLE `medical_cards` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `card_type` ENUM('X-ray', 'Medical Card') NULL,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: medical_card_items
-- Description: Medical card image files
DROP TABLE IF EXISTS `medical_card_items`;
CREATE TABLE `medical_card_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `card_photo` VARCHAR(255) NOT NULL,
  `medical_card_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: dental_charts
-- Description: Dental charting records
DROP TABLE IF EXISTS `dental_charts`;
CREATE TABLE `dental_charts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `treatment` ENUM('Fracture', 'Restoration', 'Extraction') NULL,
  `tooth` DOUBLE NULL,
  `section` DOUBLE NULL,
  `color` VARCHAR(255) NULL,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Expense Management
-- =====================================================

-- Table: expense_categories
-- Description: Categories for expenses
DROP TABLE IF EXISTS `expense_categories`;
CREATE TABLE `expense_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `chart_of_account_item_id` BIGINT UNSIGNED NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: suppliers
-- Description: Supplier information
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: expenses
-- Description: Purchase/expense records
DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_no` VARCHAR(255) NULL,
  `supplier_id` BIGINT UNSIGNED NULL,
  `supplier` VARCHAR(255) NULL,
  `purchase_date` DATE NOT NULL,
  `branch_id` BIGINT UNSIGNED NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: expense_items
-- Description: Line items for expenses
DROP TABLE IF EXISTS `expense_items`;
CREATE TABLE `expense_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `qty` DOUBLE NOT NULL,
  `price` DOUBLE NOT NULL,
  `expense_id` BIGINT UNSIGNED NULL,
  `expense_category_id` BIGINT UNSIGNED NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: expense_payments
-- Description: Payments for expenses
DROP TABLE IF EXISTS `expense_payments`;
CREATE TABLE `expense_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_date` DATE NOT NULL,
  `amount` DOUBLE NOT NULL,
  `payment_method` ENUM('Cash', 'Mobile Money', 'Cheque', 'Online Wallet') NULL,
  `payment_account_id` BIGINT UNSIGNED NULL,
  `expense_id` BIGINT UNSIGNED NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Human Resources Management
-- =====================================================

-- Table: employee_contracts
-- Description: Employee contract details
DROP TABLE IF EXISTS `employee_contracts`;
CREATE TABLE `employee_contracts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `start_date` DATE NOT NULL,
  `years` INT NOT NULL,
  `basic_salary` DOUBLE NOT NULL,
  `employee_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('Active', 'Expired') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: salary_advances
-- Description: Salary advances and payments
DROP TABLE IF EXISTS `salary_advances`;
CREATE TABLE `salary_advances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_classification` ENUM('Salary', 'Advance') NULL,
  `advance_amount` DOUBLE NOT NULL,
  `advance_month` VARCHAR(255) NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_method` ENUM('Cash', 'Bank Transfer', 'Cheque', 'Mobile Money', 'Online Wallet') NULL,
  `employee_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: pay_slips
-- Description: Employee payslips
DROP TABLE IF EXISTS `pay_slips`;
CREATE TABLE `pay_slips` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payslip_month` VARCHAR(255) NOT NULL,
  `employee_id` BIGINT UNSIGNED NOT NULL,
  `employee_contract_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: salary_allowances
-- Description: Salary allowances
DROP TABLE IF EXISTS `salary_allowances`;
CREATE TABLE `salary_allowances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `allowance` ENUM('House Rent Allowance', 'Medical Allowance', 'Bonus', 'Dearness Allowance', 'Travelling Allowance') NOT NULL,
  `allowance_amount` DOUBLE NOT NULL,
  `pay_slip_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: salary_deductions
-- Description: Salary deductions
DROP TABLE IF EXISTS `salary_deductions`;
CREATE TABLE `salary_deductions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `deduction` ENUM('Payee', 'NSSF') NOT NULL,
  `deduction_amount` DOUBLE NOT NULL,
  `pay_slip_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: holidays
-- Description: Holiday calendar
DROP TABLE IF EXISTS `holidays`;
CREATE TABLE `holidays` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `holiday_date` DATE NULL,
  `repeat_date` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: leave_types
-- Description: Types of leave (Annual, Sick, etc.)
DROP TABLE IF EXISTS `leave_types`;
CREATE TABLE `leave_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `max_days` DOUBLE NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: leave_requests
-- Description: Employee leave requests
DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE `leave_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `start_date` VARCHAR(255) NOT NULL UNIQUE,
  `duration` DOUBLE NULL COMMENT 'no of days',
  `status` ENUM('Pending Approval', 'Rejected', 'Approved') NOT NULL DEFAULT 'Pending Approval',
  `action_date` DATE NULL COMMENT 'date of approval',
  `leave_type_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `_approved_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Doctor Claims Management
-- =====================================================

-- Table: claim_rates
-- Description: Doctor claim rates for services
DROP TABLE IF EXISTS `claim_rates`;
CREATE TABLE `claim_rates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cash_rate` DOUBLE NOT NULL,
  `insurance_rate` DOUBLE NOT NULL,
  `status` ENUM('active', 'deactivated') NOT NULL DEFAULT 'active',
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: doctor_claims
-- Description: Doctor claims for services rendered
DROP TABLE IF EXISTS `doctor_claims`;
CREATE TABLE `doctor_claims` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `claim_amount` DOUBLE NOT NULL,
  `insurance_amount` DOUBLE NOT NULL DEFAULT 0,
  `cash_amount` DOUBLE NOT NULL DEFAULT 0,
  `status` ENUM('Pending', 'Approved') NOT NULL DEFAULT 'Pending',
  `claim_rate_id` BIGINT UNSIGNED NOT NULL,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: doctor_claim_payments
-- Description: Payments for doctor claims
DROP TABLE IF EXISTS `doctor_claim_payments`;
CREATE TABLE `doctor_claim_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `amount` DOUBLE NOT NULL,
  `payment_date` DATE NOT NULL,
  `doctor_claim_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Accounting System
-- =====================================================

-- Table: accounting_equations
-- Description: Accounting equation categories (Assets, Liabilities, Equity, etc.)
DROP TABLE IF EXISTS `accounting_equations`;
CREATE TABLE `accounting_equations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `sort_by` INT NOT NULL,
  `active_tab` ENUM('yes', 'no') NOT NULL DEFAULT 'no',
  `_who_added` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: chart_of_account_categories
-- Description: Chart of accounts categories
DROP TABLE IF EXISTS `chart_of_account_categories`;
CREATE TABLE `chart_of_account_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `accounting_equation_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: chart_of_account_items
-- Description: Chart of accounts items
DROP TABLE IF EXISTS `chart_of_account_items`;
CREATE TABLE `chart_of_account_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `chart_of_account_category_id` BIGINT UNSIGNED NOT NULL,
  `_who_added` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Communication and Notifications
-- =====================================================

-- Table: sms_loggings
-- Description: SMS message logs
DROP TABLE IF EXISTS `sms_loggings`;
CREATE TABLE `sms_loggings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone_number` VARCHAR(255) NOT NULL,
  `message` LONGTEXT NOT NULL,
  `cost` DOUBLE NOT NULL,
  `status` ENUM('success', 'failed') NOT NULL,
  `patient_id` BIGINT UNSIGNED NULL,
  `_who_added` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: sms_transactions
-- Description: SMS credit transactions
DROP TABLE IF EXISTS `sms_transactions`;
CREATE TABLE `sms_transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `amount` DOUBLE NOT NULL,
  `type` ENUM('topup', 'sms', 'airtime', 'mobile money') NOT NULL,
  `_who_added` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: birth_day_messages
-- Description: Birthday message templates
DROP TABLE IF EXISTS `birth_day_messages`;
CREATE TABLE `birth_day_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `message` LONGTEXT NULL,
  `_who_added` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: notifications
-- Description: System notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` CHAR(36) NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `notifiable_type` VARCHAR(255) NOT NULL,
  `notifiable_id` BIGINT UNSIGNED NOT NULL,
  `data` TEXT NOT NULL,
  `read_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`, `notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: billing_email_notifications
-- Description: Email notifications for billing
DROP TABLE IF EXISTS `billing_email_notifications`;
CREATE TABLE `billing_email_notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `message` LONGTEXT NULL,
  `item_id` DOUBLE NULL,
  `notification_type` ENUM('Invoice', 'Quotation', 'Self Account') NULL,
  `status` ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
  `_who_added` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Audit and Queue Management
-- =====================================================

-- Table: audits
-- Description: Audit trail for system changes
DROP TABLE IF EXISTS `audits`;
CREATE TABLE `audits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_type` VARCHAR(255) NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `event` VARCHAR(255) NOT NULL,
  `auditable_type` VARCHAR(255) NOT NULL,
  `auditable_id` BIGINT UNSIGNED NOT NULL,
  `old_values` TEXT NULL,
  `new_values` TEXT NULL,
  `url` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(1023) NULL,
  `tags` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `audits_user_id_user_type_index` (`user_id`, `user_type`),
  INDEX `audits_auditable_type_auditable_id_index` (`auditable_type`, `auditable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: jobs
-- Description: Queue jobs
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Foreign Key Constraints
-- =====================================================

-- Users table foreign keys
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_users_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);

-- Branches table foreign keys
ALTER TABLE `branches`
  ADD CONSTRAINT `fk_branches_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Insurance companies foreign keys
ALTER TABLE `insurance_companies`
  ADD CONSTRAINT `fk_insurance_companies_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Patients foreign keys
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patients_insurance_company_id` FOREIGN KEY (`insurance_company_id`) REFERENCES `insurance_companies` (`id`),
  ADD CONSTRAINT `fk_patients_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Appointments foreign keys
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_doctor_id` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_appointments_patient_id` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `fk_appointments_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_appointments_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Online bookings foreign keys
ALTER TABLE `online_bookings`
  ADD CONSTRAINT `fk_online_bookings_insurance_company_id` FOREIGN KEY (`insurance_company_id`) REFERENCES `insurance_companies` (`id`),
  ADD CONSTRAINT `fk_online_bookings_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);

-- Appointment histories foreign keys
ALTER TABLE `appointment_histories`
  ADD CONSTRAINT `fk_appointment_histories_appointment_id` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`);

-- Medical services foreign keys
ALTER TABLE `medical_services`
  ADD CONSTRAINT `fk_medical_services_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Invoices foreign keys
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoices_appointment_id` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`),
  ADD CONSTRAINT `fk_invoices_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Invoice items foreign keys
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `fk_invoice_items_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `fk_invoice_items_medical_service_id` FOREIGN KEY (`medical_service_id`) REFERENCES `medical_services` (`id`),
  ADD CONSTRAINT `fk_invoice_items_doctor_id` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_invoice_items_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Self accounts foreign keys
ALTER TABLE `self_accounts`
  ADD CONSTRAINT `fk_self_accounts_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Self account deposits foreign keys
ALTER TABLE `self_account_deposits`
  ADD CONSTRAINT `fk_self_account_deposits_self_account_id` FOREIGN KEY (`self_account_id`) REFERENCES `self_accounts` (`id`),
  ADD CONSTRAINT `fk_self_account_deposits_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Invoice payments foreign keys
ALTER TABLE `invoice_payments`
  ADD CONSTRAINT `fk_invoice_payments_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `fk_invoice_payments_insurance_company_id` FOREIGN KEY (`insurance_company_id`) REFERENCES `insurance_companies` (`id`),
  ADD CONSTRAINT `fk_invoice_payments_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_invoice_payments_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Quotations foreign keys
ALTER TABLE `quotations`
  ADD CONSTRAINT `fk_quotations_patient_id` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `fk_quotations_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Quotation items foreign keys
ALTER TABLE `quotation_items`
  ADD CONSTRAINT `fk_quotation_items_quotation_id` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`),
  ADD CONSTRAINT `fk_quotation_items_medical_service_id` FOREIGN KEY (`medical_service_id`) REFERENCES `medical_services` (`id`),
  ADD CONSTRAINT `fk_quotation_items_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Surgeries foreign keys
ALTER TABLE `surgeries`
  ADD CONSTRAINT `fk_surgeries_patient_id` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `fk_surgeries_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Chronic diseases foreign keys
ALTER TABLE `chronic_diseases`
  ADD CONSTRAINT `fk_chronic_diseases_patient_id` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `fk_chronic_diseases_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Allergies foreign keys
ALTER TABLE `allergies`
  ADD CONSTRAINT `fk_allergies_patient_id` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `fk_allergies_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Treatments foreign keys
ALTER TABLE `treatments`
  ADD CONSTRAINT `fk_treatments_appointment_id` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`),
  ADD CONSTRAINT `fk_treatments_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Prescriptions foreign keys
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `fk_prescriptions_appointment_id` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`),
  ADD CONSTRAINT `fk_prescriptions_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Medical cards foreign keys
ALTER TABLE `medical_cards`
  ADD CONSTRAINT `fk_medical_cards_patient_id` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `fk_medical_cards_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Medical card items foreign keys
ALTER TABLE `medical_card_items`
  ADD CONSTRAINT `fk_medical_card_items_medical_card_id` FOREIGN KEY (`medical_card_id`) REFERENCES `medical_cards` (`id`),
  ADD CONSTRAINT `fk_medical_card_items_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Dental charts foreign keys
ALTER TABLE `dental_charts`
  ADD CONSTRAINT `fk_dental_charts_appointment_id` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`),
  ADD CONSTRAINT `fk_dental_charts_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Suppliers foreign keys
ALTER TABLE `suppliers`
  ADD CONSTRAINT `fk_suppliers_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Expenses foreign keys
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_supplier_id` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `fk_expenses_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_expenses_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Expense items foreign keys
ALTER TABLE `expense_items`
  ADD CONSTRAINT `fk_expense_items_expense_id` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`),
  ADD CONSTRAINT `fk_expense_items_expense_category_id` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`),
  ADD CONSTRAINT `fk_expense_items_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Expense payments foreign keys
ALTER TABLE `expense_payments`
  ADD CONSTRAINT `fk_expense_payments_expense_id` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`),
  ADD CONSTRAINT `fk_expense_payments_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Employee contracts foreign keys
ALTER TABLE `employee_contracts`
  ADD CONSTRAINT `fk_employee_contracts_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_employee_contracts_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Salary advances foreign keys
ALTER TABLE `salary_advances`
  ADD CONSTRAINT `fk_salary_advances_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_salary_advances_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Pay slips foreign keys
ALTER TABLE `pay_slips`
  ADD CONSTRAINT `fk_pay_slips_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_pay_slips_employee_contract_id` FOREIGN KEY (`employee_contract_id`) REFERENCES `employee_contracts` (`id`),
  ADD CONSTRAINT `fk_pay_slips_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Salary allowances foreign keys
ALTER TABLE `salary_allowances`
  ADD CONSTRAINT `fk_salary_allowances_pay_slip_id` FOREIGN KEY (`pay_slip_id`) REFERENCES `pay_slips` (`id`),
  ADD CONSTRAINT `fk_salary_allowances_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Salary deductions foreign keys
ALTER TABLE `salary_deductions`
  ADD CONSTRAINT `fk_salary_deductions_pay_slip_id` FOREIGN KEY (`pay_slip_id`) REFERENCES `pay_slips` (`id`),
  ADD CONSTRAINT `fk_salary_deductions_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Holidays foreign keys
ALTER TABLE `holidays`
  ADD CONSTRAINT `fk_holidays_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Leave types foreign keys
ALTER TABLE `leave_types`
  ADD CONSTRAINT `fk_leave_types_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Leave requests foreign keys
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `fk_leave_requests_leave_type_id` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`),
  ADD CONSTRAINT `fk_leave_requests_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_leave_requests_approved_by` FOREIGN KEY (`_approved_by`) REFERENCES `users` (`id`);

-- Claim rates foreign keys
ALTER TABLE `claim_rates`
  ADD CONSTRAINT `fk_claim_rates_doctor_id` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_claim_rates_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Doctor claims foreign keys
ALTER TABLE `doctor_claims`
  ADD CONSTRAINT `fk_doctor_claims_claim_rate_id` FOREIGN KEY (`claim_rate_id`) REFERENCES `claim_rates` (`id`),
  ADD CONSTRAINT `fk_doctor_claims_appointment_id` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`),
  ADD CONSTRAINT `fk_doctor_claims_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_doctor_claims_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

-- Doctor claim payments foreign keys
ALTER TABLE `doctor_claim_payments`
  ADD CONSTRAINT `fk_doctor_claim_payments_doctor_claim_id` FOREIGN KEY (`doctor_claim_id`) REFERENCES `doctor_claims` (`id`),
  ADD CONSTRAINT `fk_doctor_claim_payments_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Accounting equations foreign keys
ALTER TABLE `accounting_equations`
  ADD CONSTRAINT `fk_accounting_equations_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Chart of account categories foreign keys
ALTER TABLE `chart_of_account_categories`
  ADD CONSTRAINT `fk_chart_of_account_categories_accounting_equation_id` FOREIGN KEY (`accounting_equation_id`) REFERENCES `accounting_equations` (`id`),
  ADD CONSTRAINT `fk_chart_of_account_categories_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Chart of account items foreign keys
ALTER TABLE `chart_of_account_items`
  ADD CONSTRAINT `fk_chart_of_account_items_chart_of_account_category_id` FOREIGN KEY (`chart_of_account_category_id`) REFERENCES `chart_of_account_categories` (`id`),
  ADD CONSTRAINT `fk_chart_of_account_items_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Expense categories foreign keys (after chart_of_account_items is created)
ALTER TABLE `expense_categories`
  ADD CONSTRAINT `fk_expense_categories_chart_of_account_item_id` FOREIGN KEY (`chart_of_account_item_id`) REFERENCES `chart_of_account_items` (`id`),
  ADD CONSTRAINT `fk_expense_categories_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Expense payments foreign keys (payment_account_id)
ALTER TABLE `expense_payments`
  ADD CONSTRAINT `fk_expense_payments_payment_account_id` FOREIGN KEY (`payment_account_id`) REFERENCES `chart_of_account_items` (`id`);

-- SMS loggings foreign keys
ALTER TABLE `sms_loggings`
  ADD CONSTRAINT `fk_sms_loggings_patient_id` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `fk_sms_loggings_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- SMS transactions foreign keys
ALTER TABLE `sms_transactions`
  ADD CONSTRAINT `fk_sms_transactions_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Birth day messages foreign keys
ALTER TABLE `birth_day_messages`
  ADD CONSTRAINT `fk_birth_day_messages_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Billing email notifications foreign keys
ALTER TABLE `billing_email_notifications`
  ADD CONSTRAINT `fk_billing_email_notifications_who_added` FOREIGN KEY (`_who_added`) REFERENCES `users` (`id`);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- End of Database Initialization Script
-- =====================================================
