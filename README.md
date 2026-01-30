# BuzzNation PM - Redirection Fix

This repository contains a PHP application with proper redirection handling for subfolder deployments.

## Problem Fixed

Previously, when the application was deployed in a subfolder (e.g., `localhost/buzznation-pm`), redirections were using absolute paths from the server root, causing issues:

- ❌ Redirecting to `/login.php` instead of `/buzznation-pm/login.php`
- ❌ Redirecting to `/dashboard.php` instead of `/buzznation-pm/dashboard.php`

## Solution

The fix implements a base path configuration system that ensures all redirections include the proper subfolder path:

- ✅ Redirects to `/buzznation-pm/login.php` correctly
- ✅ Redirects to `/buzznation-pm/dashboard.php` correctly
- ✅ All internal links use the proper base path

## Files

- **config.php** - Base path configuration and redirect helper function
- **index.php** - Entry point that redirects based on login status
- **login.php** - Login page with authentication
- **dashboard.php** - Dashboard page (requires authentication)
- **logout.php** - Logout handler

## Setup Instructions

1. Copy all PHP files to your web server's document root subfolder (e.g., `/var/www/html/buzznation-pm/` or `C:\xampp\htdocs\buzznation-pm\`)

2. Update the `BASE_DIR` constant in `config.php` if your folder name is different:
   ```php
   define('BASE_DIR', '/your-folder-name');
   ```
   
   Or use auto-detection (recommended):
   ```php
   define('BASE_DIR', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));
   ```

3. Access the application at `http://localhost/buzznation-pm/`

## Demo Credentials

- Username: `admin`
- Password: `password`

## How It Works

1. **config.php** defines the base directory and provides helper functions:
   - `BASE_DIR` constant: The subfolder path
   - `getBaseUrl()`: Returns the full base URL
   - `redirectTo($page)`: Handles redirections with proper base path

2. **All redirections** use the `redirectTo()` function instead of direct `header()` calls

3. **All internal links** prepend `BASE_DIR` to ensure correct paths

## Testing the Fix

1. Access `http://localhost/buzznation-pm/` - should redirect to login page
2. Try to access `http://localhost/buzznation-pm/dashboard.php` without logging in - should redirect to login page
3. Login with credentials - should redirect to dashboard at `http://localhost/buzznation-pm/dashboard.php`
4. Logout - should redirect back to login page at `http://localhost/buzznation-pm/login.php`

All redirections should maintain the `/buzznation-pm/` prefix in the URL.