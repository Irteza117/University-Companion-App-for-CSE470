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
    department_id INT,
    semester VARCHAR(20),
    academic_year VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
    max_marks INT DEFAULT 100,
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
    submission_url VARCHAR(500) NOT NULL,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    marks_obtained INT,
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
    event_date DATE NOT NULL,
    event_time TIME,
    location VARCHAR(255),
    organizer_id INT NOT NULL,
    target_audience ENUM('all', 'students', 'faculty') DEFAULT 'all',
    max_participants INT,
    registration_required BOOLEAN DEFAULT FALSE,
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
INSERT INTO courses (course_code, course_name, description, credit_hours, department_id, semester, academic_year) VALUES
('CS101', 'Introduction to Programming', 'Basic programming concepts using Python', 3, 1, 'Fall', '2024'),
('CS201', 'Data Structures and Algorithms', 'Fundamental data structures and algorithms', 4, 1, 'Spring', '2024'),
('EE101', 'Circuit Analysis', 'Basic electrical circuit analysis', 3, 2, 'Fall', '2024'),
('MATH201', 'Calculus II', 'Integral calculus and applications', 4, 3, 'Spring', '2024');

-- Insert sample notices
INSERT INTO notices (title, content, author_id, target_audience, is_urgent) VALUES
('Welcome to New Semester', 'Welcome all students and faculty to the new academic semester. Classes begin on Monday.', 1, 'all', FALSE),
('Library Hours Extended', 'The university library will be open 24/7 during exam weeks.', 1, 'students', FALSE),
('Faculty Meeting', 'All faculty members are required to attend the monthly meeting on Friday at 2 PM.', 1, 'faculty', TRUE);

-- Insert sample events
INSERT INTO events (title, description, event_date, event_time, location, organizer_id, target_audience) VALUES
('Orientation Day', 'Welcome orientation for new students', '2024-09-15', '09:00:00', 'Main Auditorium', 1, 'students'),
('Tech Symposium', 'Annual technology symposium with industry experts', '2024-10-20', '10:00:00', 'Conference Hall', 2, 'all'),
('Career Fair', 'Meet with potential employers and explore career opportunities', '2024-11-10', '10:00:00', 'Sports Complex', 1, 'students');