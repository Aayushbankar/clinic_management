-- Clinic Management System (CMS) - Seed Data
-- This file assumes the schema from database/schema.sql has been applied.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

START TRANSACTION;

-- Users (password hashes are bcrypt)
INSERT INTO users (user_id, user_name, login_email, password, role, status, created_at)
VALUES
  (1, 'Admin',  'admin@clinic.test',   '$2b$12$RsllK6Ur6VRvAUTOl18RDeAQyhPSEs.fNVnQEs7jCuBXsByatsKpO', 'admin',  'active', NOW()),
  (2, 'Dr. John','doctor@clinic.test', '$2b$12$G0jGdP8f.JmYgFeYRKsIiOKaqzqj6QjKrOTTD9WqryBNR2VBBxTg2', 'doctor', 'active', NOW()),
  (3, 'Reception','staff@clinic.test','$2b$12$zeX66K7qN43Z2XjijnoJSOFMEFQwVuv4lnNAc52xUi1217BVpaJii', 'staff',  'active', NOW()),
  (4, 'Jane Patient','patient@clinic.test','$2b$12$C9lGsaD9NWgID42UJTLwDO2wGAmLP0jhiA/pbXUa3siqA41BZ9bja', 'patient','active', NOW());

-- Departments
INSERT INTO departments (department_id, department_name, description)
VALUES
  (1, 'General Medicine', 'Primary care and general health consultations'),
  (2, 'Cardiology', 'Heart and cardiovascular care');

-- Staff profile (linked to users)
INSERT INTO staff (staff_id, user_id, role, salary, joining_date, status)
VALUES
  (1, 3, 'Receptionist', 35000.00, '2025-06-01', 'active');

-- Doctor profile (linked to users)
INSERT INTO doctors (doctor_id, user_id, name, specialization, qualification, mobile, experience, consultation_fee, status, department_id)
VALUES
  (1, 2, 'Dr. John Doe', 'General Physician', 'MBBS', '9999999999', 8, 500.00, 'active', 1);

-- Patient profile (linked to users)
INSERT INTO patients (patient_id, user_id, name, gender, dob, mobile, address, blood_group, created_at)
VALUES
  (1, 4, 'Jane Patient', 'female', '1995-04-12', '8888888888', '123 Main Street, City', 'O+', NOW());

-- Clinic settings (single row recommended)
INSERT INTO clinic_settings (setting_id, clinic_name, address, contact, email)
VALUES
  (1, 'CityCare Clinic', '456 Clinic Avenue, City', '+1-555-0100', 'info@clinic.test');

-- Doctor schedule
INSERT INTO doctor_schedule (schedule_id, doctor_id, day, start_time, end_time, max_patients)
VALUES
  (1, 1, 'Monday',    '09:00:00', '13:00:00', 10),
  (2, 1, 'Tuesday',   '09:00:00', '13:00:00', 10),
  (3, 1, 'Wednesday', '09:00:00', '13:00:00', 10),
  (4, 1, 'Thursday',  '09:00:00', '13:00:00', 10),
  (5, 1, 'Friday',    '09:00:00', '13:00:00', 10);

-- Medicines
INSERT INTO medicines (medicine_id, medicine_name, company, price, stock, expiry_date)
VALUES
  (1, 'Paracetamol 500mg', 'HealthPharma', 20.00, 200, '2027-12-31'),
  (2, 'Amoxicillin 500mg', 'MediCorp',     55.00, 120, '2027-06-30'),
  (3, 'Vitamin D3',        'NutriLabs',    150.00, 80, '2028-01-31');

-- Appointments
INSERT INTO appointments (appointment_id, patient_id, doctor_id, appointment_date, appointment_time, status, created_at)
VALUES
  (1, 1, 1, '2026-02-03', '10:00:00', 'scheduled', NOW());

-- Patient history
INSERT INTO patient_history (history_id, patient_id, doctor_id, visit_date, notes, diagnosis, created_at)
VALUES
  (1, 1, 1, '2026-01-20', 'Fever and headache for 2 days.', 'Viral fever', NOW());

-- Billing + items
INSERT INTO billing (bill_id, patient_id, total_amount, bill_date, created_at)
VALUES
  (1, 1, 0.00, '2026-02-01', NOW());

INSERT INTO billing_items (item_id, bill_id, description, quantity, price, total)
VALUES
  (1, 1, 'Consultation Fee (Dr. John Doe)', 1, 500.00, 500.00),
  (2, 1, 'Paracetamol 500mg', 2, 20.00, 40.00);

UPDATE billing
SET total_amount = (
  SELECT COALESCE(SUM(total), 0)
  FROM billing_items
  WHERE bill_id = 1
)
WHERE bill_id = 1;

-- Payment (partial example)
INSERT INTO payments (payment_id, bill_id, payment_mode, amount, payment_date)
VALUES
  (1, 1, 'cash', 540.00, '2026-02-01 11:00:00');

-- Feedback
INSERT INTO feedback (feedback_id, patient_id, rating, comments, created_at)
VALUES
  (1, 1, 5, 'Great service and very professional staff.', NOW());

COMMIT;

