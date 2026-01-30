# Deployment Guide

## Quick Start (Development)

1. **Copy files to your web server:**
   ```bash
   # Linux/Mac
   cp -r * /var/www/html/buzznation-pm/
   
   # Windows (XAMPP)
   # Copy to C:\xampp\htdocs\buzznation-pm\
   ```

2. **Keep config.dev.php for development** - This shows debug information and demo credentials

3. **Access the application:**
   - URL: `http://localhost/buzznation-pm/`
   - Username: `admin`
   - Password: `password`

## Production Deployment

### Before Deploying to Production:

1. **Delete development files:**
   ```bash
   rm config.dev.php
   rm test-config.php
   rm REDIRECTION_FIX.md
   ```

2. **Update BASE_DIR in config.php:**
   ```php
   // If deploying to a different folder name
   define('BASE_DIR', '/your-production-folder');
   
   // Or use auto-detection (recommended)
   define('BASE_DIR', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));
   ```

3. **Replace hardcoded credentials in login.php:**
   - Remove the demo credentials (admin/password)
   - Implement proper database authentication
   - Use `password_hash()` and `password_verify()`
   
   Example:
   ```php
   // Instead of:
   if ($username === 'admin' && $password === 'password') {
   
   // Use database authentication:
   $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
   $stmt->execute([$username]);
   $user = $stmt->fetch();
   
   if ($user && password_verify($password, $user['password_hash'])) {
       // Login successful
   ```

4. **Update .htaccess:**
   - Ensure `RewriteBase` matches your production folder
   ```apache
   RewriteBase /your-production-folder/
   ```

5. **Enable HTTPS:**
   - The secure cookie flag only works with HTTPS
   - Redirect all HTTP traffic to HTTPS
   - Add to .htaccess:
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

6. **Set proper file permissions:**
   ```bash
   # Linux
   chmod 644 *.php
   chmod 755 .
   chown www-data:www-data -R .
   ```

## Different Deployment Scenarios

### Scenario 1: Root Directory Deployment
If deploying to the root (e.g., `localhost/` instead of `localhost/buzznation-pm/`):

**config.php:**
```php
define('BASE_DIR', '');
```

### Scenario 2: Different Subfolder Name
If your production folder has a different name (e.g., `project` instead of `buzznation-pm`):

**config.php:**
```php
define('BASE_DIR', '/project');
```

**OR use auto-detection (recommended):**
```php
define('BASE_DIR', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));
```

### Scenario 3: Subdomain Deployment
If deploying to a subdomain (e.g., `pm.example.com`):

**config.php:**
```php
define('BASE_DIR', '');  // No subfolder needed
```

## Troubleshooting

### Issue: Redirects still going to wrong URL

**Check:**
1. Is `BASE_DIR` set correctly in config.php?
2. Does .htaccess `RewriteBase` match `BASE_DIR`?
3. Clear your browser cache and cookies
4. Check if mod_rewrite is enabled (Apache)

**Test:**
```bash
# Access test-config.php (development only)
http://localhost/buzznation-pm/test-config.php
```

### Issue: Session not persisting

**Check:**
1. PHP session directory is writable
2. Session cookie path is correct (set automatically in config.php)
3. Cookies are enabled in browser

### Issue: CSRF token errors

**Check:**
1. Cookies are enabled
2. Session is working
3. Not mixing HTTP/HTTPS (use one consistently)

## Security Checklist

Before going live:

- [ ] Delete `config.dev.php`
- [ ] Delete `test-config.php`
- [ ] Replace demo credentials with database authentication
- [ ] Enable HTTPS
- [ ] Set secure cookie flags (automatic if HTTPS is enabled)
- [ ] Update `.htaccess` RewriteBase if needed
- [ ] Test all redirections work correctly
- [ ] Test login/logout flow
- [ ] Verify CSRF protection is working
- [ ] Check error logs for any issues

## Environment-Specific Configuration

### Development
```php
// config.dev.php exists
// Shows debug info, uses demo credentials
```

### Staging
```php
// Delete config.dev.php
// Use database, but with test data
// Keep test-config.php for verification
```

### Production
```php
// Delete config.dev.php and test-config.php
// Use production database
// Enable HTTPS
// Monitor error logs
```

## Support

For issues or questions:
1. Check the README.md for basic setup
2. Review REDIRECTION_FIX.md for details on the fix
3. Use test-config.php (development only) to verify configuration
4. Check web server error logs for PHP errors
