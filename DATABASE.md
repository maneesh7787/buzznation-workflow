# Database Schema Documentation

## Overview
The BuzzNation Project Management Portal uses a normalized MySQL database structure with 8 main tables supporting the complete workflow.

## Entity Relationship Diagram

```
users (1) ----< (N) projects
users (1) ----< (N) project_files
users (1) ----< (N) notifications
users (1) ----< (N) logs

projects (1) ----< (N) project_files
projects (1) ----< (N) project_status_history
projects (1) ----< (N) notifications
projects (1) ----< (N) logs
projects (1) ----< (N) project_email_notifications

email_notification_users (1) ----< (N) project_email_notifications
```

## Table Definitions

### 1. users
Stores all system users with their roles and authentication details.

```sql
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('Admin', 'Sales', 'Design', 'Operation') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_active (is_active)
);
```

**Key Fields:**
- `user_id`: Primary key, auto-increment
- `username`: Unique login identifier
- `password_hash`: Bcrypt hashed password
- `role`: User role (RBAC enforcement)
- `is_active`: Account activation status

### 2. projects
Main table storing all project/task information.

```sql
CREATE TABLE projects (
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(200) NOT NULL,
    event_date DATE NOT NULL,
    venue VARCHAR(200) NOT NULL,
    initial_cost DECIMAL(10,2) DEFAULT 0.00,
    final_cost DECIMAL(10,2) DEFAULT 0.00,
    exhibition_size VARCHAR(100),
    other_instructions TEXT,
    status ENUM(...) DEFAULT 'Submitted by Sales',
    sales_confirmed TINYINT(1) DEFAULT 0,
    created_by INT NOT NULL,
    assigned_to INT DEFAULT NULL,
    timeline_days INT DEFAULT NULL,
    design_comments TEXT,
    ops_remarks TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (assigned_to) REFERENCES users(user_id),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by),
    INDEX idx_assigned_to (assigned_to)
);
```

**Status Values:**
- Submitted by Sales
- Pending Design Review
- Accepted by Design
- Returned by Design
- Design Work In Progress
- Completed & Sent to OPS
- Reviewed by OPS
- Reviewed & Sent to Sales
- Client Update Requested
- Completed
- Cancelled

**Key Fields:**
- `created_by`: User who created the project (Sales)
- `assigned_to`: Designer assigned to the task
- `timeline_days`: Estimated completion timeline
- `design_comments`: Comments from Design team
- `ops_remarks`: Remarks from Operations team

### 3. project_files
Stores all uploaded files linked to projects.

```sql
CREATE TABLE project_files (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    upload_stage ENUM('Sales Initial', 'Design Work', 'Client Update', 'Other'),
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id),
    INDEX idx_project (project_id),
    INDEX idx_stage (upload_stage)
);
```

**Upload Stages:**
- Sales Initial: Files uploaded during project creation
- Design Work: Design deliverables
- Client Update: Files for client updates
- Other: Miscellaneous files

### 4. project_status_history
Tracks complete history of all status changes.

```sql
CREATE TABLE project_status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    changed_by INT NOT NULL,
    old_status VARCHAR(100),
    new_status VARCHAR(100) NOT NULL,
    remarks TEXT,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id),
    INDEX idx_project (project_id),
    INDEX idx_date (changed_at)
);
```

**Purpose:** Complete audit trail of project lifecycle

### 5. notifications
System notifications for users.

```sql
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_date (created_at)
);
```

**Notification Types:**
- New Project
- Task Accepted
- Task Returned
- Design Complete
- Review Complete
- Client Update

### 6. email_notification_users
Email recipients for project notifications.

```sql
CREATE TABLE email_notification_users (
    email_user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
);
```

**Purpose:** Separate email list managed by Admin

### 7. project_email_notifications
Links projects to email recipients.

```sql
CREATE TABLE project_email_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    email_user_id INT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (email_user_id) REFERENCES email_notification_users(email_user_id),
    INDEX idx_project (project_id)
);
```

### 8. logs
Comprehensive system activity logging.

```sql
CREATE TABLE logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    role VARCHAR(20),
    action_type VARCHAR(100) NOT NULL,
    action_description TEXT NOT NULL,
    project_id INT DEFAULT NULL,
    old_status VARCHAR(100),
    new_status VARCHAR(100),
    remarks TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_project (project_id),
    INDEX idx_action (action_type),
    INDEX idx_date (created_at)
);
```

**Logged Actions:**
- Login/Logout
- Create Project
- Status Change
- Accept Task
- Return Task
- Submit Design Work
- Review Project
- Client Update Request
- User Management
- All CRUD operations

## Indexes

### Purpose of Indexes:
- **Primary Keys**: Unique identification and fast lookups
- **Foreign Keys**: Maintain referential integrity
- **Status Index**: Quick filtering by project status
- **Role Index**: Fast user queries by role
- **Date Indexes**: Efficient date-range queries
- **Read Status**: Quick unread notification counts

## Data Integrity

### Cascading Deletes:
- Deleting a project removes all related files, status history, and notifications
- Deleting a user sets their log entries to NULL (preserves log history)

### Constraints:
- Unique usernames and emails
- Valid ENUM values for roles and statuses
- NOT NULL for critical fields
- Default values for optional fields

## Sample Queries

### Get pending tasks for Design team:
```sql
SELECT p.*, u.full_name as created_by_name
FROM projects p
LEFT JOIN users u ON p.created_by = u.user_id
WHERE p.status = 'Pending Design Review'
ORDER BY p.created_at DESC;
```

### Get user activity log:
```sql
SELECT l.*, u.full_name, p.event_name
FROM logs l
LEFT JOIN users u ON l.user_id = u.user_id
LEFT JOIN projects p ON l.project_id = p.project_id
WHERE l.user_id = ?
ORDER BY l.created_at DESC;
```

### Get project complete history:
```sql
SELECT h.*, u.full_name as changed_by_name
FROM project_status_history h
LEFT JOIN users u ON h.changed_by = u.user_id
WHERE h.project_id = ?
ORDER BY h.changed_at DESC;
```

## Performance Considerations

- Indexes on frequently queried columns
- Pagination limits (20 records per page)
- Efficient JOIN operations
- Proper use of prepared statements
- Regular OPTIMIZE TABLE maintenance

## Backup Recommendations

```bash
# Daily backup
mysqldump -u root -p buzznation_pm > backup_$(date +%Y%m%d).sql

# Weekly full backup with compression
mysqldump -u root -p buzznation_pm | gzip > weekly_backup_$(date +%Y%m%d).sql.gz
```

## Migration Notes

For future schema changes:
1. Always backup before migration
2. Test on development environment first
3. Use ALTER TABLE for modifications
4. Update application code accordingly
5. Document all changes
