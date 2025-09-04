-- Additional Dummy Data for University Companion WebApp
-- Run this after importing the main database_schema.sql

USE university_companion;

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

-- Additional Courses
INSERT INTO courses (course_code, course_name, description, credit_hours, department_id, semester, academic_year) VALUES
('CS301', 'Database Systems', 'Introduction to database design and management', 3, 1, 'Fall', '2024'),
('CS401', 'Software Engineering', 'Software development methodologies and practices', 4, 1, 'Spring', '2024'),
('EE201', 'Digital Logic Design', 'Fundamentals of digital systems and logic circuits', 3, 2, 'Fall', '2024'),
('MATH301', 'Linear Algebra', 'Vector spaces, matrices, and linear transformations', 3, 3, 'Fall', '2024'),
('PHY201', 'Quantum Mechanics', 'Introduction to quantum physics principles', 4, 4, 'Fall', '2024');

-- Course Enrollments
INSERT INTO course_enrollments (student_id, course_id, enrollment_date, status) VALUES
(8, 1, '2024-08-15', 'enrolled'),  -- John in CS101
(8, 5, '2024-08-15', 'enrolled'),  -- John in CS301
(9, 4, '2024-08-15', 'enrolled'),  -- Mary in MATH201
(9, 8, '2024-08-15', 'enrolled'),  -- Mary in MATH301
(10, 9, '2024-08-15', 'enrolled'), -- Robert in PHY201
(11, 1, '2024-08-15', 'enrolled'); -- Jessica in CS101

-- Additional Notices
INSERT INTO notices (title, content, author_id, target_audience, is_urgent, expires_at) VALUES
('Midterm Exam Schedule', 'Midterm examinations will be held from October 15-20, 2024. Please check your course syllabi for specific dates and times.', 1, 'students', TRUE, '2024-10-20 23:59:59'),
('Campus Wi-Fi Maintenance', 'Campus Wi-Fi will be temporarily unavailable on September 15, 2024, from 2:00 AM to 6:00 AM for scheduled maintenance.', 1, 'all', FALSE, '2024-09-16 00:00:00'),
('Student Club Registration Open', 'Registration for student clubs and organizations is now open. Visit the Student Activities Office for more information.', 1, 'students', FALSE, '2024-10-01 23:59:59'),
('Graduation Application Deadline', 'Students planning to graduate in December 2024 must submit their graduation applications by October 1, 2024.', 1, 'students', TRUE, '2024-10-01 23:59:59');

-- Class Schedule
INSERT INTO class_schedule (course_id, faculty_id, day_of_week, start_time, end_time, room_number, semester, academic_year, is_active) VALUES
(1, 2, 'Monday', '09:00:00', '10:30:00', 'CS-101', 'Fall', '2024', TRUE),
(1, 2, 'Wednesday', '09:00:00', '10:30:00', 'CS-101', 'Fall', '2024', TRUE),
(1, 2, 'Friday', '09:00:00', '10:30:00', 'CS-101', 'Fall', '2024', TRUE),
(5, 2, 'Tuesday', '11:00:00', '12:30:00', 'CS-201', 'Fall', '2024', TRUE),
(5, 2, 'Thursday', '11:00:00', '12:30:00', 'CS-201', 'Fall', '2024', TRUE),
(3, 3, 'Monday', '14:00:00', '15:30:00', 'EE-101', 'Fall', '2024', TRUE),
(3, 3, 'Wednesday', '14:00:00', '15:30:00', 'EE-101', 'Fall', '2024', TRUE);

-- Course Materials
INSERT INTO course_materials (course_id, title, description, file_name, file_path, file_size, file_type, uploaded_by) VALUES
(1, 'Python Basics Tutorial', 'Introduction to Python programming fundamentals', 'python_basics.pdf', '/uploads/materials/python_basics.pdf', 2048576, 'application/pdf', 2),
(1, 'Programming Exercises', 'Practice problems for beginners', 'programming_exercises.pdf', '/uploads/materials/programming_exercises.pdf', 1536000, 'application/pdf', 2),
(5, 'Database Design Principles', 'Comprehensive guide to database normalization', 'db_design.pdf', '/uploads/materials/db_design.pdf', 3072000, 'application/pdf', 2),
(3, 'Circuit Analysis Fundamentals', 'Basic electrical circuit analysis techniques', 'circuit_analysis.pdf', '/uploads/materials/circuit_analysis.pdf', 4096000, 'application/pdf', 3);

-- Assignments
INSERT INTO assignments (course_id, title, description, due_date, max_marks, created_by) VALUES
(1, 'Python Variables and Data Types', 'Write a Python program demonstrating different data types and variable operations.', '2024-09-20 23:59:59', 100, 2),
(1, 'Control Structures Assignment', 'Implement loops and conditional statements to solve programming problems.', '2024-10-05 23:59:59', 100, 2),
(5, 'Database Schema Design', 'Design a normalized database schema for a library management system.', '2024-09-25 23:59:59', 150, 2),
(3, 'Circuit Analysis Problems', 'Solve AC and DC circuit analysis problems using Kirchhoff laws.', '2024-09-22 23:59:59', 120, 3);

-- Assignment Submissions
INSERT INTO assignment_submissions (assignment_id, student_id, submission_url, submission_date, status) VALUES
(1, 8, 'https://drive.google.com/file/d/abc123/view', '2024-09-18 14:30:00', 'submitted'),
(1, 11, 'https://github.com/jessica/python-assignment1', '2024-09-19 09:15:00', 'submitted'),
(2, 8, 'https://drive.google.com/file/d/def456/view', '2024-10-04 20:30:00', 'submitted'),
(3, 8, 'https://drive.google.com/file/d/ghi789/view', '2024-09-24 11:20:00', 'submitted');

-- Course Feedback
INSERT INTO course_feedback (course_id, student_id, rating, comments, semester, academic_year) VALUES
(1, 8, 5, 'Excellent introduction to programming. Prof. Doe explains concepts very clearly.', 'Fall', '2024'),
(1, 11, 4, 'Good course overall. More practical examples would be helpful.', 'Fall', '2024'),
(3, 5, 4, 'Challenging but rewarding course. Lab sessions are very informative.', 'Fall', '2024'),
(4, 9, 5, 'Prof. Johnson is an amazing teacher. Makes difficult concepts easy to understand.', 'Spring', '2024');

-- Events
INSERT INTO events (title, description, event_date, event_time, location, organizer_id, target_audience, max_participants, registration_required) VALUES
('Computer Science Career Fair', 'Meet with top tech companies and explore internship and job opportunities.', '2024-10-15', '10:00:00', 'Student Union Ballroom', 2, 'students', 200, TRUE),
('Mathematics Symposium', 'Annual symposium featuring research presentations from faculty and graduate students.', '2024-11-05', '09:00:00', 'Mathematics Building Auditorium', 4, 'all', 150, FALSE),
('Campus Sustainability Fair', 'Learn about environmental initiatives and sustainability practices on campus.', '2024-09-25', '11:00:00', 'Campus Green', 1, 'all', 300, FALSE),
('Study Abroad Information Session', 'Information about international study opportunities and exchange programs.', '2024-10-02', '16:00:00', 'International Center', 1, 'students', 100, FALSE);

-- Event Registrations
INSERT INTO event_registrations (event_id, user_id, registration_date, status) VALUES
(1, 8, '2024-09-10 09:00:00', 'registered'),
(1, 11, '2024-09-10 10:30:00', 'registered'),
(1, 5, '2024-09-11 14:20:00', 'registered'),
(3, 5, '2024-09-18 10:00:00', 'registered'),
(3, 9, '2024-09-18 11:30:00', 'registered'),
(4, 8, '2024-09-19 09:45:00', 'registered');