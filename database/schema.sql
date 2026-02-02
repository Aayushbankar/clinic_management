-- Clinic Management System (CMS)
-- Database: MySQL 8+
-- This schema implements all tables/columns listed in requirement.pdf,
-- with additional keys/constraints needed for integrity and performance.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Create database (optional). Uncomment if you want the script to create it.
-- CREATE DATABASE IF NOT EXISTS clinic_management
--   CHARACTER SET utf8mb4
--   COLLATE utf8mb4_unicode_ci;
-- USE clinic_management;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS clinic_settings;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS billing_items;
DROP TABLE IF EXISTS billing;
DROP TABLE IF EXISTS medicines;
DROP TABLE IF EXISTS patient_history;
DROP TABLE IF EXISTS doctor_schedule;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS staff;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1) users (Login common table)
CREATE TABLE users (
  user_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_name` VARCHAR(120) NOT NULL,
  login_email VARCHAR(190) NOT NULL,
  `password` VARCHAR(255) NOT NULL, -- stores PASSWORD HASH (bcrypt)
  role ENUM('admin', 'doctor', 'staff', 'patient') NOT NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_login_email (login_email),
  KEY idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) departments
CREATE TABLE departments (
  department_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  department_name VARCHAR(120) NOT NULL,
  `description` TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (department_id),
  UNIQUE KEY uq_departments_name (department_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12) staff
-- requirement.pdf columns: staff_id, role, salary, joining_date, status
-- Added: user_id link to users for login
CREATE TABLE staff (
  staff_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  role VARCHAR(80) NOT NULL,
  salary DECIMAL(10,2) NULL,
  joining_date DATE NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (staff_id),
  UNIQUE KEY uq_staff_user (user_id),
  KEY idx_staff_status (status),
  CONSTRAINT fk_staff_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) patients
CREATE TABLE patients (
  patient_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  gender ENUM('male', 'female', 'other') NULL,
  dob DATE NULL,
  mobile VARCHAR(20) NULL,
  address VARCHAR(255) NULL,
  blood_group VARCHAR(10) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (patient_id),
  UNIQUE KEY uq_patients_user (user_id),
  KEY idx_patients_name (name),
  KEY idx_patients_mobile (mobile),
  CONSTRAINT fk_patients_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) doctors
CREATE TABLE doctors (
  doctor_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  specialization VARCHAR(160) NULL,
  qualification VARCHAR(160) NULL,
  mobile VARCHAR(20) NULL,
  experience INT NULL,
  consultation_fee DECIMAL(10,2) NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  department_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (doctor_id),
  UNIQUE KEY uq_doctors_user (user_id),
  KEY idx_doctors_status (status),
  KEY idx_doctors_department (department_id),
  CONSTRAINT fk_doctors_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_doctors_department
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) doctor_schedule
CREATE TABLE doctor_schedule (
  schedule_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  doctor_id BIGINT UNSIGNED NOT NULL,
  day ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  max_patients INT NOT NULL DEFAULT 20,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (schedule_id),
  KEY idx_schedule_doctor_day (doctor_id, day),
  CONSTRAINT fk_schedule_doctor
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_schedule_times CHECK (start_time < end_time),
  CONSTRAINT chk_schedule_max_patients CHECK (max_patients > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) appointments
CREATE TABLE appointments (
  appointment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  patient_id BIGINT UNSIGNED NOT NULL,
  doctor_id BIGINT UNSIGNED NOT NULL,
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL,
  status ENUM('scheduled','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (appointment_id),
  KEY idx_appointments_doctor_date (doctor_id, appointment_date),
  KEY idx_appointments_patient_date (patient_id, appointment_date),
  UNIQUE KEY uq_appointments_doctor_slot (doctor_id, appointment_date, appointment_time),
  CONSTRAINT fk_appointments_patient
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_appointments_doctor
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7) patient_history
-- requirement.pdf columns: patient_id, doctor_id, visit_date
-- Added: history_id as primary key and optional clinical notes fields
CREATE TABLE patient_history (
  history_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  patient_id BIGINT UNSIGNED NOT NULL,
  doctor_id BIGINT UNSIGNED NOT NULL,
  visit_date DATE NOT NULL,
  notes TEXT NULL,
  diagnosis TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (history_id),
  KEY idx_history_patient_date (patient_id, visit_date),
  KEY idx_history_doctor_date (doctor_id, visit_date),
  CONSTRAINT fk_history_patient
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_history_doctor
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8) medicines
CREATE TABLE medicines (
  medicine_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  medicine_name VARCHAR(190) NOT NULL,
  company VARCHAR(190) NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  expiry_date DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (medicine_id),
  KEY idx_medicines_name (medicine_name),
  CONSTRAINT chk_medicines_price CHECK (price >= 0),
  CONSTRAINT chk_medicines_stock CHECK (stock >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9) billing
CREATE TABLE billing (
  bill_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  patient_id BIGINT UNSIGNED NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  bill_date DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (bill_id),
  KEY idx_billing_patient_date (patient_id, bill_date),
  CONSTRAINT fk_billing_patient
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_billing_total_amount CHECK (total_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10) billing_items
CREATE TABLE billing_items (
  item_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  bill_id BIGINT UNSIGNED NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (item_id),
  KEY idx_billing_items_bill (bill_id),
  CONSTRAINT fk_billing_items_bill
    FOREIGN KEY (bill_id) REFERENCES billing(bill_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_billing_items_qty CHECK (quantity > 0),
  CONSTRAINT chk_billing_items_price CHECK (price >= 0),
  CONSTRAINT chk_billing_items_total CHECK (total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11) payments
CREATE TABLE payments (
  payment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  bill_id BIGINT UNSIGNED NOT NULL,
  payment_mode VARCHAR(60) NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  payment_date DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (payment_id),
  KEY idx_payments_bill (bill_id),
  KEY idx_payments_date (payment_date),
  CONSTRAINT fk_payments_bill
    FOREIGN KEY (bill_id) REFERENCES billing(bill_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_payments_amount CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13) clinic_settings
CREATE TABLE clinic_settings (
  setting_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  clinic_name VARCHAR(190) NOT NULL,
  address VARCHAR(255) NULL,
  contact VARCHAR(50) NULL,
  email VARCHAR(190) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_id),
  KEY idx_settings_clinic_name (clinic_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14) reports
-- requirement.pdf columns: report_id, report_type, generated_by, generated_date
-- generated_by links to users.user_id
CREATE TABLE reports (
  report_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_type VARCHAR(80) NOT NULL,
  generated_by BIGINT UNSIGNED NOT NULL,
  generated_date DATETIME NOT NULL,
  params_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (report_id),
  KEY idx_reports_type_date (report_type, generated_date),
  KEY idx_reports_generated_by (generated_by),
  CONSTRAINT fk_reports_generated_by
    FOREIGN KEY (generated_by) REFERENCES users(user_id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15) feedback
CREATE TABLE feedback (
  feedback_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  patient_id BIGINT UNSIGNED NOT NULL,
  rating INT NOT NULL,
  comments TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (feedback_id),
  KEY idx_feedback_patient (patient_id),
  CONSTRAINT fk_feedback_patient
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_feedback_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16) login_rate_limits (security - rate limiting)
CREATE TABLE login_rate_limits (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip_address VARCHAR(45) NOT NULL,
  login_email VARCHAR(190) NULL,
  attempt_count INT NOT NULL DEFAULT 1,
  first_attempt_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  locked_until TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_rate_ip (ip_address),
  KEY idx_rate_email (login_email),
  KEY idx_rate_first_attempt (first_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Additional Performance Indexes
-- Improve appointment report queries
CREATE INDEX idx_appointments_status_date ON appointments(status, appointment_date);

-- Speed up billing lookups by date
CREATE INDEX idx_billing_date ON billing(bill_date);

-- Improve patient search
CREATE INDEX idx_patients_blood_group ON patients(blood_group);

-- Optimize medicine expiry checks
CREATE INDEX idx_medicines_expiry ON medicines(expiry_date);

-- 17) password_reset_tokens (for password recovery)
CREATE TABLE password_reset_tokens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  token VARCHAR(64) NOT NULL,
  expires_at TIMESTAMP NOT NULL,
  used_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_reset_token (token),
  KEY idx_reset_user (user_id),
  KEY idx_reset_expires (expires_at),
  CONSTRAINT fk_reset_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

