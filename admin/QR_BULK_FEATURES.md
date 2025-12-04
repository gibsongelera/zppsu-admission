# QR Code Validation & Bulk Reschedule Features

## Overview

This document describes the new features implemented for the ZPPSU Admission System:

1. **QR Code Validation System** - Generate and scan QR codes to verify student registration
2. **Bulk Reschedule** - Mass reschedule students for calamity/emergency situations
3. **Advanced Search & Filter** - Comprehensive search and filtering across all records

---

## 1. QR Code Validation System

### How It Works

When a student's application is **Approved**, a unique QR code is automatically generated containing:
- Reference number
- Student ID
- Validation token (encrypted)
- Student name

### Features

#### For Admins/Teachers:
- **QR Scanner Page** (`admin/?page=qr_scanner`)
  - Camera-based scanning using device camera
  - Manual entry for QR data
  - Search students by name, reference #, LRN
  - Generate QR codes for approved students
  - Print individual QR codes

#### For Students:
- QR code is displayed on the **Admission Slip** (`schedule/print.php`)
- Students can show this QR code at the exam venue for verification

### Verification Results

When a QR code is scanned:

| Status | Message | Color |
|--------|---------|-------|
| ✅ Approved | REGISTERED - Student is verified! | Green |
| ⏳ Pending | PENDING - Application not yet approved | Yellow |
| ❌ Rejected | REJECTED - Application was rejected | Red |
| ⚠️ Not Found | NOT YET REGISTERED | Orange |
| 🚫 Invalid | Invalid QR code format | Red |

---

## 2. Bulk Reschedule System

### Use Cases

- **Typhoon/Weather Disturbance**
- **Earthquake**
- **Flooding**
- **Power Outage**
- **Facility Maintenance**
- **Public Holiday**

### How To Use

1. Navigate to **Bulk Reschedule** (`admin/?page=bulk_reschedule`)
2. Click on a date from the "Upcoming Schedules" list
3. Optionally filter by:
   - Campus
   - Time Slot
4. Click **Preview Affected Students** to see who will be rescheduled
5. Select a **New Date** (weekends recommended)
6. Enter a **Reason** for rescheduling
7. Check **Send SMS notifications** if you want to notify all students
8. Click **Execute Bulk Reschedule**

### SMS Notification

When bulk rescheduling with SMS enabled, all affected students receive a message like:

```
IMPORTANT NOTICE!

Dear [Student Name],

Your ZPPSU entrance exam has been RESCHEDULED.

❌ Original Date: December 14, 2025
✅ New Date: December 21, 2025
📍 Campus: ZPPSU MAIN
📋 Reason: Typhoon/Weather Disturbance

Please check your email for room assignment. Thank you for your understanding.

- ZPPSU Admissions Office
```

### History Log

All bulk reschedule operations are logged with:
- Original date
- New date
- Campus/time slot filters
- Reason
- Number of affected students
- Who performed the action
- Timestamp

---

## 3. Advanced Search & Filter

### Available Filters

| Filter | Description |
|--------|-------------|
| Search | Name, Reference #, LRN, Email, Phone |
| Status | Pending, Approved, Rejected |
| Campus | All registered campuses |
| Exam Result | Pass, Fail, Pending |
| Time Slot | Morning, Afternoon |
| Date Range | From/To dates |
| Classification | Program/Course |
| Room | Assigned room |
| Gender | Male, Female |

### Quick Filters

Click the quick filter buttons to instantly filter by status:
- **All** - Show all records
- **Pending** - Show pending applications
- **Approved** - Show approved applications
- **Rejected** - Show rejected applications

---

## Database Changes

The following columns were added to `schedule_admission`:

```sql
qr_code_path VARCHAR(255)    -- Path to QR code image
qr_token VARCHAR(64)         -- Validation token
reschedule_count INT         -- Number of times rescheduled
```

New tables created:

```sql
bulk_reschedule_log          -- Audit log for bulk operations
reschedule_history           -- Individual reschedule history
```

---

## File Structure

```
admin/
├── qr_scanner/
│   ├── index.php           # QR Scanner interface
│   ├── generate_qr.php     # API: Generate QR code
│   ├── get_qr.php          # API: Get existing QR code
│   └── validate_qr.php     # API: Validate scanned QR
├── bulk_reschedule/
│   └── index.php           # Bulk reschedule interface
├── inc/
│   ├── qr_handler.php      # QR code generation/validation logic
│   ├── bulk_reschedule_handler.php  # Bulk reschedule logic
│   └── search_filter.php   # Search & filter component
└── schedule/
    └── print.php           # Updated with QR code display
```

---

## Navigation

New menu items added for **Admin** and **Staff/Teacher** roles:

- 📱 **QR Scanner** - Scan and validate student QR codes
- 📅 **Bulk Reschedule** - Mass reschedule for emergencies

---

## Security

- QR codes contain encrypted tokens that are verified server-side
- Only Admin and Staff/Teacher roles can access QR Scanner and Bulk Reschedule
- Students can only view their own QR code on their admission slip
- All bulk operations are logged for audit purposes

---

## Running the Migration

Execute the migration script:

```bash
# Windows (PowerShell)
Get-Content "database/migration_qr_bulk.sql" | C:\xampp\mysql\bin\mysql.exe -u root zppsu_admission

# Linux/Mac
mysql -u root zppsu_admission < database/migration_qr_bulk.sql
```

Make sure the QR codes directory exists:

```bash
mkdir uploads/qrcodes
chmod 777 uploads/qrcodes  # Linux/Mac only
```

