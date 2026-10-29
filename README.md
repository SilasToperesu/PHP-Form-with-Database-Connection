#Author: Silas Toperesu
#Email Address: toperesus@gmail.com

#Student Registration Form (PHP + MySQL)

A student registration form built with PHP and MySQL. It checks every field on the server, shows a red error message under each invalid field, and saves valid records to a MySQL database. Each student gets an auto-generated student number starting at **120**.


##Features
- A clean, responsive form that works on desktop and mobile
- Server-side checks on every field
- A red error message under each invalid field, with the field highlighted
- Fields keep what the user typed after an error (except the password)
- South African cell number check (`082 123 4567`, `0821234567` or `+27 82 123 4567`)
- Passwords stored as a secure hash (`password_hash`), never as plain text
- Each email address can only register once
- Protection against SQL injection (PDO prepared statements)
- CSRF token protection on the form
- A success message that shows the new student number



## Project Structure
student-form/
index.php      # Frontend: the form, CSS and error display
process.php    # Backend: captures, checks and saves the data
db.php         # Database connection settings (PDO)
database.sql   # SQL to create the database and table
               # This file


### How the files connect


index.php  ──(POST)──►  process.php  ──(require)──►  db.php  ──►  MySQL (student_db.students)
    ▲                        │
    └──── errors / success ──┘  (passed back via PHP session)


1. The user fills in the form on **index.php** and clicks **Submit**.
2. **process.php** receives the data and checks each field.
3. If anything is invalid, the errors and the typed values go into the session, and the user returns to **index.php**. Each error appears in red under its field.
4. If everything is valid, **process.php** connects through **db.php** and inserts the record.
5. The user returns to **index.php** with a success message that includes their student number.


## Requirements
- PHP **7.4 or newer** (PHP 8.x recommended)
- MySQL **5.7+** or MariaDB **10.2+**
- The PHP **PDO MySQL** extension (`pdo_mysql`), which is enabled by default in XAMPP/WAMP/MAMP
- A local server such as **XAMPP**, **WAMP**, **MAMP** or **Laragon**


##Installation & Setup

###Copy the files

Put the project folder in your web server's root directory:

| Server | Location |
|--------|----------|
| XAMPP (Windows) | `C:\xampp\htdocs\student-form\` |
| WAMP | `C:\wamp64\www\student-form\` |
| MAMP (Mac) | `/Applications/MAMP/htdocs/student-form/` |

###Start Apache and MySQL

Open the XAMPP/WAMP control panel and start **Apache** and **MySQL**.

###Create the database

**Option A: phpMyAdmin**
1. Go to `http://localhost/phpmyadmin`
2. Click the **SQL** tab
3. Paste the contents of `database.sql` and click **Go**

**Option B: Command line**
```bash
mysql -u root -p < database.sql
```

This creates the database `student_db` and the `students` table.

###Set up the database connection

Open `db.php` and change these values to match your MySQL setup:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'student_db');
define('DB_USER', 'root');   // your MySQL username
define('DB_PASS', '');       // your MySQL password (blank by default in XAMPP)
```

### Open the form

In your browser, go to:

```
http://localhost/student-form/index.php
```

---

## Database Structure

**Database:** `student_db`  **Table:** `students`

| Column | Type | Notes |
|--------|------|-------|
| `StudentNumber` | INT UNSIGNED | Primary key, auto-increment, **starts at 120** |
| `Name` | VARCHAR(50) | Required |
| `Surname` | VARCHAR(50) | Required |
| `Age` | TINYINT UNSIGNED | Must be 16–100 |
| `CellNumber` | CHAR(10) | Stored as `0XXXXXXXXX` |
| `EmailAddress` | VARCHAR(100) | Required, **unique** |
| `Password` | VARCHAR(255) | Hashed with `password_hash()` |
| `Fees` | DECIMAL(10,2) | Must be greater than 0 |
| `CreatedAt` | TIMESTAMP | Set automatically when the record is added |

---

## Validation Rules

| Field | Rule | Example of valid input |
|-------|------|------------------------|
| **Name** | Required, 2–50 letters; spaces, hyphens and apostrophes allowed | `Thabo`, `Anne-Marie` |
| **Surname** | Required, 2–50 letters; spaces, hyphens and apostrophes allowed | `Mokoena`, `O'Neil` |
| **Age** | Required, whole number between 16 and 100 | `21` |
| **SA Cell Number** | Required, 10 digits starting with 06/07/08, or +27 format | `082 123 4567`, `+27821234567` |
| **Email Address** | Required, valid format, not already registered | `thabo@example.com` |
| **Password** | Required, 8–64 characters, at least 1 uppercase, 1 lowercase, 1 number and 1 special character | `Secure@123` |
| **Fees** | Required, positive amount, up to 2 decimal places | `1500`, `1500.50` |

If a field fails a rule, a **red error message** appears directly under it and the field's border turns red.

---

## Testing the Form

| Test | Input | Expected result |
|------|-------|-----------------|
| Empty form | Submit with nothing filled in | A red "required" error under every field |
| Invalid name | `T` or `Thabo123` | Name error shown |
| Underage | Age `12` | "Age must be between 16 and 100." |
| Bad cell number | `0123456789` | SA cell number error shown |
| Bad email | `thabo@` | Email error shown |
| Weak password | `password` | Password strength error shown |
| Invalid fees | `-50` or `abc` | Fees error shown |
| Valid data | All fields correct | Success message with student number (first one is **120**) |
| Duplicate email | Register the same email twice | "This email address is already registered." |

### Useful SQL queries

```sql
-- View all students
SELECT StudentNumber, Name, Surname, Age, CellNumber, EmailAddress, Fees, CreatedAt
FROM students ORDER BY StudentNumber DESC;

-- Clear test data and restart numbering at 120
TRUNCATE TABLE students;
ALTER TABLE students AUTO_INCREMENT = 120;
```

---

## Troubleshooting

| Problem | Likely cause and fix |
|---------|----------------------|
| *"Something went wrong while saving"* | The database connection failed. Check the settings in `db.php` and make sure MySQL is running. |
| *"Unknown database 'student_db'"* | `database.sql` hasn't been run yet. Run it in phpMyAdmin. |
| *"Access denied for user 'root'"* | The username or password in `db.php` is wrong. |
| *"Your session expired. Please try again."* | The CSRF token expired or cookies are blocked. Refresh the page and submit again. |
| Page shows raw PHP code | You opened the file directly (`file:///...`). Open it through `http://localhost/...` instead. |
| *"could not find driver"* | The `pdo_mysql` extension is off. Enable it in `php.ini` and restart Apache. |
| Student numbers don't start at 120 | Run `ALTER TABLE students AUTO_INCREMENT = 120;` (this only works on an empty table or when 120 is above the highest existing number). |

Error details are written to the PHP error log (in XAMPP: `xampp/php/logs/php_error_log`). They are not shown to users.

---

## Security Notes

- **Passwords** are hashed with PHP's `password_hash()` (bcrypt). To check a password at login, use `password_verify($input, $hashFromDb)`.
- **SQL injection** is prevented with PDO prepared statements.
- **XSS** is prevented by escaping all output with `htmlspecialchars()`.
- **CSRF** is prevented with a per-session token on the form.
- **Before going live:** create a dedicated MySQL user with limited rights instead of `root`, set a strong database password, and serve the site over HTTPS.

---

## Possible Improvements

- A login page using `password_verify()`
- An admin page to view, edit and delete students
- JavaScript validation for instant feedback (keep the server-side checks too)
- A "confirm password" field
- Email confirmation after registration
