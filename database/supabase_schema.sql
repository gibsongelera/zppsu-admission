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
    type SMALLINT DEFAULT 3,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP,
    last_login TIMESTAMP
);

-- Add missing columns to users table if it already exists
DO $$ 
BEGIN
    -- Add type if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='users' AND column_name='type') THEN
        ALTER TABLE users ADD COLUMN type SMALLINT DEFAULT 3;
    END IF;
    
    -- Add avatar if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='users' AND column_name='avatar') THEN
        ALTER TABLE users ADD COLUMN avatar TEXT;
    END IF;
    
    -- Add middlename if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='users' AND column_name='middlename') THEN
        ALTER TABLE users ADD COLUMN middlename VARCHAR(255);
    END IF;
    
    -- Add date_updated if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='users' AND column_name='date_updated') THEN
        ALTER TABLE users ADD COLUMN date_updated TIMESTAMP;
    END IF;
    
    -- Add last_login if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='users' AND column_name='last_login') THEN
        ALTER TABLE users ADD COLUMN last_login TIMESTAMP;
    END IF;
END $$;

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
    qr_token VARCHAR(100),
    reschedule_count INT DEFAULT 0
);

-- Add columns if table already exists (safe to run multiple times)
DO $$ 
BEGIN
    -- Add time_slot if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='schedule_admission' AND column_name='time_slot') THEN
        ALTER TABLE schedule_admission ADD COLUMN time_slot VARCHAR(50);
    END IF;
    
    -- Add room_number if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='schedule_admission' AND column_name='room_number') THEN
        ALTER TABLE schedule_admission ADD COLUMN room_number VARCHAR(50);
    END IF;
    
    -- Add exam_result if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='schedule_admission' AND column_name='exam_result') THEN
        ALTER TABLE schedule_admission ADD COLUMN exam_result VARCHAR(20) DEFAULT 'Pending';
    END IF;
    
    -- Add exam_remarks if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='schedule_admission' AND column_name='exam_remarks') THEN
        ALTER TABLE schedule_admission ADD COLUMN exam_remarks TEXT;
    END IF;
    
    -- Add exam_score if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='schedule_admission' AND column_name='exam_score') THEN
        ALTER TABLE schedule_admission ADD COLUMN exam_score DECIMAL(5,2);
    END IF;
    
    -- Add admission_slip_generated if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='schedule_admission' AND column_name='admission_slip_generated') THEN
        ALTER TABLE schedule_admission ADD COLUMN admission_slip_generated SMALLINT DEFAULT 0;
    END IF;
    
    -- Add admission_slip_path if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='schedule_admission' AND column_name='admission_slip_path') THEN
        ALTER TABLE schedule_admission ADD COLUMN admission_slip_path VARCHAR(255);
    END IF;
    
    -- Add last_sms_sent if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='schedule_admission' AND column_name='last_sms_sent') THEN
        ALTER TABLE schedule_admission ADD COLUMN last_sms_sent TIMESTAMP;
    END IF;
    
    -- Add reminder_sent if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='schedule_admission' AND column_name='reminder_sent') THEN
        ALTER TABLE schedule_admission ADD COLUMN reminder_sent SMALLINT DEFAULT 0;
    END IF;
    
    -- Add qr_code_path if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='schedule_admission' AND column_name='qr_code_path') THEN
        ALTER TABLE schedule_admission ADD COLUMN qr_code_path VARCHAR(255);
    END IF;
    
    -- Add qr_token if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='schedule_admission' AND column_name='qr_token') THEN
        ALTER TABLE schedule_admission ADD COLUMN qr_token VARCHAR(100);
    END IF;
    
    -- Add reschedule_count if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='schedule_admission' AND column_name='reschedule_count') THEN
        ALTER TABLE schedule_admission ADD COLUMN reschedule_count INT DEFAULT 0;
    END IF;
END $$;

-- Document uploads table
CREATE TABLE IF NOT EXISTS document_uploads (
    id SERIAL PRIMARY KEY,
    schedule_id INT NOT NULL,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add unique constraint on room_assignments if it doesn't exist
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint 
        WHERE conname = 'room_assignments_room_campus_key'
    ) THEN
        ALTER TABLE room_assignments 
        ADD CONSTRAINT room_assignments_room_campus_key 
        UNIQUE (room_number, campus);
    END IF;
END $$;

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
    schedule_id INT NOT NULL,
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

-- Create indexes (only if column exists)
DO $$
BEGIN
    -- Index on status
    IF NOT EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'idx_schedule_status') THEN
        CREATE INDEX idx_schedule_status ON schedule_admission(status);
    END IF;
    
    -- Index on date_scheduled
    IF NOT EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'idx_schedule_date') THEN
        CREATE INDEX idx_schedule_date ON schedule_admission(date_scheduled);
    END IF;
    
    -- Index on school_campus
    IF NOT EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'idx_schedule_campus') THEN
        CREATE INDEX idx_schedule_campus ON schedule_admission(school_campus);
    END IF;
    
    -- Index on sms_log
    IF NOT EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'idx_sms_classification') THEN
        CREATE INDEX idx_sms_classification ON sms_log(classification, phone);
    END IF;
    
    -- Index on qr_token (only if column exists)
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='schedule_admission' AND column_name='qr_token') THEN
        IF NOT EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'idx_qr_token') THEN
            CREATE INDEX idx_qr_token ON schedule_admission(qr_token);
        END IF;
    END IF;
END $$;

-- Insert default admin user (password: admin123)
DO $$
BEGIN
    -- Only insert if type column exists (it should after the ALTER TABLE above)
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='users' AND column_name='type') THEN
        INSERT INTO users (firstname, lastname, username, password, type) 
        VALUES ('Admin', 'User', 'admin', MD5('admin123'), 1)
        ON CONFLICT (username) DO NOTHING;
    ELSE
        -- Fallback: insert without type column
        INSERT INTO users (firstname, lastname, username, password) 
        VALUES ('Admin', 'User', 'admin', MD5('admin123'))
        ON CONFLICT (username) DO NOTHING;
    END IF;
END $$;

-- Insert default system settings
INSERT INTO system_settings (id, name, short_name) 
VALUES (1, 'ZPPSU Admission System', 'ZPPSU')
ON CONFLICT (id) DO NOTHING;

-- Insert sample room assignments (handle duplicates gracefully)
DO $$
BEGIN
    INSERT INTO room_assignments (room_number, campus, capacity) VALUES ('Room 101', 'ZPPSU MAIN', 30)
    ON CONFLICT (room_number, campus) DO NOTHING;
    INSERT INTO room_assignments (room_number, campus, capacity) VALUES ('Room 102', 'ZPPSU MAIN', 30)
    ON CONFLICT (room_number, campus) DO NOTHING;
    INSERT INTO room_assignments (room_number, campus, capacity) VALUES ('Room 103', 'ZPPSU MAIN', 25)
    ON CONFLICT (room_number, campus) DO NOTHING;
    INSERT INTO room_assignments (room_number, campus, capacity) VALUES ('Room 201', 'ZPPSU MAIN', 40)
    ON CONFLICT (room_number, campus) DO NOTHING;
    INSERT INTO room_assignments (room_number, campus, capacity) VALUES ('Room 101', 'ZPPSU CAMPUS 2', 30)
    ON CONFLICT (room_number, campus) DO NOTHING;
EXCEPTION WHEN OTHERS THEN
    -- Ignore errors from duplicate inserts
    NULL;
END $$;

-- Success message
SELECT 'Schema created/updated successfully!' as result;
