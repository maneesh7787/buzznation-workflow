# Redirection Fix - Before and After

## The Problem

When deploying a PHP application in a subfolder (e.g., `localhost/buzznation-pm`), using absolute paths from the server root causes redirection issues.

## Before (Incorrect Implementation)

### ❌ Problem Code Examples

```php
// index.php - WRONG
<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: /login.php');  // ❌ Missing subfolder prefix!
    exit();
}
header('Location: /dashboard.php');  // ❌ Missing subfolder prefix!
exit();
?>
```

```php
// login.php - WRONG
<?php
if ($authenticated) {
    header('Location: /dashboard.php');  // ❌ Wrong path!
    exit();
}
?>

<!-- Form action - WRONG -->
<form method="POST" action="/login.php">  <!-- ❌ Wrong path! -->
```

```php
// dashboard.php - WRONG
<?php
if (!isset($_SESSION['logged_in'])) {
    header('Location: /login.php');  // ❌ Wrong path!
    exit();
}
?>

<!-- Logout link - WRONG -->
<a href="/logout.php">Logout</a>  <!-- ❌ Wrong path! -->
```

### Issues Caused:
- Accessing `localhost/buzznation-pm/` redirects to `localhost/login.php` (404 Not Found)
- After login, redirects to `localhost/dashboard.php` (404 Not Found)
- Links and forms point to wrong URLs
- Application doesn't work when deployed in a subfolder

## After (Correct Implementation)

### ✅ Solution Code Examples

```php
// config.php - NEW
<?php
define('BASE_DIR', '/buzznation-pm');

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . BASE_DIR;
}

function redirectTo($page) {
    header('Location: ' . BASE_DIR . '/' . ltrim($page, '/'));
    exit();
}
?>
```

```php
// index.php - CORRECT
<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in'])) {
    redirectTo('login.php');  // ✅ Correct! Uses BASE_DIR
}
redirectTo('dashboard.php');  // ✅ Correct! Uses BASE_DIR
?>
```

```php
// login.php - CORRECT
<?php
require_once 'config.php';

if ($authenticated) {
    redirectTo('dashboard.php');  // ✅ Correct!
}
?>

<!-- Form action - CORRECT -->
<form method="POST" action="<?php echo BASE_DIR; ?>/login.php">  <!-- ✅ Correct! -->
```

```php
// dashboard.php - CORRECT
<?php
require_once 'config.php';

if (!isset($_SESSION['logged_in'])) {
    redirectTo('login.php');  // ✅ Correct!
}
?>

<!-- Logout link - CORRECT -->
<a href="<?php echo BASE_DIR; ?>/logout.php">Logout</a>  <!-- ✅ Correct! -->
```

## URL Comparison

| Action | Before (Wrong) | After (Correct) |
|--------|---------------|-----------------|
| Access index | Redirects to `/login.php` | Redirects to `/buzznation-pm/login.php` |
| After login | Redirects to `/dashboard.php` | Redirects to `/buzznation-pm/dashboard.php` |
| Logout | Redirects to `/login.php` | Redirects to `/buzznation-pm/login.php` |
| Form submission | Posts to `/login.php` | Posts to `/buzznation-pm/login.php` |
| Links | Point to `/dashboard.php` | Point to `/buzznation-pm/dashboard.php` |

## Key Changes

1. **Created `config.php`** with base path configuration
2. **Added `redirectTo()` function** for centralized redirect handling
3. **Updated all `header()` calls** to use `redirectTo()`
4. **Updated all links and form actions** to include `BASE_DIR`
5. **Added auto-detection option** for dynamic environments

## Benefits

- ✅ Works correctly in subfolders
- ✅ Single point of configuration (`BASE_DIR`)
- ✅ Easy to change folder name (just update config)
- ✅ Option for auto-detection (portable across environments)
- ✅ Consistent redirect handling throughout the application

## Testing

1. Deploy to `localhost/buzznation-pm/`
2. Access `localhost/buzznation-pm/` → Should redirect to `localhost/buzznation-pm/login.php` ✅
3. Login → Should redirect to `localhost/buzznation-pm/dashboard.php` ✅
4. Logout → Should redirect to `localhost/buzznation-pm/login.php` ✅
5. All URLs maintain the `/buzznation-pm/` prefix ✅
