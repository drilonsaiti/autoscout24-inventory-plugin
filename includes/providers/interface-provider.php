<?php
/**
 * Marketplace provider contract.
 *
 * @package DealerInventory
 */

namespace DealerInventory\Providers;

use DealerInventory\Connection;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Everything that is specific to one marketplace API lives behind this
 * interface: authentication, listing download, field normalization, public
 * listing URLs, image CDN handling and market defaults.
 *
 * The rest of the plugin works only with normalized vehicle rows (the
 * columns of the local vehicles table), so adding another market means
 * adding one provider class and registering it with the
 * "dinv_providers" filter.
 */
interface Provider {

	/**
	 * Stable machine id, stored in settings. Example: "autoscout24_ch".
	 */
	public function id(): string;

	/**
	 * Human readable name shown in the admin.
	 */
	public function label(): string;

	/**
	 * ISO 3166-1 alpha-2 country of the marketplace.
	 */
	public function country(): string;

	/**
	 * ISO 4217 currency of listing prices.
	 */
	public function currency(): string;

	/**
	 * Two-letter content languages the API can return, first is the default.
	 *
	 * @return string[]
	 */
	public function languages(): array;

	/**
	 * Optional features this provider supports, for example "warranty".
	 *
	 * @return string[]
	 */
	public function features(): array;

	/**
	 * Check credentials and read access. Returns seller data on success.
	 *
	 * @param Connection $connection Connection.
	 * @param string     $language   Content language.
	 * @return array|WP_Error
	 */
	public function test_connection( Connection $connection, string $language ): array|WP_Error;

	/**
	 * Download every active listing of the connection's seller.
	 *
	 * Calls $on_row( array $row, int $position ) for each listing, where $row
	 * contains normalized vehicle table columns.
	 *
	 * @param Connection $connection Connection.
	 * @param string     $language   Content language.
	 * @param callable   $on_row     Row callback.
	 * @return int|WP_Error Number of rows received.
	 */
	public function fetch_listings( Connection $connection, string $language, callable $on_row ): int|WP_Error;

	/**
	 * External ids of listings that come with a warranty.
	 *
	 * Only called when features() contains "warranty".
	 *
	 * @param Connection $connection Connection.
	 * @param string     $language   Content language.
	 * @return int[]|WP_Error
	 */
	public function fetch_warranty_ids( Connection $connection, string $language ): array|WP_Error;

	/**
	 * Description, extra specifications and equipment of one listing,
	 * normalized and sanitized (used by local detail pages).
	 *
	 * Returned keys: description (safe HTML), specs (key => scalar),
	 * equipment (list of strings), images (list of URLs).
	 *
	 * @param Connection $connection Connection.
	 * @param int        $listing_id Listing id.
	 * @param string     $language   Content language.
	 * @return array|WP_Error
	 */
	public function fetch_listing_detail( Connection $connection, int $listing_id, string $language ): array|WP_Error;

	/**
	 * Public seller profile (name, address, zip, city, phone).
	 *
	 * @param Connection $connection Connection.
	 * @return array|WP_Error
	 */
	public function fetch_seller( Connection $connection ): array|WP_Error;

	/**
	 * Public listing URL on the marketplace website.
	 *
	 * @param array  $vehicle  Vehicle row.
	 * @param string $language Two-letter language.
	 */
	public function listing_url( array $vehicle, string $language ): string;

	/**
	 * Whether a URL may be used as the public dealer page link.
	 *
	 * @param string $url URL.
	 */
	public function is_valid_dealer_url( string $url ): bool;

	/**
	 * Resized image URL for the marketplace image CDN.
	 *
	 * @param string $url   Original image URL.
	 * @param int    $width Requested width in pixels.
	 */
	public function image_url( string $url, int $width ): string;

	/**
	 * Widths the image CDN can deliver, used for srcset.
	 *
	 * @return int[]
	 */
	public function image_widths(): array;

	/**
	 * Origins worth a preconnect hint on inventory pages.
	 *
	 * @return string[]
	 */
	public function asset_origins(): array;
}
