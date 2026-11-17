# TEAM Assignment - M5

A PHP 8 and MySQL application for managing the class roster, six subjects, teams, and assignments. All changes are stored on the server in MySQL; the student portal reads the same live records and does not require an account.

## Features

- Admin dashboard with roster, subject, team, assignment, and recent-activity summaries.
- First-run administrator setup, PHP sessions, password hashing, CSRF protection, and sign-in throttling.
- Student add/edit/delete, search, CSV import, and CSV export.
- Team and assignment management, team leaders, subject-specific unassigned lists, and duplicate assignment prevention within each subject.
- Public, read-only subject and team pages with student search.
- Responsive layouts for desktop and mobile.

The database starts with the six required subjects. **No student roster was supplied with the project**, so the database does not invent student records. Import the class’s actual 54 student records from CSV after setup.

## Requirements

- PHP 8.0+ with `PDO`, `pdo_mysql`, and `mbstring` enabled.
- MySQL 5.7+ or a compatible MariaDB server.
- Apache with PHP support (XAMPP locally; Apache or another PHP-capable web server online).
- Internet access for the Bootstrap 5 and Bootstrap Icons CDN assets.

## Run locally with XAMPP

1. Install XAMPP with PHP and MySQL/MariaDB.
2. Copy the project folder into `C:\xampp\htdocs\TEAM-Assignment-M5`.
3. Open the XAMPP Control Panel and start **Apache** and **MySQL**.
4. Visit `http://localhost/phpmyadmin`, create/import the schema:
   - Choose **Import** and select `database/team_assignment_m5.sql`. The script creates and selects `team_assignment_m5`, the tables, indexes, foreign keys, and exactly six subjects.
   - Alternatively, create a database named `team_assignment_m5` with `utf8mb4_unicode_ci`, then import the SQL file.
5. The local database defaults are `127.0.0.1:3306`, database `team_assignment_m5`, username `root`, and an empty password. If your local MySQL credentials differ, set the `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` environment variables before starting Apache.
6. Open `http://localhost/TEAM-Assignment-M5/setup.php`. Create the first admin account. The development defaults are **admin / admin123**; change them on the setup form if desired. The first successful login is required to set a new password.
7. Sign in at `http://localhost/TEAM-Assignment-M5/login.php`. Add actual students individually or import the roster. Open the student portal at `http://localhost/TEAM-Assignment-M5/student/`.

The project also works at the domain root (for example `http://localhost/`) if its files are placed directly in Apache’s document root.

## Load the initial 54 students

From **Admin → Students → Import CSV**, upload a UTF-8 comma-separated CSV containing the real student list. Download the template from the Students page or use the provided `assets/templates/students.csv`. The header order is:

```csv
student_code,first_name,last_name,gender,phone,class_name,photo_url
```

`gender` must be `Female`, `Male`, `Other`, or `Prefer not to say`. Phone and photo URL are optional. Student IDs must be unique. Imports validate the complete file before writing; existing student IDs are skipped, not overwritten. The importer accepts at most 500 records and 2 MB per file, so a 54-row class roster fits in one upload. CSV export is available beside the search field.

## Online hosting and shared access

1. Choose hosting that supports PHP 8+, MySQL/MariaDB, PDO MySQL, HTTPS, and Apache `.htaccess` rules (or configure equivalent web-server access rules).
2. Create a **remote MySQL database and user** in the hosting control panel. Grant that user access only to the project database.
3. Upload the project files to the website’s document root using the host’s file manager or SFTP. Do not upload student records or database credentials to a public repository.
4. Import `database/team_assignment_m5.sql` using the host’s phpMyAdmin. If the host does not allow `CREATE DATABASE`, create the database in its control panel first, then remove the `CREATE DATABASE` and `USE team_assignment_m5` lines before importing.
5. Configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` in the hosting environment. If the host does not offer PHP environment variables, set the fallback values in `config/database.php` over SFTP and keep that file private; never commit production credentials.
6. Enable HTTPS and verify the site’s document root points to this project. Visit the site’s `/setup.php` once, create the administrator, sign in, and change the development password when prompted.
7. Add or import the actual roster and create teams. Share the public site URL (not `localhost`) with students.

Admin and public pages use the same configured remote MySQL database, so changes made by an administrator are visible to other browsers and devices on their next page load. Back up the database through the hosting provider regularly. Restrict database access to the application host when the provider supports it.

## Security and operations

- API mutations require an authenticated admin session and a session-bound CSRF token. SQL access uses PDO prepared statements.
- Student membership changes lock the subject record and run in a transaction, preventing concurrent requests from assigning a student to multiple teams in one subject. A student can still join teams in other subjects.
- Team deletion removes its members and assignment; student deletion removes that student’s memberships and clears their team-leader references.
- Public pages expose student names, IDs, classes, team memberships, leaders, and assignment details so classmates can find teams. Do not store sensitive personal information in public-facing student fields.
- The default development password is not suitable for production. Complete first-login password change before sharing or publishing the site.
- The schema includes no placeholder students. The dashboard totals reflect records actually imported.
