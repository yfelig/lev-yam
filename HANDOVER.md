# לב ים — Project Handover

## What This Is

A Kickstarter-style Hebrew crowdfunding landing page for **לב ים (Lev Yam)** — an ancient fishermen's structure on an isolated beach on the Israeli coastal plain. Built as a static mockup + production-ready PHP files for WordPress/GoDaddy deployment.

---

## Current State

- **Mockup (GitHub Pages):** https://yfelig.github.io/lev-yam/
- **GitHub repo:** https://github.com/yfelig/lev-yam (account: yfelig)
- **Local path:** `/Users/yairfelig/projects/lev-yam/`
- **Status:** Design complete, mockup live. NOT yet deployed to production WordPress. Grow Payments credentials NOT yet filled in.

---

## File Structure

```
lev-yam/
├── index.html          ← Static mockup (GitHub Pages) — what's live now
├── mockup.html         ← Same as index.html (kept as alias)
├── levyam-fund.php     ← Production PHP page (upload to WordPress root)
├── levyam-webhook.php  ← Grow Payments webhook handler
├── levyam-data.json    ← Progress counter {"total":0,"backers":0}
├── HANDOVER.md         ← This file
└── assets/
    ├── logo.png              ← Transparent PNG logo (blue + orange)
    ├── logo.jpeg             ← Old logo with white bg (unused, can delete)
    ├── hero-video.mp4        ← Hero background video
    ├── hero-workspace.jpeg   ← Person at laptop facing the sea
    ├── img-table-sunset.jpeg ← Wooden table at golden hour
    ├── img-founders.jpeg     ← Two men talking at the fishing boats
    ├── img-bar-counter.jpeg  ← Man at rustic wooden bar
    ├── img-woman-reading.jpeg← Woman reading in the space
    ├── img-extra1.jpeg       ← Extra photo
    └── img-extra2.jpeg       ← Extra photo
```

---

## Design System

**Font:** Frank Ruhl Libre (Hebrew serif, Levantine/Mediterranean feel — not Rubik/Inter AI slop)

**Colors:**
| Variable | Hex | Used for |
|---|---|---|
| `--blue` | `#3B9BC8` | Buttons, accents, tier prices |
| `--blue-dk` | `#1A5276` | Dark blue text |
| `--blue-nav` | `#1D3A52` | Nav, dark sections |
| `--orange` | `#F5952A` | Primary CTA, highlights, popular badge |
| `--white` | `#FFFFFF` | Backgrounds |
| `--blue-lt` | `#EBF6FF` | Light section background |

**Buttons:** Square (`border-radius: 0`), border+fill style, hover inverts to outline. No pills, no shadows.

**Label chips:** Underline only (no background capsule).

---

## Page Sections (top → bottom)

1. **Nav** — Logo + "הצטרפו לגיוס" button
2. **Hero** — Full-screen video, logo, headline, 2 CTAs
3. **Progress Band** — ₪X raised / ₪250,000 goal / N backers / animated progress bar
4. **Wave divider** — Orange zigzag (echoes logo)
5. **Story** — Photo + 2-paragraph text
6. **Tiers** — 4 cards (₪180 / ₪550 / ₪1,800 / ₪5,000)
7. **Gallery** — 7 photos, auto-scroll
8. **About** — Founders photo + text
9. **CTA Band** — Logo + final call to action
10. **Footer** — Logo + email

---

## Backer Tiers

| Icon | Name | Price | Key Perk |
|---|---|---|---|
| 🐚 | חבר הים | ₪180 | שם בקיר המייסדים |
| ⚓ | עוגן | ₪550 | יום עבודה על החוף |
| 🧭 | קברניט | ₪1,800 | 3 ימים + אירוע פתיחה (**פופולרי**) |
| ⛵ | בעל הסירה | ₪5,000 | חודשיים גישה + לוח הכרה נצחי |

---

## Payment Flow (Grow Payments)

```
Click tier → Modal (name/email/phone) → AJAX POST to levyam-fund.php?action=create_payment
→ PHP calls Grow API → returns payment URL → redirect to Grow hosted page
→ After payment → Grow redirects to levyam-fund.php?status=success
→ Grow webhook → levyam-webhook.php → updates levyam-data.json
→ Progress bar updates on next page load
```

Grow API docs: https://grow-il.readme.io/

---

## To Deploy to Production (TODO)

### Step 1 — Fill in Grow credentials
Open `levyam-fund.php`, find the CONFIG block at the top, fill in:
```php
define('GROW_PAGE_CODE', 'YOUR_PAGE_CODE');
define('GROW_USER_ID',   'YOUR_USER_ID');
define('GROW_API_KEY',   'YOUR_API_KEY');
```

### Step 2 — Upload to GoDaddy
Via cPanel File Manager, upload to `public_html/`:
- `levyam-fund.php`
- `levyam-webhook.php`
- `levyam-data.json`
- `assets/` folder (all files)

### Step 3 — Set file permissions
`levyam-data.json` needs write permission: chmod 664

### Step 4 — Configure Grow dashboard
- Webhook URL: `https://[domain]/levyam-webhook.php`
- Success redirect: `https://[domain]/levyam-fund.php?status=success`
- Cancel redirect: `https://[domain]/levyam-fund.php?status=cancel`

### Step 5 — Test
Make a small real payment, verify:
- [ ] Payment completes
- [ ] Webhook fires → `levyam-data.json` updates
- [ ] Progress bar reflects new total on reload

---

## Known Issues / Next Steps

- [ ] **Grow credentials** — not yet configured (placeholder values in PHP)
- [ ] **WordPress deployment** — not yet uploaded to GoDaddy
- [ ] **Domain redirect** — point the domain (or a page) to `levyam-fund.php`
- [ ] **Real progress data** — `levyam-data.json` currently at `{"total":0,"backers":0}`
- [ ] **Email contact** — `info@levyam.co.il` in footer is placeholder — update to real address
- [ ] **Social links** — footer has no social links yet
- [ ] **`assets/logo.jpeg`** — old logo with white background, can be deleted
- [ ] Consider adding a countdown timer or deadline to create urgency

---

## How to Resume Next Session

1. Open `index.html` locally to see current design: `open /Users/yairfelig/projects/lev-yam/index.html`
2. Live mockup: https://yfelig.github.io/lev-yam/
3. To push changes: switch to `yfelig` account → `git add . && git commit && git push` → switch back to `yairUG`
4. When ready to deploy to WordPress: follow "Deploy to Production" steps above
