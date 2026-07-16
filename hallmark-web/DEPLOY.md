# Running & Deploying Hallmark Web

## A. Run on your own computer (to test locally)

You need **PHP 8.1+** with the `gd`, `pdo_sqlite`, and `mbstring` extensions.

### 1. Get the code
```bash
git clone -b claude/client-2-pdf-analysis-f2mw4f https://github.com/Karthikeyan49/hallmark.git
cd hallmark/hallmark-web
```

### 2. Start the built-in PHP server
```bash
php -S localhost:8000 -t public
```

### 3. Open it
Go to **http://localhost:8000/** in your browser and sign in:
- Username: `admin`
- Password: `admin123`

The SQLite database and admin account are created automatically on the first
page load. That's it — no build step, no Composer install.

### Don't have PHP?
- **Windows:** install [XAMPP](https://www.apachefriends.org/) (bundles PHP 8 + gd + sqlite), then run the `php -S` command above from `hallmark-web/` using XAMPP's `php.exe`.
- **macOS:** `brew install php` then run the command.
- **Linux:** `sudo apt install php php-gd php-sqlite3 php-mbstring` then run the command.

---

## B. Deploy to Hostinger (shared hosting)

This app is a good fit for Hostinger shared hosting (Apache/LiteSpeed + PHP).

1. **Set PHP version** to 8.1 or newer — hPanel → *Advanced → PHP Configuration*.
   In the *PHP extensions* tab make sure **gd**, **pdo_sqlite**, and **mbstring**
   are enabled (they usually are by default).

2. **Upload** the contents of the `hallmark-web/` folder to your hosting account
   (via hPanel *File Manager* or FTP). You can put it under `~/domains/<your-domain>/`.

3. **Point the document root to `public/`.** This is the most important step —
   hPanel → *Websites → Advanced → Website root / Document Root* → set it to the
   `public` subfolder. This keeps `src/`, `config/`, and `storage/` off the web.
   (The included `public/.htaccess` handles clean URLs on Apache/LiteSpeed.)

4. **Make storage writable:**
   ```bash
   chmod -R 755 storage
   ```
   The SQLite DB and admin account auto-create on first visit.

5. **Change the admin password before going live.** Either set environment
   variables `HALLMARK_ADMIN_USER` / `HALLMARK_ADMIN_PASS`, or edit
   `config/config.php` and change `admin_password` from the default `admin123`.
   (If you already loaded the site once, delete `storage/database.sqlite` so the
   new password re-seeds.)

### Optional: enable the paid AI model tier
Leave it off and every image costs ₹0. To turn on generative model enhancement
(kept under ₹1/image), set `HALLMARK_AI_PROVIDER=flux` and provide `FAL_KEY`.
Until then the app uses the free `passthrough` provider.

### Note on the database
SQLite is perfect for this single-admin prototype. If you later need multiple
users or higher volume, switch to MySQL (included with Hostinger) — the
`src/Core/Database.php` layer is the only file that changes.
