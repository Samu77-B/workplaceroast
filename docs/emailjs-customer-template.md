# EmailJS: Auto reply → website enquirers

Template ID: **`template_ha8jne4`** (rename to **Workplace Roast — Enquiry auto reply**)

**Used when:** someone submits the **contact form** on the marketing site (`scripts/form-handler.js`, after Web3Forms succeeds).

**Recipient:** the person who enquired (`{{customer_email}}`).

**New order alerts to cafés** use a different template: [`emailjs-cafe-order-template.md`](emailjs-cafe-order-template.md) (`template_j08h25k`).

---

## EmailJS dashboard — routing

| Field | Value |
|-------|--------|
| **To Email** | `{{customer_email}}` |
| **From Name** | `Workplace Roast` |
| **From Email** | ✓ Default email address |
| **Reply To** | `info@workplaceroast.com` |

**Subject**

```text
Thanks for contacting Workplace Roast
```

---

## EmailJS dashboard — body (HTML)

```html
<div style="font-family: system-ui, sans-serif, Arial; font-size: 14px; color: #333; max-width: 560px;">
  <div style="font-size: 18px; font-weight: 600; color: #4A3728;">Workplace Roast</div>
  <div style="margin-top: 8px; font-size: 16px; font-weight: 600;">Thanks for your enquiry</div>

  <div style="margin-top: 24px; padding: 20px 0; border-width: 1px 0; border-style: dashed; border-color: #e0e0e0;">
    <p style="margin: 0 0 16px;">Hi {{customer_name}},</p>
    <p style="margin: 0 0 16px;">
      Thank you for getting in touch with Workplace Roast. We have received your message and will reply within 1–2 business days.
    </p>

    <p style="margin: 0 0 8px; font-weight: 600;">Your message</p>
    <p style="margin: 0 0 20px; padding: 12px; background: #f8f9fa; border-radius: 8px; white-space: pre-wrap;">{{message}}</p>

    <p style="margin: 0 0 8px; font-weight: 600;">Enquiry details</p>
    <p style="margin: 0 0 20px; padding: 12px; background: #f8f9fa; border-radius: 8px; white-space: pre-wrap;">{{enquiry_details}}</p>

    <p style="margin: 0;">Best regards,<br><strong>Workplace Roast</strong></p>
  </div>

  <div style="margin-top: 20px; font-size: 12px; color: #666;">
    <p style="margin: 0;">Website: <a href="https://workplaceroast.com" style="color: #D97C2E;">workplaceroast.com</a><br>
    Email: <a href="mailto:info@workplaceroast.com" style="color: #D97C2E;">info@workplaceroast.com</a></p>
  </div>
</div>
```

Remove Teas & Cs copy. Click **Save**.

---

## Variables sent from the website

| Variable | Content |
|----------|---------|
| `customer_email` | Submitter’s email (recipient) |
| `customer_name` | Submitter’s name |
| `message` | Enquiry message |
| `enquiry_details` | Plan interest + business name |
| `name` | `Workplace Roast` |

---

## Test

Submit [workplaceroast.com/#contact](https://workplaceroast.com/#contact) on the **live** site.

- You receive the lead via **Web3Forms**
- Submitter receives this **EmailJS** auto reply

---

## Two-template overview

| Template | ID | Who gets the email |
|----------|-----|-------------------|
| New order → café | `template_j08h25k` | Partner / café inbox |
| Enquiry auto reply | `template_ha8jne4` | Website enquirer |
