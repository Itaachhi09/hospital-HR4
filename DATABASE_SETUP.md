# Hospital HR System - Database Setup Guide

## Quick Start

### Option 1: Automated Setup (Recommended)

1. **Check Database Status:**
   ```
   http://localhost/{your-domain}/public/api/health
   ```
   Replace `{your-domain}` with your actual domain or project folder name. This will show if the database connection is working.

2. **Check Database/Tables:**
   ```
   http://localhost/{your-domain}/public/api/setup?action=check
   ```

3. **Initialize Database (if not already initialized):**
   ```
   http://localhost/{your-domain}/public/api/setup?action=init
   ```

4. **Create Test Users:**
   ```
   http://localhost/{your-domain}/public/api/setup?action=create-test-users
   ```

### Option 2: Manual Setup with phpMyAdmin

1. Open phpMyAdmin: `http://localhost/phpmyadmin`

2. Create a new database named `hr_integrated_db` with UTF8MB4 encoding

3. Run the setup SQL script:
   - Go to the SQL tab
   - Copy and paste the contents of `SETUP_DATABASE.sql` from the project root
   - Click Execute

4. Verify the tables were created:
   - You should see: `roles`, `departments`, `employees`, `users` tables

### Option 3: Full Database Import

If you want to import the complete database with all tables:

1. In phpMyAdmin, select the `hr_integrated_db` database
2. Go to the Import tab
3. Choose file: `hr_integrated_db.sql`
4. Click Import

## Test Credentials

After setup, you can log in with these test accounts:

**Account 1: System Administrator**
- Email: `admin@gmail.com`
- Password: `password123`
- Role: System Admin

**Account 2: HR Administrator**
- Email: `hr_chief@gmail.com`
- Password: `password123`
- Role: HR Admin

**Account 3: System Administrator (Alternative)**
- Email: `johnpaulaustria321@gmail.com`
- Password: Check `IMPORT_ALL_SAMPLE_DATA.sql` for the correct password
- Role: System Admin

## Database Configuration

The database connection is configured in: `/common/config/config.php`

Default values:
- **Host:** localhost
- **Database:** hr_integrated_db
- **User:** root
- **Password:** (empty)

To use different credentials, set environment variables:
```
DB_HOST=your_host
DB_NAME=your_database
DB_USER=your_user
DB_PASS=your_password
```

## Troubleshooting

### "Failed to fetch" Error on Login

1. Check database status: `http://localhost/{your-domain}/public/api/health` (replace `{your-domain}` with your actual domain)
2. If database connection fails, ensure MySQL is running
3. Check the database exists: `http://localhost/{your-domain}/public/api/setup?action=check` (replace `{your-domain}` with your actual domain)
4. If tables are missing, run: `http://localhost/{your-domain}/public/api/setup?action=init` (replace `{your-domain}` with your actual domain)

### Connection Refused

1. Make sure MySQL/MariaDB is running (XAMPP Control Panel)
2. Verify the database host is correct (usually `localhost` or `127.0.0.1`)
3. Check username and password

### "Unknown table" Error

1. Run the setup action: `http://localhost/{your-domain}/public/api/setup?action=init` (replace `{your-domain}` with your actual domain)
2. Or manually import `SETUP_DATABASE.sql` in phpMyAdmin

### CORS Errors

The API is configured to allow localhost requests. If you're accessing from a different domain, you may need to update the CORS settings in `/public/api/index.php`.

## File Locations

- **Main SQL Schema:** `/hr_integrated_db.sql`
- **Quick Setup Script:** `/SETUP_DATABASE.sql`
- **Sample Data:** `/IMPORT_ALL_SAMPLE_DATA.sql`
- **Setup API:** `/public/api/setup.php`
- **Database Config:** `/common/config/config.php`
- **DB Connection:** `/common/db/db_connect.php`

## API Endpoints

### Health Check
```
GET /public/api/health
```
Response: Database connection status

### Setup (Development Only)
```
GET /public/api/setup?action=check
GET /public/api/setup?action=init
GET /public/api/setup?action=create-test-users
```

### Login
```
POST /public/api/auth/login
```
Body:
```json
{
  "username": "admin@gmail.com",
  "password": "password123"
}
```

Response:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
      "id": 1,
      "employee_id": 1,
      "username": "admin@gmail.com",
      "role": "System Admin",
      "full_name": "Admin User"
    }
  }
}
```

## Logs

Application logs are written to PHP's error log. On XAMPP, check:
- Windows: `C:\NEWXAMPP\apache\logs\error.log` or `php_error.log`
- Or check in phpMyAdmin General Log

## Security Notes

⚠️ **Important:** The setup endpoint (`/public/api/setup`) is only accessible from localhost (127.0.0.1 or localhost). It should not be accessible from remote clients.

The test credentials should be changed in production!
