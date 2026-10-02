=== Dealer Inventory for AutoScout24 ===
Contributors: drilonsaiti
Tags: car dealer, vehicle inventory, autoscout24, car listings, dealership
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show your AutoScout24 vehicle stock on your own website: synced locally, fast, searchable, crawlable and styleable without code.

== Description ==

Dealer Inventory for AutoScout24 brings the cars you list on AutoScout24 to your own WordPress site. Listings are synchronized into your WordPress database on a schedule, so visitors browse a fast, server-rendered list that search engines can crawl. Visitors never wait for the AutoScout24 API.

**Features**

* Automatic synchronization (every 15 minutes to every 12 hours, or once a day at a fixed time) plus "Sync now".
* Layouts: cards, compact grid, list and table; columns per desktop, tablet and phone; optional grid / list switch that remembers the visitor's choice.
* Vehicles per page with an optional visitor selector; pagination as page numbers, "Load more" or infinite scrolling (always with crawlable links).
* Make and model as two dropdowns, one combined picker or a searchable field, with live counts; empty makes and models can be hidden.
* Choose the filters and drag them into order; filters above the results or in a sidebar, collapsible, as an off-canvas panel on phones; ranges as fields or sliders.
* Sorting by newest, price, mileage, year, power or make; choose which sort options are offered.
* Card parts, badges (new, warranty, price reduced), monthly rate, image ratio, mini-gallery and hover effects.
* Optional vehicle detail pages on your own site with photo gallery, specifications, equipment, SEO title and description, Open Graph tags, schema.org Vehicle data and an XML sitemap.
* Crawlable pagination with real links, self-referencing canonical URLs and noindex for filtered result pages.
* Shareable result URLs (filters, sort and page in the URL), multiple independent inventories on one page.
* Gutenberg block, Elementor widget and a shortcode builder, all with live preview; every option can be set site-wide and overridden per inventory.
* Design presets (Classic, Minimal, Premium dark, Compact) or "Use theme styles", colors, radius, spacing, shadows, live preview.
* Template overrides: copy any file from templates/ to yourtheme/dealer-inventory/. Hooks and filters for data, markup and SEO output.
* Prices, numbers and units formatted for the visitor's language (for example "CHF 59’900", "59.900 €", PS / ch / CV / kW).
* Translations included: German (Germany, Austria, Switzerland), French and Italian. Works with TranslatePress, WPML and Polylang.
* Lightweight: CSS and JavaScript load only on pages with an inventory, and static blocks (for example "three newest cars" on the homepage) load no JavaScript at all.
* Works with the block editor, classic editor and Elementor (own widget, Shortcode or HTML widget).

**Markets**

Version 1.0 supports **AutoScout24 Switzerland** (autoscout24.ch). The plugin is built around a provider layer so other AutoScout24 countries can be added.

**What you need**

API credentials (Client ID and Client Secret) and your Seller ID from AutoScout24. AutoScout24 issues API access to dealers on request; contact your AutoScout24 account manager or customer service.

This plugin is not affiliated with, endorsed by or sponsored by AutoScout24 or SMG Swiss Marketplace Group. AutoScout24 is a trademark of its owner.

== External services ==

This plugin connects to services of AutoScout24 Switzerland, operated by SMG Swiss Marketplace Group AG. It only connects after you enter your own API credentials.

**AutoScout24 API (api.autoscout24.ch)**

* What it is used for: downloading your own vehicle listings and your public dealer profile so they can be shown on your site.
* When: during scheduled or manual synchronization, and when you click "Test connection". Visitors' page views never call the API.
* What is sent: your Client ID and Client Secret (to obtain an access token), your Seller ID and the requested language. No visitor data is sent.

**AutoScout24 image CDN (images.autoscout24.ch) and listing pages (www.autoscout24.ch)**

* Vehicle photos are loaded by the visitor's browser directly from the AutoScout24 image server, and vehicle links point to the listing on autoscout24.ch. Like any embedded image, this sends the visitor's IP address and browser information to that server.

AutoScout24 terms of use: https://autoscout24.ch/de/legal/gtc
Privacy policy of SMG Swiss Marketplace Group: https://privacy.swissmarketplace.group/de/

You are responsible for using the AutoScout24 API in line with your agreement with AutoScout24. Mention the image server in your site's privacy policy; the plugin adds suggested text under Settings → Privacy.

== Installation ==

1. Install and activate the plugin.
2. Go to **Dealer Inventory → Connection**, enter Client ID, Client Secret and Seller ID, save and click **Test connection**.
3. Click **Sync now** (or wait for the schedule).
4. Add `[dealer_inventory]` to a page. Use **Dealer Inventory → Help & Shortcode** to build a custom shortcode.

Credentials can also be defined in `wp-config.php`:

`define( 'DINV_CLIENT_ID', '…' );`
`define( 'DINV_CLIENT_SECRET', '…' );`
`define( 'DINV_SELLER_ID', 12345 );`

== Frequently Asked Questions ==

= Where do I get the API credentials? =

From AutoScout24. Ask your AutoScout24 account manager or customer service for API access for your own website. The plugin cannot create credentials for you.

= Does every page view call AutoScout24? =

No. Listings are copied into your database by the scheduled synchronization. Visitors only read that local copy. Only the photos are loaded from the AutoScout24 image server.

= How often is the inventory updated? =

As often as you choose under **Synchronization**: from every 15 minutes to every 12 hours, or once a day at a set time. WordPress runs scheduled tasks when the site is visited; for exact timing, trigger `wp-cron.php` from a server cron job.

= Can I show a few cars on the homepage? =

Yes, for example: `[dealer_inventory instance="home" per_page="3" sort="price_desc" show_filters="no" show_sort="no" show_count="no" show_header="no" show_pagination="no" url_state="no"]`. Such blocks are fully server-rendered and load no JavaScript.

= Can I pre-filter an inventory, for example only SUVs or one make? =

Yes: `[dealer_inventory make="bmw" body="suv"]` or `[dealer_inventory query="make=bmw&price_to=50000"]`.

= Does it work with Elementor? =

Yes. Use the "Vehicle Inventory" widget, or the Shortcode or HTML widget. Elementor's element cache is told that inventory widgets are dynamic, so lists stay current.

= Does it work with page caching? =

Yes. If a cached page is older than the latest synchronization, the list refreshes itself in the background. List requests are cacheable by CDNs because they carry the inventory version.

= Is it multilingual? =

Interface texts follow the site or visitor language (TranslatePress, WPML and Polylang are detected). The text of the listings themselves is downloaded in one language, set under Connection.

= Will AutoScout24 Germany, Austria or Italy be supported? =

They use a different AutoScout24 API. The plugin is prepared for additional markets; support depends on access to that API.

= What happens when I delete the plugin? =

Deleting the plugin removes its database tables, settings and scheduled events.

== Screenshots ==

1. Card grid with filters and sorting.
2. List layout.
3. "More filters" dialog.
4. Display settings: site-wide defaults for every inventory.
5. Design settings with presets and preview.
6. Shortcode builder.

== Changelog ==

= 1.1.0 =
* New: layouts (cards, compact grid, list, table), columns per breakpoint, grid / list switch.
* New: make / model modes (separate, combined, searchable), counts and hiding of empty entries.
* New: filter selection and order, sidebar position, mobile filter panel, range sliders.
* New: per-page selector, "Load more" and infinite scrolling, configurable sort options.
* New: badges, monthly rate, mini-gallery, hover effects.
* New: vehicle detail pages with structured data and sitemap.
* New: design presets, "Use theme styles", live previews, template overrides.
* New: Gutenberg block and Elementor widget.

= 1.0.0 =
* First public release, based on a single-dealer build: rebuilt as a configurable, translatable plugin.
* Settings schema with site-wide defaults and per-shortcode overrides; new Display screen; generated shortcode builder.
* Provider layer (AutoScout24 Switzerland) and connection-aware storage.
* Crawlable pagination, canonical and robots handling.
* Fixed "Newest" sort, stale vehicles after changing the Seller ID, settings with quotes in the secret.
* Cacheable list requests, fewer requests per page view, one query per page of listings during sync.
* Keyboard-accessible make picker and screen-reader announcements.
* German, Swiss German, Austrian German, French and Italian translations.

== Upgrade Notice ==

= 1.1.0 =
Adds layouts, detail pages, a block and an Elementor widget. Existing settings are migrated automatically.

= 1.0.0 =
First public release.
