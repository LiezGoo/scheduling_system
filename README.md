# SorSU Scheduling System

## Project Description

The **SorSU Scheduling System** is a comprehensive web-based application designed to automate and streamline the class scheduling process for Southern Region State University (SorSU). The system utilizes a Genetic Algorithm to generate optimized, conflict-free class schedules while managing faculty loads, curriculum, rooms, and user roles across multiple organizational levels.

This intelligent scheduling system ensures efficient resource allocation, prevents scheduling conflicts, and implements a robust two-level approval workflow (Program Head → Department Head) to maintain academic standards and operational excellence.

---

## Features Implemented

### 🎓 Multi-Role User Management

- **Admin** - Full system control, user role management, curriculum management
- **Department Head** - Final schedule approval, department-wide oversight
- **Program Head** - First-level schedule review and approval
- **Instructor** - View personal schedules and faculty load
- **Student** - View class schedules and course information

### 📅 Intelligent Schedule Generation

- **Conflict Detection & Resolution** - Automatic detection of time, room, and instructor conflicts
- **Multi-Block Subject Support** - Handle subjects with multiple time blocks per week
- **Room Assignment Optimization** - Intelligent room allocation based on capacity and availability
- **Time Slot Management** - Flexible time slot configuration

### 👨‍🏫 Faculty Load Management

- Faculty workload tracking and assignment
- Instructor availability management
- Load balancing across faculty members
- Teaching hours calculation and reporting

### 📚 Curriculum Management

- Program and course management
- Subject creation and configuration
- Year level and semester organization
- Course prerequisites tracking

### ✅ Schedule Approval Workflow

- **Two-Level Approval Process**
    - Level 1: Program Head Review & Approval
    - Level 2: Department Head Final Approval
- Real-time status tracking (Draft → Pending → Approved/Rejected)
- Approval history and audit trail
- Comments and remarks system
- Email notifications at each approval stage

### 🏢 Room Management

- Room database with capacity information
- Building and location tracking
- Room availability scheduling
- Conflict-free room assignment

### 🔐 Security & Authentication

- Role-based access control (RBAC)
- Secure login system for all user types
- Password reset via email
- Session management
- SQL injection prevention

### 📧 Email Notifications

- Password reset emails
- Schedule submission notifications
- Approval/rejection alerts
- Status update notifications

### 📊 Dashboard & Reporting

- Role-specific dashboards
- Schedule overview and visualization
- Faculty load reports
- Approval status tracking

---

## Technologies & Frameworks Used

### Backend

- **PHP 7.4+** - Server-side programming language
- **MySQL/MariaDB** - Relational database management system
- **Composer** - PHP dependency management
- **PHPMailer 7.0** - Email sending functionality

### Frontend

- **HTML5/CSS3** - Modern web markup and styling
- **JavaScript (ES6+)** - Client-side interactivity
- **Bootstrap 5** - Responsive UI framework
- **Bootstrap Icons 1.13.1** - Icon library
- **jQuery** - DOM manipulation and AJAX
- **DataTables** - Advanced table functionality

### Algorithm & Logic

- **Custom Conflict Detection** - Real-time conflict resolution

### Development Tools

- **XAMPP** - Local development environment (Apache, MySQL, PHP)
- **Git** - Version control
- **VS Code** - Recommended IDE

---

## System Requirements

### Server Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache Web Server 2.4+
- Composer (for dependency management)

### Client Requirements

- Modern web browser (Chrome, Firefox, Edge, Safari)
- JavaScript enabled
- Minimum screen resolution: 1366x768

---

## Installation & Setup Instructions

### 1. Prerequisites

Ensure you have XAMPP (or similar stack) installed on your system:

- Download XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org)
- Install XAMPP with Apache, MySQL, and PHP modules

### 2. Clone/Download the Project

```bash
# Clone the repository to your XAMPP htdocs directory
cd c:\xampp\htdocs
git clone <repository-url> sorsu-scheduling-sys

# Or extract the project zip file to:
c:\xampp\htdocs\sorsu-scheduling-sys
```

### 3. Install Dependencies

```bash
# Navigate to the project directory
cd c:\xampp\htdocs\sorsu-scheduling-sys

# Install PHP dependencies via Composer
composer install
```

### 4. Database Setup

#### Option A: Using phpMyAdmin

1. Start XAMPP Control Panel and start **Apache** and **MySQL** services
2. Open browser and go to: `http://localhost/phpmyadmin`
3. Create a new database named: `sorsu_scheduling`
4. Import the database schema:
    - Click on the `sorsu_scheduling` database
    - Go to the **Import** tab
    - Select file: `sql/database_schema.sql` (or appropriate SQL file)
    - Click **Go** to execute

#### Option B: Using MySQL Command Line

```bash
# Start MySQL service
# Open command prompt/terminal
mysql -u root -p

# In MySQL console:
CREATE DATABASE sorsu_scheduling;
USE sorsu_scheduling;
SOURCE c:/xampp/htdocs/sorsu-scheduling-sys/sql/database_schema.sql;
```

### 5. Configure Database Connection

Edit the database configuration file with your credentials:

```php
// File: includes/db.php (or config.php)
$host = 'localhost';
$dbname = 'sorsu_scheduling';
$username = 'root';
$password = ''; // Default XAMPP password is empty
```

### 6. Setup Schedule Approval Workflow (Optional but Recommended)

#### For Windows:

```bash
# Run the setup script
setup_approval_workflow.bat
```

#### For Linux/Mac:

```bash
# Make script executable
chmod +x setup_approval_workflow.sh
# Run the setup script
./setup_approval_workflow.sh
```

Or manually import:

```bash
# In phpMyAdmin or MySQL:
SOURCE sql/add_schedule_approval_workflow.sql;
```

### 7. Configure Email Settings (For Password Reset & Notifications)

Edit the email configuration:

```php
// File: mail.php or includes/email_config.php
$mail->Host = 'smtp.gmail.com';
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
$mail->Port = 587;
```

**Note:** For Gmail, you need to create an App Password:

1. Go to Google Account → Security → 2-Step Verification
2. Create App Password for "Mail"
3. Use the generated password in configuration

### 8. Start the Application

1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** modules
3. Open your web browser
4. Navigate to: `http://localhost/sorsu-scheduling-sys`

### 9. Default Login Credentials

After database setup, use these default credentials (if seeded):

**Admin:**

- URL: `http://localhost/sorsu-scheduling-sys/admin`
- Username: `admin`
- Password: `admin123` (change after first login)

**Department Head:**

- URL: `http://localhost/sorsu-scheduling-sys/departmenthead`

**Program Head:**

- URL: `http://localhost/sorsu-scheduling-sys/programhead`

**Instructor:**

- URL: `http://localhost/sorsu-scheduling-sys/instructor`

**Student:**

- URL: `http://localhost/sorsu-scheduling-sys/student`

---

## How to Run the System

### Daily Usage

1. **Start XAMPP Services**

    ```bash
    # Open XAMPP Control Panel
    # Click "Start" for Apache
    # Click "Start" for MySQL
    ```

2. **Access the Application**
    - Open browser: `http://localhost/sorsu-scheduling-sys`
    - Select your role (Admin, Department Head, Program Head, Instructor, Student)
    - Login with credentials

3. **Stop Services When Done**
    - Click "Stop" for Apache and MySQL in XAMPP Control Panel

### Common Workflows

#### For Admin:

1. Login to Admin dashboard
2. Manage users, programs, rooms, and curriculum
3. Generate schedules using the Genetic Algorithm
4. Monitor faculty loads

#### For Program Head:

1. Review pending schedules submitted by coordinators
2. Approve or reject with remarks
3. Forward approved schedules to Department Head

#### For Department Head:

1. Review schedules approved by Program Heads
2. Final approval or rejection
3. View complete approval history

#### For Instructors:

1. View assigned schedules
2. Check faculty load
3. View room assignments

#### For Students:

1. View class schedules
2. Check course offerings
3. View instructor assignments

---

## Project Structure

```
sorsu-scheduling-sys/
├── Admin/                      # Admin module
│   ├── curriculum_management.php
│   ├── faculty_loading.php
│   ├── schedule_generation.php
│   ├── user_role_management.php
│   └── room.php
├── DepartmentHead/            # Department Head module
├── ProgramHead/               # Program Head module
├── Instructor/                # Instructor module
├── Students/                  # Student module
├── api/                       # RESTful API endpoints
├── assets/                    # CSS, JS, images
├── backend/                   # Backend controllers & logic
│   ├── schedule_genetic_algorithm.php
│   ├── schedule_approval_workflow.php
│   ├── faculty_load_controller.php
│   └── ...
├── includes/                  # Database connection, utilities
├── sql/                       # Database schemas
├── vendor/                    # Composer dependencies
├── phpmailer/                 # Email library
├── composer.json              # PHP dependencies
└── index.php                  # Main entry point
```

---

## Testing

### Run Approval Workflow Test

```bash
# Open in browser:
http://localhost/sorsu-scheduling-sys/test_approval_workflow.php
```

This will validate:

- Database connection
- Table structures
- Backend and frontend files
- User roles
- Approval workflow setup

---

## Troubleshooting

### Common Issues

**1. Database Connection Error**

- Verify MySQL service is running in XAMPP
- Check database credentials in `includes/db.php`
- Ensure database exists: `sorsu_scheduling`

**2. Page Not Found (404)**

- Verify project is in correct directory: `c:\xampp\htdocs\sorsu-scheduling-sys`
- Check Apache is running
- Clear browser cache

**3. Email Not Sending**

- Verify SMTP settings in `mail.php`
- For Gmail: Enable 2-factor authentication and create App Password
- Check firewall isn't blocking port 587/465

**4. Permission Errors**

- Ensure proper file permissions (755 for directories, 644 for files)
- On Windows, run XAMPP as Administrator if needed

**5. Composer Dependencies Missing**

- Run: `composer install` in project root
- Ensure Composer is installed globally

---

## Documentation

For detailed documentation on specific features:

- **Schedule Approval Workflow**: See `SCHEDULE_APPROVAL_WORKFLOW_GUIDE.md`
- **Quick Start Guide**: See `QUICK_START_APPROVAL_WORKFLOW.md`
- **Delivery Summary**: See `DELIVERY_SUMMARY.txt`

---

## Support & Maintenance

### Backup Recommendations

- Regular database backups (daily/weekly)
- Backup `sql/` directory before schema changes
- Version control all code changes

### Security Best Practices

- Change default admin password immediately
- Use strong passwords for all accounts
- Keep PHP and MySQL updated
- Regularly update dependencies: `composer update`
- Enable HTTPS in production

---

## Future Enhancements

- [ ] Mobile responsive design improvements
- [ ] Advanced reporting and analytics
- [ ] Calendar view for schedules
- [ ] Automated conflict resolution suggestions
- [ ] Integration with student information system
- [ ] API for third-party integrations
- [ ] Real-time notifications
- [ ] Export schedules to PDF/Excel

---

## License

[Specify your license here - e.g., MIT, GPL, Proprietary]

---

## Contributors

- Development Team: [Your Team/Institution Name]
- Contact: [Your Contact Information]

---

## Version History

- **v1.0** (December 2025) - Initial release with core features
    - Genetic Algorithm schedule generation
    - Two-level approval workflow
    - Faculty load management
    - Multi-role authentication
    - Email notifications
