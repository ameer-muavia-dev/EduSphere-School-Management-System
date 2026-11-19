# EduSphere — School Management System

![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)

A web-based School Management System built with **PHP and MySQL** for the
**Web Technologies** course, BS Computer Science, **University of Layyah**.

One secure platform where admin, teachers, students, parents and the accountant
manage everything a school does every day.

## Team

| Name | Role |
|------|------|
| Muhammad Ameer Muavia | Group Leader |
| Muzamil Mukhtiar | Developer |
| Haseeb ul Hassan | Developer |
| Zuhaib Mushtaq | Developer |

**Course Coordinator:** Prof. Saifullah

## Features

- **Login and roles:** Admin, Teacher, Student, Parent, Accountant, each with their own dashboard
- **Students and staff:** admission, profiles, class allocation, staff daily work log
- **Attendance:** daily student attendance, staff attendance, automatic percentages
- **Fees:** fee structure, monthly vouchers, partial payments, printable receipts
- **Staff salary:** payroll with attendance-based deductions, printable payslips
- **Tests and results:** marks entry, percentage, grade, class position
- **Reports:** weekly attendance report and monthly overall education report (print / save as PDF)
- **LMS:** study material upload, assignments, submissions and grading
- **Timetable:** class-wise builder with clash detection
- **Communication:** events, announcements, parent-teacher messages, parent contact log

## Tech Stack

PHP 8 (MySQLi, prepared statements) · MySQL / MariaDB · HTML5 · CSS3 · JavaScript · Chart.js

## Security

Hashed passwords (bcrypt) · prepared statements against SQL injection · CSRF tokens on every form ·
output escaping against XSS · role check on every page · parents can only see their own child's data

## How to Run (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL**.
2. Copy this project folder into `C:\xampp\htdocs\` and rename it to `sms`.
3. Open `http://localhost/phpmyadmin` → click **Import** → choose `database/schema.sql` → **Go**.
4. In phpMyAdmin open the **SQL** tab and run:
   ```sql
   CREATE USER IF NOT EXISTS 'sms_user'@'localhost' IDENTIFIED BY 'sms_pass123';
   GRANT ALL PRIVILEGES ON school_sms.* TO 'sms_user'@'localhost';
   FLUSH PRIVILEGES;
   ```
5. Open **http://localhost/sms/** in your browser.

If you prefer to use `root` with no password, edit `config/db.php` (`$DB_USER = 'root'; $DB_PASS = '';`).

> Before deploying on a real server, change the database credentials in `config/db.php`.

## Demo Accounts

Password for all accounts: `password123`

| Role | Email |
|------|-------|
| Admin | admin@edusphere.edu |
| Teacher | imran.ali@edusphere.edu |
| Student | ali.hamza@edusphere.edu |
| Parent | tariq.mehmood@edusphere.edu |
| Accountant | waqas.sheikh@edusphere.edu |

## Project Structure

```
config/        database connection and helper functions
includes/      shared header, sidebar, footer
assets/        CSS
database/      schema.sql (tables and demo data)
admin/ teacher/ student/ parent/ accountant/    pages for each role
uploads/       uploaded study material and assignments
docs/          project proposal and presentation
```

## Future Scope

SMS / WhatsApp notifications · online fee payment gateway · biometric attendance · live classes · mobile app

## License

MIT License. See [LICENSE](LICENSE).
