# Super Dashboard (platform owner)

Manage every signed-up cafe, onboarding details, and trading health from one screen.

## URL and login

- **URL:** `https://workplaceroast.com/pwa/super-admin.html`
- **Username:** `admin`
- **Password:** `WorkplaceRoast2024` (same as catalog admin — change in `super-admin.html` and `admin.html` when you rotate it)

Also linked from the main site footer and from **Super Dashboard** in `admin.html`.

## What you can do

| Action | How |
|--------|-----|
| Add a cafe | **+ New cafe** — name, slug, plan, contact, notes |
| Edit a cafe | **Edit** on a card |
| Customer ordering URL | Set **URL slug** → customers use `/pwa/your-slug` |
| Cafe owner login | Share **access code** + password (`CafeOwner2024` default) at `cafe-admin.html` |
| Regenerate access code | Edit cafe → tick **Generate a new access code** |
| Reset owner password | Edit cafe → tick **Reset owner password to default** |

**Catalog admin** (`admin.html`) remains for products, categories, discounts, and orders across the platform.

## Cafe health

Each card shows a status, score (0–100), and recent activity:

| Status | Meaning |
|--------|---------|
| **Healthy** | Active, owner password set, orders in the last 7 days |
| **Attention** | No orders for more than 7 days |
| **At risk** | No orders for more than 30 days |
| **Setup** | No owner password, or no orders yet |
| **Inactive** | Marked inactive in the editor |

KPIs at the top count cafes, healthy venues, those needing attention, setup incomplete, and total 7-day orders.

Filter chips narrow the grid by status.

## First-time server setup

1. Upload `pwa/super-admin.html`, `pwa/api/_bootstrap.php`, `pwa/api/admin/corporate_clients.php`, `pwa/api/cafe-by-slug.php`, `pwa/api/migrate_super_dashboard.php`, and updated `pwa/.htaccess` + `pwa/config.js`.
2. Copy `pwa/api/config.example.php` → `pwa/api/config.php` with Hostinger DB credentials.
3. Ensure `$ADMIN_TOKEN` in `config.php` matches `CONFIG.ADMIN_TOKEN` in `pwa/config.js` (production: `teas2024` today).
4. Sign in to Super Dashboard → **Update database** (adds `slug`, `tier`, `contact_email`, `contact_name`, `notes` on `corporate_clients`).
5. Reload the page — cafe list should load from the API.

If the yellow error banner appears, the API or token is not wired correctly on the server.

## Slugs and routing

Apache rules in `pwa/.htaccess` map `/pwa/{slug}` → `index.html?cafe_slug={slug}`.  
`config.js` calls `cafe-by-slug.php` to resolve the cafe id, name, and tier.

Legacy demo paths `/pwa/acmehairsalon` and `/pwa/acmelawfirm` still work via fixed `cafe_id` rules.
