# Research & Technical Decisions: Student Information System (SIS)

**Feature**: Student Information System (SIS)  
**Date**: 2026-11-07  
**Status**: Completed  
**Governing Document**: `.specify/memory/constitution.md` (Constitution v2.0.0)

## 1. Technical Baseline & Constraints

### Decision: Native Procedural PHP (Zero Frameworks, Zero MVC)
- **Decision**: Develop the application strictly using Native PHP with procedural programming, avoiding object-oriented frameworks, MVC abstractions, composer packages, and third-party routing systems.
- **Rationale**: Mandated by Constitution v2.0.0. Eliminates unnecessary framework cognitive load, ensures transparent maintainability for developers learning native PHP, and runs natively on default local XAMPP Apache installations without build or autoloading tools.
- **Alternatives Considered**: 
  - *MVC Framework Architecture / Custom MVC*: Strictly rejected per Constitution v2.0.0 Section 3 & 4.
  - *CodeIgniter / Laravel / Slim*: Prohibited by constitution; adds external dependency overhead.

### Decision: Database Connectivity via `mysqli_connect()` with Procedural Prepared Statements
- **Decision**: Establish database connectivity using procedural `mysqli_connect()` and execute all parameterized database operations using procedural MySQLi prepared statements (`mysqli_prepare()`, `mysqli_stmt_bind_param()`, `mysqli_stmt_execute()`, `mysqli_stmt_get_result()`, `mysqli_stmt_close()`).
- **Rationale**: Mandated by Constitution v2.0.0 Section 1. Directly leverages native C-level PHP drivers, guarantees security against SQL injection, and avoids custom ORM complexity.
- **Alternatives Considered**:
  - *PDO / Object-Oriented mysqli*: Rejected in favor of explicit procedural `mysqli_connect()`.
  - *Raw SQL String Interpolation*: Strictly prohibited by Constitution and OWASP security standards.

### Decision: Role-Based Procedural Directory Organization
- **Decision**: Organize application pages directly by user role (`admin/`, `registrar/`, `student/`), centralize reusable layouts into `includes/`, and separate form submission processing into dedicated procedural scripts (`process/`).
- **Rationale**: Clean, intuitive structure that aligns file paths with user roles and system features while avoiding complex dispatcher/routing mechanisms.
- **Alternatives Considered**:
  - *Controller/Model/View split*: Prohibited by Constitution v2.0.0.
  - *Single-file spaghetti with mixed query and HTML*: Avoided by isolating state-changing POST handlers in `process/`.

### Decision: Session-Based Authentication & Server-Side RBAC
- **Decision**: Native PHP sessions (`session_start()`, `session_regenerate_id(true)`) with secure cookie flags (`httponly`, `samesite=Lax`), inactivity timeout validation (30 minutes), and procedural role verification guards (`require_login()`, `require_role()`).
- **Rationale**: Built-in to PHP, reliable, zero third-party dependencies, and tamper-proof server-side storage.
- **Alternatives Considered**:
  - *JWT (JSON Web Tokens)*: Unnecessary overhead for server-rendered multi-page applications.

### Decision: Frontend Presentation via HTML5, Bootstrap 5, and Vanilla JavaScript
- **Decision**: Clean HTML5 pages styled with local Bootstrap 5.3 CSS/JS bundles, custom external CSS (`assets/css/style.css`, `assets/css/print.css`), and Bootstrap Icons (or Font Awesome). Client interactions implemented with vanilla JavaScript (ES6+). Zero emojis used in interface controls.
- **Rationale**: Delivers a responsive, professional academic user experience across desktop, tablet, and mobile without requiring Node.js or build steps.
- **Alternatives Considered**:
  - *React / Vue / SPA*: Prohibited by development restrictions.
  - *Emojis as UI icons*: Strictly prohibited by Constitution Section IV.

---

## 2. Security Implementation Strategy

1. **SQL Injection**: 100% of SQL statements handling input must use parameterized prepared statements with explicit type specifiers (`s`, `i`, `d`, `b`).
2. **Cross-Site Scripting (XSS)**: All user-derived data rendered in HTML must pass through `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
3. **Cross-Site Request Forgery (CSRF)**: All state-changing POST forms must validate session-generated tokens using `hash_equals()`.
4. **Access Control (IDOR & Privilege Escalation)**: Every protected page verifies session role. Student endpoints bind queries strictly to `$_SESSION['student_id']`.
5. **Password Storage**: Cryptographically hashed using `password_hash($pwd, PASSWORD_DEFAULT)` and verified with `password_verify()`.

---

## 3. Database & Operational Utilities

### Decision: Normalized Relational Schema (3NF)
- Normalized MySQL database with 9 core tables: `roles`, `users`, `programs`, `academic_years`, `semesters`, `students`, `enrollments`, `system_logs`, `backup_logs`.
- Enforced composite unique keys (`uq_student_semester` and `uq_ay_term`) guarantee zero duplicate enrollments and zero duplicate academic terms.

### Decision: Native Procedural SQL Backup & Recovery
- Backup utility in `process/backup/` that extracts table definitions and inserts into a timestamped `.sql` file stored in `database/backup/` (protected by `.htaccess`). Recovery parses and executes the dump inside database transactions.
