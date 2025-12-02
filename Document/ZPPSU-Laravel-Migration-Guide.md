# ZPPSU Admission System – Laravel Migration Guide

This guide outlines how to move the current PHP app to Laravel while preserving functionality and design (AdminLTE + Bootstrap + jQuery).

## 1) New Laravel App

1. Install Laravel (Windows PowerShell):
```bash
composer create-project laravel/laravel zppsu_admission_laravel
cd zppsu_admission_laravel
copy .env.example .env
php artisan key:generate
```
2. Configure `.env` database to point to your existing `sms_db1` (or a new DB cloned from it):
```
DB_DATABASE=sms_db1
DB_USERNAME=root
DB_PASSWORD=
```

## 2) Migrations (schema parity)
Create migrations mirroring your existing tables. Example commands:
```bash
php artisan make:migration create_users_table
php artisan make:migration create_user_meta_table
php artisan make:migration create_schedule_admission_table
php artisan make:migration create_teacher_log_table
php artisan make:migration create_system_info_table
php artisan make:migration create_incoming_sms_table
```
In each migration, define columns equivalent to current schema (add indexes/unique keys):
- `users`: add `lrn` (nullable), `role` (tinyint: 1=Admin,2=Staff,3=Student), `avatar`, timestamps, `last_login`. Prefer `password` as hashed with Laravel.
- Add `unique('username')`, optional `unique('phone')`, and consider `unique('email')`.
- `schedule_admission`: include `status` enum-like string with default `Pending` and necessary indexes.
- `teacher_log`, `system_info`, `incoming_sms` same as current.
Then run:
```bash
php artisan migrate
```

## 3) Models & Policies
```bash
php artisan make:model User -m
php artisan make:model ScheduleAdmission -m
php artisan make:model TeacherLog -m
php artisan make:model SystemInfo -m
php artisan make:model UserMeta -m
```
- Define `$fillable` on each model.
- Add relationships: `User` hasMany `UserMeta`; `User` can have schedules if needed.
- Use gates/policies for role checks or lightweight middleware.

## 4) Auth with Roles
Install Breeze (simple) or Fortify (headless):
```bash
composer require laravel/breeze --dev
php artisan breeze:install
npm install && npm run build
```
- Add `role` to `users` migration and `User` model.
- Middleware: `AdminMiddleware`, `StaffMiddleware`, `StudentMiddleware` to guard routes.

## 5) OTP Flow (Twilio optional)
Routes (`routes/web.php`):
- `POST /otp/send` → `OtpController@send`
- `POST /otp/verify` → `OtpController@verify`

Controller (`app/Http/Controllers/OtpController.php`):
- Generate 6-digit OTP, store hash + phone in session (or cache) with expiry.
- Send via Twilio SDK or stub for local.
- On verify, check code, phone match, create user (role=3) with `password_hash`, split fullname (first/last), store `lrn`.

Environment for Twilio (optional):
```
TWILIO_SID=
TWILIO_TOKEN=
TWILIO_FROM=
```

## 6) Views (AdminLTE in Blade)
- Copy AdminLTE assets into `public/admin/...` or install via NPM.
- Create Blade layouts: `resources/views/layouts/admin.blade.php` replicating `inc/header.php`, `inc/navigation.php`, `inc/topBarNav.php`.
- Pages to port:
  - Login (`resources/views/auth/login.blade.php`)
  - Register with OTP modal (`resources/views/auth/register.blade.php`)
  - Dashboards: admin, staff, student (`resources/views/admin/home.blade.php`, `resources/views/staff/index.blade.php`, `resources/views/student/index.blade.php`)
  - Users CRUD (`resources/views/users/...`)
  - Schedule & Teacher Log (`resources/views/schedule/...`, `resources/views/teacher_log/...`)

Keep HTML/CSS identical to preserve design; replace PHP echo with Blade (`{{ }}`) and routes with `route()` helpers.

## 7) Controllers & Routes
Controllers:
```bash
php artisan make:controller Admin/HomeController
php artisan make:controller Staff/DashboardController
php artisan make:controller Student/DashboardController
php artisan make:controller UserController --resource
php artisan make:controller ScheduleAdmissionController --resource
php artisan make:controller TeacherLogController --resource
```
- Implement methods using Eloquent.
- Recreate list, manage user, avatar upload (see next section), schedule status updates.

Routes (`routes/web.php`):
- Group by middleware: `auth`, `role:admin`, `role:staff`, `role:student`.
- Map to paths close to current URLs for minimal breakage (or add redirects).

## 8) Avatar Upload & Image Resize
Use `intervention/image`:
```bash
composer require intervention/image
```
In `UserController@update`, handle image upload, resize to 200x200, save under `storage/app/public/avatars`, link via `php artisan storage:link`.

## 9) Schedule Module & Teacher Log
- Implement resource controllers with list, create, edit, status update endpoints.
- Add AJAX endpoints for status changes similar to current jQuery `.update-status` actions.

## 10) System Info & Branding
- Create `SystemInfo` service/provider to fetch `name`, `short_name`, `logo`, `cover` and share via view composers to Blade.

## 11) Data Migration (optional)
If you keep the existing DB, ensure migrations match. Otherwise, export from old DB and import into new. Then run an artisan command to backfill any new columns (e.g., `lrn` if missing).

## 12) Testing Checklist (Parity)
- Registration with OTP (normalize 09 → +63, enforce LRN=12).
- Duplicate username check.
- Role-based menus: Admin, Staff, Student.
- Users CRUD with avatar-only update path.
- Schedule status transitions (Pending/Approved/Rejected) with AJAX.
- Teacher Log list and CRUD.
- System info rendered on layouts.

## 13) Performance & Security Notes
- Replace MD5 with `Hash::make()` and `Hash::check()`.
- Rate-limit OTP, store hashed OTP + expiry.
- CSRF protection via Blade `@csrf`.
- Use policies or gates for sensitive operations.

## 14) Asset Strategy
- Place existing CSS/JS under `public/` maintaining paths, or compile via Vite.
- Update `<script src>` paths in Blade to match `public` locations.

## 15) Suggested Folder Map
- `resources/views/layouts` → AdminLTE layout and partials
- `resources/views/auth` → login/register
- `resources/views/admin|staff|student` → dashboards
- `resources/views/users` → list/manage
- `resources/views/schedule` → index/manage
- `resources/views/teacher_log` → index

---

With this structure you can port each page incrementally, keeping the same HTML/CSS, while controllers/models replace the legacy PHP scripts. Start with auth + registration/OTP, then users, then schedules/logs.
