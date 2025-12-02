# ZPPSU Admission System – Capstone Documentation (Chapters 1–5)

## Chapter 1: Introduction

- **Project Title**: ZPPSU Admission System with SMS-based OTP Registration and Scheduling
- **Context**: The current admission workflow at Zamboanga Peninsula Polytechnic State University (ZPPSU) involves manual scheduling, fragmented document handling, and limited identity verification. This system digitizes registration, identity verification via One-Time Password (OTP), and admission schedule management to improve accuracy and speed.
- **Proponents**: [Your Name/s]
- **Adviser**: [Adviser Name]
- **Date**: [Month Year]

### 1.1 Background of the Study
Admissions often require verifying applicant identity, collecting documents, and assigning schedules. Legacy processes lead to duplicate accounts, erroneous contact details, and scheduling conflicts. Modernizing with web-based registration and SMS OTP verification reduces fake or spam registrations, ensures contactability, and provides reliable data for decision-making.

### 1.2 Problem Statement
- Lack of identity verification during online sign-up leads to dummy or duplicate accounts.
- Inconsistent capturing of core student identifiers like LRN and contact details.
- Manual admission scheduling causes bottlenecks and notification delays.
- Limited auditability for administrative actions (teacher logs, user management).

### 1.3 Purpose and Significance
- Ensure authenticity of registrants through OTP tied to a phone number.
- Standardize collection of LRN, name, email, and phone for accurate profiling.
- Provide a unified interface for staff to manage users, schedules, and logs.
- Improve applicant experience with transparent status and timely updates.

### 1.4 Scope and Delimitations
- In-scope: User registration with OTP, student profile capture (LRN), schedule admission module, basic user roles (Admin, Staff, Student), system info branding, teacher log for administrative audit, basic notifications.
- Out of scope: Online payments, entrance exam scoring, complex workflow automation, and integration to student information systems.

### 1.5 Definition of Terms
- **OTP**: One-Time Password sent via SMS to verify ownership of a phone number.
- **LRN**: Learner Reference Number, a 12-digit identifier required during registration.
- **Admin/Staff/Student Roles**: Role-based access to features as defined in the system.

---

## Chapter 2: Review of Related Literature and Systems

- **SMS OTP for Identity Verification**: Literature confirms SMS OTP is widely used for initial identity proofing despite SIM-swap risks; pairing with phone-number normalization (e.g., +63 format) reduces input errors.
- **Higher-Ed Admission Portals**: Prior systems focused on online form collection. Recent work emphasizes stepwise verification, schedule coordination, and audit logging.
- **User Profile Standards**: Capturing LRN and ensuring uniqueness (e.g., username uniqueness, phone association) mirrors best practices in K–12 and higher education onboarding.
- **Scheduling Systems**: Research highlights conflicts and no-show mitigation via reminders and status tracking (Pending/Approved/Rejected), reflected in `schedule_admission.status`.

Synthesis: The proposed system aligns with literature by emphasizing verified onboarding (OTP), standardized identifiers (LRN), and operational modules (scheduling, logs) with role-based management.

---

## Chapter 3: Methodology

### 3.1 System Overview
- Web application built with PHP (AdminLTE UI components), jQuery, and MySQL/MariaDB.
- Core modules: Registration with OTP, User Management, Schedule Admission, Teacher Log, System Branding.

### 3.2 Architecture and Technologies
- **Backend**: PHP 8.x; classes like `Users` extend a `DBConnection` and use `mysqli`.
- **Database**: `users`, `user_meta`, `schedule_admission`, `teacher_log`, `system_info`, `incoming_sms`.
- **Frontend**: Bootstrap 4.6.2, AdminLTE UI, jQuery for AJAX and modals.
- **SMS/OTP**: Endpoint `send_otp.php` issues OTP; `verify_otp.php` verifies and completes registration.

### 3.3 Data Model (Key Tables)
- `users(id, firstname, middlename, lastname, username, password(md5), phone, email, course, year_level, avatar, last_login, date_added, date_updated, role[1=Admin,2=Staff,3=Student], lrn[added during runtime])`
- `user_meta(user_id, meta_field, meta_value, date_created)` for flexible attributes and role legacy support.
- `schedule_admission(..., academic_year, application_type, classification, grade_level, school_campus, date_scheduled, reference_number, status[Pending/Approved/Rejected])`
- `teacher_log(teacher_name, department, subject, log_date, remarks)`
- `system_info(meta_field, meta_value)` for name, logo, cover.

### 3.4 Process Flows
- **Registration**: User submits fullname, username, email, 12-digit LRN, 09XXXXXXXXX phone, password → phone normalized to `+63` → OTP sent → `verify_otp.php` validates OTP, phone, uniqueness, then inserts user with role=Student.
- **User Management**: Admin/Staff manage users via `classes/Users.php` (`save_users`, `delete_users`) with username uniqueness checks and optional avatar upload.
- **Scheduling**: Staff create/update admission schedules with status tracking.
- **Audit/Logs**: Teacher Log records activities for oversight.

### 3.5 Development Method
- Iterative/incremental: prioritize Registration + OTP, then Scheduling, then Logs.
- Environment: XAMPP on Windows; versioned vendor libraries (e.g., PhpSpreadsheet folder present but not central to current scope).

### 3.6 Non-Functional Considerations
- Usability: Clear forms and validation messages.
- Security: OTP verification, server-side validation, role checks. Note: MD5 used for passwords in legacy code; recommend migration to `password_hash()`.
- Reliability: Input normalization (phone), required fields, and constraints.

---

## Chapter 4: System Design and Implementation

### 4.1 UI Design Summary
- AdminLTE-based pages: `admin/register.php`, `admin/login.php`, dashboards (`admin/home.php`).
- Modal-driven OTP entry (`#otpModal`) with jQuery and Bootstrap.

### 4.2 Key Implementations
- `admin/register.php`:
  - Collects fullname, username, email, LRN (12 digits), phone (09XXXXXXXXX), password/confirm.
  - Normalizes phone to `+63` and displays OTP modal.
  - Has a temporary `SKIP_OTP` flag for testing.
- `admin/send_otp.php` (not shown here): generates and sends OTP; stores OTP and target phone in session.
- `admin/verify_otp.php`:
  - Validates OTP against session and confirms same phone.
  - Validates fields, checks username uniqueness, splits fullname, inserts into `users` with role=3 (Student) and `date_added`.
- `classes/Users.php`:
  - `save_users()`: validates, ensures `lrn` column exists, enforces username uniqueness (except avatar-only updates), updates `user_meta` role, handles avatar upload/resizing.
  - `delete_users()`: removes user and avatar file.

### 4.3 Database Design Notes
- Ensure indices on `users.username`, `users.phone`, `schedule_admission.status` for query speed.
- Consider adding `UNIQUE(username)`, `UNIQUE(phone)` and a proper `lrn` column with validations.

### 4.4 Security and Data Integrity
- Replace MD5 with `password_hash()` and `password_verify()`.
- Rate-limit OTP requests; expire OTP after N minutes; store OTP hashes server-side.
- Server-side sanitization via `mysqli::real_escape_string`; prefer prepared statements throughout.

### 4.5 Deployment
- XAMPP stack locally: place code under `htdocs/zppsu_admission`, import `database/sms_db1 (1).sql`.
- Configure `config.php` for DB credentials and paths; ensure vendor autoload if using external libs.

---

## Chapter 5: Testing, Results, and Evaluation

### 5.1 Test Plan
- **Registration Form Validation**:
  - Reject empty fields; enforce LRN=12 digits; phone pattern `09[0-9]{9}` then normalize to `+63`.
  - Verify mismatched passwords are rejected.
- **OTP Flow**:
  - Verify OTP required when `SKIP_OTP=false`; mismatched phone should fail.
  - OTP expiry, retry limits, and error messaging.
- **User Uniqueness**:
  - Duplicate username should be rejected.
- **Scheduling Module**:
  - Create/approve/reject schedules; verify status transitions and timestamps.
- **Role Access**:
  - Admin/Staff vs Student capabilities verified.

### 5.2 Sample Test Cases
- Register with valid details and correct OTP → success, role=3.
- Register with existing username → error.
- Register with phone 09XXXXXXXXX → saved as +639XXXXXXXXX.
- Update user avatar only → allowed without username uniqueness check.

### 5.3 Results and Discussion
- System reduces fake accounts via OTP and standardizes student identifiers with LRN.
- Scheduling statuses provide visibility into applicant flow.
- Admin and Staff can manage users consistently with auditability via user meta and logs.

### 5.4 Limitations and Future Work
- Password hashing uses MD5 in legacy sections; migration needed.
- Add email verification and multi-factor options.
- Integrate SMS provider webhooks and implement message queue for OTPs.
- Expand reports/analytics (admission pipeline, attendance from teacher logs).

---

## References
[List your books, articles, standards, and systems reviewed]

## Appendices
- Screenshots of key pages (Registration, OTP Modal, User Management)
- ERD snapshot of core tables
- Sample configuration (`config.php` DB settings redacted)
