# Interface Contract: Backup & Recovery

**Module**: Database Backup & Maintenance (`modules/backup`)  
**Feature**: Student Information System (SIS)  
**Governing Document**: `.specify/memory/constitution.md`

## 1. Create Backup Contract

- **URI**: `/modules/backup/create.php` (POST)
- **Role Permission**: `Administrator` strictly.
- **Security**: Valid CSRF token, session verification.

### Action Flow
1. Verify role is `Administrator`.
2. Generate timestamped filename: `sis_backup_YYYYMMDD_His.sql`.
3. Iterate over application tables in dependency order:
   - `roles`, `programs`, `academic_years`, `semesters`, `users`, `students`, `enrollments`, `system_logs`, `backup_logs`.
4. Generate `DROP TABLE IF EXISTS`, `CREATE TABLE`, and batch `INSERT` statements with escaped values.
5. Save file to `storage/backups/` directory (protected with `.htaccess` deny from all).
6. Record entry in `backup_logs` table (`filename`, `file_size_bytes`, `record_count`, `created_by = $_SESSION['user_id']`).
7. Log event in `system_logs`: `action_type = 'BACKUP'`, `module = 'DATABASE'`.
8. Redirect with success message: *"Database backup created successfully: [filename]"*.

---

## 2. Download Backup Contract

- **URI**: `/modules/backup/download.php?id=[backup_id]` (GET)
- **Role Permission**: `Administrator` strictly.
- **Action**: Validates administrator session, verifies file existence under `storage/backups/`, and streams binary file content with `Content-Disposition: attachment; filename="[filename]"`. Direct public web access to the `storage/backups/` folder is blocked by web server configuration.

---

## 3. Restore Database Contract

- **URI**: `/modules/backup/restore.php` (POST)
- **Role Permission**: `Administrator` strictly.
- **Security**: Double confirmation password confirmation, CSRF token, uploaded `.sql` file validation.
- **Action Flow**:
  1. Confirm administrator's current password.
  2. Validate `.sql` file syntax and structure.
  3. Execute SQL statements within transaction blocks.
  4. Log restore event in `system_logs`.
  5. Refresh session and present notification.
