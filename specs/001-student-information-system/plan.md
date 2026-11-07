# Implementation Plan: Student Information System (SIS)

**Branch**: `001-student-information-system` | **Date**: 2026-11-07 | **Spec**: [spec.md](spec.md)  
**Governing Document**: [`.specify/memory/constitution.md`](../../.specify/memory/constitution.md) (Constitution v2.0.0)

---

## 1. Summary

The Student Information System (SIS) is a secure, web-based Native PHP application developed using a **strict procedural programming approach**. The system centralizes student records, user management, registration, academic term enrollments, role-specific dashboards, and institutional reports. 

The architecture strictly rejects MVC framework patterns, framework-style controllers/models, and third-party PHP/JS frameworks. It organizes procedural PHP scripts directly by user role (`admin/`, `registrar/`, `student/`), centralizes shared functionality into reusable include files (`includes/`), separates state-changing operations into dedicated processing scripts (`process/`), and connects to MySQL using `mysqli_connect()` with procedural prepared statements on a local XAMPP environment.

---

## 2. Technical Context

- **Backend Runtime**: Native PHP 8.0+ (Strict Procedural Programming, Zero Frameworks)
- **Database Driver**: `mysqli` extension using procedural `mysqli_connect()` and prepared statement functions (`mysqli_prepare`, `mysqli_stmt_bind_param`, `mysqli_stmt_execute`, `mysqli_stmt_get_result`, `mysqli_stmt_close`)
- **Session & Security**: Native PHP sessions (`session_start()`, `session_regenerate_id(true)`), `password_hash()`, `password_verify()`, CSRF tokens, output escaping via `htmlspecialchars()`
- **Frontend Presentation**: HTML5, external CSS3 (`assets/css/style.css`, `assets/css/print.css`), Vanilla JavaScript (ES6+), Bootstrap 5.3 (local bundle in `assets/vendor/bootstrap/`), and Bootstrap Icons (local bundle in `assets/vendor/bootstrap-icons/`)
- **Strict Prohibition**: Emojis are strictly forbidden as interface icons or decorative elements
- **Local Environment**: Apache web server and MariaDB/MySQL managed through XAMPP on `http://localhost/sis/`
- **Target Roles**: Administrator, Registrar Personnel, and Student

---

## 3. Constitution Check (v2.0.0 Compliance)

*GATE: Must pass against Constitution v2.0.0. All gates verified.*

| Constitutional Principle | Requirement | Plan Status | Verification Details |
| :--- | :--- | :---: | :--- |
| **I. Native Procedural PHP** | Native PHP with procedural programming; `mysqli_connect()`; procedural prepared statements; `include`/`require` for shared code; local XAMPP only. | **PASS** | 100% procedural architecture. No classes or ORMs. `mysqli_connect()` used in `config/database.php`. Prepared statements used for all SQL queries involving input. |
| **II. Defense-in-Depth Security** | Server-side validation first; `password_hash()` and `password_verify()`; CSRF tokens on all POST submissions; XSS escaping; role validation on all protected pages. | **PASS** | Validates CSRF tokens with `hash_equals()` in all `process/` scripts; sanitizes output with `htmlspecialchars()`; enforces session role checks in `includes/auth.php`. |
| **III. Straightforward Procedural Architecture** | MUST NOT implement MVC architecture; NO framework-style controllers, models, or custom routers; files organized by role and functionality. | **PASS** | Zero MVC structures. No `controllers/` or `models/`. Pages organized directly into `admin/`, `registrar/`, `student/`, `includes/`, and `process/`. |
| **IV. Professional Frontend Standards** | HTML5, CSS3 external files, Vanilla JS, Bootstrap 5, Bootstrap Icons or Font Awesome, ZERO emojis in UI. | **PASS** | Local offline Bootstrap 5.3 and Bootstrap Icons bundles. Zero emojis used across all templates and layout components. |
| **V. Relational Database Normalization** | Normalized MySQL database (3NF target), primary/foreign keys with explicit constraints, version-controlled `.sql` scripts. | **PASS** | 9 normalized tables with explicit foreign keys and unique constraints in `database/database.sql` and `database/seeds.sql`. |
| **VI. Zero-Placeholder Delivery** | Fully functional features, no dead links or incomplete placeholders, non-regression verification. | **PASS** | Clear 12-phase implementation roadmap with complete procedural logic at every stage. |

---

## 4. Finalized Project Directory Structure

```text
student-information-system/
│
├── index.php                         # Landing page & user login interface
├── login.php                         # Dedicated login page (includes/login form)
├── logout.php                        # Invokes process/logout-process.php
│
├── config/
│   ├── database.php                  # Database credentials & mysqli_connect() connection helper
│   └── system.php                    # Application constants, base URL, session timeout, timezones
│
├── includes/
│   ├── auth.php                      # Procedural authentication checks and role-based guards
│   ├── session.php                   # Secure session starter, cookie settings, timeout validation
│   ├── functions.php                 # Shared procedural helpers (sanitize, format, flash messages)
│   ├── header.php                    # Reusable HTML <head>, stylesheets, and meta tags
│   ├── navbar.php                    # Top navigation header with user profile & logout
│   ├── sidebar.php                   # Role-filtered responsive sidebar navigation
│   ├── footer.php                    # Reusable footer, copyright, and JavaScript script tags
│   ├── alerts.php                    # Reusable Bootstrap alert banners (success, error, warning)
│   └── logger.php                    # Centralized procedural activity logging helper
│
├── admin/
│   ├── dashboard.php                 # Administrator metrics, statistics, and recent activity
│   ├── users/
│   │   ├── index.php                 # User account list, search, and status toggling
│   │   ├── create.php                # Add new user account form
│   │   └── edit.php                  # Edit user account details form
│   ├── students/
│   │   ├── index.php                 # Master student directory view
│   │   └── view.php                  # Detailed student profile view
│   ├── academic-years/
│   │   ├── index.php                 # Academic years and semesters management
│   │   └── create.php                # Add new academic year / semester form
│   ├── reports/
│   │   ├── student-reports.php       # Master student reports & print layout
│   │   └── enrollment-reports.php    # Enrollment summary reports & print layout
│   ├── backup/
│   │   └── index.php                 # Database backup generation, history, and restore interface
│   └── logs/
│       └── index.php                 # System activity audit log viewer
│
├── registrar/
│   ├── dashboard.php                 # Registrar overview, pending registrations, quick stats
│   ├── students/
│   │   ├── index.php                 # Student directory, search, and status filters
│   │   ├── register.php              # Register new student form (auto ID + account creation)
│   │   ├── edit.php                  # Edit student biographical and contact details
│   │   └── view.php                  # View complete student profile & academic history
│   ├── enrollment/
│   │   ├── index.php                 # Term enrollment list & search
│   │   ├── enroll.php                # Enroll student into active term & year level
│   │   ├── edit-status.php           # Update enrollment status (Pending, Enrolled, Dropped, Completed)
│   │   └── history.php               # Student chronological enrollment history
│   ├── academic-years/
│   │   └── index.php                 # View active and historical academic calendar terms
│   └── reports/
│       ├── student-list.php          # Registrar student rosters
│       └── enrollment-list.php       # Registrar term enrollment rosters
│
├── student/
│   ├── dashboard.php                 # Student personal profile snapshot & active term standing
│   ├── profile.php                   # View complete personal profile
│   ├── profile-edit.php              # Update permitted contact info (phone, address, emergency contact)
│   ├── enrollment.php                # Current term enrollment details and status
│   ├── academic-records.php          # Chronological term enrollment history
│   └── change-password.php           # Secure password change interface
│
├── process/
│   ├── login-process.php             # Authenticate credentials & route to role dashboard
│   ├── logout-process.php            # Invalidate session, clear cookies, redirect to login
│   ├── change-password-process.php   # Verify current password & update with password_hash()
│   ├── users/
│   │   ├── create-user-process.php   # Validate & insert new user account
│   │   ├── edit-user-process.php     # Validate & update user account
│   │   └── toggle-status-process.php # Activate / deactivate user account
│   ├── students/
│   │   ├── register-process.php      # Transactional student registration & user provisioning
│   │   ├── update-student-process.php# Update biographical & contact info
│   │   └── update-status-process.php # Update student institutional standing
│   ├── enrollment/
│   │   ├── enroll-process.php        # Create enrollment record with duplicate prevention
│   │   └── update-status-process.php # Update enrollment status & remarks
│   ├── academic-years/
│   │   └── create-term-process.php   # Create academic year or semester
│   ├── reports/
│   │   └── export-csv.php            # Generate & stream CSV report downloads
│   └── backup/
│       ├── create-backup-process.php # Generate native SQL dump file
│       └── restore-backup-process.php# Restore database from SQL dump
│
├── assets/
│   ├── css/
│   │   ├── style.css                 # Custom styling for dashboard, cards, tables, and sidebars
│   │   └── print.css                 # Clean print stylesheet for institutional reports
│   ├── js/
│   │   └── main.js                   # Client interactions, modal triggers, dynamic filters
│   ├── images/                       # Application logos, seals, and avatars
│   └── vendor/
│       ├── bootstrap/                # Local compiled Bootstrap 5.3 CSS & JS bundles
│       └── bootstrap-icons/          # Local compiled Bootstrap Icons fonts & CSS
│
├── database/
│   ├── database.sql                  # Complete table DDL, foreign keys, unique constraints
│   ├── seeds.sql                     # Seed data for roles, users, programs, and terms
│   └── backup/                       # Secure directory for SQL backup dumps (.htaccess protected)
│
└── README.md                         # Setup instructions, architecture overview, and test credentials
```

---

## 5. Directory Responsibilities & File Roles

### 1. Root Scripts
- `index.php`: The primary entry point. Checks session status: if authenticated, redirects to the role-appropriate dashboard (`admin/dashboard.php`, `registrar/dashboard.php`, or `student/dashboard.php`); if unauthenticated, renders the clean login view.
- `login.php`: Dedicated login presentation view containing the username/password form and CSRF token.
- `logout.php`: Simple gateway that calls `process/logout-process.php` to destroy the session.

### 2. `config/` Directory
- `database.php`: Contains database credentials (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_PORT`) and the central procedural function `db_connect()` returning a shared connection via `mysqli_connect()`.
- `system.php`: Defines global application constants (`APP_NAME`, `APP_URL`, `SESSION_TIMEOUT`, date formats) and sets institutional timezones.

### 3. `includes/` Directory
- `auth.php`: Core procedural authorization guards:
  - `is_logged_in()`: Returns boolean status of session.
  - `require_login()`: Redirects unauthenticated visitors to `login.php`.
  - `require_role($roles)`: Halts execution with HTTP 403 Forbidden if the user's role is not authorized for the requested script.
  - `current_user()`: Retrieves active user session data.
- `session.php`: Bootstraps session configuration before any output: sets `cookie_httponly = true`, `cookie_samesite = Lax`, checks session inactivity timeout (30 minutes), and regenerates session IDs.
- `functions.php`: Procedural utility functions:
  - `sanitize($data)` / `escape($string)`: Wraps `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')`.
  - `set_flash_message($type, $message)`: Sets one-time notifications in `$_SESSION['flash']`.
  - `get_flash_message()`: Retrieves and unsets flash notifications.
  - `generate_csrf_token()` & `verify_csrf_token($token)`: Cryptographic token validation via `hash_equals()`.
  - `format_date($date)`: Standardizes date rendering.
- `header.php`: Standard HTML `<head>` containing page title, viewport tags, Bootstrap 5 CSS, Bootstrap Icons CSS, and `style.css`.
- `navbar.php`: Reusable top navigation header with user profile badge, active role indicator, and logout link.
- `sidebar.php`: Role-based navigation sidebar that dynamically highlights active pages and displays only links permitted for the active role.
- `footer.php`: Standard closing HTML body tags, copyright text, and local Bootstrap 5 JS bundle script tags.
- `alerts.php`: Renders dismissible Bootstrap alert boxes if a flash message exists in session.
- `logger.php`: Procedural helper `log_activity($conn, $action, $module, $target_id, $details)` that inserts records into `system_logs`.

### 4. `admin/` Directory
Contains procedural management scripts accessible strictly to users with the `Administrator` role:
- `dashboard.php`: Queries total counts (students, registrars, user accounts, active enrollments) and displays statistics cards and recent audit activities.
- `users/`: User account administration (`index.php` lists accounts with search/filter; `create.php` creates staff/admin accounts; `edit.php` edits roles/details).
- `students/`: Administrative student oversight (`index.php` lists all students; `view.php` displays comprehensive student records).
- `academic-years/`: `index.php` lists academic years and semesters; `create.php` adds new academic calendar periods and activates current terms.
- `reports/`: Generates institutional reports (`student-reports.php`, `enrollment-reports.php`) with printable views and CSV export triggers.
- `backup/`: Database maintenance interface (`index.php` shows backup history, triggers backup creation, and handles SQL restore).
- `logs/`: `index.php` provides an audit log viewer with action, module, date, and keyword filters.

### 5. `registrar/` Directory
Contains procedural scripts accessible to `Registrar Personnel` and `Administrator`:
- `dashboard.php`: Displays registrar metrics (total registered, current term enrolled, pending registrations, recent student admissions).
- `students/`: Student record management (`index.php` provides searchable student directory; `register.php` provides student registration form with auto-generated ID; `edit.php` updates biographical details; `view.php` displays profile and enrollment history).
- `enrollment/`: Term enrollment management (`index.php` lists term enrollments; `enroll.php` assigns student to active term and year level; `edit-status.php` updates status; `history.php` shows student term timeline).
- `academic-years/`: `index.php` provides read-only view of academic calendar periods.
- `reports/`: Registrar student rosters and enrollment lists.

### 6. `student/` Directory
Contains procedural scripts accessible strictly to authenticated `Students`:
- `dashboard.php`: Displays personal biographical snapshot, current term enrollment status, and degree program standing.
- `profile.php`: Displays complete personal profile records.
- `profile-edit.php`: Allows updating permitted personal contact fields (mobile number, address, emergency contact). Institutional academic fields remain strictly read-only.
- `enrollment.php`: Details active semester enrollment and status.
- `academic-records.php`: Chronological view of past term enrollments.
- `change-password.php`: Secure interface allowing students to change their password.

### 7. `process/` Directory
Dedicated backend procedural scripts that handle form submissions (`POST`), execute validation, perform database transactions with MySQLi prepared statements, set flash messages, and redirect (Post/Redirect/Get pattern):
- `login-process.php`: Verifies CSRF token, sanitizes credentials, queries `users` table via prepared statement, verifies password via `password_verify()`, establishes session, and redirects to dashboard.
- `logout-process.php`: Invalidates session, unsets cookies, and redirects to login.
- `change-password-process.php`: Validates current password, hashes new password with `password_hash()`, and updates database.
- `users/`: Scripts for creating users, updating profiles, and toggling active/inactive status.
- `students/`: `register-process.php` runs a database transaction creating the `users` account and `students` record with auto-generated `YYYY-NNNNN` student number; `update-student-process.php` updates biographical data; `update-status-process.php` updates standing.
- `enrollment/`: `enroll-process.php` checks for duplicate semester enrollment (`uq_student_semester`) before inserting; `update-status-process.php` updates enrollment standing.
- `academic-years/`: `create-term-process.php` inserts new academic years and manages active semester flags.
- `reports/`: `export-csv.php` generates and streams formatted CSV files with appropriate MIME headers.
- `backup/`: `create-backup-process.php` dumps SQL tables and data into `database/backup/`; `restore-backup-process.php` executes SQL restore scripts within transaction blocks.

### 8. `assets/` Directory
- `css/style.css`: Custom application styling for dashboard layouts, sidebars, cards, tables, and badge states.
- `css/print.css`: Print stylesheet formatting official reports with institutional headers and hiding navigation bars.
- `js/main.js`: Vanilla JavaScript for client validation, modal handling, dynamic search filters, and print triggers.
- `images/`: Institutional logos, seals, and default user avatars.
- `vendor/`: Local, offline copies of Bootstrap 5.3 and Bootstrap Icons.

### 9. `database/` Directory
- `database.sql`: Full DDL script creating all 9 normalized relational tables, foreign keys with `ON DELETE RESTRICT`/`SET NULL`, and composite unique keys.
- `seeds.sql`: Initial seed data provisioning roles, default accounts (`admin`, `registrar`, `student`), sample programs, and academic terms.
- `backup/`: Secure directory storing generated `.sql` database backup dumps, protected with `.htaccess` (`Deny from all`).

---

## 6. Database Connection Strategy (`mysqli_connect()`)

In strict accordance with the Constitution, database connections are managed through procedural `mysqli_connect()` wrapped in `config/database.php`:

```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sis_db');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns the shared procedural MySQLi database connection
 * @return mysqli
 */
function db_connect() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if (!$conn) {
            error_log('Database Connection Failed: ' . mysqli_connect_error());
            die('<div style="font-family:sans-serif;padding:2rem;text-align:center;">'
                . '<h2 style="color:#b91c1c;">Database Connection Error</h2>'
                . '<p>Unable to connect to the database. Please verify that MySQL is running in XAMPP.</p>'
                . '</div>');
        }
        
        mysqli_set_charset($conn, DB_CHARSET);
    }
    
    return $conn;
}
```

Every procedural script or process handler that requires database access simply executes:
```php
require_once __DIR__ . '/../config/database.php';
$conn = db_connect();
```

---

## 7. Authentication & PHP Session Management Strategy

### 1. Session Initialization (`includes/session.php`)
Every page load begins by including `includes/session.php`:
- Configures secure session parameters:
  ```php
  ini_set('session.cookie_httponly', 1);
  ini_set('session.use_only_cookies', 1);
  ini_set('session.cookie_samesite', 'Lax');
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }
  ```
- Validates inactivity timeout (30 minutes). If inactive duration exceeds `SESSION_TIMEOUT`, the session is cleared and the user is redirected to `login.php?expired=1`.
- Provides automatic session ID regeneration on login (`session_regenerate_id(true)`) to prevent session fixation attacks.

### 2. Session Data Structure
Upon successful authentication, `$_SESSION` stores:
```php
$_SESSION['user_id'] = (int)$user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['role_id'] = (int)$user['role_id'];
$_SESSION['role_name'] = $user['role_name']; // 'Administrator' | 'Registrar' | 'Student'
$_SESSION['must_change_password'] = (bool)$user['must_change_password'];
$_SESSION['last_activity'] = time();

// Student-specific session metadata
if ($user['role_name'] === 'Student') {
    $_SESSION['student_id'] = (int)$student['student_id'];
    $_SESSION['student_number'] = $student['student_number'];
}
```

---

## 8. Role-Based Access Control (RBAC) Strategy

Authorization is verified on the server side on every request before any page content renders or any action script executes.

### 1. Guard Implementation (`includes/auth.php`)
```php
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function has_role($allowed_roles) {
    if (!is_logged_in()) return false;
    if (is_string($allowed_roles)) $allowed_roles = [$allowed_roles];
    return in_array($_SESSION['role_name'], $allowed_roles, true);
}

function require_role($allowed_roles) {
    require_login();
    if (!has_role($allowed_roles)) {
        http_response_code(403);
        include __DIR__ . '/../includes/403.php';
        exit;
    }
}
```

### 2. Role Gating Mapping
- `admin/*` pages: `require_role('Administrator');`
- `registrar/*` pages: `require_role(['Administrator', 'Registrar']);`
- `student/*` pages: `require_role('Student');`
- Insecure Direct Object Reference (IDOR) Defense: In `student/profile.php`, `student/enrollment.php`, etc., data queries strictly bind `$_SESSION['student_id']`, ignoring any manipulated query parameters.

---

## 9. Form Submission & Processing Workflow

The system strictly utilizes the **Post/Redirect/Get (PRG)** pattern to prevent duplicate form submissions and maintain clean browser histories:

```text
1. User Views Form (GET)
   ├── Renders HTML form with hidden csrf_token:
   │   <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
   └── Displays any flash messages from previous actions

2. User Submits Form (POST)
   └── Submits to process/ directory (e.g., process/students/register-process.php)

3. Processing Script Executes (POST Handler)
   ├── Step 1: Verify user authentication & role authorization
   ├── Step 2: Validate CSRF token using hash_equals()
   ├── Step 3: Sanitize and validate required input parameters server-side
   ├── Step 4: If validation fails:
   │   ├── set_flash_message('error', 'Validation failed: [details]');
   │   └── Redirect back (GET) to form page: header('Location: ...'); exit;
   ├── Step 5: Execute database operation using procedural prepared statements
   ├── Step 6: Log activity using log_activity($conn, ...)
   ├── Step 7: Set success notification: set_flash_message('success', 'Operation completed.');
   └── Step 8: Redirect (GET) to target view: header('Location: ...'); exit;

4. Target View Displays Result (GET)
   └── includes/alerts.php renders dismissible success alert banner
```

---

## 10. Database Interaction Workflow using MySQLi Prepared Statements

In accordance with Constitution Section I and II, **100% of database queries involving user or session input MUST use MySQLi procedural prepared statements**. Direct string concatenation in SQL is strictly forbidden.

### Standard Procedural Prepared Statement Pattern

#### 1. Data Selection Query (SELECT)
```php
$sql = "SELECT student_id, student_number, first_name, last_name, email, current_status 
        FROM students 
        WHERE program_id = ? AND current_status = ? 
        ORDER BY last_name ASC 
        LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "isii", $program_id, $status, $limit, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}
mysqli_stmt_close($stmt);
```

#### 2. Data Mutation Query (INSERT / UPDATE)
```php
$sql = "UPDATE students 
        SET contact_number = ?, home_address = ?, emergency_contact_name = ?, emergency_contact_number = ?, updated_at = NOW() 
        WHERE student_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssssi", $contact, $address, $emergency_name, $emergency_phone, $student_id);
$success = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
```

#### 3. Transaction Handling (Multi-Table Insertion)
Used during student registration (creating `users` account + `students` profile):
```php
mysqli_begin_transaction($conn);
try {
    // 1. Insert into users
    $stmt1 = mysqli_prepare($conn, "INSERT INTO users (username, email, password_hash, role_id, status, must_change_password) VALUES (?, ?, ?, 3, 'Active', 1)");
    mysqli_stmt_bind_param($stmt1, "sss", $student_number, $email, $password_hash);
    mysqli_stmt_execute($stmt1);
    $user_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt1);

    // 2. Insert into students
    $stmt2 = mysqli_prepare($conn, "INSERT INTO students (student_number, user_id, first_name, last_name, date_of_birth, gender, civil_status, email, contact_number, home_address, emergency_contact_name, emergency_contact_number, program_id, current_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
    mysqli_stmt_bind_param($stmt2, "sissssssssssi", $student_number, $user_id, $first_name, $last_name, $dob, $gender, $civil, $email, $contact, $address, $em_name, $em_phone, $program_id);
    mysqli_stmt_execute($stmt2);
    $student_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt2);

    mysqli_commit($conn);
} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Registration failed: " . $e->getMessage());
    // handle error
}
```

---

## 11. Reusable PHP Component Strategy

Shared layout components live in `includes/` and are included procedurally.

### Standard Page Composition
```php
<?php
// admin/users/index.php
require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Server-side authorization check
require_role('Administrator');

$conn = db_connect();
$page_title = 'User Accounts Management';

// Page-specific data queries...

// Include Header Layout
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="app-main">
        <!-- Top Navigation -->
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
        
        <main class="content-body">
            <!-- Flash Message Alerts -->
            <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0"><?php echo escape($page_title); ?></h1>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-person-plus-fill me-1"></i> Add User
                </a>
            </div>
            
            <!-- Tabular Data Card -->
            <div class="sis-card">
                ...
            </div>
        </main>
        
        <!-- Footer -->
        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
```

---

## 12. Frontend Asset Organization

- All styling is centralized in `assets/css/style.css` (layout, sidebar, cards, tables, badges) and `assets/css/print.css` (official printable reports).
- Client interactivity (modals, client form verification, confirmation dialogs) is centralized in `assets/js/main.js`.
- Local offline vendor libraries are stored in `assets/vendor/`:
  - `assets/vendor/bootstrap/bootstrap.min.css`
  - `assets/vendor/bootstrap/bootstrap.bundle.min.js`
  - `assets/vendor/bootstrap-icons/bootstrap-icons.min.css` and font files (`.woff`, `.woff2`)
- **Zero Emoji Compliance**: All icons use Bootstrap Icons classes (`<i class="bi bi-..."></i>`). Emojis are strictly banned from UI templates.

---

## 13. Local Development Environment Setup using XAMPP

1. **Root Directory**: `c:\xampp\htdocs\sis`
2. **Access URL**: `http://localhost/sis/`
3. **Database Provisioning**:
   - Create database `sis_db` in phpMyAdmin (`http://localhost/phpmyadmin/`) with collation `utf8mb4_unicode_ci`.
   - Import `database/database.sql` (schema and constraints).
   - Import `database/seeds.sql` (default roles, programs, academic periods, and seed accounts).
4. **Default Test Accounts**:
   - Administrator: Username `admin`, Password `Admin@123`
   - Registrar: Username `registrar`, Password `Registrar@123`
   - Student: Username `2026-00001`, Password `Student@2026`

---

## 14. Phased Implementation Roadmap

1. **Phase 1: Setup & Environment Baseline**: Establish procedural directory structure, `config/database.php`, `config/system.php`, `database/database.sql`, `database/seeds.sql`, and local Bootstrap 5 assets.
2. **Phase 2: Foundational Includes & Security**: Implement `includes/session.php`, `includes/functions.php`, `includes/auth.php`, `includes/header.php`, `includes/sidebar.php`, `includes/navbar.php`, `includes/footer.php`, `includes/alerts.php`, and `includes/logger.php`.
3. **Phase 3: Authentication & Login Flow**: Implement `login.php`, `logout.php`, `process/login-process.php`, `process/logout-process.php`, and `process/change-password-process.php`.
4. **Phase 4: User Account Management (Admin)**: Implement `admin/users/index.php`, `create.php`, `edit.php`, and `process/users/*.php`.
5. **Phase 5: Student Profile Management (Registrar & Admin)**: Implement `registrar/students/index.php`, `view.php`, `edit.php`, and `admin/students/*.php`.
6. **Phase 6: Student Registration Workflow**: Implement `registrar/students/register.php` and `process/students/register-process.php` with transactional account provisioning.
7. **Phase 7: Enrollment Management**: Implement `registrar/enrollment/index.php`, `enroll.php`, `edit-status.php`, `history.php`, and `process/enrollment/*.php`.
8. **Phase 8: Role Dashboards & Student Self-Service**: Implement `admin/dashboard.php`, `registrar/dashboard.php`, `student/dashboard.php`, `student/profile.php`, `profile-edit.php`, and `academic-records.php`.
9. **Phase 9: Search, Filtering & Pagination**: Build reusable pagination helpers and multi-filter dropdown queries.
10. **Phase 10: Reports & CSV Export**: Implement `admin/reports/*.php`, `registrar/reports/*.php`, and `process/reports/export-csv.php`.
11. **Phase 11: System Activity Logging**: Implement `admin/logs/index.php` audit log viewer.
12. **Phase 12: Database Backup & Recovery**: Implement `admin/backup/index.php`, `process/backup/create-backup-process.php`, and `restore-backup-process.php`.
13. **Phase 13: Polish, Security Audits & Verification**: Verify 100% prepared statements, CSRF protection, XSS escaping, zero emojis, and run test scenarios in `quickstart.md`.
