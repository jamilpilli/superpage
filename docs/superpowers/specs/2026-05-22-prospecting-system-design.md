# Prospecting System — Design Spec
**Date:** 2026-05-22
**Status:** Awaiting implementation

---

## Overview

A system for admins to create client sites in bulk (or one at a time) as a prospecting tool. The admin creates the site, the client receives login credentials via WhatsApp + email, and the first year of hosting is free. After 1 year, the client pays to continue.

---

## Entry Points (both live in the Hub)

| Page | Description |
|---|---|
| `/hub/create_client.php` | Single wizard — 1 client at a time with full personalisation |
| `/hub/import_clients.php` | Bulk CSV import → draft queue → select & send |

Both share the same site-creation logic (a shared function/service).

---

## Single Wizard — `/hub/create_client.php`

### Step 1 — Client Info
| Field | Required | Notes |
|---|---|---|
| `name` | Yes | Business name |
| `slug` | Yes | URL: `superpage.co.uk/{slug}` |
| `email` | At least one of email/phone | Client login email |
| `phone` | At least one of email/phone | WhatsApp number |

### Step 2 — Site Personalisation
All fields optional — auto-generated if empty.

| Field | Default if empty |
|---|---|
| `hero` | `"{name} — welcome to our website"` |
| `about` | Generic paragraph based on category |
| `service1–4` | Generic service names from category (e.g. "Our Services", "What We Offer") |
| `color` | `#685ef7` (Superpage purple) |
| `category` | `other` |

### Step 3 — Review & Send
- Summary of what will be created
- Preview of WhatsApp message + email
- Button: **Create Site & Notify Client**

---

## Bulk CSV Import — `/hub/import_clients.php`

### CSV Format

```
name,slug,email,phone,hero,about,service1,service2,service3,service4,color,category
```

**Required per row:** `name`, `slug`, and at least one of `email`/`phone`.
**Everything else:** optional, auto-generated if empty.

### Flow

1. **Upload CSV** — drag & drop or file picker
2. **Preview table** — shows all rows, flags errors (duplicate slug, missing required fields)
3. **Generate Drafts** — creates all valid rows as `draft` sites (no notifications sent yet)
4. **Prospect Queue** — list of draft sites with checkboxes; admin can edit or preview each before sending
5. **Send Selected** — fires creation + notification for selected drafts

---

## Site Creation Logic (shared)

When a site is created (single or bulk), the system:

1. **Creates user account**
   - Email = provided email (or `{slug}@superpage.co.uk` placeholder if no email)
   - Password = `Welcome{Year}!` (e.g. `Welcome2026!`) — shown in review step
   - Role = `client`

2. **Creates site** with status = `active` (single) or `draft` (bulk, until sent)

3. **Creates blocks** in order:
   - `header` — business name as logo text
   - `hero` — name + hero text + generic category background image
   - `about` — about text
   - `services` — up to 4 items; names from CSV or generic; photos from category stock set
   - `contact` — pre-filled with email/phone
   - `footer` — business name

4. **Sets site design**
   - `primary_color` = provided color or `#685ef7`
   - `title_font` = `Plus Jakarta Sans`
   - `text_font` = `Inter`
   - `button_style` = `rounded`

5. **Creates subscription** — 1 year free, expires `created_at + 365 days`

6. **Fires n8n webhook** (only when sending, not on draft creation)
   ```json
   {
     "event": "prospect_site_created",
     "business_name": "Lemon Blue",
     "site_url": "https://superpage.co.uk/lemonblue",
     "email": "lb@gmail.com",
     "phone": "+447700900000",
     "password": "Welcome2026!",
     "has_email": true,
     "has_phone": true
   }
   ```
   n8n routes: if `has_phone` → send WhatsApp via Evolution API; if `has_email` → send email.

---

## Category Stock Content

Each category has:
- A set of **generic service names** (4 names)
- A **hero background image URL** (Unsplash or local stock)
- A **generic about paragraph**

| Category | Services |
|---|---|
| `marketing` | Social Media, Brand Design, Paid Ads, SEO |
| `restaurant` | Dine In, Takeaway, Catering, Events |
| `health` | Consultation, Treatments, Wellness, Nutrition |
| `construction` | Renovation, New Build, Repairs, Surveying |
| `retail` | In-Store Shopping, Online Orders, Gift Cards, Returns |
| `professional` | Consultation, Advisory, Support, Training |
| `other` | Our Services, What We Offer, How We Work, Get In Touch |

---

## Database Changes

### `sites` table
- Add `status` value `draft` (alongside existing `active`, `inactive`)

### `subscriptions` table (new or existing)
- `site_id`, `plan` = `free_year`, `started_at`, `expires_at` = started_at + 365 days

### `prospect_log` table (new)
- `id`, `site_id`, `notified_at`, `notified_via` (whatsapp/email/both), `admin_id`
- Tracks when and how the client was notified

---

## n8n Webhook Config

- Webhook URL stored in `.env` as `N8N_PROSPECT_WEBHOOK_URL`
- n8n workflow: receives payload → checks `has_phone` → sends WhatsApp via Evolution API → checks `has_email` → sends email
- WhatsApp message template (in Portuguese):
  > "Olá! Preparamos a página da *{business_name}*. Acesse: {site_url} | Login: {email} | Senha: {password} 🎁 1 ano de hospedagem grátis!"

---

## Out of Scope (Phase 2)

- Renewal reminders / payment flow after 1 year
- Additional services (email marketing, etc.)
- Client-facing onboarding checklist
- Analytics on prospecting conversion rate
