# Quick Setup Guide

## Step-by-Step Installation

### 1. Prerequisites Check
```bash
# Check PHP version (needs 7.4+)
php -v

# Check MySQL version (needs 5.7+)
mysql --version
```

### 2. Database Setup
```bash
# Login to MySQL
mysql -u root -p

# Create database and import schema
mysql -u root -p < database/schema.sql
```

Or manually:
```sql
CREATE DATABASE buzznation_pm;
USE buzznation_pm;
SOURCE database/schema.sql;
```

### 3. Configure Database Connection
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Your MySQL username
define('DB_PASS', '');               // Your MySQL password
define('DB_NAME', 'buzznation_pm');
```

### 4. Set File Permissions
```bash
# For Linux/Mac
chmod -R 755 uploads/

# For Windows (using icacls)
icacls uploads /grant Users:F /T
```

### 5. Apache Configuration (if needed)

**Enable mod_rewrite:**
```bash
# Ubuntu/Debian
sudo a2enmod rewrite
sudo service apache2 restart

# CentOS/RHEL
# Usually enabled by default
```

**Virtual Host (optional):**
```apache
<VirtualHost *:80>
    ServerName buzznation-pm.local
    DocumentRoot /var/www/html/buzznation-workflow
    
    <Directory /var/www/html/buzznation-workflow>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/buzznation-error.log
    CustomLog ${APACHE_LOG_DIR}/buzznation-access.log combined
</VirtualHost>
```

### 6. Access the Application

**Using localhost:**
```
http://localhost/buzznation-workflow/
```

**Using virtual host:**
```
http://buzznation-pm.local/
```

### 7. First Login

Use admin account:
- Username: `admin`
- Password: `admin123`

**IMPORTANT:** Change this password immediately!

## Verify Installation

### Test Checklist:
- [ ] Can access login page
- [ ] Can login as admin
- [ ] Dashboard loads correctly
- [ ] Can create a new user
- [ ] Can upload files
- [ ] Notifications appear
- [ ] Logs are being recorded

## Workflow Test

### Complete Workflow Test:
1. **Login as Sales** (sales1 / password123)
   - Create a new project
   - Upload files
   - Submit project

2. **Login as Design** (design1 / password123)
   - Check pending tasks
   - Accept a task (set timeline)
   - Upload design work
   - Submit to operations

3. **Login as Operation** (ops1 / password123)
   - Review submitted design
   - Add final cost
   - Submit review

4. **Login as Sales again**
   - Check for reviewed project
   - Request client update (optional)

5. **Login as Admin** (admin / admin123)
   - View all projects
   - Check system logs
   - Review user activity

## Common Issues & Solutions

### Issue: Database Connection Failed
**Solution:**
- Verify MySQL is running: `sudo service mysql status`
- Check credentials in `config/database.php`
- Ensure database `buzznation_pm` exists

### Issue: Cannot Upload Files
**Solution:**
```bash
# Check PHP settings
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Increase limits in php.ini if needed
upload_max_filesize = 10M
post_max_size = 10M
```

### Issue: Permission Denied
**Solution:**
```bash
# Set proper ownership
sudo chown -R www-data:www-data uploads/

# Or for your user
sudo chown -R $USER:$USER uploads/
chmod -R 755 uploads/
```

### Issue: .htaccess Not Working
**Solution:**
```bash
# Enable AllowOverride in Apache config
sudo nano /etc/apache2/apache2.conf

# Find <Directory /var/www/> and change:
AllowOverride All

# Restart Apache
sudo service apache2 restart
```

### Issue: Session Not Persisting
**Solution:**
```bash
# Check session directory permissions
ls -la /var/lib/php/sessions/
sudo chmod 1733 /var/lib/php/sessions/
```

## Production Deployment

### Security Hardening:
1. Change all default passwords
2. Use strong database password
3. Enable HTTPS/SSL
4. Disable PHP error display
5. Set up firewall rules
6. Regular backups
7. Update PHP and MySQL regularly

### Recommended php.ini Settings:
```ini
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
session.cookie_httponly = 1
session.cookie_secure = 1
```

### Database Backup:
```bash
# Daily backup script
mysqldump -u root -p buzznation_pm > backup_$(date +%Y%m%d).sql

# Automated backup (crontab)
0 2 * * * mysqldump -u root -pYOURPASSWORD buzznation_pm > /backups/buzznation_$(date +\%Y\%m\%d).sql
```

## Support Contact

For technical support:
- Email: admin@buzznation.com
- GitHub Issues: https://github.com/maneesh7787/buzznation-workflow/issues

## Version Information

- Version: 1.0.0
- PHP: 7.4+
- MySQL: 5.7+
- Bootstrap: 4.6.2
- jQuery: 3.6.0
