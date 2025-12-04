-- =====================================================
-- ZPPSU Admission System - PostgreSQL Schema for Supabase
-- =====================================================
-- Run this in Supabase SQL Editor to create all tables
-- =====================================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    firstname VARCHAR(255) NOT NULL,
    middlename VARCHAR(255),
    lastname VARCHAR(255) NOT NULL,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar TEXT,
    type SMALLINT DEFAULT 3, -- 1=Admin, 2=Staff, 3=Student
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP,
    last_login TIMESTAMP
);

-- System settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    contact VARCHAR(255),
    address TEXT,
    cover_img TEXT,
    short_name VARCHAR(50)
);

-- Schedule admission table (main student records)
CREATE TABLE IF NOT EXISTS schedule_admission (
    id SERIAL PRIMARY KEY,
    reference_number VARCHAR(50) UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    gender VARCHAR(10),
    age INT,
    phone_number VARCHAR(20),
    date_of_birth DATE,
    address TEXT,
    email VARCHAR(255),
    classification VARCHAR(255),
    grade_level VARCHAR(100),
    school_campus VARCHAR(100),
    lrn VARCHAR(50),
    previous_school VARCHAR(255),
    date_scheduled DATE,
    document TEXT,
    status VARCHAR(20) DEFAULT 'Pending',
    date_log TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    application_type VARCHAR(50) DEFAULT 'New Student',
    -- New columns for enhanced features
    time_slot VARCHAR(50),
    room_number VARCHAR(50),
    exam_result VARCHAR(20) DEFAULT 'Pending',
    exam_remarks TEXT,
    exam_score DECIMAL(5,2),
    admission_slip_generated SMALLINT DEFAULT 0,
    admission_slip_path VARCHAR(255),
    last_sms_sent TIMESTAMP,
    reminder_sent SMALLINT DEFAULT 0,
    qr_code_path VARCHAR(255),
    qr_token VARCHAR(100) UNIQUE,
    reschedule_count INT DEFAULT 0
);

-- Document uploads table
CREATE TABLE IF NOT EXISTS document_uploads (
    id SERIAL PRIMARY KEY,
    schedule_id INT NOT NULL REFERENCES schedule_admission(id) ON DELETE CASCADE,
    document_type VARCHAR(50) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Room assignments table
CREATE TABLE IF NOT EXISTS room_assignments (
    id SERIAL PRIMARY KEY,
    room_number VARCHAR(50) NOT NULL,
    campus VARCHAR(100) NOT NULL,
    capacity INT DEFAULT 30,
    is_active SMALLINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(room_number, campus)
);

-- SMS log table
CREATE TABLE IF NOT EXISTS sms_log (
    id SERIAL PRIMARY KEY,
    classification VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    message_type VARCHAR(50),
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reschedule history table
CREATE TABLE IF NOT EXISTS reschedule_history (
    id SERIAL PRIMARY KEY,
    schedule_id INT NOT NULL REFERENCES schedule_admission(id) ON DELETE CASCADE,
    old_date DATE,
    new_date DATE,
    old_time_slot VARCHAR(50),
    new_time_slot VARCHAR(50),
    old_room VARCHAR(50),
    new_room VARCHAR(50),
    reason TEXT,
    rescheduled_by INT,
    rescheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bulk reschedule log table
CREATE TABLE IF NOT EXISTS bulk_reschedule_log (
    id SERIAL PRIMARY KEY,
    original_date DATE NOT NULL,
    new_date DATE NOT NULL,
    reason TEXT,
    affected_count INT DEFAULT 0,
    send_sms SMALLINT DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- OTP table for verification
CREATE TABLE IF NOT EXISTS otp_verification (
    id SERIAL PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    verified SMALLINT DEFAULT 0
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_schedule_status ON schedule_admission(status);
CREATE INDEX IF NOT EXISTS idx_schedule_date ON schedule_admission(date_scheduled);
CREATE INDEX IF NOT EXISTS idx_schedule_campus ON schedule_admission(school_campus);
CREATE INDEX IF NOT EXISTS idx_sms_classification ON sms_log(classification, phone);
CREATE INDEX IF NOT EXISTS idx_qr_token ON schedule_admission(qr_token);

-- Insert default admin user (password: admin123)
INSERT INTO users (firstname, lastname, username, password, type) 
VALUES ('Admin', 'User', 'admin', MD5('admin123'), 1)
ON CONFLICT (username) DO NOTHING;

-- Insert default system settings
INSERT INTO system_settings (id, name, short_name) 
VALUES (1, 'ZPPSU Admission System', 'ZPPSU')
ON CONFLICT (id) DO NOTHING;

-- Insert sample room assignments
INSERT INTO room_assignments (room_number, campus, capacity) VALUES
('Room 101', 'ZPPSU MAIN', 30),
('Room 102', 'ZPPSU MAIN', 30),
('Room 103', 'ZPPSU MAIN', 25),
('Room 201', 'ZPPSU MAIN', 40),
('Room 101', 'ZPPSU CAMPUS 2', 30)
ON CONFLICT DO NOTHING;

-- Grant permissions (adjust as needed)
-- ALTER TABLE users ENABLE ROW LEVEL SECURITY;
-- ALTER TABLE schedule_admission ENABLE ROW LEVEL SECURITY;

