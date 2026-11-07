# Interface Contract: Authentication & Session Management

**Module**: Authentication (`process/` & `includes/auth.php`)  
**Feature**: Student Information System (SIS)  
**Governing Document**: `.specify/memory/constitution.md` (Constitution v2.0.0)

## 1. Login Request Contract

- **URI**: `/process/login-process.php` (POST)
- **Form Action Source**: `/login.php` or `/index.php`
- **Content-Type**: `application/x-www-form-urlencoded`
- **Security**: Server-side CSRF validation, prepared statements, session regeneration.

### Request Parameters

| Parameter | Type | Required | Constraints | Description |
| :--- | :--- | :--- | :--- | :--- |
| `csrf_token` | string | Yes | 64 hex characters | Must match `$_SESSION['csrf_token']` |
| `username` | string | Yes | Trimmed, 3-50 chars | Username or Student Number |
| `password` | string | Yes | Plaintext string | User's account password |

### Procedural Processing Flow
1. Verify `verify_csrf_token($_POST['csrf_token'])`. If invalid, redirect to `login.php?error=invalid_token`.
2. Prepare procedural query using `mysqli_prepare()`:
   ```php
   $sql = "SELECT u.user_id, u.username, u.email, u.password_hash, u.role_id, r.role_name, u.status, u.must_change_password 
           FROM users u 
           JOIN roles r ON u.role_id = r.role_id 
           WHERE u.username = ? OR u.email = ? LIMIT 1";
   $stmt = mysqli_prepare($conn, $sql);
   mysqli_stmt_bind_param($stmt, "ss", $username, $username);
   mysqli_stmt_execute($stmt);
   $res = mysqli_stmt_get_result($stmt);
   $user = mysqli_fetch_assoc($res);
   mysqli_stmt_close($stmt);
   ```
3. If user not found, set flash error: *"Invalid credentials. Please verify your username and password."* and redirect to `login.php`.
4. If `status != 'Active'`, set flash error: *"Your account has been deactivated. Please contact an administrator."* and redirect to `login.php`.
5. Verify `password_verify($password, $user['password_hash'])`. If false, log failed attempt and return generic error.
6. Regenerate session ID: `session_regenerate_id(true)`.
7. Populate `$_SESSION`:
   ```php
   $_SESSION['user_id'] = (int)$user['user_id'];
   $_SESSION['username'] = $user['username'];
   $_SESSION['email'] = $user['email'];
   $_SESSION['role_id'] = (int)$user['role_id'];
   $_SESSION['role_name'] = $user['role_name'];
   $_SESSION['must_change_password'] = (bool)$user['must_change_password'];
   $_SESSION['last_activity'] = time();

   if ($user['role_name'] === 'Student') {
       // Query student_id & student_number
       $stmt_s = mysqli_prepare($conn, "SELECT student_id, student_number FROM students WHERE user_id = ?");
       mysqli_stmt_bind_param($stmt_s, "i", $user['user_id']);
       mysqli_stmt_execute($stmt_s);
       $res_s = mysqli_stmt_get_result($stmt_s);
       $student = mysqli_fetch_assoc($res_s);
       mysqli_stmt_close($stmt_s);

       if ($student) {
           $_SESSION['student_id'] = (int)$student['student_id'];
           $_SESSION['student_number'] = $student['student_number'];
       }
   }
   ```
8. Update `users.last_login_at = NOW()`.
9. Log `LOGIN` event using `log_activity($conn, 'LOGIN', 'AUTH', $user['user_id'], 'User logged in successfully')`.
10. Redirect based on role:
    - `Administrator` -> `/admin/dashboard.php`
    - `Registrar` -> `/registrar/dashboard.php`
    - `Student` -> If `must_change_password`, redirect to `/student/change-password.php`; else `/student/dashboard.php`

---

## 2. Logout Request Contract

- **URI**: `/process/logout-process.php` (POST or GET)
- **Action**:
  1. Log `LOGOUT` event in `system_logs` for current `user_id`.
  2. Clear `$_SESSION = []`.
  3. Invalidate session cookie: `setcookie(session_name(), '', time() - 3600, '/')`.
  4. Destroy session: `session_destroy()`.
  5. Redirect to `/login.php?logout=1`.

---

## 3. Session Guard Contract

Every protected procedural page includes `includes/auth.php` and checks role assertions:

```php
require_once __DIR__ . '/../includes/auth.php';
require_login(); // Redirects to login.php if not authenticated
require_role('Administrator'); // Renders 403 Forbidden if unauthorized
```
