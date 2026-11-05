-- ==========================================
-- HOSTEL MANAGEMENT SYSTEM
-- DATABASE CREATION
-- ==========================================

CREATE DATABASE IF NOT EXISTS hostel_management;

USE hostel_management;


-- ==========================================
-- 1. USERS TABLE
-- ==========================================

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Student') DEFAULT 'Student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ==========================================
-- 2. STUDENTS TABLE
-- ==========================================

CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    student_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(15),
    course VARCHAR(100),
    year INT,
    parent_name VARCHAR(100),
    parent_phone VARCHAR(15),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE SET NULL
);


-- ==========================================
-- 3. ROOMS TABLE
-- ==========================================

CREATE TABLE rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    room_type ENUM('Single', 'Double') NOT NULL,
    capacity INT NOT NULL,
    room_fee DECIMAL(10,2) NOT NULL,
    status ENUM('Available', 'Full', 'Maintenance')
    DEFAULT 'Available'
);


-- ==========================================
-- 4. ROOM ALLOCATIONS TABLE
-- ==========================================

CREATE TABLE room_allocations (
    allocation_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    room_id INT NOT NULL,
    allocation_date DATE NOT NULL,
    checkout_date DATE DEFAULT NULL,

    FOREIGN KEY (student_id)
    REFERENCES students(student_id),

    FOREIGN KEY (room_id)
    REFERENCES rooms(room_id)
);


-- ==========================================
-- 5. FEES TABLE
-- ==========================================

CREATE TABLE fees (
    fees_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE,
    payment_date DATE DEFAULT NULL,
    status ENUM('Paid', 'Pending', 'Overdue')
    DEFAULT 'Pending',

    FOREIGN KEY (student_id)
    REFERENCES students(student_id)
);


-- ==========================================
-- 6. COMPLAIN TABLE
-- ==========================================

CREATE TABLE complain (
    complain_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Pending', 'In Progress', 'Resolved')
    DEFAULT 'Pending',
    complaint_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id)
    REFERENCES students(student_id)
);


-- ==========================================
-- 7. VISITORS TABLE
-- ==========================================

CREATE TABLE visitors (
    visitor_id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    student_id INT DEFAULT NULL,
    purpose TEXT,
    visit_date DATE NOT NULL,
    check_in TIME,
    check_out TIME,

    FOREIGN KEY (student_id)
    REFERENCES students(student_id)
    ON DELETE SET NULL
);


-- ==========================================
-- 8. EXPENSES TABLE
-- ==========================================

CREATE TABLE expenses (
    expense_id INT AUTO_INCREMENT PRIMARY KEY,
    expense_name VARCHAR(150) NOT NULL,
    category VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ==========================================
-- INSERT SAMPLE DATA
-- ==========================================


-- USERS

INSERT INTO users
(username, email, password, role)
VALUES
('admin', 'admin@hostel.com', 'admin123', 'Admin'),
('student1', 'student1@gmail.com', 'student123', 'Student'),
('student2', 'student2@gmail.com', 'student123', 'Student');


-- STUDENTS

INSERT INTO students
(user_id, student_name, email, phone, course, year,
parent_name, parent_phone, address)
VALUES
(2, 'Aarav Sharma', 'student1@gmail.com',
'9876543210', 'B.Tech CSE', 2,
'Raj Sharma', '9876500001', 'Guwahati, Assam'),

(3, 'Priya Das', 'student2@gmail.com',
'9876543211', 'B.Tech CSE', 3,
'Ramesh Das', '9876500002', 'Jorhat, Assam');


-- ROOMS

INSERT INTO rooms
(room_number, room_type, capacity, room_fee, status)
VALUES
('101', 'Double', 2, 6000, 'Available'),
('102', 'Single', 1, 8000, 'Available'),
('103', 'Double', 2, 6000, 'Available'),
('104', 'Single', 1, 8000, 'Maintenance'),
('105', 'Double', 2, 6000, 'Available');


-- ROOM ALLOCATIONS

INSERT INTO room_allocations
(student_id, room_id, allocation_date, checkout_date)
VALUES
(1, 1, '2026-08-01', NULL),
(2, 3, '2026-08-05', NULL);


-- FEES

INSERT INTO fees
(student_id, amount, due_date, payment_date, status)
VALUES
(1, 6000, '2026-08-10', '2026-08-08', 'Paid'),
(2, 6000, '2026-08-10', NULL, 'Pending');


-- COMPLAINTS

INSERT INTO complain
(student_id, subject, description, status)
VALUES
(1, 'Water Problem', 'Water supply is not regular.', 'Pending'),
(2, 'Fan Problem', 'Room fan is not working.', 'In Progress');


-- VISITORS

INSERT INTO visitors
(visitor_name, phone, student_id, purpose,
visit_date, check_in, check_out)
VALUES
('Rahul Sharma', '9876501234', 1,
'Meeting student', '2026-08-15', '10:00:00', '11:00:00'),
('Anita Das', '9876501235', 2,
'Family visit', '2026-08-16', '12:00:00', '13:00:00');


-- EXPENSES

INSERT INTO expenses
(expense_name, category, amount, expense_date, description)
VALUES
('Electricity Bill', 'Utilities', 5000, '2026-08-01',
'Monthly electricity bill'),
('Plumbing Repair', 'Maintenance', 1500, '2026-08-05',
'Bathroom pipe repair'),
('Cleaning Supplies', 'Cleaning', 2000, '2026-08-10',
'Purchase of cleaning materials');


-- ==========================================
-- SELECT QUERIES
-- ==========================================

-- Display all users
SELECT * FROM users;

-- Display all students
SELECT * FROM students;

-- Display all rooms
SELECT * FROM rooms;

-- Display all room allocations
SELECT * FROM room_allocations;

-- Display all fee records
SELECT * FROM fees;

-- Display all complaints
SELECT * FROM complain;

-- Display all visitors
SELECT * FROM visitors;

-- Display all expenses
SELECT * FROM expenses;


-- ==========================================
-- JOIN QUERY
-- STUDENT WITH ROOM DETAILS
-- ==========================================

SELECT
s.student_name,
r.room_number,
r.room_type,
r.room_fee,
ra.allocation_date
FROM room_allocations ra
JOIN students s
ON ra.student_id = s.student_id
JOIN rooms r
ON ra.room_id = r.room_id;


-- ==========================================
-- UPDATE QUERIES
-- ==========================================

UPDATE rooms
SET status = 'Maintenance'
WHERE room_id = 1;

UPDATE fees
SET status = 'Paid',
payment_date = '2026-08-12'
WHERE fees_id = 2;


-- ==========================================
-- DELETE QUERY
-- ==========================================

DELETE FROM visitors
WHERE visitor_id = 1;


-- ==========================================
-- AGGREGATE QUERIES
-- ==========================================

-- Total number of students
SELECT COUNT(*) AS total_students
FROM students;

-- Total number of rooms
SELECT COUNT(*) AS total_rooms
FROM rooms;

-- Total paid fees
SELECT SUM(amount) AS total_paid
FROM fees
WHERE status = 'Paid';

-- Total hostel expenses
SELECT SUM(amount) AS total_expenses
FROM expenses;

-- Pending fees
SELECT SUM(amount) AS pending_fees
FROM fees
WHERE status = 'Pending';

-- Available rooms
SELECT * FROM rooms
WHERE status = 'Available';


-- ==========================================
-- SHOW ALL TABLES
-- ==========================================

SHOW TABLES;