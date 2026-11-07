<!--
SYNC IMPACT REPORT
==================
- Version Change: 1.0.0 -> 2.0.0 (Major Architectural Redefinition: Procedural Native PHP)
- Modified Principles:
  - Principle I (Native PHP & Stack Discipline) -> Updated to mandate procedural programming, mysqli_connect(), and procedural MySQLi prepared statements.
  - Principle III (Structured Separation of Concerns) -> Redefined to require straightforward procedural Native PHP architecture; strictly prohibits MVC framework architectures, framework-style controllers, models, and routing systems.
  - Principle IV (Professional Frontend Standards) -> Allows Bootstrap Icons or Font Awesome; strictly maintains zero-emoji policy.
- Added Sections / Structural Clarifications:
  - Procedural Native Architecture & Role-Based File Organization (admin/, registrar/, student/, includes/, database/, assets/).
  - Prohibited technologies expanded to include MVC frameworks, composer-based frameworks, and framework-style controllers/models.
- Removed Sections: None
- Follow-up TODOs: None (all directives fully defined)
-->

# Student Information System Constitution

## Core Principles

### I. Native Procedural PHP & Stack Discipline (Strictly Local XAMPP)
The system MUST be developed exclusively using Native PHP with procedural programming, MySQL (managed via phpMyAdmin), HTML5, CSS3, and vanilla JavaScript, running locally on Apache under XAMPP.
- The system MUST use Native PHP with procedural programming throughout all application scripts.
- Database connections MUST use `mysqli_connect()`.
- All database operations involving user or external input MUST use procedural MySQLi prepared statements (`mysqli_prepare()`, `mysqli_stmt_bind_param()`, `mysqli_stmt_execute()`, `mysqli_stmt_get_result()`, `mysqli_stmt_close()`). Direct string concatenation in SQL queries is strictly prohibited.
- PHP sessions MUST handle user authentication, identity tracking, and session management.
- Reusable PHP files and `include` / `require` statements MUST be used for shared functionality.
- Containerization (Docker, Docker Compose), cloud hosting, VPS deployment, and automated CI/CD deployment pipelines are STRICTLY PROHIBITED. All development and testing must remain on localhost under XAMPP.
*Rationale*: Maximizes maintainability, eliminates framework friction, ensures direct learning accessibility, and guarantees seamless execution on standard local XAMPP Apache environments.

### II. Defense-in-Depth Security & Data Integrity
Application security MUST be strictly enforced on the server side for every request and data operation.
- Server-side validation is MANDATORY for all incoming request payloads before database storage. Client-side JavaScript serves solely for user experience and MUST NOT replace server validation.
- User authentication MUST utilize secure PHP sessions. Passwords MUST be hashed using `password_hash()` (using `PASSWORD_DEFAULT` or `PASSWORD_BCRYPT`) and verified using `password_verify()`.
- State-changing form submissions (POST) MUST validate CSRF tokens generated in the session.
- Output escaping via `htmlspecialchars()` (or an `escape()` helper) MUST be applied to all dynamic content rendered in HTML views to prevent Cross-Site Scripting (XSS).
- The application MUST enforce server-side role validation on every protected page to prevent privilege escalation and unauthorized access.
*Rationale*: Academic records and student personal data require uncompromising data integrity and robust defenses against SQL injection, XSS, CSRF, and unauthorized access.

### III. Straightforward Procedural Architecture (Zero MVC Framework Overhead)
The codebase MUST follow a straightforward, organized Native PHP procedural architecture designed for clarity and maintainability.
- The project MUST NOT implement an MVC framework architecture.
- Framework-style controllers, models, and custom routing engines are STRICTLY PROHIBITED.
- PHP files SHOULD be organized logically by functionality and user role (e.g., `admin/`, `registrar/`, `student/`, `includes/`).
- Shared components (headers, navigation, sidebars, footers, alerts) SHOULD be placed in reusable include files under `includes/`.
- Form processing MAY be separated into dedicated procedural PHP action scripts (e.g., `process_login.php`, `save_student.php`) or processed within the page when appropriate.
- The architecture MUST remain simple, transparent, and understandable for developers learning native PHP.
*Rationale*: Framework-style abstractions (controllers, models, custom ORMs, dispatchers) add unnecessary complexity to native PHP educational systems; clean procedural scripts organized by user role provide maximum transparency and ease of debugging.

### IV. Professional Frontend Standards (Zero Emoji Policy)
The user interface MUST be modern, clean, responsive, accessible, and strictly professional.
- HTML5 MUST be used for page structure.
- CSS3 MUST be maintained in external stylesheets (`assets/css/`).
- Vanilla JavaScript MUST be used for client-side interactions and enhancements.
- Bootstrap 5 MAY be used for responsive layouts and standard UI components.
- Bootstrap Icons or Font Awesome MUST be used for interface icons.
- Emojis MUST NOT be used as interface icons, status indicators, or decorative elements anywhere in the system.
- The UI MUST adapt cleanly to desktop, tablet, and mobile screens with consistent typography, cards, tables, forms, and navigation.
- Every state-dependent view MUST provide clear feedback for success, error, loading, and empty states.
*Rationale*: An official academic record-keeping platform requires an institutional, authoritative, and clean user experience.

### V. Relational Database Normalization
The MySQL database MUST follow rigorous relational design and normalization standards.
- Database tables MUST be properly normalized (targeting 3NF) with explicit primary keys, foreign key constraints, and cascading rules where appropriate.
- Entity relationships (one-to-many, many-to-many) MUST be clearly modeled with explicit constraints.
- Data types, lengths, and nullability constraints MUST be selected deliberately to reflect business rules and prevent redundant data storage.
- All database migrations, initial data, and schema definitions MUST be maintained in version-controlled SQL script files executable in phpMyAdmin.
*Rationale*: Preserves relational integrity, eliminates data anomalies, and ensures reliable reporting for enrollment, grading, and student histories.

### VI. Zero-Placeholder Incremental Delivery
Features MUST be implemented completely, functionally, and incrementally according to approved specifications.
- Developers MUST NOT commit dummy buttons, dead links, non-functional filters, or incomplete "Coming Soon" placeholder functionality.
- Existing functionality MUST be verified prior to modifying shared code to guarantee zero regressions.
- Role-based permissions across the three primary roles (Administrator, Registrar Personnel, Student) MUST be consistently enforced across all modules and navigation menus.
*Rationale*: Partial or cosmetic-only implementations degrade system reliability and undermine user trust in institutional record-keeping.

## Technology Stack & Strict Prohibitions

The application is bounded by the following technological boundaries:

- **Backend Platform**: Native PHP with procedural programming (XAMPP-compatible).
- **Database Connection**: `mysqli_connect()`.
- **Database Operations**: MySQLi procedural prepared statements (`mysqli_prepare`, `mysqli_stmt_bind_param`, `mysqli_stmt_execute`).
- **Session & Auth**: Native PHP session management with procedural role-based access control guards.
- **Frontend Stack**: HTML5, CSS3 external stylesheets, Vanilla JavaScript, Bootstrap 5 (CSS/JS bundle), and Bootstrap Icons or Font Awesome.
- **Local Environment**: Apache web server and MySQL managed via XAMPP control panel and phpMyAdmin on `localhost`.
- **Primary Roles**:
  1. *Administrator*: User account management, global configurations, audit logging, system settings, database backup and recovery.
  2. *Registrar Personnel*: Student registration, student profile management, enrollment records, academic terms, and student reporting.
  3. *Student*: Profile viewing, enrollment history, permitted contact information updates, and password changes.

**Strictly Prohibited Technologies & Approaches**:
- Laravel, CodeIgniter, Symfony, Yii, Slim, or any other PHP frameworks.
- Framework-based architectures and composer-based frameworks.
- Framework-style controllers, models, and custom routing systems.
- Single-Page Application (SPA) frameworks (React, Vue, Angular, Svelte) or Node.js backends.
- Docker, Docker Compose, Podman, Vagrant, or any containerization tooling.
- Cloud deployment, VPS hosting, remote deployments, or automated CI/CD deployment pipelines.
- External third-party microservices or cloud database dependencies.
- Emojis as interface icons or decorative UI elements.

## System Architecture & Role-Based File Organization

The application MUST organize native PHP files into straightforward procedural directories organized by user role and functional responsibility:

```text
sis/
├── index.php                         # Landing page & user login interface
├── config/
│   ├── database.php                  # Database connection parameters & mysqli_connect()
│   └── app.php                       # Application constants (app name, URL, session timeout)
├── includes/
│   ├── auth.php                      # Procedural session checks, login, logout, and role guards
│   ├── header.php                    # Reusable HTML head, Bootstrap 5 CSS, icon fonts, and top nav
│   ├── sidebar.php                   # Reusable role-filtered sidebar navigation
│   ├── footer.php                    # Reusable footer and script tags
│   ├── alerts.php                    # Reusable alert banners (success, error, warning)
│   ├── functions.php                 # Reusable procedural helper functions (sanitize, format, flash)
│   └── logger.php                    # Procedural activity audit logging helper
├── admin/                            # Administrator Module (Procedural scripts)
│   ├── dashboard.php                 # Administrator statistics & summary cards
│   ├── users.php                     # User account list & search
│   ├── user_create.php               # Create new user account
│   ├── user_edit.php                 # Edit user account details
│   ├── user_status.php               # Toggle user account activation status
│   ├── academic_terms.php            # Manage academic years & semesters
│   ├── audit_logs.php                # View system audit trail
│   └── backup.php                    # Database backup export & restore management
├── registrar/                        # Registrar Personnel Module (Procedural scripts)
│   ├── dashboard.php                 # Registrar overview & recent activities
│   ├── students.php                  # Student directory, search, and filters
│   ├── student_register.php          # Register new student & auto-provision account
│   ├── student_view.php              # Comprehensive student profile
│   ├── student_edit.php              # Update student biographical/contact details
│   ├── student_status.php            # Update student institutional standing
│   ├── enrollments.php               # Term enrollment list & filters
│   ├── enroll_student.php            # Enroll student into active term
│   └── reports.php                   # Student rosters & enrollment summary reports
├── student/                          # Student Self-Service Module (Procedural scripts)
│   ├── dashboard.php                 # Personal profile snapshot & active term standing
│   ├── profile.php                   # View complete personal profile
│   ├── profile_edit.php              # Update permitted contact info
│   ├── enrollment_history.php        # View past and current term enrollments
│   └── change_password.php           # Secure password change
├── actions/                          # Form processing action scripts (when separated)
│   ├── process_login.php             # Authenticate credentials & route to role dashboard
│   ├── process_logout.php            # Invalidate session & redirect to login
│   ├── export_csv.php                # Stream CSV report export
│   └── process_backup.php            # Generate & download SQL backup file
├── assets/
│   ├── css/
│   │   ├── style.css                 # External stylesheet for layout, cards, and tables
│   │   └── print.css                 # Clean print stylesheet for institutional reports
│   ├── js/
│   │   └── main.js                   # Client interactions and form validation
│   └── vendor/                       # Local offline Bootstrap 5 and Bootstrap Icons assets
├── database/
│   ├── schema.sql                    # Complete table creation DDL with constraints & indexes
│   └── seeds.sql                     # Seed data for roles, users, programs, and terms
└── storage/
    └── backups/                      # Secure directory for generated .sql dumps (.htaccess protected)
```

## Documentation & Operational Standards

Comprehensive, up-to-date documentation MUST be maintained in the repository for:

1. **System Architecture**: High-level module organization, directory structure, and procedural request flow.
2. **Database Schema**: Entity-Relationship diagrams, table dictionaries, and foreign key mappings.
3. **Installation & Setup**: Instructions for configuring XAMPP, Apache, importing SQL schemas in phpMyAdmin, and accessing default seed accounts.
4. **User Roles & Access Control**: Matrix detailing allowed actions and restrictions for Administrator, Registrar Personnel, and Student roles.
5. **Application Modules**: Procedural workflow documentation for student registration, enrollment processing, grading/status, account management, and reporting.
6. **Testing & Verification**: Manual test procedures, role-switching verification steps, and input validation test cases.
7. **Backup & Recovery**: Guidance for exporting and restoring MySQL database backups through phpMyAdmin or the admin backup utility.

## Governance

This Constitution is the supreme development authority for the Student Information System project. It supersedes all informal agreements, ad-hoc coding habits, and unratified design decisions.

1. **Compliance Requirements**: Every specification (`spec.md`), technical plan (`plan.md`), task list (`tasks.md`), generated code, and future modification MUST strictly comply with this Constitution.
2. **Conflict Resolution**: If any generated architecture, task breakdown, or code implementation conflicts with these rules (such as introducing MVC patterns, controllers, models, or third-party frameworks), this Constitution MUST take priority and the conflicting artifacts MUST be brought into compliance.
3. **Amendment Process**: Amendments to this constitution require a documented justification, version increment, and explicit approval before any disallowed technology or workflow change may be executed.
4. **Versioning Policy**:
   - **MAJOR** version bumps indicate backward-incompatible governance changes, removal or major redefinition of core principles (e.g., transitioning from MVC to procedural architecture, or altering core role models).
   - **MINOR** version bumps indicate addition of new principles, material expansion of technical guidelines, or new architectural modules.
   - **PATCH** version bumps indicate non-semantic clarifications, typo corrections, or minor documentation refinements.
5. **Enforcement**: Any code or proposal introducing prohibited technologies (Docker, frameworks, cloud deployments, framework-style controllers/models) or violating security rules (raw queries with user inputs, plain-text passwords, emoji iconography) MUST be rejected.

**Version**: 2.0.0 | **Ratified**: 2026-11-07 | **Last Amended**: 2026-11-07
