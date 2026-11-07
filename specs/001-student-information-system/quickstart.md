# Quickstart & Verification Guide: Student Information System (SIS)

**Feature**: Student Information System (SIS)  
**Date**: 2026-11-07  
**Status**: Ready for Verification  
**Governing Document**: `.specify/memory/constitution.md` (Constitution v2.0.0)

## 1. Prerequisites

- **XAMPP**: Running on Windows with Apache and MySQL started.
- **PHP Version**: 8.0 or higher.
- **Web Root**: `c:\xampp\htdocs\sis`
- **Access URL**: `http://localhost/sis/`

---

## 2. Local Setup & Database Provisioning

1. **Start Services**:
   - Open the **XAMPP Control Panel**.
   - Start **Apache** and **MySQL**.

2. **Create Database**:
   - Open phpMyAdmin in your browser: `http://localhost/phpmyadmin/`
   - Create a new database named `sis_db` with collation `utf8mb4_unicode_ci`.

3. **Import Database Schema & Seeds**:
   - In phpMyAdmin, select `sis_db` and click **Import**.
   - Import `database/database.sql` (creates tables and constraints).
   - Import `database/seeds.sql` (provisions initial roles, academic years, semesters, programs, and default accounts).

4. **Verify Configuration**:
   - Check `config/database.php`:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'sis_db');
     ```

---

## 3. Seed Accounts for Local Testing

| Role | Username / ID | Password | Landing Page |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `Admin@123` | `http://localhost/sis/admin/dashboard.php` |
| **Registrar** | `registrar` | `Registrar@123` | `http://localhost/sis/registrar/dashboard.php` |
| **Student** | `2026-00001` | `Student@2026` | `http://localhost/sis/student/dashboard.php` |

---

## 4. End-to-End Verification Scenarios

### Scenario 1: Authentication & Role Boundary Check
1. Navigate to `http://localhost/sis/login.php`.
2. Attempt to log in with invalid credentials; verify error alert displays.
3. Log in as `admin` / `Admin@123`; verify redirection to Administrator Dashboard (`admin/dashboard.php`) with summary cards and zero emoji icons.
4. Try navigating directly to `http://localhost/sis/student/dashboard.php` while logged in as admin; verify HTTP 403 Forbidden is returned.
5. Click **Logout**; verify session terminates and redirects to `login.php`.

### Scenario 2: Student Registration by Registrar
1. Log in as `registrar` / `Registrar@123`.
2. Navigate to **Students** -> **Register New Student** (`registrar/students/register.php`).
3. Fill in biographical and contact details. Leave required field empty; verify server-side validation highlights error.
4. Submit complete valid form; verify student is created with `2026-XXXXX` ID format and account is auto-provisioned in `users`.
5. Search for the student by name or ID in `registrar/students/index.php`; verify immediate availability.

### Scenario 3: Term Enrollment & Duplicate Prevention
1. As Registrar, select the registered student and click **Enroll Student** (`registrar/enrollment/enroll.php`).
2. Select current Academic Year and Semester, choose Year Level, and submit.
3. Verify enrollment record is created with `Enrolled` status.
4. Attempt to submit a second enrollment for the exact same student and term; verify system blocks duplicate with error notification.

### Scenario 4: Student Portal Self-Service
1. Log out and log in with student credentials (e.g. `2026-00001` / `Student@2026`).
2. Verify system prompts for initial password change.
3. View Student Dashboard (`student/dashboard.php`); verify personal details and current enrollment term match accurately.
4. Try modifying another student ID in URL parameters; verify system denies access (IDOR protection).

### Scenario 5: Reporting, Export & Database Backup
1. Log in as `admin`.
2. Go to **Reports** -> **Enrollment Report** (`admin/reports/enrollment-reports.php`). Filter by current term; click **Export CSV** and verify downloaded file structure.
3. Click **Print Report**; verify clean layout without navigation artifacts.
4. Go to **System** -> **Database Backup** (`admin/backup/index.php`); click **Generate Backup Now**. Verify `.sql` file appears in backup logs and downloads securely.
5. Check **Activity Logs** (`admin/logs/index.php`); confirm all previous actions are recorded with actor, action type, IP address, and timestamp.
