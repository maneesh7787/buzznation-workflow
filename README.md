# BuzzNation Project Management Portal

A secure, scalable internal Project Management Web Portal for managing projects across Sales, Design, and Operation departments with role-based access control.

## Features

### User Roles & Access Control
- **Admin**: Full system access, user management, email list management, system logs
- **Sales**: Create projects, track status, request client updates
- **Design**: Review pending tasks, accept/return tasks, upload design work
- **Operation**: Review completed design work, add final costs and remarks

### Key Functionalities

#### Sales Module
- Create new projects with validated forms
- Multiple file uploads (images/PDFs)
- Email notification selector
- Client update requests
- Project tracking dashboard

#### Design Module
- **Important Rule**: Tasks must be ACCEPTED before appearing in ongoing projects
- Pending tasks queue with accept/return options
- Timeline and comments when accepting
- Design work submission with file uploads
- Completed projects tracking

#### Operation Module
- Review design-completed projects
- Add final cost and operation remarks
- Submit reviewed projects back to Sales

#### Admin Module
- User management (Create/Edit/Delete/Activate/Deactivate)
- Password management
- Email notification users management
- Complete system logs with advanced filtering
- View all projects across departments

### Workflow

```
Sales → Design (Review) → Design (Accept/Return) → Design (Submit) → Operation (Review) → Sales
         ↓                                                                                  ↓
    Client Update ←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←
```

### Security Features
- Session-based authentication
- Password hashing using bcrypt
- Role-based access control (RBAC)
- Input sanitization and validation
- Secure file uploads with type/size validation
- Protected directories via .htaccess
- IP address logging
- CSRF protection

### System Features
- Comprehensive logging system
- Real-time notifications
- Email notifications
- Search, sort, filter capabilities
- Pagination (20 records per page)
- Responsive Bootstrap UI
- Status history tracking

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/maneesh7787/buzznation-workflow.git
   cd buzznation-workflow
   ```

2. **Create MySQL Database**
   ```bash
   mysql -u root -p
   ```
   
   Then run the SQL schema:
   ```sql
   source database/schema.sql
   ```
   
   Or manually import:
   ```bash
   mysql -u root -p < database/schema.sql
   ```

3. **Configure Database Connection**
   
   Edit `config/database.php` and update credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'buzznation_pm');
   ```

4. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/projects/
   ```

5. **Configure Apache Virtual Host** (Optional)
   ```apache
   <VirtualHost *:80>
       ServerName buzznation-pm.local
       DocumentRoot /path/to/buzznation-workflow
       
       <Directory /path/to/buzznation-workflow>
           Options -Indexes +FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

6. **Access the Application**
   
   Open your browser and navigate to:
   - http://localhost/buzznation-workflow/
   - Or your configured virtual host

## Default Login Credentials

### Admin Account
- **Username**: admin
- **Password**: admin123

### Test Accounts
- **Sales**: sales1 / password123
- **Design**: design1 / password123
- **Operation**: ops1 / password123

**⚠️ IMPORTANT**: Change these default passwords immediately after first login!

## Database Schema

### Main Tables
- **users**: User accounts with roles
- **projects**: Project/task information
- **project_files**: Uploaded files linked to projects
- **project_status_history**: Complete status change tracking
- **notifications**: User notifications
- **email_notification_users**: Email recipients list
- **logs**: Comprehensive action logging

## Folder Structure

```
buzznation-workflow/
├── config/
│   ├── database.php       # Database configuration
│   └── security.php       # Security & session functions
├── database/
│   └── schema.sql         # Complete database schema
├── includes/
│   ├── functions.php      # Common utility functions
│   ├── header.php         # Header template
│   └── footer.php         # Footer template
├── modules/
│   ├── admin/            # Admin module
│   │   ├── users.php
│   │   ├── email_users.php
│   │   ├── projects.php
│   │   └── logs.php
│   ├── sales/            # Sales module
│   │   ├── create_project.php
│   │   ├── my_projects.php
│   │   ├── view_project.php
│   │   └── client_update.php
│   ├── design/           # Design module
│   │   ├── pending.php
│   │   ├── ongoing.php
│   │   ├── completed.php
│   │   ├── accept_task.php
│   │   ├── return_task.php
│   │   └── submit_work.php
│   └── operation/        # Operation module
│       ├── pending_reviews.php
│       ├── reviewed.php
│       └── submit_review.php
├── uploads/              # File uploads (excluded from git)
│   └── projects/
├── assets/               # Static assets (CSS, JS, images)
├── .htaccess            # Apache configuration
├── index.php            # Entry point
├── login.php            # Login page
├── logout.php           # Logout handler
├── dashboard.php        # Main dashboard
└── notifications.php    # Notifications page
```

## Usage Guide

### For Sales Users
1. Login with Sales credentials
2. Click "New Project" to create a project
3. Fill in all required fields
4. Upload relevant files (optional)
5. Select email notification recipients
6. Confirm and submit
7. Track project status in "My Projects"

### For Design Users
1. Login with Design credentials
2. Check "Pending Tasks" for new projects
3. Review project details
4. Either:
   - **Accept**: Set timeline and add comments
   - **Return**: Provide reason for returning
5. For accepted tasks, upload design work in "Ongoing Projects"
6. Submit completed work to Operations

### For Operation Users
1. Login with Operation credentials
2. Review projects in "Pending Reviews"
3. Check all project details and design files
4. Add final cost and operation remarks
5. Submit review back to Sales

### For Admin Users
1. Full access to all modules
2. Manage users and permissions
3. View system logs with advanced filters
4. Monitor all projects across departments

## Logging System

All actions are logged with:
- User ID and role
- Action type and description
- Project ID (if applicable)
- Old and new status (for status changes)
- IP address
- Timestamp
- Remarks

Access logs via: Admin → System Logs

## Email Notifications

Configure SMTP settings in `includes/functions.php` for production use. Currently uses PHP's `mail()` function.

For production, consider using PHPMailer or similar library.

## Security Considerations

### For Production Deployment:
1. Change all default passwords
2. Use strong database credentials
3. Enable HTTPS/SSL
4. Configure proper email settings
5. Set up regular database backups
6. Review and restrict file upload types
7. Enable PHP error logging (disable display_errors)
8. Implement rate limiting for login attempts
9. Regular security audits

## Future Enhancements

Potential features for future versions:
- PDF report generation
- Advanced analytics dashboard
- File preview functionality
- Real-time chat/comments
- Mobile app integration
- Export data to Excel/CSV
- Calendar integration
- Advanced notification settings

## Troubleshooting

### Common Issues

**Database Connection Failed**
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database exists and schema is imported

**File Upload Not Working**
- Check `uploads/` directory permissions (755 or 777)
- Verify PHP upload_max_filesize and post_max_size settings
- Check available disk space

**Session Issues**
- Ensure PHP session.save_path is writable
- Check session settings in php.ini

**Permission Denied Errors**
- Review file/folder permissions
- Ensure web server has write access to uploads/

## Support

For issues, questions, or contributions:
- Create an issue on GitHub
- Contact: admin@buzznation.com

## License

Internal use only - BuzzNation Project Management Portal

## Credits

Developed for BuzzNation
Version 1.0.0
Built with PHP, MySQL, Bootstrap, jQuery