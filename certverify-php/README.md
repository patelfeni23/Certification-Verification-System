# CertVerify — PHP + MySQL Certificate Verification System

A production-ready web application with real MySQL database connectivity via PHP.

---

## ⚡ Quick Setup (5 Minutes)

### Step 1 — Requirements
- **XAMPP** (Windows) or **LAMP** (Linux) or **MAMP** (Mac)
- PHP 7.4+ with PDO and PDO_MySQL extensions
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx web server

### Step 2 — Place Files
Copy the entire `certverify-php/` folder into your web root:

| Platform | Web Root Path               |
|----------|-----------------------------|
| XAMPP    | `C:/xampp/htdocs/certverify/` |
| LAMP     | `/var/www/html/certverify/`   |
| MAMP     | `/Applications/MAMP/htdocs/certverify/` |

### Step 3 — Create Database
Open **phpMyAdmin** (http://localhost/phpmyadmin) or MySQL terminal:

```sql
-- In phpMyAdmin: click "Import" → select database.sql
-- OR in terminal:
mysql -u root -p < database.sql
```

This creates the `certverify_db` database with 10 sample certificates.

### Step 4 — Configure Database Connection
Edit `config/db.php`:

```php
define('DB_HOST', 'localhost');   // usually localhost
define('DB_NAME', 'certverify_db');
define('DB_USER', 'root');        // ← your MySQL username
define('DB_PASS', '');            // ← your MySQL password
```

### Step 5 — Run!
Open in browser: **http://localhost/certverify/**

---

## 📁 File Structure

```
certverify-php/
│
├── index.php               ← Main app (frontend + API calls)
├── database.sql            ← Run ONCE to create DB & tables
├── generate_hash.php       ← Utility to hash passwords (DELETE after use)
│
├── config/
│   ├── db.php              ← ⭐ MySQL connection settings (EDIT THIS)
│   └── helpers.php         ← Shared utility functions
│
└── api/
    ├── verify.php          ← GET  Public certificate verification
    ├── auth.php            ← POST Admin login / logout / session check
    ├── certificates.php    ← CRUD Add / Edit / Delete (Admin only)
    ├── upload.php          ← POST Bulk CSV import (Admin only)
    └── stats.php           ← GET  Dashboard statistics (Admin only)
```

---

## 🔐 Admin Login

| Field    | Value                  |
|----------|------------------------|
| Username | `admin`                |
| Password | `Admin@123`            |
| URL      | http://localhost/certverify/ → click Admin tab |

---

## 🔌 API Reference

All endpoints return JSON. Base URL: `http://localhost/certverify/api/`

### Public Endpoints

#### Verify Certificate
```
GET /api/verify.php?id=CERT-2024-001

Response (200 OK):
{
  "success": true,
  "data": {
    "cert_id": "CERT-2024-001",
    "student_name": "Aarav Sharma",
    "domain": "Web Development",
    "start_date": "2024-01-15",
    "end_date": "2024-04-15",
    "duration": "3 Months",
    "issued_on": "2024-01-01",
    "verified_at": "2024-07-01 12:00:00"
  }
}

Response (404 Not Found):
{
  "success": false,
  "error": "No certificate found with ID: CERT-XXXX"
}
```

### Admin Endpoints (require login session)

#### Login
```
POST /api/auth.php
Body: { "action": "login", "username": "admin", "password": "Admin@123" }
```

#### Logout
```
POST /api/auth.php
Body: { "action": "logout" }
```

#### List All Certificates
```
GET /api/certificates.php
GET /api/certificates.php?search=Sharma&domain=Web+Development&page=1&limit=50
```

#### Get One Certificate
```
GET /api/certificates.php?id=CERT-2024-001
```

#### Add Certificate
```
POST /api/certificates.php
Body: {
  "cert_id": "CERT-2024-011",
  "student_name": "Rahul Kumar",
  "domain": "Web Development",
  "start_date": "2024-07-01",
  "end_date": "2024-10-01"
}
```

#### Update Certificate
```
PUT /api/certificates.php
Body: { "cert_id": "CERT-2024-001", "student_name": "New Name" }
```

#### Delete Certificate
```
DELETE /api/certificates.php?id=CERT-2024-001
```

#### Bulk Upload (CSV)
```
POST /api/upload.php
Content-Type: multipart/form-data
Field: file (CSV file)
```

#### Dashboard Stats
```
GET /api/stats.php
```

---

## 📊 CSV Upload Format

Your CSV file must have these column headers in **row 1**:

```csv
cert_id,student_name,domain,start_date,end_date
CERT-2024-011,Rahul Kumar,Web Development,2024-07-01,2024-10-01
CERT-2024-012,Meera Joshi,Data Science,2024-07-15,2024-10-15
```

**Rules:**
- Certificate ID must be unique and uppercase (letters, numbers, hyphens)
- Dates must be in `YYYY-MM-DD` format
- End date must be after start date
- All 5 columns are required

---

## 🗄 Database Schema

```
certverify_db
├── admins              — Admin accounts (bcrypt passwords)
├── domains             — Internship domain lookup table
├── certificates        — Main certificate records
├── verification_logs   — Every public verification is logged
├── v_certificates      — View: certificates with admin names
└── v_stats             — View: dashboard statistics
```

---

## 🛡 Security Notes

1. **Change the default password** — Login as admin → Settings
2. **Use HTTPS** in production — set `'secure' => true` in helpers.php session config
3. **Delete `generate_hash.php`** after first use
4. **Restrict `config/`** folder — add `.htaccess`:
   ```
   Deny from all
   ```
5. **Change `SESSION_SECRET`** in `config/db.php`

---

## 🧪 Test Certificate IDs

| ID            | Student        | Domain            |
|---------------|----------------|-------------------|
| CERT-2024-001 | Aarav Sharma   | Web Development   |
| CERT-2024-002 | Priya Patel    | Data Science      |
| CERT-2024-003 | Rohan Mehta    | UI/UX Design      |
| CERT-2024-004 | Sneha Verma    | Android Dev       |
| CERT-2024-005 | Arjun Nair     | Machine Learning  |

---

*CertVerify v1.0 — PHP + MySQL | Frontend: HTML, CSS, JS*
