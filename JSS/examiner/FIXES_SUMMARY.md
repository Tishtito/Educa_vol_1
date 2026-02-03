# Session Variable Fixes - Quick Reference

## Problem Summary
The examiner portal was returning a **400 Bad Request** error when accessing the dashboard endpoint because the session variable names didn't match between the authentication and dashboard controllers.

## Root Cause
- **AuthController** was setting: `$_SESSION['id'] = $examiner['examiner_id']`
- **DashboardController** was looking for: `$_SESSION['examiner_id']` ← **WRONG KEY!**
- **ProfileController** was looking for: `$_SESSION['examiner_id']` ← **WRONG KEY!**

This mismatch caused `$examinerId` to be `null`, triggering the 400 error.

---

## Fixes Applied

### ✅ Fix 1: DashboardController.php (Line 33)

**BEFORE:**
```php
$examinerId = $_SESSION['examiner_id'] ?? null;  // ❌ WRONG - examiner_id is never set!
```

**AFTER:**
```php
$examinerId = $_SESSION['id'] ?? null;  // ✅ CORRECT - matches what AuthController sets
```

### ✅ Fix 2: ProfileController.php (Line 33)

**BEFORE:**
```php
$examiner_id = $_SESSION['examiner_id'] ?? null;  // ❌ WRONG
```

**AFTER:**
```php
$examiner_id = $_SESSION['id'] ?? null;  // ✅ CORRECT
```

### ✅ Fix 3: Debug Logging Added

All three controllers now include comprehensive logging:

**AuthController**
```php
error_log('[AUTH] Login success username=' . $examiner['username']);
error_log('[AUTH] Session after login: ' . json_encode($_SESSION));
```

**DashboardController**
```php
error_log('[DASHBOARD] Request started. Session status: ' . session_status());
error_log('[DASHBOARD] Session data: ' . json_encode($_SESSION, JSON_UNESCAPED_SLASHES));
error_log('[DASHBOARD] Extracted examiner ID: ' . ($examinerId ?? 'NULL'));
error_log('[DASHBOARD] Fetching subjects for examiner_id=' . $examinerId);
error_log('[DASHBOARD] Subjects query result: ' . json_encode($subjects ?? []));
error_log('[DASHBOARD] Fetching classes for examiner_id=' . $examinerId);
error_log('[DASHBOARD] Classes query result: ' . json_encode($classes ?? []));
error_log('[DASHBOARD] Success: Returning dashboard data for examiner_id=' . $examinerId);
```

**ProfileController**
```php
error_log('[PROFILE] Session contents: ' . json_encode($_SESSION));
error_log('[PROFILE] Examiner ID from session: ' . ($examinerId ?? 'NULL'));
error_log('[PROFILE] Fetching profile for examiner_id=' . $examinerId);
error_log('[PROFILE] Examiner query result: ' . json_encode($examiner));
error_log('[PROFILE] Exam count: ' . $examCount);
```

---

## Session Variables - What's Actually Set

After successful login via `AuthController::login()`:

```php
$_SESSION = [
    'loggedin'   => true,                    // Boolean flag
    'id'         => 1,                       // Integer examiner ID ✅ USE THIS
    'username'   => 'john_examiner',         // String
    'name'       => 'John Smith',            // String
    // 'examiner_id' => ??? (NOT SET!)     // ❌ THIS IS NEVER SET!
];
```

---

## Testing the Fix

### 1. Login
```bash
curl -X POST http://localhost/Educa_vol_1/JSS/examiner/backend/public/index.php/auth/login \
  -d "username=examiner&password=password" \
  -c cookies.txt
```
✅ Should return: `{"success": true, "message": "Login successful"}`

### 2. Check Session (Verify `id` was set)
```bash
curl http://localhost/Educa_vol_1/JSS/examiner/backend/public/index.php/auth/check \
  -b cookies.txt
```
✅ Should return: `{"success": true, "examiner_id": 1, ...}`

### 3. Dashboard (Should now work!)
```bash
curl http://localhost/Educa_vol_1/JSS/examiner/backend/public/index.php/dashboard \
  -b cookies.txt
```
✅ Should return: `{"success": true, "subjects": [...], "classes": [...]}`

---

## Checking the Logs

View debug logs to confirm fixes:

```powershell
# PowerShell
Get-Content "backend\logs\php_errors.log" -Tail 50

# Or search for specific events
Select-String "AUTH|DASHBOARD" "backend\logs\php_errors.log"
```

Expected log flow for successful login + dashboard request:

```
[AUTH] Login success username=examiner
[DASHBOARD] Request started. Session status: 2
[DASHBOARD] Session data: {"loggedin":true,"id":1,"username":"examiner","name":"John"}
[DASHBOARD] Extracted examiner ID: 1
[DASHBOARD] Fetching subjects for examiner_id=1
[DASHBOARD] Subjects query result: [{"subject_id":1,"name":"Math"}, ...]
[DASHBOARD] Fetching classes for examiner_id=1
[DASHBOARD] Classes query result: [{"class_id":1,"class_name":"A"}, ...]
[DASHBOARD] Success: Returning dashboard data for examiner_id=1
```

---

## Files Modified

| File | Change | Line |
|------|--------|------|
| `app/src/Controllers/DashboardController.php` | Fixed session key `examiner_id` → `id` | 33 |
| `app/src/Controllers/DashboardController.php` | Added debug logging throughout | 21-87 |
| `app/src/Controllers/ProfileController.php` | Fixed session key `examiner_id` → `id` | 33 |
| `app/src/Controllers/AuthController.php` | Verified correct session key usage | 62 |
| `app/src/Controllers/AuthController.php` | Added debug logging | 44, 52, 65, 78-79 |

---

## Important Notes

1. **The session key is `'id'`, NOT `'examiner_id'`**
   - This is set by AuthController in the login method
   - Value comes from `$examiner['examiner_id']` in the database
   - Don't confuse the array key with the database column name!

2. **Logging helps troubleshooting**
   - Check `backend/logs/php_errors.log` for any issues
   - Logs are timestamped and include context
   - Use logs to diagnose session, database, or permission issues

3. **Session must be active**
   - Controllers use `$this->startSession()` to ensure session is started
   - Browser must accept cookies for session to persist
   - Each request must send the session cookie back

4. **Related Controllers**
   - ExamController (also uses same session pattern)
   - Any new controllers should follow the same pattern

---

## Common Mistakes to Avoid

❌ **WRONG** - Looking for wrong session key:
```php
$examinerId = $_SESSION['examiner_id'];  // This will be null!
```

❌ **WRONG** - Not starting session:
```php
public function getProfile(): void {
    // No session_start() call - session might not be active
    $id = $_SESSION['id'];  // This might fail!
}
```

❌ **WRONG** - Setting wrong key during login:
```php
$_SESSION['examiner_id'] = $examiner['examiner_id'];  // Wrong key name!
$_SESSION['id'] = $examiner['id'];  // database column is 'examiner_id', not 'id'!
```

✅ **CORRECT** - As implemented:
```php
// In AuthController::login()
$_SESSION['id'] = $examiner['examiner_id'];  // ✅ Right key, right value

// In DashboardController::getDashboard()
$examinerId = $_SESSION['id'] ?? null;  // ✅ Using correct key
```

---

## Summary

| Aspect | Before | After |
|--------|--------|-------|
| Session Key | `$_SESSION['examiner_id']` ❌ | `$_SESSION['id']` ✅ |
| Error | 400 Bad Request (examiner_id=null) | 200 OK with data |
| Logging | None | Comprehensive debug logs |
| Troubleshooting | Difficult | Easy (check logs) |

The fixes ensure all controllers use the same session variable names, eliminating the mismatch that was causing the 400 error.
