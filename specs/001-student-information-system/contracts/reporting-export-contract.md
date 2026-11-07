# Interface Contract: Search, Filtering & Report Generation

**Module**: Reports & Filtering (`modules/reports`)  
**Feature**: Student Information System (SIS)  
**Governing Document**: `.specify/memory/constitution.md`

## 1. Filtered Search Query Contract

- **URI**: `/modules/reports/students.php` or `/modules/reports/enrollments.php` (GET)
- **Role Permission**: `Administrator`, `Registrar`

### Query Parameters

| Parameter | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `q` | string | `""` | Search keyword (Student Number, First/Last Name) |
| `program_id` | int | `0` (All) | Filter by Degree Program ID |
| `status` | string | `""` (All) | Filter by Student or Enrollment Status |
| `ay_id` | int | `0` (All) | Filter by Academic Year |
| `semester_id`| int | `0` (All) | Filter by Semester |
| `page` | int | `1` | Pagination page number |
| `limit` | int | `25` | Rows per page (Options: 10, 25, 50, 100) |
| `export` | string | `""` | Optional export trigger: `csv` or `print` |

---

## 2. CSV Export Contract

When `export=csv` is requested:
- **Response Headers**:
  ```http
  Content-Type: text/csv; charset=UTF-8
  Content-Disposition: attachment; filename="sis_report_[type]_[YYYYMMDD_His].csv"
  Cache-Control: no-cache, no-store, must-revalidate
  ```
- **CSV Data Columns (Student List Report)**:
  1. Student Number
  2. Last Name
  3. First Name
  4. Middle Name
  5. Program Code
  6. Year Level / Standing
  7. Status
  8. Email
  9. Contact Number
  10. Date Registered

---

## 3. Printable Report Contract

When `export=print` is requested:
- Renders an isolated clean print stylesheet view:
  - Institutional title and header
  - Active filter parameters block
  - High-contrast, clean tabular layout without sidebar or top navigation
  - Timestamp of generation and generated-by staff member footer
  - CSS `@media print` directives to suppress interactive buttons.
