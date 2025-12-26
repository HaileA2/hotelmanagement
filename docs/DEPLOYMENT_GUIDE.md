# Deployment Guide for Hotel Management System

## Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- SSL certificate for HTTPS

## 1. Database Setup

### Option A: Using phpMyAdmin (Recommended for XAMPP)

1. **Access phpMyAdmin:**
   - Open your browser and go to `http://localhost/phpmyadmin` (for XAMPP)
   - Or access your hosting control panel's phpMyAdmin

2. **Create Database:**
   ```sql
   CREATE DATABASE hotel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Import Database Schema:**
   - Click on the `hotel_management` database in the left sidebar
   - Click on the "Import" tab
   - Click "Choose File" and select your `database.sql` file
   - Click "Go" to execute the import

4. **Verify Tables Created:**
   - You should see these tables: users, hotels, rooms, bookings, token_blacklist
   - Check that the admin user and API customer are created

### Option B: Using MySQL Command Line

```bash
# Connect to MySQL
mysql -u root -p

# Create database
CREATE DATABASE hotel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Use the database
USE hotel_management;

# Import the SQL file
SOURCE /path/to/your/database.sql;

# Exit MySQL
EXIT;
```

### Option C: Using MySQL from PHP (if you have SSH access)

```bash
# Upload database.sql to your server
# Then run:
mysql -u your_db_user -p your_database_name < database.sql
```

## 2. Environment Configuration

### Create .env File

1. **Copy the example file:**
   ```bash
   cp .env.example .env
   ```

2. **Edit .env with your settings:**
   ```env
   # Database Configuration
   DB_HOST=localhost
   DB_NAME=hotel_management
   DB_USER=your_db_username
   DB_PASS=your_db_password

   # JWT Configuration
   JWT_SECRET=your_super_secure_random_jwt_secret_key_here
   JWT_ISSUER=hotel-management.lovestoblog.com
   JWT_AUDIENCE=hotel-management-client
   JWT_EXPIRE=86400

   # API Keys for External Services
   HOTEL_API_KEY_1=your_secure_api_key_1_here
   HOTEL_API_KEY_1_NAME=External Booking Service
   HOTEL_API_KEY_1_PERMISSIONS=create_booking
   HOTEL_API_KEY_1_ACTIVE=true

   HOTEL_API_KEY_2=another_secure_api_key_here
   HOTEL_API_KEY_2_NAME=Partner Booking System
   HOTEL_API_KEY_2_PERMISSIONS=create_booking
   HOTEL_API_KEY_2_ACTIVE=true

   # Application Settings
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://hotel-management.lovestoblog.com
   ```

### Generate Secure API Keys

Use a secure random key generator:

```bash
# Linux/Mac
openssl rand -hex 32

# Or use online generators like:
# https://www.uuidgenerator.net/
# https://passwordsgenerator.net/
```

Example secure keys:
```
HOTEL_API_KEY_1=4f8d9e2a7b5c1f3e6a9d8b7c5f2e4a1b9c8d7e6f5a4b3c2d1e9f8a7b6c5d4e3f2
HOTEL_API_KEY_2=8a5b9c7d2e1f4a6b8c9d3e5f7a2b4c6d8e9f1a3b5c7d9e2f4a6b8c1d3e5f7a9
```

## 3. File Permissions

Set proper permissions for your web server:

```bash
# Set ownership to web server user (adjust for your server)
sudo chown -R www-data:www-data /path/to/hotel-management-system

# Set directory permissions
find /path/to/hotel-management-system -type d -exec chmod 755 {} \;

# Set file permissions
find /path/to/hotel-management-system -type f -exec chmod 644 {} \;

# Special permissions for sensitive files
chmod 600 .env
chmod 600 config/database.php
```

## 4. Web Server Configuration

### Apache (.htaccess)

Create or update `.htaccess` in your root directory:

```apache
RewriteEngine On

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Handle Angular routing (if using SPA features)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.html [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# PHP settings
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value memory_limit 256M
php_value max_execution_time 300
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name hotel-management.lovestoblog.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name hotel-management.lovestoblog.com;

    # SSL Configuration
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    root /path/to/hotel-management-system;
    index index.html index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Handle PHP files
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Handle static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Handle API routes
    location /api/ {
        try_files $uri $uri/ /api/index.php?$query_string;
    }

    # Handle SPA routing
    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

## 5. Testing Deployment

### Test Database Connection
Create a test file `test_db.php`:

```php
<?php
require_once 'config/database.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "Database connection successful!";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage();
}
```

### Test API Endpoints
```bash
# Test API key booking creation
curl -X POST https://hotel-management.lovestoblog.com/api/booking/create_booking.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your_api_key_here" \
  -d '{
    "room_id": 1,
    "hotel_id": 1,
    "check_in": "2024-12-25",
    "check_out": "2024-12-27",
    "guest_count": 2,
    "total_price": 200.00
  }'
```

### Test User Registration
- Visit: `https://hotel-management.lovestoblog.com/public/customer_registration.html`
- Try registering a customer account

## 6. Post-Deployment Tasks

1. **Remove Development Files:**
   ```bash
   rm test_db.php
   rm -rf tests/
   ```

2. **Set Up Backups:**
   - Configure automated database backups
   - Set up file system backups

3. **Monitoring:**
   - Set up error logging
   - Configure monitoring alerts
   - Set up log rotation

4. **Security Hardening:**
   - Regularly update PHP and MySQL
   - Monitor for security vulnerabilities
   - Implement rate limiting
   - Set up firewall rules

## 7. Troubleshooting

### Common Issues:

**Database Connection Failed:**
- Check DB credentials in `.env`
- Verify database exists and user has permissions
- Check MySQL server is running

**API Key Not Working:**
- Verify API key is set in environment variables
- Check API key permissions in `config/api_keys.php`
- Ensure `X-API-Key` header is sent correctly

**File Permissions:**
- Web server user must have read access to all files
- Write access to necessary directories (uploads, logs, etc.)

**HTTPS Not Working:**
- Verify SSL certificate is properly installed
- Check redirect rules in web server config

## Support

For deployment issues, check:
1. Web server error logs
2. PHP error logs
3. Database error logs
4. Application logs in `logs/` directory