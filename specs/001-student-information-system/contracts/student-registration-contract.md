# Interface Contract: Student Registration & Profile Management

**Module**: Student Information (`modules/students`)  
**Feature**: Student Information System (SIS)  
**Governing Document**: `.specify/memory/constitution.md`

## 1. Student Registration Form Contract

- **URI**: `/modules/students/create.php` (POST)
- **Role Permission**: `Administrator`, `Registrar`
- **Security**: CSRF validation, Server-side field sanitization and regex validation.

### Form Fields & Validation Rules

| Field Name | Type | Required | Constraints | Error Message on Failure |
| :--- | :--- | :--- | :--- | :--- |
| `csrf_token` | string | Yes | Valid session token | "Invalid session request." |
| `student_number` | string | Optional | Regex `^[0-9]{4}-[0-9]{5}$` or auto-generated | "Student number must follow format YYYY-NNNNN." |
| `first_name` | string | Yes | 1-50 chars, letters/spaces | "First name is required (max 50 chars)." |
| `middle_name`| string | No | 0-50 chars | "Middle name exceeds 50 characters." |
| `last_name` | string | Yes | 1-50 chars, letters/spaces | "Last name is required (max 50 chars)." |
| `date_of_birth`| date | Yes | Valid date, Age >= 15 | "Valid date of birth is required." |
| `gender` | enum | Yes | `Male`, `Female`, `Other` | "Please select a valid gender." |
| `civil_status`| enum | Yes | `Single`, `Married`, `Widowed`, `Separated` | "Please select a valid civil status." |
| `email` | string | Yes | Valid email format, Unique in `students` & `users` | "A valid and unique email address is required." |
| `contact_number`| string | Yes | 7-20 digits/symbols | "Contact number is required." |
| `home_address`| text | Yes | Min 5 chars | "Home address is required." |
| `emergency_contact_name` | string | Yes | 1-100 chars | "Emergency contact name is required." |
| `emergency_contact_number`| string | Yes | 7-20 digits/symbols | "Emergency contact number is required." |
| `program_id` | int | Yes | Exists in `programs` WHERE `status='Active'` | "Please select an active degree program." |

### Submission Execution
1. Check `csrf_token`.
2. Check duplicate `student_number` (if manually entered) or generate next sequential:
   `SELECT MAX(student_number) FROM students WHERE student_number LIKE 'YYYY-%'`
   Format: `YYYY-` + zero-padded 5 digits.
3. Check duplicate `email` in both `students` and `users`.
4. Begin Database Transaction:
   - Insert new user into `users`:
     - `username = student_number`
     - `email = student.email`
     - `password_hash = password_hash('Student@YYYY', PASSWORD_DEFAULT)`
     - `role_id = 3` (Student)
     - `must_change_password = 1`
   - Insert into `students` with generated `user_id`.
   - Log activity into `system_logs`: `action_type = 'CREATE'`, `module = 'STUDENTS'`.
   - Commit transaction.
5. Set flash message in `$_SESSION['flash_success'] = "Student [student_number] successfully registered."`
6. Redirect to `/modules/students/view.php?id=[student_id]`.
