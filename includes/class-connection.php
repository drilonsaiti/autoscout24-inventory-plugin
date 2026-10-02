<?php
/**
 * Dealer account connection value object.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One dealer account on one marketplace provider.
 *
 * Version 1 supports a single connection with the id "default". Every stored
 * vehicle row already carries its connection id, so additional connections
 * can be added later without another data migration.
 */
final class Connection {

	public const DEFAULT_ID = 'default';

	/**
	 * Per-request cache of the configured connection (avoids repeated decryption).
	 *
	 * @var self|null
	 */
	private static ?self $current = null;

	/**
	 * Constructor.
	 *
	 * @param string $id            Connection id.
	 * @param string $provider      Provider id, for example "autoscout24_ch".
	 * @param string $client_id     API client id.
	 * @param string $client_secret Decrypted API client secret.
	 * @param int    $seller_id     Dealer / seller id on the marketplace.
	 * @param bool   $secret_unreadable True when a secret is stored but cannot be decrypted.
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $provider,
		public readonly string $client_id,
		public readonly string $client_secret,
		public readonly int $seller_id,
		public readonly bool $secret_unreadable = false
	) {}

	/**
	 * The connection configured in the plugin settings (or wp-config.php constants).
	 */
	public static function current(): self {
		if ( null !== self::$current ) {
			return self::$current;
		}

		$stored_secret = (string) Settings::get( 'client_secret', '' );

		if ( defined( 'DINV_CLIENT_SECRET' ) && DINV_CLIENT_SECRET ) {
			$secret     = (string) DINV_CLIENT_SECRET;
			$unreadable = false;
		} else {
			$secret     = Crypto::decrypt( $stored_secret );
			$unreadable = '' !== $stored_secret && '' === $secret;
		}

		$client_id = defined( 'DINV_CLIENT_ID' ) && DINV_CLIENT_ID
			? (string) DINV_CLIENT_ID
			: (string) Settings::get( 'client_id', '' );

		$seller_id = defined( 'DINV_SELLER_ID' ) && DINV_SELLER_ID
			? absint( DINV_SELLER_ID )
			: absint( Settings::get( 'seller_id', 0 ) );

		self::$current = new self(
			self::DEFAULT_ID,
			(string) Settings::get( 'provider', Providers::DEFAULT_ID ),
			$client_id,
			$secret,
			$seller_id,
			$unreadable
		);

		return self::$current;
	}

	/**
	 * Forget the cached connection (after settings change).
	 */
	public static function reset(): void {
		self::$current = null;
	}

	/**
	 * Whether all credentials needed for synchronization are present.
	 */
	public function is_configured(): bool {
		return '' !== $this->client_id && '' !== $this->client_secret && $this->seller_id > 0;
	}

	/**
	 * Provider instance for this connection.
	 */
	public function provider(): Providers\Provider {
		return Providers::get( $this->provider );
	}
}
