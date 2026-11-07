# Student Information System (SIS)

A comprehensive, web-based Student Information System built strictly using **Native Procedural PHP**, **MySQLi Prepared Statements**, and **Bootstrap 5**. Designed for local deployment on **XAMPP (Apache + MySQL/MariaDB)**.

Complies strictly with **Project Constitution v2.0.0**:
- 100% Native Procedural PHP (Zero frameworks, zero MVC abstractions, zero Composer dependencies).
- 100% Database queries with user variables use MySQLi Prepared Statements (`mysqli_prepare`, `mysqli_stmt_bind_param`).
- Zero Emoji Policy (Strictly Bootstrap Icons `bi bi-*` throughout the entire interface).
- Local Offline Static Assets (Bootstrap 5.3 and Bootstrap Icons bundled locally in `assets/vendor/`).

---

## 1. System Requirements & Technology Stack

- **Operating System**: Windows (Localhost)
- **Web Server**: Apache 2.4 (via XAMPP)
- **PHP Version**: PHP 8.0, 8.1, or 8.2 (Procedural mode)
- **Database**: MySQL 5.7+ or MariaDB 10.4+
- **Frontend**: HTML5, CSS3, Vanilla JavaScript (ES6+), Bootstrap 5.3, Bootstrap Icons
- **Web Root**: `c:\xampp\htdocs\sis`
- **Application URL**: `http://localhost/sis/`

---

## 2. Default Seed Accounts

The system is pre-configured with the following initial credentials for immediate local testing:

| Role | Username / ID | Default Password | Primary Landing Page |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `Admin@123` | `http://localhost/sis/admin/dashboard.php` |
| **Registrar** | `registrar` | `Registrar@123` | `http://localhost/sis/registrar/dashboard.php` |
| **Student** | `2026-00001` | `Student@2026` | `http://localhost/sis/student/dashboard.php` |

> **Security Note**: All default accounts and auto-provisioned student accounts require a mandatory password update on their first login or can update it anytime via **Change Password**.

---

## 3. Installation & Database Setup

1. **Clone or Copy Project**:
   Ensure the project files reside directly in your XAMPP web root:
   ```text
   C:\xampp\htdocs\sis\
   ```

2. **Start Services in XAMPP Control Panel**:
   - Start **Apache**
   - Start **MySQL**

3. **Create Database**:
   - Navigate to phpMyAdmin: `http://localhost/phpmyadmin/`
   - Create a new database named `sis_db` with `utf8mb4_unicode_ci` collation.

4. **Import Database Schema & Initial Data**:
   Import `database/database.sql` and `database/seeds.sql` using phpMyAdmin or the MySQL command line:
   ```powershell
   cmd.exe /c "C:\xampp\mysql\bin\mysql.exe -u root sis_db < C:\xampp\htdocs\sis\database\database.sql"
   cmd.exe /c "C:\xampp\mysql\bin\mysql.exe -u root sis_db < C:\xampp\htdocs\sis\database\seeds.sql"
   ```

5. **Verify Configuration**:
   Inspect `config/database.php` and `config/system.php` to verify your local database settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'sis_db');
   define('APP_URL', 'http://localhost/sis');
   ```

6. **Access Application**:
   Open your browser and navigate to:
   ```text
   http://localhost/sis/
   ```

---

## 4. Directory & Architectural Structure

```text
sis/
├── index.php                          # Root entry point & role dispatcher
├── login.php                          # Responsive authentication page
├── logout.php                         # Secure session destruction handler
│
├── config/
│   ├── database.php                   # Procedural database connection (mysqli_connect)
│   └── system.php                     # Application constants, paths, and timeouts
│
├── includes/
│   ├── session.php                    # Session security (HttpOnly, SameSite, 30m timeout)
│   ├── auth.php                       # Authentication & role verification guards
│   ├── functions.php                  # Procedural helpers (escape, sanitize, pagination, CSRF)
│   ├── logger.php                     # Procedural activity audit logger (system_logs)
│   ├── header.php                     # Reusable HTML head with local assets
│   ├── navbar.php                     # Top navigation bar with user profile menu
│   ├── sidebar.php                    # Role-filtered responsive sidebar navigation
│   ├── footer.php                     # Shared HTML footer and local script bundles
│   ├── alerts.php                     # Toast/alert messaging component
│   └── 403.php                        # Access denied forbidden response view
│
├── admin/                             # Administrator Tier Modules
│   ├── dashboard.php                  # Institutional metrics & system overview
│   ├── users/                         # User Account Management (CRUD, status toggle)
│   ├── students/                      # Student record oversight & editing
│   ├── programs/                      # Course & Degree Program Management (CRUD, status toggle)
│   ├── academic-years/                # Academic calendar & semester activation
│   ├── reports/                       # Institutional student & enrollment reports
│   ├── backup/                        # Native database backup & double-confirmation recovery
│   └── logs/                          # Activity audit trail browser & multi-criteria filter
│
├── registrar/                         # Registrar Personnel Tier Modules
│   ├── dashboard.php                  # Admission & enrollment operational dashboard
│   ├── students/                      # Student registration workflow, directory & editing
│   ├── programs/                      # Course & Degree Program Management
│   ├── enrollment/                    # Term enrollment, status updates & term history
│   ├── academic-years/                # Read-only academic calendar view
│   └── reports/                       # Student rosters & term enrollment print rolls
│
├── student/                           # Student Self-Service Portal (IDOR Protected)
│   ├── dashboard.php                  # Biographical snapshot & active term standing
│   ├── profile.php                    # Official profile view (read-only official data)
│   ├── profile-edit.php               # Student editable contact & emergency details
│   ├── enrollment.php                 # Current active semester enrollment verification
│   ├── academic-records.php           # Chronological academic history
│   └── change-password.php            # Secure password change interface
│
├── process/                           # Dedicated POST Processing Request Handlers
│   ├── login-process.php              # Auth verification & role routing
│   ├── logout-process.php             # Logout audit & cookie invalidation
│   ├── change-password-process.php    # Password hash update handler
│   ├── academic-years/                # Academic term creation, activation & deletion
│   ├── programs/                      # Course & Program creation, editing, status toggles & deletion
│   ├── students/                      # Student registration, updates, status toggles & deletion
│   ├── enrollment/                    # Term enrollment, status modification & deletion
│   ├── users/                         # User creation, editing, deactivation & deletion
│   ├── reports/                       # Streaming CSV export engine with UTF-8 BOM
│   └── backup/                        # Native SQL backup generator & restoration
│
├── assets/                            # Static Frontend Resources (Local Offline Bundle)
│   ├── css/                           # Custom layouts (style.css, print.css)
│   ├── js/                            # Vanilla JavaScript (main.js)
│   └── vendor/                        # Offline Bootstrap 5.3 & Bootstrap Icons
│
├── database/                          # Database Schemas & Secure Storage
│   ├── database.sql                   # Complete relational DDL schema (9 tables)
│   ├── seeds.sql                      # Seed data & initial user accounts
│   └── backup/                        # Timestamped .sql dump archives (.htaccess protected)
│
└── tests/                             # Verification Suite
    └── verify_e2e.php                 # CLI automated end-to-end verification script
```

---

## 5. Security & Architectural Guarantees

1. **Prepared Statements**:
   Every database operation with user-supplied variables uses parameterized prepared statements:
   `mysqli_prepare()`, `mysqli_stmt_bind_param()`, `mysqli_stmt_execute()`, `mysqli_stmt_get_result()`.
2. **Cross-Site Scripting (XSS) Mitigation**:
   All user-supplied and dynamic database output is escaped via `escape()` (`htmlspecialchars` with `ENT_QUOTES` and `UTF-8`).
3. **Cross-Site Request Forgery (CSRF) Protection**:
   All 16 state-changing POST forms across the application include cryptographically secure CSRF tokens verified by `verify_csrf_token()`.
4. **Insecure Direct Object Reference (IDOR) Mitigation**:
   Student portal routes strictly bind to `$_SESSION['student_id']` resolved directly from the authenticated session. Any external query parameters (e.g. `?id=X`) are completely ignored.
5. **Session Security & Inactivity Timeout**:
   - `session.cookie_httponly = 1`
   - `session.cookie_samesite = Lax`
   - Automatic 30-minute inactivity timeout (`SESSION_TIMEOUT = 1800`)
   - `session_regenerate_id(true)` upon authentication to prevent session fixation.
6. **SQL Backup Storage Security**:
   The `database/backup/` storage directory includes an Apache `.htaccess` rule enforcing `Deny from all`, preventing unauthorized public HTTP downloads. Backups can only be streamed via `process/backup/download-backup.php` by authenticated Administrators.
7. **Database Restoration Double-Confirmation**:
   Restoring a database snapshot requires entering the active Administrator's password, preventing accidental or unauthorized database overwrites.

---

## 6. Running the Verification Suite

Automated verification and test render scripts are included:

1. **Database & Security Integrity Suite**:
   ```powershell
   php tests/verify_e2e.php
   ```
   Verifies tables, accounts, password hashing, active terms, duplicate constraints, audit logging, and filesystem protection.

2. **Full Page Render Smoke Test Suite**:
   ```powershell
   php tests/test_pages.php
   ```
   Renders all 34 primary views across Administrator, Registrar, and Student portals to confirm clean rendering with 0 syntax or runtime errors.

Expected Output:
```text
=========================================================
SIS CONSTITUTION v2.0.0 - AUTOMATED VERIFICATION SUITE
=========================================================

[PASS] Table `roles` exists in database
[PASS] Table `programs` exists in database
[PASS] Table `academic_years` exists in database
[PASS] Table `semesters` exists in database
[PASS] Table `users` exists in database
[PASS] Table `students` exists in database
[PASS] Table `enrollments` exists in database
[PASS] Table `system_logs` exists in database
[PASS] Table `backup_logs` exists in database
[PASS] Account `admin` exists with role `Administrator`
[PASS] Account `admin` password hashes verify properly
[PASS] Account `registrar` exists with role `Registrar`
[PASS] Account `registrar` password hashes verify properly
[PASS] Account `2026-00001` exists with role `Student`
[PASS] Account `2026-00001` password hashes verify properly
[PASS] Active academic semester is provisioned
[PASS] Initial enrollment inserts successfully
[PASS] Duplicate enrollment for same semester is blocked by unique constraint
[PASS] Audit logger log_activity() executes successfully
[PASS] Audit entry is persisted in `system_logs`
[PASS] Backup directory `database/backup/.htaccess` exists
[PASS] Backup directory denies public HTTP access

---------------------------------------------------------
RESULTS: 22 PASSED, 0 FAILED.
---------------------------------------------------------
```
#   S I S  
 