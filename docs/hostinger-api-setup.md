# Hostinger: set up `pwa/api/config.php`

The Super Dashboard calls `https://workplaceroast.com/pwa/api/...`.  
Git deploy **does not** upload `config.php` (secrets stay off GitHub). You must create it on the server once.

## 1. Get MySQL details from Hostinger

1. Log in to [Hostinger hPanel](https://hpanel.hostinger.com).
2. **Websites** → **workplaceroast.com** → **Databases** → **Management** (or **phpMyAdmin**).
3. Note:
   - **Database name** — should be `u556329104_workplaceroast` (or similar).
   - **MySQL username** — often `u556329104_…`
   - **MySQL password** — reset if you do not have it.
   - **Host** — on shared hosting use **`localhost`**.

## 2. Create `config.php` on the server

### Option A — File Manager (easiest)

1. hPanel → **Files** → **File Manager**.
2. Open **`public_html/pwa/api/`** (path may be `public_html/pwa/api` if the site lives at `/pwa`).
3. If **`config.example.php`** is there, click it → **Copy** → name the copy **`config.php`**.
4. **Edit** `config.php` and set:

```php
$DB_HOST = 'localhost';
$DB_NAME = 'u556329104_workplaceroast';   // your exact DB name
$DB_USER = 'u556329104_youruser';        // from step 1
$DB_PASS = 'your_mysql_password';
$ADMIN_TOKEN = 'teas2024';
```

5. **Save**.

### Option B — Upload from your PC

1. On your machine, open `website/pwa/api/config.php` (created from the example).
2. Replace `REPLACE_WITH_MYSQL_USERNAME` and `REPLACE_WITH_MYSQL_PASSWORD`.
3. Upload via File Manager or FTP to **`public_html/pwa/api/config.php`**.

## 3. Verify

Open in a browser:

**https://workplaceroast.com/pwa/api/health.php**

You want JSON like:

```json
{"ok":true,"database":"connected","corporate_clients_table":true}
```

| Response | Fix |
|----------|-----|
| `config: missing` | `config.php` not in `pwa/api/` or wrong folder |
| `database: error` | Wrong user, password, or database name |
| `corporate_clients_table: false` | Import DB or run migrations; table missing |

## 4. Super Dashboard

1. Go to **https://workplaceroast.com/pwa/super-admin.html**
2. Log in (`admin` / `WorkplaceRoast2024`).
3. Click **Update database** once (adds slug/tier/contact columns).
4. The red error toast should disappear and cafes should load.

## 5. Token must match

In **`pwa/config.js`** (on the server), production uses:

```js
ADMIN_TOKEN: 'teas2024'
```

That must equal **`$ADMIN_TOKEN`** in `config.php`. If you change one, change both.

## Security

- Never commit `config.php` to GitHub (it is in `.gitignore`).
- Do not share `config.php` in chat or email.
