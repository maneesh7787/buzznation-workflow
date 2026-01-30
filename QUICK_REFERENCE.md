# Quick Reference Card

## Problem
Redirections not working in subfolder deployment (buzznation-pm).

## Solution
Use `BASE_DIR` constant and `redirectTo()` function for all redirects.

## Quick Setup

1. **Copy files to:** `/var/www/html/buzznation-pm/` or `C:\xampp\htdocs\buzznation-pm\`

2. **Access:** `http://localhost/buzznation-pm/`

3. **Login:** 
   - Username: `admin`
   - Password: `password`

## Configuration

**config.php:**
```php
define('BASE_DIR', '/buzznation-pm');  // Change to your folder name
```

**OR use auto-detection:**
```php
define('BASE_DIR', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));
```

## Usage in Code

**Redirects:**
```php
redirectTo('login.php');        // Redirects to /buzznation-pm/login.php
redirectTo('dashboard.php');    // Redirects to /buzznation-pm/dashboard.php
```

**Links:**
```php
<a href="<?php echo BASE_DIR; ?>/dashboard.php">Dashboard</a>
```

**Forms:**
```php
<form action="<?php echo BASE_DIR; ?>/login.php" method="POST">
```

## File Overview

| File | Purpose |
|------|---------|
| `config.php` | Base configuration (BASE_DIR, redirectTo()) |
| `config.dev.php` | Development settings (optional) |
| `index.php` | Entry point |
| `login.php` | Login page |
| `dashboard.php` | Protected page |
| `logout.php` | Logout handler |
| `.htaccess` | Apache config |
| `test-config.php` | Test tool (dev only) |

## Common Issues

### Issue: Wrong redirects
**Fix:** Check `BASE_DIR` in config.php matches your folder name

### Issue: Session not working
**Fix:** Ensure cookies are enabled and PHP session directory is writable

### Issue: CSRF errors
**Fix:** Enable cookies, don't mix HTTP/HTTPS

## Production Deployment

1. Delete: `config.dev.php`, `test-config.php`
2. Replace demo credentials with database authentication
3. Enable HTTPS
4. Update `.htaccess` RewriteBase
5. Test thoroughly

## Documentation

- **README.md** - Full setup guide
- **DEPLOYMENT.md** - Production deployment
- **REDIRECTION_FIX.md** - Before/after comparison
- **FLOW_DIAGRAM.md** - Visual diagrams
- **SUMMARY.md** - Complete summary

## Security Features

- ✅ CSRF protection
- ✅ Session security
- ✅ Path validation
- ✅ Secure cookies
- ✅ Session regeneration

## Support

Check `test-config.php` for configuration verification (development only).
