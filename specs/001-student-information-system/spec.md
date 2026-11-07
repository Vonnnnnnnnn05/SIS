# Feature Specification: Student Information System (SIS)

**Feature Branch**: `001-student-information-system`

**Created**: 2026-11-07

**Status**: Draft

**Input**: User description: "Establish a comprehensive product specification for a web-based Student Information System (SIS) that centralizes student records, registration, enrollment information, and administrative operations across Administrator, Registrar Personnel, and Student user roles."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Secure Authentication & Role-Based Access Control (Priority: P1)

As an Administrator, Registrar Personnel, or Student, I want to securely log in with my credentials so that I can access only the features and data permitted for my role, and safely log out when finished.

**Why this priority**: Authentication and role-based access control (RBAC) form the foundation of institutional data protection. Without role gating, no student records or administrative operations can be safely conducted.

**Independent Test**: Can be fully tested by creating one account per role (Admin, Registrar, Student), verifying successful logins route to role-appropriate interfaces, verifying unauthorized URL tampering is blocked, and confirming sessions terminate upon logout.

**Acceptance Scenarios**:

1. **Given** an active user account with valid credentials, **When** the user submits the login form, **Then** the system authenticates the user, establishes a protected session, and redirects to their role-specific dashboard.
2. **Given** an inactive or deactivated account, **When** login is attempted with correct credentials, **Then** the system denies access with a clear message indicating the account is deactivated.
3. **Given** an unauthenticated visitor, **When** attempting to access any protected administrative, registrar, or student route directly, **Then** the system denies access and redirects to the login screen.
4. **Given** an authenticated Student, **When** attempting to access Registrar or Administrator management URLs, **Then** the system rejects the request with an access-denied status.
5. **Given** an authenticated user in any role, **When** clicking logout, **Then** the session is invalidated and subsequent navigation to protected pages requires re-authentication.

---

### User Story 2 - Student Profile Registration & Lifecycle Management (Priority: P1)

As Registrar Personnel, I want to register a new student with complete biographical, contact, and academic details while ensuring uniqueness, so that the institution maintains an authoritative, non-redundant student registry.

**Why this priority**: Student record creation is the primary operational prerequisite for enrollment, reporting, and student account creation.

**Independent Test**: Can be fully tested by registering a new student, verifying required field validation, verifying duplicate student ID rejection, and viewing the created student profile with correct status.

**Acceptance Scenarios**:

1. **Given** valid registration details (name, date of birth, gender, contact information, program/course), **When** Registrar Personnel submits the registration form, **Then** a new student profile is created with an "Active" status and a unique student ID.
2. **Given** an attempt to register a student with a student ID that already exists in the system, **When** the form is submitted, **Then** the system blocks creation and displays a clear duplicate error notification.
3. **Given** an existing student profile, **When** Registrar Personnel updates editable contact or personal details, **Then** the updated values are saved and reflected immediately on the student's profile view.
4. **Given** a student with active or past enrollment history, **When** an Administrator or Registrar updates the student status (e.g., Active, Inactive, Graduated, Withdrawn), **Then** historical enrollment records remain intact and unchanged.

---

### User Story 3 - Academic Term & Enrollment Management (Priority: P2)

As Registrar Personnel, I want to create and manage student enrollment records tied to specific academic years and semesters, so that the institution can track which students are actively enrolled in each term.

**Why this priority**: Enrollment connects registered students to official academic periods and determines active student standing.

**Independent Test**: Can be fully tested by selecting an existing student, enrolling them into a specific academic year and semester, attempting a duplicate enrollment for the same student and term, and verifying enrollment status transitions.

**Acceptance Scenarios**:

1. **Given** an active registered student and an active academic period (e.g., Academic Year 2026-2027, 1st Semester), **When** Registrar Personnel submits an enrollment entry, **Then** the enrollment record is created with a designated status (e.g., Enrolled, Pending, Dropped) and linked to the student.
2. **Given** an existing enrollment record for a student in a specific academic period, **When** Registrar Personnel attempts to create a second enrollment for the same student in the same period, **Then** the system prevents duplicate entry and informs the user that an enrollment already exists.
3. **Given** a student with multiple past enrollment terms, **When** reviewing the student's enrollment tab, **Then** the full historical timeline of enrollments is displayed in chronological order.

---

### User Story 4 - Student Self-Service Portal & Record Access (Priority: P2)

As a Student, I want to log into my own portal so that I can review my personal profile, check my current enrollment standing, view past enrollment history, update allowed contact information, and change my password.

**Why this priority**: Empowers students with direct visibility into their academic standing while reducing administrative inquiry volume.

**Independent Test**: Can be fully tested by logging in as a student, confirming personal data and enrollment history display accurately, updating contact details, changing password, and verifying strict inability to view or edit another student's data.

**Acceptance Scenarios**:

1. **Given** an authenticated student, **When** viewing the student dashboard, **Then** the student sees their personal profile summary, current term enrollment status, and academic period information.
2. **Given** an authenticated student, **When** modifying permitted contact fields (such as phone number, emergency contact, or address), **Then** the updates are saved while restricted institutional fields (student ID, enrolled program, official status) remain strictly read-only.
3. **Given** an authenticated student, **When** supplying their current password and a new valid password, **Then** the password is updated and verified upon subsequent login.
4. **Given** an authenticated student, **When** attempting to query or tamper with URLs or parameters targeting another student's ID, **Then** the system denies the request with an authorization error.

---

### User Story 5 - Multi-Criteria Search, Filtering & Administrative Reporting (Priority: P3)

As an Administrator or Registrar Personnel, I want to search and filter students and enrollments by multiple criteria and generate formatted printable reports or CSV exports, so that institutional audits and operational lists can be produced efficiently.

**Why this priority**: Administrative reporting is essential for operational review, classroom planning, and external compliance reporting.

**Independent Test**: Can be fully tested by applying combinations of student ID search, name search, program filter, status filter, and term filter, validating pagination, and exporting the filtered dataset as printable views and CSV files.

**Acceptance Scenarios**:

1. **Given** a large student dataset, **When** a user types a search query (student ID or name substring) or selects filters (program, enrollment status, academic term), **Then** the table updates to display only matching records with accurate pagination counters.
2. **Given** a filtered view of enrolled students for a specific academic year and semester, **When** the user clicks "Export CSV", **Then** a structured CSV file matching the current filter criteria is generated and downloaded.
3. **Given** a filtered view, **When** the user clicks "Print Report", **Then** a clean, professional print-friendly layout opens containing the institution header, active filter summary, data rows, and timestamp.

---

### User Story 6 - Activity Audit Logging & System Data Safeguards (Priority: P3)

As an Administrator, I want the system to record critical security and record-modification events and provide database backup and recovery utilities, so that system accountability and data recovery are ensured.

**Why this priority**: Essential for administrative oversight, forensic auditing, and safeguarding institutional records against accidental data loss.

**Independent Test**: Can be fully tested by executing record modifications and user status changes, verifying entries in the audit log view, triggering an administrative backup export, and testing authorization boundaries so non-administrators cannot access backup tools.

**Acceptance Scenarios**:

1. **Given** any critical administrative or registrar action (such as user account deactivation, student profile modification, or enrollment status change), **When** the action completes, **Then** the system logs an audit entry recording the actor ID, action type, affected record, timestamp, and IP address.
2. **Given** an Administrator accessing the system monitoring module, **When** reviewing the activity log, **Then** the log presents filterable, chronological records of all logged events.
3. **Given** an Administrator accessing database utilities, **When** triggering a backup creation, **Then** the system generates a complete, downloadable database dump file.
4. **Given** non-administrator users (Registrar or Student), **When** attempting to access backup, restore, or audit log functions, **Then** access is forbidden.

---

### Edge Cases

- **Concurrent Enrollment**: What happens if two registrar staff members attempt to modify or enroll the same student simultaneously? The system must evaluate existing records on submission and notify the second user if the target record has been altered.
- **Student Profile Deactivation / Leave of Absence**: How does the system handle students who are on leave or suspended? Deactivation must preserve all historical enrollment and academic records without cascade-deleting related relational rows.
- **Incomplete or Malformed Inputs**: How does the system respond to missing required fields, non-numeric phone numbers, or invalid date formats? The system must reject the request server-side, maintain entered data in form controls, and highlight specific invalid fields.
- **Session Expiration During Long Form Entry**: What happens if a session expires while a registrar is filling out a comprehensive registration form? The system must cleanly redirect to login with an informational notice, preventing corrupted database records.
- **Empty Search Results**: How does the table interface behave when no records match a search query? The table must render a clear, professional empty state message rather than a broken layout or blank container.

## Requirements *(mandatory)*

### Functional Requirements

#### Authentication & User Management
- **FR-001**: System MUST authenticate users using their unique identifier (username or email) and password, establishing an isolated session.
- **FR-002**: System MUST enforce Role-Based Access Control (RBAC) across three distinct roles: Administrator, Registrar Personnel, and Student.
- **FR-003**: System MUST permit Administrators to create, edit, activate, and deactivate user accounts for all roles.
- **FR-004**: System MUST prevent deactivated user accounts from authenticating.
- **FR-005**: System MUST allow authenticated users across all roles to securely change their own passwords by confirming their current password.
- **FR-006**: System MUST securely hash all stored passwords using industry-standard one-way cryptographic algorithms (`password_hash`).
- **FR-007**: System MUST validate all authentication attempts on the server side and present non-revealing error messages for failed attempts.
- **FR-008**: System MUST immediately terminate and invalidate session tokens upon user logout.

#### Student Information & Registration
- **FR-009**: System MUST allow Registrar Personnel and Administrators to register new students with complete biographical details (First Name, Middle Name, Last Name, Date of Birth, Gender, Civil Status) and contact information (Email, Mobile Number, Residential Address, Emergency Contact).
- **FR-010**: System MUST enforce uniqueness for student identification numbers and reject any duplicate registration.
- **FR-011**: System MUST generate Student IDs automatically using an institutional sequential format (`YYYY-NNNNN`, e.g., `2026-00001`) while allowing Registrar Personnel to manually enter or override an ID for transfer/legacy students with real-time uniqueness validation.
- **FR-012**: System MUST allow authorized users (Registrar and Administrator) to view comprehensive student profiles.
- **FR-013**: System MUST allow authorized users to update editable biographical and contact details of existing student records.
- **FR-014**: System MUST track student institutional status (e.g., Active, Inactive, Graduated, Withdrawn).
- **FR-015**: System MUST validate all mandatory fields server-side before persisting student records.
- **FR-016**: System MUST prevent deletion of student records that have associated historical enrollments, requiring status deactivation instead.

#### Enrollment Management
- **FR-017**: System MUST allow Registrar Personnel and Administrators to define and manage academic periods (Academic Year and Semester/Term).
- **FR-018**: System MUST enable Registrar Personnel to create enrollment records associating an active student with a specific academic period.
- **FR-019**: System MUST enforce that a student can only have one active enrollment record per academic year and semester to prevent duplicate term enrollments.
- **FR-020**: System MUST manage official enrollment records at the Academic Period (Academic Year and Semester) and Degree Program level, tracking enrollment status, year level, and term remarks.
- **FR-021**: System MUST maintain and display a student's chronological enrollment history.
- **FR-022**: System MUST allow authorized personnel to update enrollment statuses (e.g., Enrolled, Pending, Dropped, Completed).
- **FR-023**: System MUST reject any attempt to create an enrollment for a non-existent or inactive student record.

#### Student Self-Service & Credential Provisioning
- **FR-024**: System MUST provide a dedicated self-service interface accessible exclusively to authenticated Students.
- **FR-025**: System MUST automatically provision a Student UserAccount upon successful registration using the Student ID as the username, an initial secure default password (`Student@<Year>`), and flag the account to require a password change upon first login.
- **FR-026**: System MUST restrict students to viewing strictly their own profile, enrollment status, and academic period records.
- **FR-027**: System MUST permit students to update only designated personal contact fields (such as phone number, address, and emergency contact).
- **FR-028**: System MUST keep core institutional records (Student ID, official name, enrollment history, program standing) read-only for students.
- **FR-029**: System MUST strictly reject any student request attempting to query or modify data belonging to another student identifier.

#### Role-Specific Dashboards
- **FR-030**: System MUST present an Administrator Dashboard displaying total students count, registrar staff count, total user accounts, currently enrolled count, recent system activity log, and enrollment summary statistics.
- **FR-031**: System MUST present a Registrar Dashboard displaying total registered students, currently enrolled term count, pending registrations, and recent enrollment actions.
- **FR-032**: System MUST present a Student Dashboard displaying personal information summary, current academic term, active enrollment status, and recent enrollment timeline.
- **FR-033**: System MUST dynamically load dashboard summary metrics based on the logged-in user's role and authorization tier.
- **FR-034**: System MUST provide responsive, clean dashboard cards and metric widgets without emoji decorations.

#### Search, Filtering & Pagination
- **FR-035**: System MUST support searching student records by Student ID (exact and prefix match).
- **FR-036**: System MUST support searching student records by student name (First, Middle, or Last name substring match).
- **FR-037**: System MUST support filtering records by academic program/course.
- **FR-038**: System MUST support filtering records by enrollment status (e.g., Enrolled, Pending, Dropped, Completed).
- **FR-039**: System MUST support filtering records by Academic Year and Semester.
- **FR-040**: System MUST paginate table datasets exceeding standard page thresholds (e.g., 10, 25, 50 records per page) with navigation controls and total record counts.

#### Reporting & Exporting
- **FR-041**: System MUST generate master student lists filterable by program and status.
- **FR-042**: System MUST generate official enrollment summary reports filterable by academic year and semester.
- **FR-043**: System MUST generate registration summary reports for newly registered students within a specified date window.
- **FR-044**: System MUST allow exporting filtered report datasets into structured CSV files.
- **FR-045**: System MUST provide dedicated, styled printable layouts for all generated reports, including institutional header metadata and generated timestamps.
- **FR-046**: System MUST ensure report generation and export permissions are restricted to Administrator and Registrar roles.

#### Audit Logging & System Data Safeguards
- **FR-047**: System MUST log critical administrative and operational events including user authentication, account creations/deactivations, student record modifications, and enrollment status updates.
- **FR-048**: System MUST record actor identity, event type, affected record identifier, timestamp, and client IP address for each audit event.
- **FR-049**: System MUST provide an audit log viewer restricted to Administrators with date and action filtering.
- **FR-050**: System MUST provide a database backup generation mechanism accessible exclusively to Administrators.
- **FR-051**: System MUST provide a database restoration/recovery mechanism accessible exclusively to Administrators.
- **FR-052**: System MUST enforce that backup dump files are protected against unauthorized direct URL downloads.

### Key Entities *(include if feature involves data)*

- **UserAccount**: Represents an authenticated identity in the system. Attributes: `account_id`, `username`, `email`, `password_hash`, `role_id`, `status` (Active/Inactive), `last_login_at`, `created_at`, `updated_at`.
- **Role**: Defines the authorization tier and permissions. Attributes: `role_id`, `role_name` (`Administrator`, `Registrar`, `Student`), `description`.
- **StudentProfile**: Authoritative biographical and institutional record of a student. Attributes: `student_id` (Unique Institutional Identifier), `account_id` (foreign key to UserAccount, nullable until account provisioned), `first_name`, `middle_name`, `last_name`, `date_of_birth`, `gender`, `civil_status`, `email`, `contact_number`, `home_address`, `emergency_contact_name`, `emergency_contact_number`, `program_id`, `current_status` (Active, Inactive, Graduated, Withdrawn), `created_at`, `updated_at`.
- **AcademicPeriod**: Represents an institutional academic term. Attributes: `period_id`, `academic_year` (e.g., "2026-2027"), `semester` (e.g., "1st Semester", "2nd Semester", "Summer"), `is_active`, `start_date`, `end_date`.
- **ProgramCourse**: Represents an academic degree program or track. Attributes: `program_id`, `program_code` (e.g., "BSCS"), `program_name` (e.g., "Bachelor of Science in Computer Science"), `department`, `status`.
- **EnrollmentRecord**: Connects a student to an academic period with an official enrollment status. Attributes: `enrollment_id`, `student_id`, `period_id`, `enrollment_date`, `enrollment_status` (`Pending`, `Enrolled`, `Dropped`, `Completed`), `remarks`, `created_by` (Staff User ID), `created_at`, `updated_at`.
- **ActivityAuditLog**: Tracks system and data mutations for security and institutional accountability. Attributes: `log_id`, `user_id`, `user_role`, `action_type`, `module_name`, `target_entity_id`, `details`, `ip_address`, `created_at`.
- **SystemBackupRecord**: Metadata tracking database backup files created by administrators. Attributes: `backup_id`, `filename`, `file_size_bytes`, `created_by`, `created_at`.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Authorised users can complete new student registration in under 2 minutes for standard data entry.
- **SC-002**: Search and filter queries across a dataset of 5,000+ student records display results on screen in under 1 second.
- **SC-003**: 100% of state-changing operations (create, update, status change) enforce server-side validation and record an audit entry.
- **SC-004**: 0% unauthorized access rate: zero student users can access another student's profile data or administrative routes under simulated URL/parameter tampering tests.
- **SC-005**: 100% of reports (student lists, enrollment summaries) generate printable views and downloadable CSV exports matching active filter criteria.
- **SC-006**: 100% of user interface components (forms, tables, cards, modals) render responsively across standard desktop (1920x1080, 1366x768), tablet (768x1024), and mobile viewports.
- **SC-007**: Zero emoji icons present in the user interface, verified by UI review against Bootstrap Icon standards.
- **SC-008**: System database backup and recovery operations can be initiated and completed by an Administrator in under 3 minutes for typical demonstration datasets.

## Assumptions

- **Localhost Environment**: The system will be hosted and executed within a local XAMPP environment (Apache, MySQL, local file storage) strictly for development, testing, and institutional demonstration.
- **Program/Curriculum Structure**: Students belong to one primary degree program or track at any given time.
- **Authentication Credentials**: The institutional email or unique Student ID serves as the primary login identifier for students, while administrative and registrar staff use unique usernames or work emails.
- **Direct Database Recovery**: Administrator database backup and recovery operations operate on standard `.sql` script dumps compatible with phpMyAdmin / MySQL CLI.
- **Audit Retention**: Activity logs are permanently stored in the audit table for historical traceability during local demonstration.
