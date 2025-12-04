-- Migration: QR Code and Bulk Reschedule Features
-- Run this migration to add support for QR code validation and bulk rescheduling

-- Add QR code columns to schedule_admission
ALTER TABLE schedule_admission 
ADD COLUMN IF NOT EXISTS qr_code_path VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS qr_token VARCHAR(64) NULL,
ADD COLUMN IF NOT EXISTS reschedule_count INT DEFAULT 0;

-- Create bulk reschedule log table
CREATE TABLE IF NOT EXISTS bulk_reschedule_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    old_date DATE NOT NULL,
    new_date DATE NOT NULL,
    campus VARCHAR(100) NULL,
    time_slot VARCHAR(50) NULL,
    reason TEXT,
    performed_by INT NULL,
    total_affected INT DEFAULT 0,
    success_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dates (old_date, new_date),
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create reschedule_history table if not exists
CREATE TABLE IF NOT EXISTS reschedule_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    old_date DATE NOT NULL,
    old_time_slot VARCHAR(50),
    old_room VARCHAR(50),
    new_date DATE NOT NULL,
    new_time_slot VARCHAR(50),
    new_room VARCHAR(50),
    reason TEXT,
    rescheduled_by INT,
    rescheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_schedule (schedule_id),
    INDEX idx_dates (old_date, new_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create QR code uploads directory (run this manually)
-- mkdir -p uploads/qrcodes
-- chmod 777 uploads/qrcodes

-- Add indexes for better search performance
CREATE INDEX IF NOT EXISTS idx_schedule_admission_date_status ON schedule_admission(date_scheduled, status);
CREATE INDEX IF NOT EXISTS idx_schedule_admission_campus ON schedule_admission(school_campus);
CREATE INDEX IF NOT EXISTS idx_schedule_admission_reference ON schedule_admission(reference_number);
CREATE INDEX IF NOT EXISTS idx_schedule_admission_lrn ON schedule_admission(lrn);
CREATE INDEX IF NOT EXISTS idx_schedule_admission_phone ON schedule_admission(phone);
CREATE INDEX IF NOT EXISTS idx_schedule_admission_email ON schedule_admission(email);
CREATE INDEX IF NOT EXISTS idx_schedule_admission_name ON schedule_admission(surname, given_name);

-- Sample data for testing (optional - uncomment to use)
-- INSERT INTO room_assignments (room_number, campus, capacity, is_active) VALUES
-- ('Room 101', 'ZPPSU MAIN', 30, 1),
-- ('Room 102', 'ZPPSU MAIN', 30, 1),
-- ('Room 103', 'ZPPSU MAIN', 25, 1),
-- ('Room 201', 'ZPPSU MAIN', 35, 1),
-- ('Computer Lab 1', 'ZPPSU MAIN', 40, 1);

