=== POI – Preorders for Importers (for WooCommerce) ===
Contributors: jesseleestringer
Tags: woocommerce, preorder, deposit, shipment, import
Requires at least: 6.4
Tested up to: 6.6
Requires PHP: 8.1
Stable tag: 0.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Shipment-based preorders for WooCommerce importers. Take deposits, manage inbound containers, and release stock manually when goods clear customs.

== Description ==

**POI – Preorders for Importers** is built for WooCommerce stores that import physical goods on long lead times — automotive parts, machinery, furniture, electronics, anything coming in by container.

Unlike traditional preorder plugins that attach a flag to a single product, POI is **shipment-centric**. You define an inbound shipment (PO #, container reference, expected arrival date), assign products to that shipment with allocated quantities and per-product deposits, and customers pre-order against that shipment. When the goods arrive and clear customs, you release the shipment manually — stock is added, customers are emailed, and orders move to Processing.

= Why shipment-based? =

If you import on a 6–12 week lead time, the same product might be in two shipments at once at different unit costs (USD/AUD fluctuation, supplier price changes). The shipment is the natural unit of inventory, not the product. Customers need to know **which** shipment they're pre-ordering against. Admins need to know **which** containers have been paid against and how much margin is locked in.

= Key features =

*   **Deposit checkout** — customer pays a configurable deposit (% or fixed) at checkout, balance invoiced separately when goods arrive. The deposit replaces the product price; it isn't a fee added on top.
*   **Per-shipment pricing** — same product can sell at $1,300 in shipment A and $1,420 in shipment B. The customer is locked into the price of the shipment they ordered against.
*   **Manual release only** — no cron, no auto-fire. Customs delays, biosecurity holds, port strikes, and manifest mismatches (ABC ordered, ABC-2 supplied) are common in importing. The release confirmation modal includes a pre-flight checklist forcing the admin to verify before stock is released.
*   **HPOS-only** (WooCommerce 9.0+) — written for the new High-Performance Order Storage API, no legacy `wp_posts` queries.
*   **Multi-shipment cascade** — when the earliest shipment sells out, the product page automatically rolls to the next. Optional per-product toggle lets the customer choose between shipments (currency lock-in, delayed-delivery use cases).
*   **Customer-facing display** — three drop-in shortcodes (`[preorder_status]`, `[preorder_stock]`, `[preorder_date]`) plus native Elementor Dynamic Tags. The archive "Add to Cart" button is replaced with "Pre-Orders Available" so customers always land on the product page where they can see shipment terms.
*   **Service / labour exclusion** — multiselect of product categories that should never be preorderable (installations, fitting fees, etc.).
*   **Stock log** — every preorder placed, cancelled, released, or manually adjusted is logged with user attribution. CSV export. Audit-trail safe even after a shipment is deleted.
*   **WP-CLI commands** — `wp wpi list / preview / release [--dry-run] [--yes]` for headless workflows.
*   **WC_Email integration** — three transactional emails (Customer Confirmation, Customer Released, Admin Notification) all manageable via WooCommerce → Settings → Emails.

= What it deliberately avoids =

*   No Action Scheduler usage. The plugin is fully synchronous — no background jobs.
*   No mixed-cart restrictions. Customers can mix preorder + in-stock items freely.
*   No quantity caps. Orders can exceed allocation (oversell); customers see real-time stock plus inbound figures and decide. The release flow flags shortfalls for manual resolution.

== Installation ==

1.  Upload the plugin folder to `/wp-content/plugins/` or install via Plugins → Add New.
2.  Activate the plugin through the **Plugins** screen in WordPress.
3.  Visit **WooCommerce → Settings → Preorders** to configure the loop badge text, archive button label, date format, excluded categories, and admin notification email defaults.
4.  Visit **WooCommerce → Settings → Emails** to configure the three POI transactional emails.
5.  Visit **WooCommerce → Shipments** to create your first shipment.

== Frequently Asked Questions ==

= Does this work without HPOS? =

No. The plugin targets WooCommerce 9.0+ with HPOS enabled. There is no legacy posts-mode fallback. Enable HPOS under **WooCommerce → Settings → Advanced → Features**.

= Can I auto-release a shipment when its due date arrives? =

No, by design. Imports are too unpredictable — customs holds, biosecurity inspections, port strikes, and manifest mismatches are common. Release is always manual and gated by a confirmation modal that asks you to verify the goods, the SKUs, and the quantities.

= What happens if I oversell? =

Customers can place orders beyond a shipment's allocated quantity. When you release, the plugin warns you about the shortfall but still moves all orders to Processing. You handle the shortfall manually (next shipment, refund, partial dispatch) — the plugin doesn't pretend to know what you want.

= Can a customer choose which shipment to order from? =

Yes. On any shipment line item, tick **Customer Can Choose**. If at least one item for the product has that flag, a shipment selector appears on the product page. Otherwise the cascade default applies (earliest shipment with remaining qty).

= Does it work with variable products? =

Currently the plugin treats `product_id` as a parent product. Per-variation allocations are not supported in this release.

= How do I display preorder info inside an Elementor template? =

Two options:
1.  Use the bundled shortcodes (`[preorder_status]`, `[preorder_stock]`, `[preorder_date]`) inside any Text widget.
2.  Use the **POI Preorders** group in Elementor's Dynamic Content picker — three native dynamic tags map to the same data.

= Can I exclude services from preorder? =

Yes. Under **Settings → Preorders → Eligibility**, multiselect any product categories that should be ineligible (e.g. *Fitting*, *Labour*, *Installation*). Products in those categories cannot be added as preorder line items.

= Will it work alongside other WooCommerce extensions? =

Tested with the WC core extensions and the standard checkout. Action Scheduler is intentionally not used by this plugin — useful if you've had AS conflicts with other plugins. Compatibility with subscription / bookings extensions has not been tested.

== Screenshots ==

1.  Shipments admin list with stock-impact preview before release.
2.  Shipment editor with product autocomplete, per-line deposit type, and customer-choice toggle.
3.  Release confirmation modal with the pre-flight customs/biosecurity/SKU checklist.
4.  Single product page showing the deposit-secured Pre-Order button, ETA, and stock breakdown.
5.  Stock Log with filterable history and CSV export.
6.  WooCommerce → Settings → Preorders configuration tab.

== Changelog ==

= 0.3.1 =
*   Polish: Elementor group renamed to **POI Preorders**; tags now POI Status / POI Stock / POI Arrival Date.
*   Polish: `[preorder_stock]` default reformatted as labelled rows separated by `<br />` for easier templating.
*   Polish: archive button CSS no longer uses `!important` — active theme styles take precedence.
*   New: `readme.txt` for WordPress.org submission.
*   Repo: `TODO.md`, `.editorconfig`, `.gitattributes`, GitHub Actions PHP-lint workflow.

= 0.3.0 =
*   New: archive add-to-cart replaced with **Pre-Orders Available** permalink button (configurable label).
*   New: atomic shortcodes `[preorder_status]`, `[preorder_stock]`, `[preorder_date]` for templates / Elementor.
*   New: Elementor Dynamic Tags under a **POI Preorders** group — Status, Stock, Arrival Date.
*   New: admin notification email is now a proper `WC_Email` subclass with full WC settings UI.
*   New: excluded-categories setting prevents service / labour categories being added as preorder line items.

= 0.2.0 =
*   Critical: editor no longer wipes `qty_preordered` on save (UPDATE-by-id rather than truncate-and-rebuild).
*   Critical: release query uses HPOS-friendly `include` instead of `post__in` (was returning all preorders).
*   Critical: stock log now correctly writes NULL for unbound `shipment_id`.
*   Critical: `_wpi_has_preorder` is set at order creation so the status filter actually fires.
*   New: customer-choice shipment picker on the product page.
*   New: product autocomplete (`wc-product-search`) in the editor.
*   New: delete-shipment action with safety guards.
*   New: WP-CLI commands — `wp wpi list / preview / release [--dry-run] [--yes]`.
*   New: stock-impact preview rendered inside the release modal.
*   New: shipment audit columns (`released_by`, `released_at`).
*   Polish: HPOS Orders list table now registers the `preorder` status correctly.
*   Polish: deprecated `add_submenu_page(null, …)` replaced by single-page dispatch.
*   Polish: cascade deletion + nullify on shipment removal preserves stock log audit trail.
*   Polish: pluralisation + translator comments throughout user-facing strings.
*   Polish: frontend CSS only loads on WC pages.
*   Plain-text email templates added.

= 0.1.0 =
*   Initial scaffold: shipment + line-item models, deposit cart override, manual release with manifest checklist, stock log, WC settings tab, customer + admin emails, original spec shortcodes.

== Upgrade Notice ==

= 0.3.0 =
Adds Elementor support and disables archive quick-add. Existing add-to-cart styling on shop pages will change — review your theme's button styling. New "Excluded categories" setting; review if you have service/labour products that should be ineligible for preorder.

= 0.2.0 =
Critical bug fixes: do upgrade. Editor-save data-loss bug and release-query HPOS bug are both fixed. Database schema bumps to v2 — migrations run automatically on activation.
