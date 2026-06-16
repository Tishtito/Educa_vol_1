# Examiner Portal API - Testing Guide

## API Endpoints

### Base URL
```
http://localhost/Educa_vol_1/JSS/examiner/backend/public/
```

## 1. Authentication Tests

### POST /auth/login
**Test Login**

```bash
curl -X POST http://localhost/Educa_vol_1/JSS/examiner/backend/public/index.php/auth/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "username=examiner_username&password=password" \
  -c cookies.txt
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful"
}
```

**What happens:**
- Session is created with:
  - `$_SESSION['loggedin'] = true`
  - `$_SESSION['id'] = examiner_id` (integer)
  - `$_SESSION['username'] = "examiner_username"`
  - `$_SESSION['name'] = "Examiner Name"`
- Session cookie is sent to browser

**Check logs for:**
```
[AUTH] Login success username=examiner_username
```

---

### GET /auth/check
**Verify Authentication Status**

```bash
curl -X GET http://localhost/Educa_vol_1/JSS/examiner/backend/public/index.php/auth/check \
  -b cookies.txt
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "username": "examiner_username",
  "name": "Examiner Name",
  "examiner_id": 1,
  "class_assigned": null
}
```

**Check logs for:**
```
[AUTH-CHECK] Session loggedin=true | Session contents: {...}
```

---

### GET /auth/logout
**Logout**

```bash
curl -X GET http://localhost/Educa_vol_1/JSS/examiner/backend/public/index.php/auth/logout \
  -b cookies.txt
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## 2. Dashboard Tests

### GET /dashboard
**Get Examiner Dashboard Data (Subjects & Classes)**

```bash
curl -X GET http://localhost/Educa_vol_1/JSS/examiner/backend/public/index.php/dashboard \
  -b cookies.txt
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "subjects": [
    {"subject_id": 1, "name": "Mathematics"},
    {"subject_id": 2, "name": "English"}
  ],
  "classes": [
    {"class_id": 1, "class_name": "Class A"},
    {"class_id": 2, "class_name": "Class B"}
  ]
}
```

**Possible Error Responses:**

| Status | Response | Cause |
|--------|----------|-------|
| 401 | `{"success": false, "message": "Unauthorized"}` | Not logged in |
| 400 | `{"success": false, "message": "Examiner ID not found in session"}` | Session doesn't have `id` field |
| 403 | `{"success": false, "message": "No classes assigned. Visit Admin for assistance."}` | Examiner has no assigned classes |
| 500 | `{"success": false, "message": "..."}` | Database error |

**Check logs for:**
```
[DASHBOARD] Request started. Session status: 2
[DASHBOARD] Session data: {"loggedin":true,"id":1,...}
[DASHBOARD] Extracted examiner ID: 1
[DASHBOARD] Fetching subjects for examiner_id=1
[DASHBOARD] Subjects query result: [...]
[DASHBOARD] Fetching classes for examiner_id=1
[DASHBOARD] Classes query result: [...]
[DASHBOARD] Success: Returning dashboard data for examiner_id=1
```

---

## 3. Profile Tests

### GET /profile
**Get Examiner Profile Information**

```bash
curl -X GET http://localhost/Educa_vol_1/JSS/examiner/backend/public/index.php/profile \
  -b cookies.txt
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "name": "John Examiner",
  "username": "john_examiner",
  "email": "john@school.edu",
  "phone": "+254712345678",
  "total_exams": 5
}
```

**Error Responses:**

| Status | Response | Cause |
|--------|----------|-------|
| 401 | `{"success": false, "message": "Unauthorized"}` | Not logged in |
| 400 | `{"success": false, "message": "Invalid session"}` | Session doesn't have `id` field |
| 404 | `{"success": false, "message": "Examiner not found"}` | Examiner ID doesn't exist in DB |

---

## 4. Exam Tests

### GET /exams
**Get List of Available Exams**

```bash
curl -X GET http://localhost/Educa_vol_1/JSS/examiner/backend/public/index.php/exams \
  -b cookies.txt
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "exams": [
    {
      "exam_id": 1,
      "exam_name": "Mid-Term Exam",
      "date_created": "2024-01-15"
    },
    {
      "exam_id": 2,
      "exam_name": "Final Exam",
      "date_created": "2024-03-15"
    }
  ]
}
```

---

### POST /exams/select
**Select an Exam**

```bash
curl -X POST http://localhost/Educa_vol_1/JSS/examiner/backend/public/index.php/exams/select \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "exam_id=1" \
  -b cookies.txt
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Exam selected successfully"
}
```

---

## Manual Browser Testing

### 1. Login Flow
```
1. Open: http://localhost/Educa_vol_1/JSS/examiner/index.php
2. Enter credentials
3. Click Login
4. Open DevTools > Network
5. Look for POST to /auth/login
6. Check response status and body
7. Check cookies are set (Application tab > Cookies)
```

### 2. Dashboard Flow
```
1. After login, click "Exam" or Dashboard link
2. Open DevTools > Network
3. Look for GET to /dashboard
4. Check response contains subjects and classes
5. Check Application > Session Storage (if using client-side storage)
```

### 3. Check Server Logs
```
PowerShell:
  Get-Content "backend\logs\php_errors.log" -Tail 30

Windows CMD:
  type backend\logs\php_errors.log | tail -30
```

---

## Troubleshooting Checklist

### 401 Unauthorized on Dashboard
- [ ] Verify login was successful (check `[AUTH] Login success` in logs)
- [ ] Verify session cookie is being sent with dashboard request
- [ ] Check browser cookies (DevTools > Application > Cookies)
- [ ] Verify session file exists in PHP tmp directory

### 400 Bad Request on Dashboard
- [ ] Check logs for `Examiner ID from session: NULL`
- [ ] Verify AuthController is setting `$_SESSION['id']` correctly
- [ ] Check database has examiner with given ID
- [ ] Look for `$_SESSION['examiner_id']` (wrong key)

### 403 No Classes Assigned
- [ ] Verify examiner exists in database
- [ ] Verify `examiner_classes` table has entries for this examiner
- [ ] Use admin panel to assign classes to examiner
- [ ] Check database query: `SELECT * FROM examiner_classes WHERE examiner_id = {id}`

### 500 Server Error
- [ ] Check error logs for exception messages
- [ ] Verify database connection is working
- [ ] Verify Medoo library is properly loaded
- [ ] Check for missing table or column names

---

## Sample cURL Script (test-api.sh)

```bash
#!/bin/bash

BASE_URL="http://localhost/Educa_vol_1/JSS/examiner/backend/public/index.php"
COOKIES="cookies.txt"

echo "=== Testing Examiner Portal API ==="

# Test 1: Login
echo -e "\n1. Testing Login..."
curl -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "username=examiner&password=password" \
  -c "$COOKIES" \
  -s | jq .

# Test 2: Check Auth
echo -e "\n2. Testing Auth Check..."
curl -X GET "$BASE_URL/auth/check" \
  -b "$COOKIES" \
  -s | jq .

# Test 3: Dashboard
echo -e "\n3. Testing Dashboard..."
curl -X GET "$BASE_URL/dashboard" \
  -b "$COOKIES" \
  -s | jq .

# Test 4: Profile
echo -e "\n4. Testing Profile..."
curl -X GET "$BASE_URL/profile" \
  -b "$COOKIES" \
  -s | jq .

# Test 5: Logout
echo -e "\n5. Testing Logout..."
curl -X GET "$BASE_URL/auth/logout" \
  -b "$COOKIES" \
  -s | jq .

echo -e "\n=== Tests Complete ==="
rm "$COOKIES"
```

---

## Using Postman

1. **Create Environment Variables:**
   - `base_url`: `http://localhost/Educa_vol_1/JSS/examiner/backend/public/index.php`
   - `username`: `examiner_username`
   - `password`: `password`

2. **Create Collection with Requests:**
   - POST `/auth/login` (Body: form-data with username & password)
   - GET `/auth/check`
   - GET `/dashboard`
   - GET `/profile`
   - GET `/auth/logout`

3. **Run Tests:**
   - Enable "Send cookies with requests"
   - Run requests in sequence
   - Check responses match expected format

---

## Database Verification

### Check Examiner Exists
```sql
SELECT * FROM examiners WHERE username = 'examiner_username';
```

### Check Classes Assigned
```sql
SELECT * FROM examiner_classes WHERE examiner_id = 1;
```

### Check Subjects Assigned
```sql
SELECT * FROM examiner_subjects WHERE examiner_id = 1;
```

### Check Exams Available
```sql
SELECT * FROM exams LIMIT 5;
```
