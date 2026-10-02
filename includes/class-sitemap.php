<?php
/**
 * Core XML sitemap provider for vehicle detail pages.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lists active vehicles at /wp-sitemap-dinvvehicles-1.xml.
 */
final class Sitemap extends \WP_Sitemaps_Provider {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name        = 'dinvvehicles';
		$this->object_type = 'dinvvehicles';
	}

	/**
	 * URLs of one sitemap page.
	 *
	 * @param int    $page_num       Page number.
	 * @param string $object_subtype Unused.
	 * @return array[]
	 */
	public function get_url_list( $page_num, $object_subtype = '' ) {
		unset( $object_subtype );
		$page = (string) get_permalink( (int) Settings::get( 'detail_page', 0 ) );
		if ( '' === $page ) {
			return array();
		}

		$urls = array();
		foreach ( Repository::sitemap_rows( (int) $page_num, wp_sitemaps_get_max_urls( $this->object_type ) ) as $row ) {
			$urls[] = array(
				'loc'     => Detail::url( $row, $page ),
				'lastmod' => gmdate( DATE_W3C, (int) strtotime( (string) $row['updated_at'] . ' UTC' ) ),
			);
		}
		return $urls;
	}

	/**
	 * Number of sitemap pages.
	 *
	 * @param string $object_subtype Unused.
	 */
	public function get_max_num_pages( $object_subtype = '' ) {
		unset( $object_subtype );
		return (int) ceil( Repository::count_active() / wp_sitemaps_get_max_urls( $this->object_type ) );
	}
}
