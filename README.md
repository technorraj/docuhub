# 🎬 DocumentaryHub

A documentary streaming website inspired by Amazon Prime Video.  
Built with **PHP**, **MySQL**, **Bootstrap 5** — a complete BCA-level project.

---

## 📁 Folder Structure

```
documentaryhub/
├── index.php               ← Homepage (hero, trending, new, popular)
├── watch.php               ← Documentary detail + YouTube player
├── search.php              ← Search + filter by category
├── categories.php          ← Browse all categories
├── login.php               ← User login
├── register.php            ← User registration
├── dashboard.php           ← User dashboard (history, watchlist, profile)
├── logout.php              ← Logout
├── ajax.php                ← AJAX: favorites & view tracking
├── cron.php                ← YouTube auto-fetch cron script
├── schema.sql              ← MySQL database schema + sample data
│
├── includes/
│   ├── config.php          ← DB config, helpers, session
│   ├── header.php          ← Navbar + HTML head
│   ├── footer.php          ← Footer + scripts
│   └── card.php            ← Reusable documentary card component
│
├── assets/
│   ├── css/style.css       ← Custom dark theme stylesheet
│   └── js/main.js          ← JavaScript (favorites, toast, lazy load)
│
├── admin/
│   ├── login.php           ← Admin login page
│   ├── index.php           ← Admin dashboard with statistics
│   ├── documentaries.php   ← List / search documentaries
│   ├── add-documentary.php ← Add new documentary
│   ├── edit-documentary.php← Edit existing documentary
│   ├── categories.php      ← Add / edit / delete categories
│   ├── users.php           ← View / suspend / delete users
│   ├── delete.php          ← Delete handler (docs, categories, users)
│   ├── auth.php            ← Admin authentication guard
│   ├── header.php          ← Admin layout header + sidebar
│   └── footer.php          ← Admin layout footer
│
└── uploads/
    └── thumbnails/         ← (optional) local thumbnail storage
```

---

## ⚡ Quick Setup

### Requirements
- PHP 7.4+ (PHP 8.x recommended)
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx with mod_rewrite (for XAMPP/WAMP/Laragon)
- A web browser

---

### Step 1 — Clone / Copy Project

Place the `documentaryhub/` folder inside your web root:
- XAMPP: `C:/xampp/htdocs/documentaryhub/`
- WAMP: `C:/wamp64/www/documentaryhub/`
- Linux: `/var/www/html/documentaryhub/`

---

### Step 2 — Create the Database

1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **"New"** → name it `documentaryhub` → Create
3. Click the database → **Import** tab
4. Select `schema.sql` → Click **Go**

Or via terminal:
```bash
mysql -u root -p -e "CREATE DATABASE documentaryhub;"
mysql -u root -p documentaryhub < schema.sql
```

---

### Step 3 — Configure Database

Open `includes/config.php` and update:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'documentaryhub');
define('DB_USER', 'root');        // ← your MySQL username
define('DB_PASS', '');            // ← your MySQL password
define('SITE_URL', 'http://localhost/documentaryhub');
```

---

### Step 4 — Browse the Site

| URL | Description |
|-----|-------------|
| `http://localhost/documentaryhub/` | Homepage |
| `http://localhost/documentaryhub/login.php` | User Login |
| `http://localhost/documentaryhub/register.php` | Register |
| `http://localhost/documentaryhub/admin/` | Admin Panel |
| `http://localhost/documentaryhub/admin/login.php` | Admin Login |

---

## 🔐 Default Credentials

> ⚠️ Change these immediately in a real deployment!

### Admin Account
| Field | Value |
|-------|-------|
| Email | `admin@documentaryhub.com` |
| Password | `password` |

### Sample User Account
| Field | Value |
|-------|-------|
| Email | `john@example.com` |
| Password | `password` |

---

## 🤖 YouTube Auto-Fetch (cron.php)

### Step 1 — Get a YouTube Data API v3 Key
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project → Enable **YouTube Data API v3**
3. Create credentials → **API Key**
4. Paste it in `includes/config.php`:
   ```php
   define('YOUTUBE_API_KEY', 'AIza...');
   ```

### Step 2 — Test Manually
```bash
php /var/www/html/documentaryhub/cron.php
```

### Step 3 — Schedule via Crontab (Linux)
```bash
crontab -e
```
Add this line to run daily at 6 AM:
```
0 6 * * * php /var/www/html/documentaryhub/cron.php >> /var/log/dh_cron.log 2>&1
```

**Channels monitored:**
- DW Documentary
- Free Documentary
- BBC Earth
- National Geographic
- PBS
- Absolute History

---

## 🗄️ Database Tables

| Table | Description |
|-------|-------------|
| `users` | Registered users (id, username, email, password_hash, role) |
| `categories` | Documentary categories (id, name, slug, icon) |
| `documentaries` | All documentaries (id, title, youtube_video_id, views, ...) |
| `favorites` | User watchlist entries |
| `watch_history` | User watch events with progress |

---

## ✨ Features Checklist

### Frontend
- [x] Amazon Prime Video-inspired dark UI (#0F171E)
- [x] Hero carousel with featured documentaries
- [x] Trending / New / Popular sections
- [x] Horizontal scroll rows (Netflix-style)
- [x] Responsive grid (mobile, tablet, desktop)
- [x] Category browsing + filtering
- [x] Full-text search with sort options
- [x] YouTube iframe embed player
- [x] Related documentaries sidebar
- [x] Lazy-loaded thumbnails
- [x] Dark mode by default

### User Features
- [x] Register / Login / Logout
- [x] Watch history (auto-tracked after 5s)
- [x] Continue watching with progress bar
- [x] Favorites / Watchlist (AJAX toggle)
- [x] User dashboard with tabs
- [x] Profile editing

### Admin Panel
- [x] Dashboard statistics (docs, users, views)
- [x] Add / Edit / Delete documentaries
- [x] YouTube thumbnail auto-preview
- [x] Manage categories with icons
- [x] User management (suspend / delete)
- [x] Featured & Trending content management

### Technical
- [x] PDO with prepared statements
- [x] password_hash() for passwords
- [x] XSS protection (htmlspecialchars)
- [x] Session-based authentication
- [x] AJAX for favorite toggling
- [x] YouTube auto-fetch cron script

---

## 🛡️ Security Notes

This is a **BCA/learning project**, not production-ready. For real deployment:

1. Change all default passwords
2. Add CSRF tokens to all forms
3. Use HTTPS
4. Set proper file permissions (755 dirs, 644 files)
5. Add rate limiting on login
6. Move `includes/config.php` outside web root
7. Restrict direct access to `includes/` and `admin/`

---

## 🎨 Color Theme

| Variable | Value | Usage |
|----------|-------|-------|
| `--bg-primary` | `#0F171E` | Page background |
| `--bg-secondary` | `#1A242F` | Cards, panels |
| `--bg-tertiary` | `#253345` | Hover states |
| `--accent` | `#00A8E1` | Buttons, links, highlights |
| `--gold` | `#F5A623` | Ratings, featured badge |

---

## 📚 Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons |
| Fonts | Bebas Neue (headings), Inter (body) |
| Backend | PHP 8.x |
| Database | MySQL / MariaDB |
| ORM | PDO with prepared statements |
| Video | YouTube Data API v3 (iFrame embed) |
| Auth | PHP Sessions + password_hash |

---

*Built as a BCA-level project. Not for commercial use.*
