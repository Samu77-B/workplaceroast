# EmailJS: New order → café

Template ID: **`template_j08h25k`** (rename to **Workplace Roast — New order**)

**Used when:** a customer completes payment in the **PWA** (`pwa/index.html` → `sendOrderEmail`).

**Recipient:** café / partner inbox — set in `pwa/config.js` (`ORDER_NOTIFICATION_EMAIL` and `CAFE_ORDER_EMAILS`).

**Enquiry auto-reply** (website contact form) uses a different template: [`emailjs-customer-template.md`](emailjs-customer-template.md) (`template_ha8jne4`).

---

## EmailJS dashboard — routing

| Field | Value |
|-------|--------|
| **To Email** | `{{to_email}}` |
| **From Name** | `Workplace Roast Orders` |
| **From Email** | ✓ Default email address |
| **Reply To** | `{{customer_email}}` |

Remove `dem@teasandcs.com` and any fixed Teas & Cs **To** address.

**Subject**

```text
New order — {{customer_name}} · {{venue_name}}
```

---

## EmailJS dashboard — body (HTML)

```html
<div style="font-family: system-ui, sans-serif, Arial; font-size: 14px; color: #333; max-width: 560px;">
  <div style="font-size: 18px; font-weight: 600; color: #4A3728;">New order — Workplace Roast</div>
  <div style="margin-top: 6px; font-size: 13px; color: #666;">{{venue_name}}</div>

  <div style="margin-top: 20px; padding: 16px 0; border-width: 1px 0; border-style: dashed; border-color: #e0e0e0;">
    <p style="margin: 0 0 12px;"><strong>Customer:</strong> {{customer_name}}</p>
    <p style="margin: 0 0 12px;"><strong>Time:</strong> {{order_time}}</p>
    <p style="margin: 0 0 12px;"><strong>Total:</strong> £{{order_total}}</p>
    <p style="margin: 0 0 12px;"><strong>Delivery:</strong> {{delivery_option}}</p>
    <p style="margin: 0 0 12px;"><strong>Location:</strong> {{chair_number}}</p>
    <p style="margin: 0 0 12px;"><strong>Email:</strong> {{customer_email}}</p>
    <p style="margin: 0 0 16px;"><strong>Phone:</strong> {{customer_phone}}</p>

    <p style="margin: 0 0 8px; font-weight: 600;">Items</p>
    <pre style="margin: 0 0 16px; padding: 12px; background: #f8f9fa; border-radius: 8px; font-family: inherit; white-space: pre-wrap;">{{order_items}}</pre>

    {{#special_instructions}}
    <p style="margin: 0 0 8px; font-weight: 600;">Special instructions</p>
    <p style="margin: 0; padding: 12px; background: #fff8f2; border-radius: 8px; white-space: pre-wrap;">{{special_instructions}}</p>
    {{/special_instructions}}
  </div>

  <p style="margin-top: 16px; font-size: 12px; color: #888;">Sent from Workplace Roast ordering</p>
</div>
```

Click **Save**.

---

## Code configuration

In **`pwa/config.js`**:

- `ORDER_NOTIFICATION_EMAIL` — default inbox (demos, single-site)
- `CAFE_ORDER_EMAILS` — map `cafe_id` → partner email for multi-tenant production

EmailJS **To Email** must be `{{to_email}}` so the app can route per venue.

---

## Demo for café owners

1. EmailJS → **Workplace Roast — New order** → **Test It**
2. Sample values:

| Variable | Example |
|----------|---------|
| `to_email` | Your demo inbox |
| `venue_name` | Acme Hair Salon |
| `customer_name` | Alex Demo |
| `order_time` | 29 Sep 2026, 14:00 |
| `order_total` | 8.50 |
| `delivery_option` | Deliver to chair |
| `chair_number` | Chair 3 |
| `customer_email` | customer@example.com |
| `customer_phone` | 07700 900000 |
| `order_items` | 1× Flat White — £3.50<br>1× Latte (Oat) — £5.00 |
| `special_instructions` | Extra hot please |

3. Or place a **test order** on a demo PWA URL after deploy.

**Pitch line:** *“Every order hits your inbox instantly with items, total, and delivery details.”*

---

## Test checklist

- [ ] Template **To Email** = `{{to_email}}` (not a old client address)
- [ ] Test order on live PWA → email arrives at `CAFE_ORDER_EMAILS[cafe_id]`
- [ ] Contact form still uses `template_ha8jne4` only (see enquiry doc)
