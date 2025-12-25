Notes:
- Do NOT commit `.env` to version control. Use it only for local development.
- The repository contains `.env.example` with default values; copy it to `.env` and update secrets.

Server-level option (recommended for production)
----------------------------------------------
For Apache, set environment variables per vhost using `SetEnv` (example below). Add these lines to your Apache virtual host configuration (inside the `<VirtualHost>` block) or include a small conf file.

```
# Example: include this in your vhost or a separate file and include it
SetEnv TOUR_API_KEY "your_real_api_key_here"
SetEnv TOUR_API_BASE_URL "https://tour-management-web.onrender.com/api/v1"
```

After changing Apache config restart Apache:

```bash
sudo systemctl restart apache2
# or for XAMPP:
sudo /opt/lampp/lampp restart
```


Local quick test (not for production)
-----------------------------------
You can create a `.env` file at the project root with the values above. The project ships a small loader (`config/env.php`) that reads `.env` or falls back to `.env.example`.

Tips:
- After changing `.env` or Apache vhost, restart your web server.
- Use the included `public/get_test_token.php` (development-only) to generate a local JWT for testing endpoints that require authentication.
This returns JSON showing whether the Hotel and Booking endpoints responded and any error messages. If both report `ok: true`, the key and outbound connectivity are fine.

