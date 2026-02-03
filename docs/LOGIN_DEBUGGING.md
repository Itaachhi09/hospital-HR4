# Login Debugging Guide

## Current Issue
Login is returning 401 Unauthorized with "Invalid username or password"

## Debugging Steps Added

### 1. Enhanced Logging in Login Function
Added detailed logging to `modules/auth/routes/auth.php`:
- Logs request method
- Logs Content-Type header
- Logs raw input from php://input
- Logs parsed data
- Logs POST and GET data separately
- Logs extracted username and password status

### 2. Enhanced JSON Parsing
Improved `Request::getJsonBody()` to:
- Check for empty input
- Log JSON decode errors
- Return null on error instead of false

## How to Debug

### Step 1: Check PHP Error Log
The error logs should now show:
```
=== LOGIN DEBUG ===
Request method: POST
Content-Type: application/json
Raw input: {"username":"test","password":"test"}
Parsed data: {"username":"test","password":"test"}
...
```

### Step 2: Verify Data is Being Received
If `Parsed data` is empty or doesn't contain `username` and `password`, the issue is with data parsing.

### Step 3: Check Database
If data is being received correctly, check:
1. Does the user exist in the database?
2. Is the password hash correct?
3. Is the user account active?

### Step 4: Test with Direct API Call
Use Postman or curl to test:
```bash
curl -X POST http://localhost/{your-domain}/public/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"test","password":"test"}'
```

## Common Issues

### Issue 1: JSON Body Not Being Read
**Symptom**: `Parsed data` is empty
**Solution**: Check if `php://input` is being read multiple times elsewhere

### Issue 2: Wrong Field Names
**Symptom**: Data exists but `username` or `password` keys are missing
**Solution**: Check frontend is sending correct field names

### Issue 3: User Not Found
**Symptom**: "User found: No" in logs
**Solution**: 
- Verify user exists in database
- Check username/email matches exactly
- Check database connection

### Issue 4: Password Verification Fails
**Symptom**: "User found: Yes" but login still fails
**Solution**:
- Check password hash in database
- Verify password_verify() is working
- Check if password was hashed with password_hash()

## Next Steps

1. Try logging in again
2. Check PHP error log (usually in XAMPP: `C:\xampp\php\logs\php_error_log` or Apache: `C:\xampp\apache\logs\error.log`)
3. Look for the "=== LOGIN DEBUG ===" section
4. Share the log output to identify the exact issue
