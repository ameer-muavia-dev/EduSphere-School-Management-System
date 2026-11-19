-- EduSphere School Management System
-- Database Schema + Sample Data
-- University of Layyah | BS Computer Science | Web Technologies

CREATE DATABASE IF NOT EXISTS school_sms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_sms;

-- ========== USERS & ROLES ==========
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(30) NOT NULL UNIQUE
);
INSERT INTO roles (id, name) VALUES (1,'admin'),(2,'teacher'),(3,'student'),(4,'parent'),(5,'accountant');

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    phone VARCHAR(20),
    photo VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- ========== ACADEMIC SETUP ==========
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(30) NOT NULL,
    section VARCHAR(10) NOT NULL,
    UNIQUE KEY uq_class (name, section)
) ENGINE=InnoDB;

CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(60) NOT NULL,
    code VARCHAR(20)
) ENGINE=InnoDB;

CREATE TABLE class_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT DEFAULT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE timetable (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT DEFAULT NULL,
    day_of_week ENUM('Mon','Tue','Wed','Thu','Fri','Sat') NOT NULL,
    period_no INT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========== PEOPLE ==========
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    class_id INT NOT NULL,
    roll_no VARCHAR(20) NOT NULL,
    dob DATE,
    gender ENUM('Male','Female','Other'),
    guardian_name VARCHAR(100),
    guardian_phone VARCHAR(20),
    parent_user_id INT DEFAULT NULL,
    address VARCHAR(255),
    admission_date DATE DEFAULT (CURRENT_DATE),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (parent_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    designation VARCHAR(60) NOT NULL,
    department VARCHAR(60),
    basic_salary DECIMAL(10,2) DEFAULT 0,
    joining_date DATE DEFAULT (CURRENT_DATE),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== ATTENDANCE ==========
CREATE TABLE student_attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    att_date DATE NOT NULL,
    status ENUM('Present','Absent','Late','Leave') NOT NULL,
    marked_by INT,
    UNIQUE KEY uq_att (student_id, att_date),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE staff_attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    att_date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    status ENUM('Present','Absent','Late','Leave') NOT NULL,
    UNIQUE KEY uq_satt (staff_id, att_date),
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE leave_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    reason VARCHAR(255),
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== FINANCE ==========
CREATE TABLE fee_structures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    title VARCHAR(60) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE fee_vouchers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0,
    paid_amount DECIMAL(10,2) DEFAULT 0,
    due_date DATE,
    status ENUM('Unpaid','Partial','Paid') DEFAULT 'Unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE fee_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    voucher_id INT NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    paid_date DATE NOT NULL,
    method VARCHAR(30) DEFAULT 'Cash',
    received_by INT,
    FOREIGN KEY (voucher_id) REFERENCES fee_vouchers(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE payroll (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    basic DECIMAL(10,2) NOT NULL,
    allowances DECIMAL(10,2) DEFAULT 0,
    deductions DECIMAL(10,2) DEFAULT 0,
    advance DECIMAL(10,2) DEFAULT 0,
    net_salary DECIMAL(10,2) NOT NULL,
    status ENUM('Pending','Paid') DEFAULT 'Pending',
    paid_date DATE DEFAULT NULL,
    UNIQUE KEY uq_payroll (staff_id, month, year),
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== EXAMS / RESULTS ==========
CREATE TABLE exams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(80) NOT NULL,
    class_id INT NOT NULL,
    term VARCHAR(40),
    exam_date DATE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE exam_marks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    marks_obtained DECIMAL(5,2) NOT NULL,
    total_marks DECIMAL(5,2) NOT NULL DEFAULT 100,
    UNIQUE KEY uq_mark (exam_id, student_id, subject_id),
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== LMS ==========
CREATE TABLE study_materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE assignment_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    file_path VARCHAR(255),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    marks DECIMAL(5,2) DEFAULT NULL,
    feedback VARCHAR(255),
    UNIQUE KEY uq_sub (assignment_id, student_id),
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== COMMUNICATION ==========
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    audience ENUM('All','Students','Staff','Parents') DEFAULT 'All',
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE parent_contact_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    staff_id INT NOT NULL,
    contact_date DATE NOT NULL,
    mode VARCHAR(30) DEFAULT 'Call',
    notes VARCHAR(255),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== STAFF DAILY WORK ==========
CREATE TABLE staff_tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    assigned_date DATE NOT NULL,
    status ENUM('Pending','In Progress','Completed') DEFAULT 'Pending',
    assigned_by INT,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE daily_work_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    log_date DATE NOT NULL,
    task_summary VARCHAR(255) NOT NULL,
    status ENUM('Pending','Completed') DEFAULT 'Completed',
    remarks VARCHAR(255),
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Password for ALL demo accounts is:  password123
-- (bcrypt hash below corresponds to "password123")
SET @pw = '$2y$10$/7eJO90OwvrQgP0mx1OFiO8qQtKrxqEyk9tGOTovSejW3PVf763r2';

INSERT INTO users (name, email, password, role_id, phone, status) VALUES
('Muhammad Ameer Muavia', 'admin@edusphere.edu', @pw, 1, '0300-1234567', 'active'),
('Sir Imran Ali', 'imran.ali@edusphere.edu', @pw, 2, '0301-1111111', 'active'),
('Miss Sana Tariq', 'sana.tariq@edusphere.edu', @pw, 2, '0301-2222222', 'active'),
('Ali Hamza', 'ali.hamza@edusphere.edu', @pw, 3, '0302-1111111', 'active'),
('Ayesha Noor', 'ayesha.noor@edusphere.edu', @pw, 3, '0302-2222222', 'active'),
('Bilal Ahmad', 'bilal.ahmad@edusphere.edu', @pw, 3, '0302-3333333', 'active'),
('Fatima Zahra', 'fatima.zahra@edusphere.edu', @pw, 3, '0302-4444444', 'active'),
('Mr. Tariq Mehmood', 'tariq.mehmood@edusphere.edu', @pw, 4, '0303-1111111', 'active'),
('Mrs. Nadia Bilal', 'nadia.bilal@edusphere.edu', @pw, 4, '0303-2222222', 'active'),
('Waqas Sheikh', 'waqas.sheikh@edusphere.edu', @pw, 5, '0304-1111111', 'active');

INSERT INTO classes (name, section) VALUES ('8','A'),('9','A'),('9','B');

INSERT INTO subjects (name, code) VALUES
('Mathematics','MATH'),('English','ENG'),('Urdu','URD'),('Science','SCI'),('Islamiat','ISL'),('Computer Science','CS');

INSERT INTO class_subjects (class_id, subject_id, teacher_id) VALUES
(1,1,2),(1,2,3),(1,4,2),
(2,1,2),(2,2,3),(2,6,2);

INSERT INTO staff (user_id, designation, department, basic_salary, joining_date) VALUES
(2, 'Subject Teacher', 'Mathematics & Science', 55000, '2022-06-01'),
(3, 'Subject Teacher', 'English', 50000, '2023-01-15'),
(10,'Accountant', 'Finance', 45000, '2021-09-01');

INSERT INTO students (user_id, class_id, roll_no, dob, gender, guardian_name, guardian_phone, parent_user_id, address) VALUES
(4, 1, '8A-01', '2012-03-10', 'Male', 'Tariq Mehmood', '0303-1111111', 8, 'Layyah City'),
(5, 1, '8A-02', '2012-07-22', 'Female', 'Nadia Bilal', '0303-2222222', 9, 'Layyah City'),
(6, 2, '9A-01', '2011-01-05', 'Male', 'Tariq Mehmood', '0303-1111111', 8, 'Layyah City'),
(7, 2, '9A-02', '2011-11-30', 'Female', 'Nadia Bilal', '0303-2222222', 9, 'Layyah City');

INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, period_no, start_time, end_time) VALUES
(1,1,2,'Mon',1,'08:00:00','08:45:00'),
(1,2,3,'Mon',2,'08:45:00','09:30:00'),
(1,4,2,'Mon',3,'09:30:00','10:15:00'),
(1,1,2,'Tue',1,'08:00:00','08:45:00'),
(1,2,3,'Tue',2,'08:45:00','09:30:00');

INSERT INTO fee_structures (class_id, title, amount) VALUES
(1,'Monthly Tuition Fee', 3500),(2,'Monthly Tuition Fee', 4000),(3,'Monthly Tuition Fee', 4000);

INSERT INTO fee_vouchers (student_id, month, year, amount, discount, paid_amount, due_date, status) VALUES
(1, 9, 2026, 3500, 0, 3500, '2026-09-10', 'Paid'),
(2, 9, 2026, 3500, 200, 1000, '2026-09-10', 'Partial'),
(3, 9, 2026, 4000, 0, 0, '2026-09-10', 'Unpaid'),
(4, 9, 2026, 4000, 0, 4000, '2026-09-10', 'Paid');

INSERT INTO fee_payments (voucher_id, amount_paid, paid_date, method, received_by) VALUES
(1, 3500, '2026-09-05', 'Cash', 10),
(2, 1000, '2026-09-07', 'Cash', 10),
(4, 4000, '2026-09-03', 'Bank', 10);

INSERT INTO payroll (staff_id, month, year, basic, allowances, deductions, advance, net_salary, status, paid_date) VALUES
(1, 9, 2026, 55000, 5000, 0, 0, 60000, 'Paid', '2026-09-01'),
(2, 9, 2026, 50000, 4000, 1500, 0, 52500, 'Pending', NULL);

INSERT INTO exams (title, class_id, term, exam_date) VALUES
('Monthly Test - September', 1, 'Monthly', '2026-09-15'),
('Monthly Test - September', 2, 'Monthly', '2026-09-16');

INSERT INTO exam_marks (exam_id, student_id, subject_id, marks_obtained, total_marks) VALUES
(1,1,1,82,100),(1,1,2,76,100),(1,1,4,88,100),
(1,2,1,91,100),(1,2,2,85,100),(1,2,4,79,100);

INSERT INTO study_materials (class_id, subject_id, teacher_id, title, description) VALUES
(1,1,2,'Chapter 3 - Fractions Notes','Complete notes on fractions with solved examples.'),
(1,4,2,'Science Lab Safety Rules','Guidelines for the science laboratory.');

INSERT INTO assignments (class_id, subject_id, teacher_id, title, description, due_date) VALUES
(1,1,2,'Fractions Practice Worksheet','Solve questions 1 to 20 from the textbook.','2026-09-28');

INSERT INTO events (title, description, event_date, audience, created_by) VALUES
('Annual Sports Day','Sports gala for all students.','2026-10-15','All',1),
('Parent-Teacher Meeting','Discuss monthly progress of students.','2026-09-30','Parents',1),
('Mid-Term Exams Start','Mid-term examinations begin for all classes.','2026-11-01','Students',1);

INSERT INTO student_attendance (student_id, att_date, status, marked_by) VALUES
(1,'2026-09-21','Present',2),(2,'2026-09-21','Present',2),(3,'2026-09-21','Absent',2),(4,'2026-09-21','Present',2),
(1,'2026-09-20','Present',2),(2,'2026-09-20','Late',2),(3,'2026-09-20','Present',2),(4,'2026-09-20','Present',2);

INSERT INTO staff_attendance (staff_id, att_date, check_in, check_out, status) VALUES
(1,'2026-09-21','07:55:00','14:05:00','Present'),
(2,'2026-09-21','08:10:00','14:00:00','Late');

INSERT INTO staff_tasks (staff_id, title, assigned_date, status, assigned_by) VALUES
(1,'Prepare monthly test paper','2026-09-18','Completed',1),
(2,'Submit lesson plan for October','2026-09-20','Pending',1);

INSERT INTO daily_work_logs (staff_id, log_date, task_summary, status, remarks) VALUES
(1,'2026-09-21','Taught Math to 8-A, checked homework, prepared test paper','Completed','Good progress'),
(2,'2026-09-21','Taught English to 8-A, graded assignments','Completed','');
