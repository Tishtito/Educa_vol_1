# Examiner Portal - Debugging Guide

## Session Variable Mismatch - FIXED

### The Problem
The AuthController was setting `$_SESSION['id']` during login, but older code might be looking for `$_SESSION['examiner_id']`, causing a 400 error.

### The Solution
All controllers now use the correct session key: `$_SESSION['id']`

**Fixed in:**
- DashboardController.php - Line 33: Uses `$_SESSION['id']` ✅
- ProfileController.php - Uses `$_SESSION['id']` ✅

## Debug Logging Added

All controllers now log to `backend/logs/php_errors.log` for troubleshooting:

### AuthController
```
[AUTH] Login success username={username}
[AUTH] Session after login: {JSON session contents}
[AUTH-CHECK] Session loggedin=true/false | Session contents: {JSON}
```

### DashboardController
```
[DASHBOARD] Request started. Session status: {status}
[DASHBOARD] Session data: {JSON}
[DASHBOARD] Not logged in. loggedin={value}
[DASHBOARD] Extracted examiner ID: {id}
[DASHBOARD] Error: Examiner ID not found in session
[DASHBOARD] Fetching subjects for examiner_id={id}
[DASHBOARD] Subjects query result: {JSON}
[DASHBOARD] Fetching classes for examiner_id={id}
[DASHBOARD] Classes query result: {JSON}
[DASHBOARD] WARNING: No classes assigned to examiner_id={id}
[DASHBOARD] Success: Returning {count} subjects and {count} classes
[DASHBOARD] EXCEPTION: {error message} | File: {file}:{line}
```

### ProfileController
```
[PROFILE] Session contents: {JSON}
[PROFILE] Not logged in
[PROFILE] Examiner ID from session: {id}
[PROFILE] Fetching profile for examiner_id={id}
[PROFILE] Examiner query result: {JSON}
[PROFILE] ERROR: Examiner not found for examiner_id={id}
[PROFILE] Exam count: {count}
[PROFILE] Success: Returning examiner profile for {name}
[PROFILE] EXCEPTION: {error message} | File: {file}:{line}
```

## How to View Logs

### In Windows Terminal/PowerShell:
```powershell
# View last 50 lines
Get-Content "backend\logs\php_errors.log" -Tail 50

# Follow log in real-time
Get-Content "backend\logs\php_errors.log" -Wait

# Search for specific errors
Select-String "DASHBOARD|ERROR" "backend\logs\php_errors.log"
```

### In Windows Command Prompt:
```cmd
# View logs
type backend\logs\php_errors.log

# Search for errors
findstr "ERROR" backend\logs\php_errors.log
```

## Testing the Fix

### 1. Test Login
```
POST /auth/login
Username: examiner_username
Password: password
```
Check log for: `[AUTH] Login success`

### 2. Test Dashboard
```
GET /dashboard
```
Expected log sequence:
```
[DASHBOARD] Request started. Session status: 2
[DASHBOARD] Session data: {"loggedin":true,"id":1,"username":"...","name":"..."}
[DASHBOARD] Extracted examiner ID: 1
[DASHBOARD] Fetching subjects for examiner_id=1
[DASHBOARD] Subjects query result: [...]
[DASHBOARD] Fetching classes for examiner_id=1
[DASHBOARD] Classes query result: [...]
[DASHBOARD] Success: Returning X subjects and Y classes
```

### 3. Test Profile
```
GET /profile
```
Expected log: `[PROFILE] Success: Returning examiner profile`

## Session Variables Reference

After successful login, `$_SESSION` contains:

| Variable | Value | Usage |
|----------|-------|-------|
| `loggedin` | `true` | Check if user is authenticated |
| `id` | `{examiner_id}` | ✅ Use for database queries |
| `username` | `{username}` | Display user's username |
| `name` | `{examiner_name}` | Display user's full name |

⚠️ **Important**: Do NOT use `$_SESSION['examiner_id']` - it will be `null`!

## Common Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| 400 Bad Request on /dashboard | Examiner ID null in session | Check `[DASHBOARD] Extracted examiner ID: NULL` in logs |
| 401 Unauthorized | Session not active or `loggedin=false` | Check logs for `[DASHBOARD] Not logged in` or `[AUTH-CHECK]` |
| 404 Examiner not found | Examiner ID doesn't exist in `examiners` table | Verify examiner was created in database |
| No classes assigned | Examiner has no entries in `examiner_classes` | Admin must assign classes in admin panel |
| Empty session data | Session cookie not sent by client | Check if cookies are enabled in browser |

## Performance Monitoring

### Log File Size
The `php_errors.log` file may grow over time. To manage:

```powershell
# Check log file size
(Get-Item "backend\logs\php_errors.log").Length
# Output in MB
(Get-Item "backend\logs\php_errors.log").Length / 1MB
```

### Clear Old Logs
```powershell
# Clear logs if over 10MB
if ((Get-Item "backend\logs\php_errors.log").Length -gt 10MB) {
    Clear-Content "backend\logs\php_errors.log"
}
```

## Files Modified

| File | Changes |
|------|---------|
| `AuthController.php` | Added debug logging for login & auth check |
| `DashboardController.php` | Fixed session key, added comprehensive logging |
| `ProfileController.php` | Fixed session key, added logging |

## Frontend Troubleshooting

### Network Tab (Browser DevTools)
1. Open DevTools (F12)
2. Go to Network tab
3. Make API calls
4. Check responses:
   - 401 = Not logged in
   - 400 = Missing examiner ID
   - 500 = Server error (check logs)

### Console Errors
1. Open DevTools Console (F12 > Console)
2. Look for fetch/XMLHttpRequest errors
3. Note status codes and response messages
4. Check backend logs for correlation

## Next Steps

1. ✅ Verify session variables are correctly set after login
2. ✅ Test dashboard API returns data
3. ✅ Test profile API returns examiner info
4. 🔄 Monitor logs for any unexpected errors
5. 🔄 Add similar debug logging to other controllers as needed

## Support

For issues:
1. Check logs in `backend/logs/php_errors.log`
2. Look for error patterns and timestamps
3. Cross-reference with network requests in browser DevTools
4. Verify database has correct data for examiner
