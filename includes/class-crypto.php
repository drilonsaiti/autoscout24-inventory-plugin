<?php
/**
 * Encryption of stored secrets.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AES-256-GCM encryption for the API client secret and cached access tokens.
 *
 * The key is derived from the WordPress security keys in wp-config.php, so a
 * database dump alone does not reveal the secret. Rotating those keys makes
 * stored values unreadable; the admin then asks for the secret again.
 */
final class Crypto {

	private const PREFIX = 'dinvenc:';
	private const CIPHER = 'aes-256-gcm';

	/**
	 * Encrypt a value. Returns '' when encryption is unavailable.
	 *
	 * @param string $plaintext Value.
	 */
	public static function encrypt( string $plaintext ): string {
		if ( '' === $plaintext || ! self::is_secure_storage_available() ) {
			return '';
		}

		$iv         = random_bytes( 12 );
		$tag        = '';
		$ciphertext = openssl_encrypt( $plaintext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag, '', 16 );

		if ( false === $ciphertext ) {
			return '';
		}

		// Binary-safe storage of IV + tag + ciphertext (not code obfuscation).
		return self::PREFIX . base64_encode( $iv . $tag . $ciphertext ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt a stored value. Returns '' when it cannot be decrypted.
	 *
	 * @param string $stored Stored value.
	 */
	public static function decrypt( string $stored ): string {
		if ( '' === $stored || ! str_starts_with( $stored, self::PREFIX ) || ! self::is_secure_storage_available() ) {
			return '';
		}

		$raw = base64_decode( substr( $stored, strlen( self::PREFIX ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding our own stored ciphertext.
		if ( false === $raw || strlen( $raw ) < 29 ) {
			return '';
		}

		$plaintext = openssl_decrypt( substr( $raw, 28 ), self::CIPHER, self::key(), OPENSSL_RAW_DATA, substr( $raw, 0, 12 ), substr( $raw, 12, 16 ) );

		return false === $plaintext ? '' : $plaintext;
	}

	/**
	 * Whether OpenSSL is available.
	 */
	public static function is_secure_storage_available(): bool {
		return function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' );
	}

	/**
	 * 256-bit key derived from the site's security keys.
	 */
	private static function key(): string {
		$material = '';
		foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY' ) as $constant ) {
			if ( defined( $constant ) ) {
				$material .= constant( $constant );
			}
		}

		return hash( 'sha256', '' !== $material ? $material : wp_salt( 'auth' ), true );
	}
}
