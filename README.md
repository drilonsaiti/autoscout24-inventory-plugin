# Dealer Inventory for AutoScout24

A WordPress plugin that shows a car dealer's AutoScout24 stock on their own site. Listings are synced into the WordPress database on a schedule, so visitors browse a fast, server-rendered and crawlable list. Page views never call the AutoScout24 API.

The user-facing description, FAQ and changelog are in [`readme.txt`](readme.txt) (WordPress.org format). Release notes are in [`CHANGELOG.md`](CHANGELOG.md).

- **Requires:** WordPress 6.5+, PHP 8.1+ (tested up to PHP 8.4). Elementor is optional.
- **Market:** AutoScout24 Switzerland. Other markets can be added through the provider layer.
- **Translations:** German (DE, AT, CH), French, Italian.

## Using it

1. Activate the plugin and enter the Client ID, Client Secret and Seller ID under **Dealer Inventory → Connection**. Click **Test connection**, then **Sync now**.
2. Add the **Vehicle Inventory** block, the Elementor widget, or the shortcode to a page:

   ```
   [dealer_inventory]
   [dealer_inventory instance="home" per_page="3" sort="price_desc" show_filters="no" show_pagination="no"]
   [dealer_inventory layout="list" filter_position="sidebar" make_model_mode="searchable" range_style="slider"]
   [dealer_inventory make="bmw" query="body=suv&price_to=60000"]
   ```

   Every option has a site-wide default (**Display**, **Design**) that each block, widget or shortcode can override. **Help & Shortcode** lists all attributes and has a builder with live preview.

Credentials can also be set in `wp-config.php` with `DINV_CLIENT_ID`, `DINV_CLIENT_SECRET` and `DINV_SELLER_ID`.

## How it works

```
AutoScout24 API ──(WP-Cron sync)──▶ wp_dinv_vehicles ──▶ shortcode / block / widget (server-rendered)
                                                     └──▶ REST dinv/v1/vehicles (filter, sort, page; cacheable per sync version)
```

- `includes/class-schema.php` defines every setting once (type, default, allowed values, scope). Admin screens, shortcode attributes, the block, the Elementor widget and the builder are all generated from it. Precedence: instance attribute → site-wide setting → default.
- `includes/class-inventory.php` combines an instance's configuration with the visitor's request (filters, sort, page, page size, view). Shortcodes and REST requests use it, so both produce the same markup.
- `includes/providers/` holds the marketplace API clients (`Provider` interface, `AutoScout24_CH`).
- `includes/class-detail.php` adds vehicle detail pages: a rewrite endpoint (`/page/vehicle/12345-slug/`), SEO tags, JSON-LD and a sitemap provider.
- `public/js/inventory.js` is a dependency-free progressive enhancement. Without it, filters submit as GET forms and pagination uses links.
- Front-end assets load only on pages that contain an inventory. Static inventories (no filters, sort or pagination) load no JavaScript.

## Customizing

**Templates.** Copy any file from `templates/` to `yourtheme/dealer-inventory/` (same sub-folder) to override it:

```
inventory.php            single-vehicle.php
parts/header.php         parts/toolbar.php        parts/filters.php
parts/filter-make.php    parts/filter-range.php   parts/results.php
parts/vehicle-media.php  parts/vehicle-title.php  parts/vehicle-price.php  parts/vehicle-button.php
loop/card.php            loop/grid.php            loop/list.php            loop/table-row.php
```

**Filters**

| Hook | Use |
| --- | --- |
| `dinv_inventory_config` | Change the resolved configuration of an inventory. |
| `dinv_search_filters` | Change the active search filters. |
| `dinv_card_data` | Change a vehicle's view model before it is rendered. |
| `dinv_vehicle_badges` | Add or remove badges. |
| `dinv_vehicle_url` | Change where a vehicle links to. |
| `dinv_detail_data`, `dinv_detail_specs` | Change the detail page view model and specification rows. |
| `dinv_vehicle_json_ld` | Change the schema.org data of a detail page. |
| `dinv_design_tokens` | Change the CSS custom properties. |
| `dinv_template`, `dinv_template_args` | Change the template file or its variables. |
| `dinv_format_price` | Format prices yourself. |
| `dinv_schema_fields` | Add or change settings. |
| `dinv_providers` | Register another marketplace provider. |
| `dinv_detail_batch_size` | Number of vehicles whose details are downloaded per sync (default 25). |

**Actions:** `dinv_before_inventory`, `dinv_after_inventory`, `dinv_before_detail`, `dinv_after_detail`, `dinv_inventory_synced`.

## Development

Development tools are not part of the plugin zip. Files listed in [`.distignore`](.distignore) are excluded from the build.

```bash
composer install       # WPCS, PHPCompatibilityWP, PHPUnit, wp-phpunit
npm ci                 # jsdom (DOM for the JavaScript smoke test)

composer lint          # phpcs (WordPress standard + PHP 8.1+ compatibility)
npm run test:js        # filter UI smoke test (node:test + jsdom)
```

**PHPUnit** needs a WordPress test database. Point `WP_PHPUNIT__TESTS_CONFIG` to a `wp-tests-config.php` (see `.github/workflows/ci.yml` for an example), then run `composer test`.

The test suites cover:

- the repository (search, filters, sorting, facets, sync bookkeeping);
- settings sanitizing and precedence;
- the 1.0 → 1.1 migration;
- shortcode and request parsing, and rendering;
- the REST endpoints;
- detail pages and JSON-LD;
- WCAG contrast of all design presets;
- uninstall.

The JavaScript fixtures in `tests/js/fixtures/` are generated from the real templates. To regenerate them on a site with vehicles, run `wp eval-file tests/js/build-fixtures.php`.

**CI** (GitHub Actions) runs these jobs:

- phpcs;
- PHPUnit on PHP 8.1–8.4 with the latest WordPress, plus PHP 8.1 with WordPress 6.5;
- the JavaScript smoke test;
- Plugin Check on the built plugin.

## License

GPL-2.0-or-later. This plugin is not affiliated with, endorsed by or sponsored by AutoScout24 or SMG Swiss Marketplace Group.
