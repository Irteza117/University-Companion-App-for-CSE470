-- University Companion WebApp Database Schema
-- Create this database in your XAMPP MySQL server

CREATE DATABASE IF NOT EXISTS university_companion;
USE university_companion;

-- Users table for authentication and role management
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'faculty', 'admin') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Departments table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL,
    description TEXT,
    head_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (head_id) REFERENCES users(id)
);

-- Courses table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(200) NOT NULL,
    description TEXT,
    credit_hours INT DEFAULT 3,
    max_students INT DEFAULT NULL,
    department VARCHAR(100),
    department_id INT,
    faculty_id INT,
    academic_year VARCHAR(10),
    semester VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Course enrollments
CREATE TABLE course_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    grade VARCHAR(5),
    status ENUM('enrolled', 'completed', 'dropped') DEFAULT 'enrolled',
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (course_id) REFERENCES courses(id),
    UNIQUE KEY unique_enrollment (student_id, course_id)
);

-- Course assignments (which faculty teaches which course)
CREATE TABLE course_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    course_id INT NOT NULL,
    semester VARCHAR(20),
    academic_year VARCHAR(10),
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES users(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

-- Notices table
CREATE TABLE notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author_id INT NOT NULL,
    target_audience ENUM('all', 'students', 'faculty') DEFAULT 'all',
    is_urgent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- Course materials table
CREATE TABLE course_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    file_type VARCHAR(50),
    uploaded_by INT NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    download_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Class schedule/routine table
CREATE TABLE class_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    faculty_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room_number VARCHAR(50),
    semester VARCHAR(20),
    academic_year VARCHAR(10),
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (faculty_id) REFERENCES users(id)
);

-- Assignments table
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATETIME NOT NULL,
    max_points INT DEFAULT 100,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Assignment submissions table
CREATE TABLE assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_text TEXT,
    submission_url VARCHAR(500),
    file_name VARCHAR(255),
    file_path VARCHAR(500),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    grade DECIMAL(5,2),
    feedback TEXT,
    graded_by INT,
    graded_at TIMESTAMP NULL,
    status ENUM('submitted', 'graded', 'late') DEFAULT 'submitted',
    FOREIGN KEY (assignment_id) REFERENCES assignments(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (graded_by) REFERENCES users(id),
    UNIQUE KEY unique_submission (assignment_id, student_id)
);

-- Course feedback table
CREATE TABLE course_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comments TEXT,
    semester VARCHAR(20),
    academic_year VARCHAR(10),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_anonymous BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    UNIQUE KEY unique_feedback (course_id, student_id, semester, academic_year)
);

-- Events table
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATETIME NOT NULL,
    location VARCHAR(255),
    organizer_id INT NOT NULL,
    category ENUM('general', 'academic', 'cultural', 'sports', 'social') DEFAULT 'general',
    max_registrations INT DEFAULT 0,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (organizer_id) REFERENCES users(id)
);

-- Event registrations table
CREATE TABLE event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('registered', 'attended', 'cancelled') DEFAULT 'registered',
    FOREIGN KEY (event_id) REFERENCES events(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_registration (event_id, user_id)
);

-- Insert sample data

-- Insert departments
INSERT INTO departments (name, code, description) VALUES
('Computer Science', 'CS', 'Department of Computer Science and Engineering'),
('Electrical Engineering', 'EE', 'Department of Electrical Engineering'),
('Mathematics', 'MATH', 'Department of Mathematics'),
('Physics', 'PHY', 'Department of Physics'),
('Business Administration', 'BBA', 'Department of Business Administration');

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, email, password, role, full_name, department) VALUES
('admin', 'admin@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', 'Administration');

-- Insert sample faculty users (password: faculty123)
INSERT INTO users (username, email, password, role, full_name, department, phone) VALUES
('john.doe', 'john.doe@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', 'Dr. John Doe', 'Computer Science', '+1234567890'),
('jane.smith', 'jane.smith@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', 'Dr. Jane Smith', 'Electrical Engineering', '+1234567891'),
('bob.johnson', 'bob.johnson@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', 'Prof. Bob Johnson', 'Mathematics', '+1234567892');

-- Insert sample student users (password: student123)
INSERT INTO users (username, email, password, role, full_name, department) VALUES
('alice.brown', 'alice.brown@student.university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Alice Brown', 'Computer Science'),
('charlie.wilson', 'charlie.wilson@student.university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Charlie Wilson', 'Electrical Engineering');

-- Insert sample courses
INSERT INTO courses (course_code, course_name, description, credit_hours, department, faculty_id, semester, academic_year, is_active) VALUES
('CS101', 'Introduction to Programming', 'Basic programming concepts using Python', 3, 'Computer Science', 2, 'Fall', '2024', 1),
('CS201', 'Data Structures and Algorithms', 'Fundamental data structures and algorithms', 4, 'Computer Science', 2, 'Spring', '2024', 1),
('EE101', 'Circuit Analysis', 'Basic electrical circuit analysis', 3, 'Electrical Engineering', 3, 'Fall', '2024', 1),
('MATH201', 'Calculus II', 'Integral calculus and applications', 4, 'Mathematics', 4, 'Spring', '2024', 1);

-- Insert sample notices
INSERT INTO notices (title, content, author_id, target_audience, is_urgent) VALUES
('Welcome to New Semester', 'Welcome all students and faculty to the new academic semester. Classes begin on Monday.', 1, 'all', FALSE),
('Library Hours Extended', 'The university library will be open 24/7 during exam weeks.', 1, 'students', FALSE),
('Faculty Meeting', 'All faculty members are required to attend the monthly meeting on Friday at 2 PM.', 1, 'faculty', TRUE);

-- Insert sample events
INSERT INTO events (title, description, event_date, location, organizer_id, category, max_registrations, is_public, is_active) VALUES
('Orientation Day', 'Welcome orientation for new students', '2024-09-15 09:00:00', 'Main Auditorium', 1, 'academic', 200, 1, 1),
('Tech Symposium', 'Annual technology symposium with industry experts', '2024-10-20 10:00:00', 'Conference Hall', 2, 'academic', 150, 1, 1),
('Career Fair', 'Meet with potential employers and explore career opportunities', '2024-11-10 10:00:00', 'Sports Complex', 1, 'general', 300, 1, 1);

-- Additional Faculty Users
INSERT INTO users (username, email, password, role, full_name, department, phone, address) VALUES
('sarah.wilson', 'sarah.wilson@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', 'Dr. Sarah Wilson', 'English Literature', '+1234567893', '789 Faculty Lane'),
('mike.chen', 'mike.chen@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', 'Prof. Mike Chen', 'Chemistry', '+1234567894', '456 Research Drive'),
('lisa.garcia', 'lisa.garcia@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', 'Dr. Lisa Garcia', 'Psychology', '+1234567895', '123 Academic Street');

-- Additional Student Users
INSERT INTO users (username, email, password, role, full_name, department, phone, address) VALUES
('john.student', 'john.student@student.university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'John Smith', 'Computer Science', '+1987654321', '123 Student Dorm'),
('mary.jones', 'mary.jones@student.university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Mary Jones', 'Mathematics', '+1987654322', '456 Campus Housing'),
('robert.davis', 'robert.davis@student.university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Robert Davis', 'Physics', '+1987654323', '789 University Ave'),
('jessica.martinez', 'jessica.martinez@student.university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Jessica Martinez', 'Chemistry', '+1987654324', '321 Science Hall');

-- Additional Course Materials
INSERT INTO course_materials (course_id, title, description, file_name, file_path, file_size, file_type, uploaded_by) VALUES
(1, 'Python Basics Tutorial', 'Introduction to Python programming fundamentals', 'python_basics.pdf', '/uploads/materials/python_basics.pdf', 2048576, 'application/pdf', 2),
(1, 'Programming Exercises', 'Practice problems for beginners', 'programming_exercises.pdf', '/uploads/materials/programming_exercises.pdf', 1536000, 'application/pdf', 2),
(2, 'Database Design Principles', 'Comprehensive guide to database normalization', 'db_design.pdf', '/uploads/materials/db_design.pdf', 3072000, 'application/pdf', 2),
(3, 'Circuit Analysis Fundamentals', 'Basic electrical circuit analysis techniques', 'circuit_analysis.pdf', '/uploads/materials/circuit_analysis.pdf', 4096000, 'application/pdf', 3);

-- Additional Course Assignments
INSERT INTO assignments (course_id, title, description, due_date, max_points, created_by) VALUES
(1, 'Python Variables and Data Types', 'Write a Python program demonstrating different data types and variable operations.', '2024-09-20 23:59:59', 100, 2),
(1, 'Control Structures Assignment', 'Implement loops and conditional statements to solve programming problems.', '2024-10-05 23:59:59', 100, 2),
(2, 'Database Schema Design', 'Design a normalized database schema for a library management system.', '2024-09-25 23:59:59', 150, 2),
(3, 'Circuit Analysis Problems', 'Solve AC and DC circuit analysis problems using Kirchhoff laws.', '2024-09-22 23:59:59', 120, 3);

-- Additional Assignment Submissions
INSERT INTO assignment_submissions (assignment_id, student_id, submission_url, submitted_at, status) VALUES
(5, 5, 'https://drive.google.com/file/d/abc123/view', '2024-09-18 14:30:00', 'submitted'),
(5, 6, 'https://github.com/student/python-assignment1', '2024-09-19 09:15:00', 'submitted'),
(6, 5, 'https://drive.google.com/file/d/def456/view', '2024-10-04 20:30:00', 'submitted'),
(7, 5, 'https://drive.google.com/file/d/ghi789/view', '2024-09-24 11:20:00', 'submitted');

-- Additional Course Feedback
INSERT INTO course_feedback (course_id, student_id, rating, comments, semester, academic_year) VALUES
(1, 5, 5, 'Excellent introduction to programming. Prof. Doe explains concepts very clearly.', 'Fall', '2024'),
(1, 6, 4, 'Good course overall. More practical examples would be helpful.', 'Fall', '2024'),
(3, 5, 4, 'Challenging but rewarding course. Lab sessions are very informative.', 'Fall', '2024'),
(4, 6, 5, 'Prof. Johnson is an amazing teacher. Makes difficult concepts easy to understand.', 'Spring', '2024');