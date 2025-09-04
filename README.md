# University Companion WebApp

A comprehensive web platform for university students and faculty to manage academic activities, communicate, and stay updated with university events and notices.

## Features

### Core Functionality
- **Role-Based Access System**: Separate dashboards for Admin, Faculty, and Students
- **Notice Board**: Faculty/Admin can post notices viewable to everyone
- **Course Materials**: Download study materials uploaded by instructors
- **Weekly Class Routine**: Students can view their class schedules
- **Teacher Directory**: List of all faculty with contact information
- **Assignment Submission**: Students can submit assignment URLs
- **Upcoming Events**: View university events posted by faculty
- **Course Feedback**: Students can rate and comment on courses
- **Material Statistics**: Faculty can see uploaded materials per course
- **Course Enrollment**: Students can view their enrolled courses

### User Roles
- **Admin**: Complete system management, user management, course management
- **Faculty**: Course management, assignment creation, material upload, grading
- **Student**: View courses, submit assignments, access materials, provide feedback

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP 8.x
- **Database**: MySQL 8.x
- **Server**: Apache (XAMPP recommended)

## Installation Instructions

### Prerequisites
1. **XAMPP** (includes Apache, MySQL, PHP)
   - Download from: https://www.apachefriends.org/
   - Install and start Apache and MySQL services

### Setup Steps

1. **Clone/Download the project**
   ```
   Place the project folder in your XAMPP htdocs directory
   Example: C:\xampp\htdocs\university-companion\
   ```

2. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `university_companion`
   - Import the database schema:
     - Click on the `university_companion` database
     - Go to "Import" tab
     - Select the `database_schema.sql` file
     - Click "Go" to import

3. **Configure Database Connection**
   - Open `php/config.php`
   - Update database credentials if needed (default XAMPP settings are already configured):
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USERNAME', 'root');
     define('DB_PASSWORD', ''); // Default XAMPP password is empty
     define('DB_NAME', 'university_companion');
     ```

4. **File Permissions**
   - Ensure the `uploads` folder has write permissions
   - Create additional subfolders in uploads: `materials`, `assignments`

5. **Access the Application**
   - Open your web browser
   - Navigate to: `http://localhost/university-companion/`
   - Use the demo credentials provided on the login page

## Demo Credentials

### Admin Access
- **Username**: admin
- **Password**: admin123
- **Features**: Full system management, user management, reports

### Faculty Access
- **Username**: john.doe
- **Password**: faculty123
- **Features**: Course management, material upload, assignment grading

### Student Access
- **Username**: alice.brown
- **Password**: student123
- **Features**: Course enrollment, assignment submission, material download

## File Structure

```
university-companion/
├── index.html              # Landing page
├── login.php               # User authentication
├── register.php            # User registration
├── logout.php              # Session logout
├── database_schema.sql     # Database structure and sample data
├── css/
│   └── style.css          # Custom CSS styles
├── js/
│   └── script.js          # Custom JavaScript functions
├── php/
│   └── config.php         # Database configuration and utilities
├── uploads/               # File upload directory
├── admin/
│   └── dashboard.php      # Admin dashboard
├── faculty/
│   └── dashboard.php      # Faculty dashboard
├── student/
│   └── dashboard.php      # Student dashboard
└── README.md              # This file
```

## Database Schema

The application uses the following main tables:
- `users` - User accounts and roles
- `departments` - University departments
- `courses` - Course information
- `course_enrollments` - Student course enrollments
- `course_assignments` - Faculty course assignments
- `notices` - Notice board posts
- `course_materials` - Uploaded study materials
- `class_schedule` - Weekly class routines
- `assignments` - Assignment details
- `assignment_submissions` - Student submissions
- `course_feedback` - Student course ratings
- `events` - University events

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention using prepared statements
- CSRF token protection
- Role-based access control
- Input validation and sanitization
- Session management

## Development Notes

### Adding New Features
1. Create new PHP files in appropriate role directories
2. Update navigation menus in dashboard files
3. Add new database tables if needed
4. Update the `config.php` file for new utility functions

### Styling
- Bootstrap 5 is used for responsive design
- Custom CSS is in `css/style.css`
- Icons are from Bootstrap Icons

### JavaScript
- Custom functions are in `js/script.js`
- Bootstrap JavaScript components are included via CDN

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure XAMPP MySQL is running
   - Check database credentials in `config.php`
   - Verify database exists and is imported correctly

2. **Page Not Found**
   - Check file paths and ensure proper directory structure
   - Verify Apache is running in XAMPP

3. **Permission Denied**
   - Check file permissions on uploads folder
   - Ensure Apache has write access to required directories

4. **Styling Issues**
   - Clear browser cache
   - Check if CSS and JS files are loading properly
   - Verify Bootstrap CDN links are accessible

## Future Enhancements

- Email notifications for assignments and notices
- Real-time chat system
- Mobile app development
- Advanced reporting and analytics
- Integration with external systems
- File version control for materials
- Attendance tracking
- Grade management system

## Support

For issues and questions:
1. Check the troubleshooting section
2. Verify XAMPP configuration
3. Review error logs in XAMPP control panel

## License

This project is developed for educational purposes as part of a software engineering course.