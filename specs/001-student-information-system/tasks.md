# Tasks: Student Information System (SIS)

**Input**: Design documents from `/specs/001-student-information-system/`  
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/`, `quickstart.md`  
**Governing Document**: `.specify/memory/constitution.md` (Constitution v2.0.0)

## Format: `[ID] [P?] [Story?] Description with file path`

- **[P]**: Can run in parallel (different files, no blocking dependencies on incomplete tasks)
- **[Story]**: Maps task to spec user stories (`[US1]` through `[US6]`)
- Explicit procedural file paths included for every task

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization, procedural directory structure, database DDL, seeds, and vendor asset distribution.

- [x] T001 Create procedural project directory structure (`config/`, `includes/`, `admin/`, `registrar/`, `student/`, `process/`, `assets/`, `database/backup/`).
- [x] T002 [P] Create application configuration in `config/system.php` defining `APP_NAME`, `APP_URL`, `SESSION_TIMEOUT` (1800s), and `STORAGE_PATH`.
- [x] T003 [P] Create database connection in `config/database.php` defining credentials and procedural `db_connect()` using `mysqli_connect()`.
- [x] T004 Create `database/database.sql` containing full DDL with normalized tables (`roles`, `users`, `programs`, `academic_years`, `semesters`, `students`, `enrollments`, `system_logs`, `backup_logs`), primary keys, foreign keys with `ON DELETE RESTRICT`/`SET NULL`, composite unique constraints (`uq_ay_term`, `uq_student_semester`), and `utf8mb4_unicode_ci`.
- [x] T005 Create `database/seeds.sql` provisioning roles (`Administrator`, `Registrar`, `Student`), default admin account (`admin`), default registrar account (`registrar`), initial degree programs (`BSCS`, `BSIT`, `BSBA`, `BEED`), and active academic year/semester (`2026-2027`, `1st Semester`).
- [x] T006 [P] Set up vendor frontend assets in `assets/vendor/bootstrap/` and `assets/vendor/bootstrap-icons/` (local Bootstrap 5.3 CSS/JS and Bootstrap Icons font files for offline XAMPP operation).
- [x] T007 [P] Implement core CSS styling in `assets/css/style.css` for dashboard layout, sidebar, cards, tables, and badge states with zero emojis.
- [x] T008 [P] Create security protection file in `database/backup/.htaccess` containing `Deny from all` to block direct public HTTP access to SQL dumps.

---

## Phase 2: Foundational Infrastructure (Blocking Prerequisites)

**Purpose**: Core procedural session, authentication guard, helper functions, CSRF, and shared UI include components.

**⚠️ CRITICAL**: Foundational tasks MUST be completed before user story implementation begins.

- [x] T009 Implement `includes/session.php` to initialize secure PHP sessions with `cookie_httponly = 1`, `cookie_samesite = Lax`, session timeout verification, and session regeneration.
- [x] T010 [P] Implement `includes/functions.php` providing procedural helpers `escape()`, `sanitize()`, `set_flash_message()`, `get_flash_message()`, `generate_csrf_token()`, `verify_csrf_token()`, `format_date()`, and `redirect()`.
- [x] T011 [P] Implement `includes/auth.php` providing procedural guards `is_logged_in()`, `current_user()`, `require_login()`, `require_role()`, and `has_role()`.
- [x] T012 [P] Implement `includes/logger.php` providing procedural activity logging helper `log_activity($conn, $action, $module, $target_id, $details)`.
- [x] T013 [P] Implement reusable HTML header layout in `includes/header.php` linking local Bootstrap 5 CSS, Bootstrap Icons CSS, and `style.css`.
- [x] T014 [P] Implement reusable top navigation bar in `includes/navbar.php` displaying user name, role badge, profile link, and logout button.
- [x] T015 [P] Implement reusable sidebar navigation component in `includes/sidebar.php` dynamically displaying navigation links filtered by active session role (`Administrator`, `Registrar`, `Student`).
- [x] T016 [P] Implement reusable footer component in `includes/footer.php` with institutional copyright metadata and local Bootstrap 5 bundle JS script tags.
- [x] T017 [P] Implement reusable alert flash component in `includes/alerts.php` rendering dismissible Bootstrap 5 alerts for success, error, warning, and info states.
- [x] T018 [P] Implement HTTP 403 Forbidden error presentation view in `includes/403.php` for unauthorized access attempts.

**Checkpoint**: Foundation ready. User story implementation can now begin.

---

## Phase 3: User Story 1 - Secure Authentication & Role-Based Access Control (Priority: P1) 🎯 MVP

**Goal**: Deliver secure authentication, session management, role verification, and password management.

**Independent Test**: Navigate to `http://localhost/sis/login.php`, attempt unauthenticated access to protected routes (verify redirect), log in as `admin`, verify redirection to `admin/dashboard.php`, test logout, and test password change.

- [x] T019 [US1] Implement `login.php` rendering the login form with CSRF token, username/password inputs, error alert banners, and Bootstrap Icons.
- [x] T020 [US1] Implement `process/login-process.php` handling login POST requests, verifying CSRF token, validating credentials with `mysqli_prepare()` and `password_verify()`, checking account `status = 'Active'`, setting `$_SESSION`, and redirecting to role dashboards.
- [x] T021 [US1] Implement `logout.php` and `process/logout-process.php` handling session invalidation, cookie clearing, session destruction, and redirection to `login.php`.
- [x] T022 [US1] Implement `index.php` root router redirecting authenticated users to their role dashboard or unauthenticated visitors to `login.php`.
- [x] T023 [US1] Implement `student/change-password.php` rendering the password change form with current password verification and CSRF token.
- [x] T024 [US1] Implement `process/change-password-process.php` validating current password with `password_verify()`, hashing new password with `password_hash()`, updating MySQL via prepared statement, and setting flash success.

**Checkpoint**: User Story 1 MVP fully functional and independently testable.

---

## Phase 4: User Story 1 - Administrator User Account Management (Priority: P1)

**Goal**: Enable administrators to manage system-wide user accounts across Administrator, Registrar, and Student roles.

**Independent Test**: Log in as `admin`, navigate to User Management (`admin/users/index.php`), create new accounts for different roles, edit user details, toggle account activation status, and verify deactivated users cannot log in.

- [x] T025 [P] [US1] Implement `admin/users/index.php` listing user accounts with search input, role filter, status badges (`Active`/`Inactive`), pagination, and action buttons.
- [x] T026 [P] [US1] Implement `admin/users/create.php` providing the form for administrators to create staff and student user accounts with role selection and CSRF token.
- [x] T027 [US1] Implement `process/users/create-user-process.php` validating input server-side, checking uniqueness of username and email via prepared statements, hashing default password, inserting new record, and logging activity.
- [x] T028 [US1] Implement `admin/users/edit.php` and `process/users/edit-user-process.php` allowing administrators to update user email, role, and password resets with validation.
- [x] T029 [US1] Implement `process/users/toggle-status-process.php` toggling account status between `Active` and `Inactive` and logging the action.

---

## Phase 5: User Story 2 - Student Profile Information Management (Priority: P1)

**Goal**: Enable Registrar Personnel and Administrators to view, search, filter, and update comprehensive student biographical and academic profiles.

**Independent Test**: Log in as `registrar`, view the student list (`registrar/students/index.php`), click a student to view full details (demographics, contacts, program), edit contact fields, and verify data updates accurately.

- [x] T030 [P] [US2] Implement `registrar/students/index.php` displaying the student directory table with student number, name, program, status badge (`Active`, `Inactive`, `Graduated`, `Withdrawn`), and view/edit action triggers.
- [x] T031 [P] [US2] Implement `registrar/students/view.php` displaying the full biographical profile, contact information, emergency contacts, and academic program standing.
- [x] T032 [US2] Implement `registrar/students/edit.php` allowing authorized staff to edit biographical and contact information with server-side validation.
- [x] T033 [US2] Implement `process/students/update-student-process.php` handling profile updates via procedural prepared statements with server-side validation and logging.
- [x] T034 [US2] Implement `process/students/update-status-process.php` updating student institutional status (`Active`, `Inactive`, `Graduated`, `Withdrawn`) without deleting historical records.
- [x] T035 [US2] Implement `admin/students/index.php` and `admin/students/view.php` for administrative student record oversight.

---

## Phase 6: User Story 2 - Student Registration Workflow (Priority: P1)

**Goal**: Implement the student registration workflow with auto-generated Student Numbers (`YYYY-NNNNN`), duplicate prevention, and automatic student user account provisioning.

**Independent Test**: Register a new student at `registrar/students/register.php`, verify auto-generated ID, verify required field validation, test duplicate rejection, and verify the linked user account is provisioned with default credentials (`Student@YYYY`).

- [x] T036 [US2] Implement `registrar/students/register.php` with responsive student registration form, auto-generated student number preview with manual override toggle, program selector, and CSRF token.
- [x] T037 [US2] Implement `process/students/register-process.php` executing within a database transaction (`mysqli_begin_transaction`): auto-generating/validating student number, inserting into `users` (`password_hash('Student@YYYY')`, `must_change_password=1`), inserting into `students`, and logging activity.
- [x] T038 [US2] Enforce server-side duplicate validation for `student_number` and `email` with clean error feedback and form field preservation.

---

## Phase 7: User Story 3 - Academic Term & Enrollment Management (Priority: P2)

**Goal**: Enable Registrar Personnel to create and manage student term enrollments, track enrollment history, and prevent duplicate term entries.

**Independent Test**: Select an active registered student, enroll them into the current academic semester and program via `registrar/enrollment/enroll.php`, verify enrollment record creation, attempt duplicate enrollment for the same term (verify rejection), and update status to `Completed`/`Dropped`.

- [x] T039 [P] [US3] Implement `admin/academic-years/index.php` and `create.php` along with `process/academic-years/create-term-process.php` allowing staff to define academic years and activate semesters.
- [x] T040 [P] [US3] Implement `registrar/academic-years/index.php` displaying the current active academic calendar terms.
- [x] T041 [US3] Implement `registrar/enrollment/index.php` listing term enrollments with filters for Academic Year, Semester, Program, and Status.
- [x] T042 [US3] Implement `registrar/enrollment/enroll.php` providing student selection, active term confirmation, year level selection (`1st Year` through `5th Year`), status selection, and CSRF token.
- [x] T043 [US3] Implement `process/enrollment/enroll-process.php` validating active student status, checking for duplicate term enrollment (`uq_student_semester`), executing insertion via prepared statements, and logging activity.
- [x] T044 [US3] Implement `registrar/enrollment/edit-status.php` and `process/enrollment/update-status-process.php` allowing staff to update enrollment standing (`Pending`, `Enrolled`, `Dropped`, `Completed`).
- [x] T045 [US3] Implement `registrar/enrollment/history.php` displaying the chronological enrollment history for a specific student.

---

## Phase 8: User Story 4 - Student Self-Service Portal & Role Dashboards (Priority: P2)

**Goal**: Implement dedicated role-specific dashboards (Admin, Registrar, Student) and the student self-service portal with IDOR protection.

**Independent Test**: Log in as a student, verify personal dashboard displays accurate biographical data and current term enrollment status, verify restricted fields are read-only, update permitted contact info, and confirm inability to access another student's profile.

- [x] T046 [P] [US4] Implement Administrator Dashboard in `admin/dashboard.php` displaying institutional statistics cards (students, registrars, total accounts, enrolled count) and recent system activities.
- [x] T047 [P] [US4] Implement Registrar Dashboard in `registrar/dashboard.php` displaying registered students count, current term enrolled count, pending registrations, and recent enrollment actions.
- [x] T048 [P] [US4] Implement Student Dashboard in `student/dashboard.php` displaying student's personal biographical snapshot, active term standing, and recent enrollment history.
- [x] T049 [US4] Implement student profile view in `student/profile.php` and edit form in `student/profile-edit.php` allowing students to update only permitted contact fields (`contact_number`, `home_address`, `emergency_contact_name`, `emergency_contact_number`).
- [x] T050 [US4] Implement `student/enrollment.php` and `student/academic-records.php` showing the student's active semester enrollment and historical terms.
- [x] T051 [US4] Enforce IDOR protection across all `student/*` scripts ensuring queries strictly bind `$_SESSION['student_id']` regardless of any URL query parameter.

---

## Phase 9: User Story 5 - Multi-Criteria Search, Filtering & Pagination (Priority: P3)

**Goal**: Implement unified search, filtering, and pagination infrastructure across student and enrollment lists.

**Independent Test**: Perform keyword searches by student number and name, apply program and term filters, navigate through paginated result pages, and verify URL query parameter preservation.

- [x] T052 [P] [US5] Implement reusable procedural pagination helper in `includes/functions.php` (`render_pagination($current_page, $total_pages, $query_params)`).
- [x] T053 [US5] Implement multi-criteria search and filter queries using parameterized MySQLi statements (`LIKE ?`, `LIMIT ? OFFSET ?`) in `registrar/students/index.php`.
- [x] T054 [US5] Implement multi-criteria search and filter queries in `registrar/enrollment/index.php` and `admin/users/index.php`.

---

## Phase 10: User Story 5 - Report Generation & Export (Priority: P3)

**Goal**: Deliver institutional reporting for student rosters and term enrollments with streaming CSV exports and print layouts.

**Independent Test**: Navigate to Reports, generate student roster filterable by program, click Export CSV (verify downloaded file), click Print Report (verify clean layout without sidebar/header artifacts).

- [x] T055 [P] [US5] Implement `admin/reports/student-reports.php` and `registrar/reports/student-list.php` supporting program and status filtering with printable report layout.
- [x] T056 [P] [US5] Implement `admin/reports/enrollment-reports.php` and `registrar/reports/enrollment-list.php` supporting term filtering with printable report layout.
- [x] T057 [US5] Implement `process/reports/export-csv.php` generating compliant streaming CSV files with UTF-8 BOM, appropriate MIME headers, and filter parameter preservation.

---

## Phase 11: User Story 6 - Activity Audit Logging (Priority: P3)

**Goal**: Implement audit logging capturing critical security and data mutation operations, and provide an administrative log viewer.

**Independent Test**: Perform user logins, account deactivations, student registrations, and enrollment status updates; navigate to Admin Audit Logs (`admin/logs/index.php`) and verify each event is accurately recorded with actor, IP, and details.

- [x] T058 [US6] Instrument all processing scripts (`login-process.php`, `register-process.php`, `enroll-process.php`, `create-user-process.php`, `toggle-status-process.php`) to call `log_activity()`.
- [x] T059 [US6] Implement Administrator audit log viewer in `admin/logs/index.php` with action filter, module filter, date range filter, and pagination.

---

## Phase 12: User Story 6 - Database Backup & Recovery (Priority: P3)

**Goal**: Implement native procedural PHP database backup generation and recovery utilities restricted exclusively to Administrators.

**Independent Test**: Log in as `admin`, navigate to Database Backup (`admin/backup/index.php`), generate a backup dump (verify `.sql` file appears in history and downloads securely), test recovery upload confirmation, and verify non-admin users cannot access the module.

- [x] T060 [US6] Implement `process/backup/create-backup-process.php` that iterates database tables, extracts schemas and table rows, and writes timestamped `.sql` dump files into `database/backup/`.
- [x] T061 [US6] Implement secure download streaming action in `process/backup/download-backup.php` ensuring only authenticated Administrators can download backup files.
- [x] T062 [US6] Implement database restoration handler in `process/backup/restore-backup-process.php` validating uploaded `.sql` files, verifying administrator password confirmation, executing SQL statements in transactions, and logging the event.
- [x] T063 [US6] Implement Administrator backup and recovery management view in `admin/backup/index.php` with backup trigger button, history table, and restore form with confirmation modal.

---

## Phase 13: Polish, Security Hardening & End-to-End Verification

**Purpose**: Cross-cutting security audits, responsive UI verification, quickstart validation, and documentation.

- [x] T064 [P] Perform comprehensive SQL injection audit across all procedural queries verifying 100% prepared statement parameter binding (`mysqli_stmt_bind_param`).
- [x] T065 [P] Perform XSS audit across all pages verifying 100% user output uses `escape()`.
- [x] T066 [P] Perform CSRF audit verifying all state-changing POST forms include valid `csrf_token` input.
- [x] T067 [P] Audit all templates to verify zero emojis are used and all icons utilize Bootstrap Icons classes (`bi bi-*`).
- [x] T068 Test responsive layouts across mobile, tablet, and desktop viewports, verifying sidebar toggle behavior and table responsiveness.
- [x] T069 Execute end-to-end quickstart validation scenarios documented in `quickstart.md` and verify all acceptance scenarios for US1 through US6.
- [x] T070 Document installation, configuration, user accounts, and testing procedures in `README.md`.

---

## Dependencies & Execution Order

### Phase Dependencies

```text
Phase 1: Setup ──> Phase 2: Foundational ──┬──> Phase 3 & 4: US1 (Auth & Users) ──┬──> Phase 5 & 6: US2 (Students) ──> Phase 7: US3 (Enrollment) ──> Phase 8: US4 (Dashboards & Portal) ──┬──> Phase 13: Polish & Hardening
                                           │                                      │                                                                                                  │
                                           │                                      └──> Phase 9 & 10: US5 (Search & Reports) ─────────────────────────────────────────────────────────┤
                                           │                                                                                                                                         │
                                           └─────────────────────────────────────────> Phase 11 & 12: US6 (Audit & Backup) ──────────────────────────────────────────────────────────┘
```

- **Phase 1 (Setup)**: Can start immediately. No dependencies.
- **Phase 2 (Foundational)**: Depends on Phase 1. Blocks ALL user stories.
- **Phase 3 & 4 (US1 Auth & Users)**: Depends on Phase 2. MVP core foundation.
- **Phase 5 & 6 (US2 Student Profile & Registration)**: Depends on Phase 3 (needs User accounts and roles).
- **Phase 7 (US3 Academic Term & Enrollment)**: Depends on Phase 5 (needs Student records).
- **Phase 8 (US4 Role Dashboards & Student Portal)**: Depends on Phase 5 and Phase 7 (summarizes students and enrollments).
- **Phase 9 & 10 (US5 Search, Filtering & Reports)**: Depends on Phase 5 and Phase 7.
- **Phase 11 & 12 (US6 Audit Logging & Backup)**: Depends on Phase 2 (Foundational) and Phase 3 (Auth).
- **Phase 13 (Polish & Verification)**: Depends on all previous phases.

---

## Implementation Strategy: MVP First

1. **Step 1**: Complete Phase 1 (Setup) and Phase 2 (Foundational).
2. **Step 2**: Complete Phase 3 (User Story 1 - Authentication).
3. **STOP & VALIDATE**: Test login/logout and session management on `http://localhost/sis/login.php`. This forms the viable **MVP** foundation.
4. **Step 3**: Deliver Phase 5 & 6 (Student Registration & Profile Management).
5. **Step 4**: Deliver Phase 7 (Enrollment Management).
6. **Step 5**: Deliver Phase 8 (Dashboards & Student Self-Service).
7. **Step 6**: Deliver Phase 9 & 10 (Search, Filter, and Reports) and Phase 11 & 12 (Audit & Backup).
8. **Step 7**: Complete Phase 13 (Security audits, responsive verification, and documentation).
