# Interface Contract: Term Enrollment Management

**Module**: Enrollment (`modules/enrollment`)  
**Feature**: Student Information System (SIS)  
**Governing Document**: `.specify/memory/constitution.md`

## 1. Create Enrollment Contract

- **URI**: `/modules/enrollment/create.php` (POST)
- **Role Permission**: `Administrator`, `Registrar`
- **Security**: CSRF validation, Server-side verification.

### Parameters

| Parameter | Type | Required | Constraints |
| :--- | :--- | :--- | :--- |
| `csrf_token` | string | Yes | Valid session CSRF token |
| `student_id` | int | Yes | Must exist in `students` with `current_status = 'Active'` |
| `semester_id`| int | Yes | Must exist in `semesters` with `is_active = 1` |
| `program_id` | int | Yes | Must exist in `programs` |
| `year_level` | enum | Yes | `1st Year`, `2nd Year`, `3rd Year`, `4th Year`, `5th Year` |
| `enrollment_status` | enum | Yes | `Pending`, `Enrolled` |
| `remarks` | string | No | Optional string notes |

### Business Rules & Constraints
1. **Duplicate Check**: Query `SELECT enrollment_id FROM enrollments WHERE student_id = ? AND semester_id = ?`. If record exists, reject with: *"Student is already registered/enrolled for this semester."*
2. **Active Student Check**: If `students.current_status != 'Active'`, reject with: *"Cannot enroll inactive, graduated, or withdrawn student."*
3. **Execution**:
   - Insert into `enrollments` (`student_id`, `semester_id`, `program_id`, `year_level`, `enrollment_date = CURDATE()`, `enrollment_status`, `remarks`, `created_by = $_SESSION['user_id']`).
   - Log activity in `system_logs`: `action_type = 'CREATE'`, `module = 'ENROLLMENT'`, `target_id = enrollment_id`.
   - Set flash message: *"Student successfully enrolled for the selected term."*
   - Redirect to `/modules/enrollment/view.php?id=[enrollment_id]`.

---

## 2. Update Enrollment Status Contract

- **URI**: `/modules/enrollment/status.php` (POST)
- **Role Permission**: `Administrator`, `Registrar`
- **Parameters**: `csrf_token`, `enrollment_id`, `enrollment_status` (`Pending`, `Enrolled`, `Dropped`, `Completed`), `remarks`
- **Action**: Update `enrollment_status`, update `updated_at`, record in `system_logs`.
