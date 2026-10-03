# Changelog

All notable changes to this project are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/).

## [1.1.0]

### Added
- Layouts: cards, compact grid, list and table, with columns set separately for desktop, tablet and phone. Visitors can switch between grid and list, and the browser remembers their choice.
- Make and model filter modes: two dropdowns, one combined picker, a searchable field (ARIA combobox), or hidden. Counts can be shown and empty entries hidden.
- Filter selection and order (drag and drop), sidebar or top position, collapsed start, mobile off-canvas panel, and sliders for ranges.
- Visitor page-size selector, "Load more" and infinite scrolling (with crawlable links), and a choice of which sort options are offered.
- Card parts, badges (new, warranty, price reduced), monthly rate, image ratio, mini-gallery and hover effects.
- Vehicle detail pages on the site: gallery, specifications, equipment, dealer box, SEO title and description, canonical URL, Open Graph tags, schema.org `Car` JSON-LD and an XML sitemap.
- Optional download of descriptions and equipment during sync.
- Design presets (Classic, Minimal, Premium dark, Compact), "Use theme styles", shadows, and a live preview on the Design screen and in the shortcode builder.
- Gutenberg block and Elementor widget, both generated from the settings schema.
- Template overrides (`yourtheme/dealer-inventory/`) and new filters and actions.
- Setting for what happens when the plugin is deleted: keep the data (default) or remove everything.
- PHPUnit tests, a JavaScript smoke test, and GitHub Actions for phpcs, PHPUnit (PHP 8.1–8.4) and Plugin Check.

### Changed
- Filters set by a shortcode or block can no longer be widened by visitors. Range filters use the stricter value.
- The mobile filter panel is now a labelled modal dialog that traps focus and closes with Escape.
- Deleting the plugin keeps its data unless "Remove everything" is selected.

### Fixed
- A filter with fewer than two choices could leave an output buffer open.
- Template rendering now closes its output buffer when a template throws an error.
- The plugin header now links to the current repository.

### Migration
- 1.0 settings are mapped automatically:
  - `show_*` options become the ordered filter list;
  - card toggles become `card_fields`;
  - the image ratio moves to `image_ratio`;
  - old preset names map to the new presets.

## [1.0.0]

### Added
- First public release, based on a single-dealer build and rebuilt as a configurable, translatable plugin.
- Settings schema with site-wide defaults and per-shortcode overrides; Display screen; generated shortcode builder.
- Provider layer (AutoScout24 Switzerland) and storage that knows which connection each vehicle came from.
- Crawlable pagination, canonical and robots handling.
- German (DE, AT, CH), French and Italian translations.

### Fixed
- The "Newest" sort.
- Old vehicles stayed visible after the Seller ID changed.
- Settings broke when the secret contained quotes.
