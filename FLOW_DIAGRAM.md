# Application Flow Diagram

## Redirection Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    User Access Flow                              │
└─────────────────────────────────────────────────────────────────┘

User visits: localhost/buzznation-pm/
              │
              ▼
       ┌──────────────┐
       │  index.php   │
       └──────────────┘
              │
              ├─── Check Session
              │
        ┌─────┴──────┐
        │            │
    Not Logged In  Logged In
        │            │
        ▼            ▼
  ┌──────────┐  ┌──────────────┐
  │login.php │  │dashboard.php │
  └──────────┘  └──────────────┘
        │            │
        │            ▼
        │       ┌─────────┐
        │       │logout.php│
        │       └─────────┘
        │            │
        └────────────┘
             │
             ▼
      Back to login.php


┌─────────────────────────────────────────────────────────────────┐
│              URL Structure (BEFORE FIX - BROKEN)                 │
└─────────────────────────────────────────────────────────────────┘

Access:    localhost/buzznation-pm/
Redirect:  localhost/login.php           ❌ 404 Not Found!
Expected:  localhost/buzznation-pm/login.php


┌─────────────────────────────────────────────────────────────────┐
│              URL Structure (AFTER FIX - WORKING)                 │
└─────────────────────────────────────────────────────────────────┘

Access:    localhost/buzznation-pm/
Redirect:  localhost/buzznation-pm/login.php    ✅ Working!


┌─────────────────────────────────────────────────────────────────┐
│                   Redirection Function Flow                      │
└─────────────────────────────────────────────────────────────────┘

Application calls: redirectTo('login.php')
                        │
                        ▼
              ┌─────────────────────┐
              │ config.php          │
              │ redirectTo()        │
              └─────────────────────┘
                        │
                        ├─── Validate path (no ..)
                        ├─── Remove leading slashes
                        ├─── Prepend BASE_DIR
                        │
                        ▼
        header('Location: /buzznation-pm/login.php')
                        │
                        ▼
                   User redirected
                        │
                        ▼
           localhost/buzznation-pm/login.php ✅


┌─────────────────────────────────────────────────────────────────┐
│                    Security Features                             │
└─────────────────────────────────────────────────────────────────┘

1. Session Security
   ├─── httponly flag (prevents XSS)
   ├─── secure flag (HTTPS only)
   ├─── samesite=Lax (CSRF protection)
   └─── Session regeneration after login

2. CSRF Protection
   ├─── Token generation
   ├─── Token validation on forms
   └─── Token regeneration after login

3. Path Validation
   ├─── Prevent path traversal (..)
   └─── Validate redirect targets

4. Input Sanitization
   ├─── Trim username input
   ├─── Validate POST data
   └─── htmlspecialchars() for output


┌─────────────────────────────────────────────────────────────────┐
│                  Configuration Structure                         │
└─────────────────────────────────────────────────────────────────┘

config.php
   │
   ├─── BASE_DIR = '/buzznation-pm'
   │    (or auto-detect from $_SERVER)
   │
   ├─── Session configuration
   │    ├─── cookie_httponly
   │    ├─── cookie_secure
   │    └─── cookie_path = BASE_DIR
   │
   ├─── getBaseUrl()
   │    Returns: http://localhost/buzznation-pm
   │
   └─── redirectTo($page)
        ├─── Validates path
        └─── Returns: BASE_DIR + '/' + $page


┌─────────────────────────────────────────────────────────────────┐
│                      File Dependencies                           │
└─────────────────────────────────────────────────────────────────┘

All PHP files require config.php:

index.php ─────┐
login.php ─────┤
dashboard.php ─┼──▶ config.php ◀──── config.dev.php (optional)
logout.php ────┤
test-config.php┘

config.php provides:
   • BASE_DIR constant
   • getBaseUrl() function
   • redirectTo() function
   • Session security settings
```
