# TODO — pre-WP.org submission

Items deferred from the v0.3.1 review. None block a private install on Shotgun Performance staging.

## Before submitting to WordPress.org

- [ ] Register a `wordpress.org` username and update `Contributors:` field in `readme.txt` (currently `jesseleestringer`).
- [ ] Capture screenshots from a real WC + Elementor install and add to `/assets/` (PNG/JPG) at the repo root:
  - [ ] `screenshot-1.png` — Shipments admin list with stock-impact preview button
  - [ ] `screenshot-2.png` — Shipment editor (autocomplete, deposit type, customer-choice toggle)
  - [ ] `screenshot-3.png` — Release confirmation modal with pre-flight checklist
  - [ ] `screenshot-4.png` — Single product page (Pre-Order button + ETA + stock breakdown)
  - [ ] `screenshot-5.png` — Stock Log with filters + CSV export
  - [ ] `screenshot-6.png` — Settings → Preorders configuration tab
- [ ] Bump `Tested up to:` in `readme.txt` to the current WP version at submission time.
- [ ] Confirm WP.org plugin title at submission: **POI – Preorders for Importers (for WooCommerce)** (slug `woocommerce-preorders-for-importers` is free as of 2026-04-29).

## Functional / nice-to-have

- [ ] Variable products (per-variation allocations) — not in v0.3.x; SP doesn't use variations.
- [ ] CSV import for shipment line items (paste a manifest, get a draft shipment).
- [ ] v2 manifest arrival screen: Expected vs Delivered vs Pre-orders vs Current Stock vs Final Stock with overrides; pre/post-receipt log snapshots.
- [ ] Product substitution handling (ABC ordered → ABC-2 delivered).

## Engineering

- [ ] GitHub Action to run `php -l` on every push (catches syntax errors before merging).
- [ ] PHPCS config matching WordPress coding standards.
- [ ] Optional: a GitHub Action that builds the `.zip` artifact for WP.org submission on tagged releases.
