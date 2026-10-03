<?php
/**
 * Admin screens.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin menu, settings screens and admin-post actions.
 *
 * Every settings form is rendered from the Schema, so labels, defaults and
 * allowed values are defined in exactly one place.
 */
final class Admin {

	private const CAPABILITY = 'manage_options';

	/**
	 * Admin page slugs.
	 */
	private const PAGES = array(
		'dinv-dashboard',
		'dinv-connection',
		'dinv-synchronization',
		'dinv-display',
		'dinv-design',
		'dinv-help',
		'dinv-logs',
	);

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_dinv_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_dinv_reset_design', array( $this, 'reset_design' ) );
		add_action( 'admin_post_dinv_test_connection', array( $this, 'test_connection' ) );
		add_action( 'admin_post_dinv_sync_now', array( $this, 'sync_now' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( DINV_PLUGIN_FILE ), array( $this, 'action_links' ) );
		add_action( 'admin_init', array( $this, 'privacy_policy' ) );
	}

	/**
	 * Suggested privacy policy text (Settings → Privacy).
	 */
	public function privacy_policy(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		$content  = '<p>' . esc_html__( 'Our vehicle listings are provided by AutoScout24 (SMG Swiss Marketplace Group AG). Vehicle photos are loaded directly from the AutoScout24 image server (images.autoscout24.ch); your browser therefore sends your IP address and browser information to that server. Links to vehicles lead to autoscout24.ch.', 'dealer-inventory-for-autoscout24' ) . '</p>';
		$content .= '<p>' . sprintf(
			/* translators: %s: URL of the SMG privacy policy. */
			esc_html__( 'Privacy policy of SMG Swiss Marketplace Group: %s', 'dealer-inventory-for-autoscout24' ),
			'<a href="https://privacy.swissmarketplace.group/de/">https://privacy.swissmarketplace.group/de/</a>'
		) . '</p>';
		$content .= '<p>' . esc_html__( 'The vehicle search on this site does not store personal data or set cookies.', 'dealer-inventory-for-autoscout24' ) . '</p>';

		wp_add_privacy_policy_content( __( 'Dealer Inventory for AutoScout24', 'dealer-inventory-for-autoscout24' ), wp_kses_post( $content ) );
	}

	/**
	 * Admin menu.
	 */
	public function menu(): void {
		add_menu_page(
			__( 'Dealer Inventory', 'dealer-inventory-for-autoscout24' ),
			__( 'Dealer Inventory', 'dealer-inventory-for-autoscout24' ),
			self::CAPABILITY,
			'dinv-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-car',
			58
		);

		$pages = array(
			'dinv-dashboard'       => array( __( 'Dashboard', 'dealer-inventory-for-autoscout24' ), 'render_dashboard' ),
			'dinv-connection'      => array( __( 'Connection', 'dealer-inventory-for-autoscout24' ), 'render_connection' ),
			'dinv-synchronization' => array( __( 'Synchronization', 'dealer-inventory-for-autoscout24' ), 'render_synchronization' ),
			'dinv-display'         => array( __( 'Display', 'dealer-inventory-for-autoscout24' ), 'render_display' ),
			'dinv-design'          => array( __( 'Design', 'dealer-inventory-for-autoscout24' ), 'render_design' ),
			'dinv-help'            => array( __( 'Help & Shortcode', 'dealer-inventory-for-autoscout24' ), 'render_help' ),
			'dinv-logs'            => array( __( 'Logs', 'dealer-inventory-for-autoscout24' ), 'render_logs' ),
		);

		foreach ( $pages as $slug => $page ) {
			add_submenu_page( 'dinv-dashboard', $page[0], $page[0], self::CAPABILITY, $slug, array( $this, $page[1] ) );
		}
	}

	/**
	 * Settings link on the Plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public function action_links( array $links ): array {
		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=dinv-connection' ) ), esc_html__( 'Settings', 'dealer-inventory-for-autoscout24' ) )
		);
		return $links;
	}

	/**
	 * Admin assets on plugin screens only.
	 */
	public function assets(): void {
		$page = sanitize_key( wp_unslash( $_GET['page'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Screen detection only.
		if ( ! in_array( $page, self::PAGES, true ) ) {
			return;
		}

		wp_enqueue_style( 'dinv-admin', DINV_PLUGIN_URL . 'admin/css/admin.css', array(), DINV_VERSION );

		if ( in_array( $page, array( 'dinv-display', 'dinv-design', 'dinv-help' ), true ) ) {
			$deps = array( 'jquery', 'jquery-ui-sortable' );
			if ( 'dinv-design' === $page ) {
				wp_enqueue_style( 'wp-color-picker' );
				$deps[] = 'wp-color-picker';
			}
			wp_enqueue_script( 'dinv-admin', DINV_PLUGIN_URL . 'admin/js/admin.js', $deps, DINV_VERSION, true );
			wp_localize_script(
				'dinv-admin',
				'DinvAdmin',
				array(
					'presets'    => Design::presets(),
					'previewUrl' => Preview::url(),
					'labels'     => array(
						'desktop' => __( 'Desktop', 'dealer-inventory-for-autoscout24' ),
						'mobile'  => __( 'Mobile', 'dealer-inventory-for-autoscout24' ),
					),
				)
			);
		}

		if ( 'dinv-help' === $page ) {
			wp_enqueue_style( 'dinv-shortcode-builder', DINV_PLUGIN_URL . 'admin/css/shortcode-builder.css', array( 'dinv-admin' ), DINV_VERSION );
			wp_enqueue_script( 'dinv-shortcode-builder', DINV_PLUGIN_URL . 'admin/js/shortcode-builder.js', array( 'dinv-admin' ), DINV_VERSION, true );
			wp_localize_script(
				'dinv-shortcode-builder',
				'DinvBuilder',
				array(
					'tag'    => Shortcode::TAG,
					'copied' => __( 'Copied', 'dealer-inventory-for-autoscout24' ),
				)
			);
		}
	}

	/**
	 * Dashboard.
	 */
	public function render_dashboard(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$stats      = (array) get_option( 'dinv_sync_stats', array() );
		$status     = (string) get_option( 'dinv_connection_status', 'unknown' );
		$next       = wp_next_scheduled( Sync::HOOK );
		$connection = Connection::current();

		$status_labels = array(
			'connected' => __( 'Connected', 'dealer-inventory-for-autoscout24' ),
			'error'     => __( 'Error', 'dealer-inventory-for-autoscout24' ),
			'unknown'   => __( 'Not tested', 'dealer-inventory-for-autoscout24' ),
		);
		?>
		<div class="wrap dinv-admin">
			<?php $this->page_header( __( 'Dashboard', 'dealer-inventory-for-autoscout24' ), __( 'Connection, inventory and synchronization at a glance.', 'dealer-inventory-for-autoscout24' ) ); ?>
			<?php $this->notice(); ?>
			<?php $this->secret_warning( $connection ); ?>

			<div class="dinv-admin__statgrid">
				<?php
				$this->stat_card(
					__( 'Connection', 'dealer-inventory-for-autoscout24' ),
					$status_labels[ $status ] ?? $status_labels['unknown'],
					$connection->is_configured() ? __( 'Credentials saved', 'dealer-inventory-for-autoscout24' ) : __( 'Setup incomplete', 'dealer-inventory-for-autoscout24' ),
					'dinv-status--' . sanitize_html_class( $status )
				);
				$this->stat_card( __( 'Vehicles', 'dealer-inventory-for-autoscout24' ), number_format_i18n( Repository::count_active() ), __( 'Active in the local inventory', 'dealer-inventory-for-autoscout24' ) );
				$this->stat_card(
					__( 'Last successful sync', 'dealer-inventory-for-autoscout24' ),
					$this->local_time( (string) ( $stats['last_success'] ?? '' ) ),
					isset( $stats['duration'] )
						/* translators: %s: duration in seconds. */
						? sprintf( __( '%s seconds', 'dealer-inventory-for-autoscout24' ), number_format_i18n( (float) $stats['duration'], 2 ) )
						: __( 'No completed sync yet', 'dealer-inventory-for-autoscout24' )
				);
				$this->stat_card( __( 'Next sync', 'dealer-inventory-for-autoscout24' ), $next ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next ) : '—', Scheduler::summary() );
				?>
			</div>

			<div class="dinv-admin__grid">
				<section class="dinv-admin__card">
					<h2><?php esc_html_e( 'Status', 'dealer-inventory-for-autoscout24' ); ?></h2>
					<dl class="dinv-admin__stats">
						<div><dt><?php esc_html_e( 'Last attempt', 'dealer-inventory-for-autoscout24' ); ?></dt><dd><?php echo esc_html( $this->local_time( (string) get_option( 'dinv_last_sync_attempt', '' ) ) ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Last error', 'dealer-inventory-for-autoscout24' ); ?></dt><dd><?php echo esc_html( '' !== (string) ( $stats['last_error'] ?? '' ) ? (string) $stats['last_error'] : __( 'None', 'dealer-inventory-for-autoscout24' ) ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Marketplace', 'dealer-inventory-for-autoscout24' ); ?></dt><dd><?php echo esc_html( $connection->provider()->label() ); ?></dd></div>
						<div><dt><?php esc_html_e( 'WP-Cron', 'dealer-inventory-for-autoscout24' ); ?></dt><dd><?php echo esc_html( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? __( 'Triggered by the server', 'dealer-inventory-for-autoscout24' ) : __( 'Triggered by page visits', 'dealer-inventory-for-autoscout24' ) ); ?></dd></div>
					</dl>
					<div class="dinv-admin__actions">
						<?php $this->action_form( 'dinv_test_connection', __( 'Test connection', 'dealer-inventory-for-autoscout24' ), 'secondary' ); ?>
						<?php $this->action_form( 'dinv_sync_now', __( 'Sync now', 'dealer-inventory-for-autoscout24' ), 'primary' ); ?>
					</div>
				</section>

				<section class="dinv-admin__card">
					<h2><?php esc_html_e( 'Getting started', 'dealer-inventory-for-autoscout24' ); ?></h2>
					<ol class="dinv-list">
						<li><?php esc_html_e( 'Ask AutoScout24 for API access (Client ID and Client Secret) for your dealer account.', 'dealer-inventory-for-autoscout24' ); ?></li>
						<li><?php esc_html_e( 'Enter the credentials and your Seller ID under Connection, then test the connection.', 'dealer-inventory-for-autoscout24' ); ?></li>
						<li><?php esc_html_e( 'Run the first synchronization.', 'dealer-inventory-for-autoscout24' ); ?></li>
						<li>
							<?php
							printf(
								/* translators: %s: shortcode. */
								esc_html__( 'Add %s to any page, or build a custom one under Help & Shortcode.', 'dealer-inventory-for-autoscout24' ),
								'<code>[' . esc_html( Shortcode::TAG ) . ']</code>'
							);
							?>
						</li>
					</ol>
				</section>
			</div>
		</div>
		<?php
	}

	/**
	 * Connection screen.
	 */
	public function render_connection(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$connection = Connection::current();
		?>
		<div class="wrap dinv-admin">
			<?php $this->page_header( __( 'Connection', 'dealer-inventory-for-autoscout24' ), __( 'Your AutoScout24 API credentials.', 'dealer-inventory-for-autoscout24' ) ); ?>
			<?php $this->notice(); ?>
			<?php $this->secret_warning( $connection ); ?>

			<div class="dinv-admin__grid">
				<section class="dinv-admin__card">
					<h2><?php esc_html_e( 'API access', 'dealer-inventory-for-autoscout24' ); ?></h2>
					<?php if ( ! Crypto::is_secure_storage_available() && ! defined( 'DINV_CLIENT_SECRET' ) ) : ?>
						<div class="notice notice-warning inline"><p><?php esc_html_e( 'OpenSSL is not available on this server. Define DINV_CLIENT_SECRET in wp-config.php instead of saving the secret here.', 'dealer-inventory-for-autoscout24' ); ?></p></div>
					<?php endif; ?>
					<?php $this->settings_form( 'dinv-connection', array_keys( Schema::group( 'connection' ) ), __( 'Save connection', 'dealer-inventory-for-autoscout24' ) ); ?>
				</section>

				<section class="dinv-admin__card">
					<h2><?php esc_html_e( 'Connection test', 'dealer-inventory-for-autoscout24' ); ?></h2>
					<p><?php esc_html_e( 'Checks the credentials, the Seller ID and read access to your listings.', 'dealer-inventory-for-autoscout24' ); ?></p>
					<?php $this->action_form( 'dinv_test_connection', __( 'Test connection', 'dealer-inventory-for-autoscout24' ), 'secondary' ); ?>
					<hr>
					<h3><?php esc_html_e( 'Where do I get the credentials?', 'dealer-inventory-for-autoscout24' ); ?></h3>
					<p><?php esc_html_e( 'AutoScout24 issues API credentials to dealers on request. Contact your AutoScout24 account manager or customer service and ask for access to the listing API for your own website.', 'dealer-inventory-for-autoscout24' ); ?></p>
					<h3><?php esc_html_e( 'Storing credentials in wp-config.php', 'dealer-inventory-for-autoscout24' ); ?></h3>
					<p><?php esc_html_e( 'Optional. Constants override the fields on this screen:', 'dealer-inventory-for-autoscout24' ); ?></p>
					<pre><code>define( 'DINV_CLIENT_ID', '…' );
define( 'DINV_CLIENT_SECRET', '…' );
define( 'DINV_SELLER_ID', 12345 );</code></pre>
				</section>
			</div>
		</div>
		<?php
	}

	/**
	 * Synchronization screen.
	 */
	public function render_synchronization(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$stats = (array) get_option( 'dinv_sync_stats', array() );
		$next  = wp_next_scheduled( Sync::HOOK );
		?>
		<div class="wrap dinv-admin">
			<?php $this->page_header( __( 'Synchronization', 'dealer-inventory-for-autoscout24' ), __( 'How often your local copy of the listings is refreshed.', 'dealer-inventory-for-autoscout24' ) ); ?>
			<?php $this->notice(); ?>

			<div class="dinv-admin__grid">
				<section class="dinv-admin__card">
					<h2><?php esc_html_e( 'Schedule', 'dealer-inventory-for-autoscout24' ); ?></h2>
					<?php $this->settings_form( 'dinv-synchronization', array_keys( Schema::group( 'sync' ) ), __( 'Save schedule', 'dealer-inventory-for-autoscout24' ) ); ?>
				</section>

				<section class="dinv-admin__card">
					<h2><?php esc_html_e( 'Status', 'dealer-inventory-for-autoscout24' ); ?></h2>
					<dl class="dinv-admin__stats">
						<div><dt><?php esc_html_e( 'Schedule', 'dealer-inventory-for-autoscout24' ); ?></dt><dd><?php echo esc_html( Scheduler::summary() ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Next sync', 'dealer-inventory-for-autoscout24' ); ?></dt><dd><?php echo esc_html( $next ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next ) : '—' ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Last success', 'dealer-inventory-for-autoscout24' ); ?></dt><dd><?php echo esc_html( $this->local_time( (string) ( $stats['last_success'] ?? '' ) ) ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Last error', 'dealer-inventory-for-autoscout24' ); ?></dt><dd><?php echo esc_html( '' !== (string) ( $stats['last_error'] ?? '' ) ? (string) $stats['last_error'] : __( 'None', 'dealer-inventory-for-autoscout24' ) ); ?></dd></div>
					</dl>
					<div class="dinv-admin__actions">
						<?php $this->action_form( 'dinv_sync_now', __( 'Sync now', 'dealer-inventory-for-autoscout24' ), 'primary' ); ?>
					</div>
				</section>
			</div>

			<section class="dinv-admin__card dinv-admin__widecard">
				<h2><?php esc_html_e( 'Reliable timing', 'dealer-inventory-for-autoscout24' ); ?></h2>
				<p><?php esc_html_e( 'WordPress runs scheduled tasks when someone visits the site. For exact timing on low-traffic sites, disable page-load cron and let the server call wp-cron.php, for example every 5 minutes:', 'dealer-inventory-for-autoscout24' ); ?></p>
				<pre><code>*/5 * * * * wget -q -O - "<?php echo esc_html( site_url( 'wp-cron.php?doing_wp_cron' ) ); ?>" &gt;/dev/null 2&gt;&amp;1</code></pre>
			</section>
		</div>
		<?php
	}

	/**
	 * Display defaults screen.
	 */
	public function render_display(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$groups = array(
			'display' => Schema::instance_groups()['display'],
			'filters' => Schema::instance_groups()['filters'],
			'card'    => Schema::instance_groups()['card'],
			'format'  => Schema::instance_groups()['format'],
			'detail'  => __( 'Vehicle detail pages', 'dealer-inventory-for-autoscout24' ),
		);
		?>
		<div class="wrap dinv-admin">
			<?php $this->page_header( __( 'Display', 'dealer-inventory-for-autoscout24' ), __( 'Site-wide defaults for every inventory. Each shortcode, block or widget can override them.', 'dealer-inventory-for-autoscout24' ) ); ?>
			<?php $this->notice(); ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="dinv_save_settings">
				<input type="hidden" name="return_page" value="dinv-display">
				<?php wp_nonce_field( 'dinv_save_settings' ); ?>
				<nav class="dinv-admin__tabs" aria-label="<?php esc_attr_e( 'Sections', 'dealer-inventory-for-autoscout24' ); ?>">
					<?php foreach ( $groups as $group => $title ) : ?>
						<a href="#dinv-group-<?php echo esc_attr( $group ); ?>"><?php echo esc_html( $title ); ?></a>
					<?php endforeach; ?>
				</nav>
				<?php foreach ( $groups as $group => $title ) : ?>
					<section class="dinv-admin__card dinv-admin__widecard" id="dinv-group-<?php echo esc_attr( $group ); ?>">
						<h2><?php echo esc_html( $title ); ?></h2>
						<?php if ( 'detail' === $group ) : ?>
							<p class="description"><?php esc_html_e( 'Used when "Vehicle links" is set to "Detail page on this site". Without a detail page, vehicles open on the page of the inventory itself.', 'dealer-inventory-for-autoscout24' ); ?></p>
						<?php endif; ?>
						<?php $this->settings_fields( array_keys( Schema::group( $group ) ) ); ?>
					</section>
				<?php endforeach; ?>
				<div class="dinv-admin__savebar">
					<?php submit_button( __( 'Save display settings', 'dealer-inventory-for-autoscout24' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Design screen.
	 */
	public function render_design(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$settings = Settings::all();
		$design   = Schema::group( 'design' );
		$colors   = array_keys( Design::color_labels() );
		$other    = array_diff( array_keys( $design ), $colors, array( 'design_preset' ) );
		?>
		<div class="wrap dinv-admin">
			<?php $this->page_header( __( 'Design', 'dealer-inventory-for-autoscout24' ), __( 'Colors, typography and card options without writing CSS.', 'dealer-inventory-for-autoscout24' ) ); ?>
			<?php $this->notice(); ?>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="dinv-design-form">
				<input type="hidden" name="action" value="dinv_save_settings">
				<input type="hidden" name="return_page" value="dinv-design">
				<?php wp_nonce_field( 'dinv_save_settings' ); ?>

				<div class="dinv-admin__design-layout">
					<div>
						<section class="dinv-admin__card">
							<h2><?php esc_html_e( 'Preset', 'dealer-inventory-for-autoscout24' ); ?></h2>
							<div class="dinv-inline-fields">
								<label>
									<span><?php echo esc_html( $design['design_preset']['label'] ); ?></span>
									<select id="dinv-design-preset" name="design_preset">
										<?php foreach ( $design['design_preset']['options'] as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['design_preset'], $value ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</label>
								<button id="dinv-apply-preset" type="button" class="button"><?php esc_html_e( 'Apply preset colors', 'dealer-inventory-for-autoscout24' ); ?></button>
							</div>
							<p class="description"><?php esc_html_e( 'Applying a preset fills the color fields below. Nothing is saved until you click Save design.', 'dealer-inventory-for-autoscout24' ); ?></p>
						</section>

						<section class="dinv-admin__card">
							<h2><?php esc_html_e( 'Colors', 'dealer-inventory-for-autoscout24' ); ?></h2>
							<div class="dinv-colour-grid">
								<?php foreach ( $colors as $key ) : ?>
									<div class="dinv-colour-field">
										<label for="dinv-field-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $design[ $key ]['label'] ); ?></label>
										<input id="dinv-field-<?php echo esc_attr( $key ); ?>" class="dinv-color-field" type="text" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( (string) $settings[ $key ] ); ?>" data-default-color="<?php echo esc_attr( (string) $design[ $key ]['default'] ); ?>" data-dinv-preview>
									</div>
								<?php endforeach; ?>
							</div>
						</section>

						<section class="dinv-admin__card">
							<h2><?php esc_html_e( 'Typography, spacing and cards', 'dealer-inventory-for-autoscout24' ); ?></h2>
							<table class="form-table" role="presentation">
								<?php
								foreach ( $other as $key ) {
									$this->field_row( $key, $settings );
								}
								?>
							</table>
						</section>

						<div class="dinv-admin__savebar">
							<?php submit_button( __( 'Save design', 'dealer-inventory-for-autoscout24' ), 'primary', 'submit', false ); ?>
						</div>
					</div>

					<aside class="dinv-admin__preview-column">
						<section class="dinv-admin__card dinv-admin__preview">
							<?php
							$this->preview_frame(
								array(
									'per_page'        => '3',
									'show_pagination' => 'no',
								)
							);
							?>
						</section>

						<button type="submit" form="dinv-reset-design-form" class="button button--reset" data-dinv-confirm="<?php esc_attr_e( 'Reset all design settings to the defaults?', 'dealer-inventory-for-autoscout24' ); ?>">
							<?php esc_html_e( 'Reset design to defaults', 'dealer-inventory-for-autoscout24' ); ?>
						</button>
					</aside>
				</div>
			</form>

			<form id="dinv-reset-design-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" hidden>
				<input type="hidden" name="action" value="dinv_reset_design">
				<?php wp_nonce_field( 'dinv_reset_design' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Help and shortcode builder.
	 */
	public function render_help(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$fields = Schema::instance_fields();
		$groups = Schema::instance_groups();
		$tag    = Shortcode::TAG;
		?>
		<div class="wrap dinv-admin">
			<?php $this->page_header( __( 'Help & Shortcode', 'dealer-inventory-for-autoscout24' ), __( 'Build a shortcode, see every parameter and copy examples.', 'dealer-inventory-for-autoscout24' ) ); ?>

			<section class="dinv-admin__card dinv-admin__widecard">
				<h2><?php esc_html_e( 'Shortcode builder', 'dealer-inventory-for-autoscout24' ); ?></h2>
				<p><?php esc_html_e( 'Only settings that differ from the site-wide Display settings are added to the shortcode. Paste it into the block editor, a Shortcode block, or an Elementor Shortcode widget.', 'dealer-inventory-for-autoscout24' ); ?></p>

				<form class="dinv-shortcode-builder" data-dinv-shortcode-builder onsubmit="return false;">
					<?php foreach ( $groups as $group => $title ) : ?>
						<div class="dinv-builder__section">
							<h3><?php echo esc_html( $title ); ?></h3>
							<div class="dinv-builder__grid">
								<?php
								foreach ( $fields as $attr => $field ) {
									if ( $group === $field['group'] ) {
										$this->builder_field( $attr, $field );
									}
								}
								?>
							</div>
						</div>
					<?php endforeach; ?>

					<div class="dinv-builder__preview">
						<?php $this->preview_frame( array( 'per_page' => '6' ) ); ?>
					</div>

					<div class="dinv-builder__output">
						<label for="dinv-generated-shortcode"><?php esc_html_e( 'Your shortcode', 'dealer-inventory-for-autoscout24' ); ?></label>
						<textarea id="dinv-generated-shortcode" readonly data-dinv-shortcode-output><?php echo esc_textarea( '[' . $tag . ']' ); ?></textarea>
						<div class="dinv-builder__actions">
							<button type="button" class="button button-primary" data-dinv-copy-shortcode><?php esc_html_e( 'Copy shortcode', 'dealer-inventory-for-autoscout24' ); ?></button>
							<button type="button" class="button" data-dinv-builder-reset><?php esc_html_e( 'Reset', 'dealer-inventory-for-autoscout24' ); ?></button>
							<span class="dinv-builder__status" data-dinv-copy-status role="status"></span>
						</div>
					</div>
				</form>
			</section>

			<section class="dinv-admin__card dinv-admin__widecard">
				<h2><?php esc_html_e( 'All shortcode parameters', 'dealer-inventory-for-autoscout24' ); ?></h2>
				<table class="widefat striped dinv-param-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Parameter', 'dealer-inventory-for-autoscout24' ); ?></th>
							<th><?php esc_html_e( 'Values', 'dealer-inventory-for-autoscout24' ); ?></th>
							<th><?php esc_html_e( 'Default', 'dealer-inventory-for-autoscout24' ); ?></th>
							<th><?php esc_html_e( 'Description', 'dealer-inventory-for-autoscout24' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $fields as $attr => $field ) : ?>
							<tr>
								<td><code><?php echo esc_html( $attr ); ?></code></td>
								<td><?php echo esc_html( $this->value_description( $field ) ); ?></td>
								<td><code><?php echo esc_html( $this->value_text( Schema::SCOPE_BOTH === $field['scope'] ? Settings::get( $field['key'], $field['default'] ) : $field['default'] ) ); ?></code></td>
								<td><?php echo esc_html( trim( $field['label'] . '. ' . $field['help'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</section>

			<div class="dinv-admin__grid">
				<section class="dinv-admin__card">
					<h2><?php esc_html_e( 'Full inventory page', 'dealer-inventory-for-autoscout24' ); ?></h2>
					<pre><code>[<?php echo esc_html( $tag ); ?>]</code></pre>
				</section>
				<section class="dinv-admin__card">
					<h2><?php esc_html_e( 'Three most expensive cars on the homepage', 'dealer-inventory-for-autoscout24' ); ?></h2>
					<pre><code>[<?php echo esc_html( $tag ); ?> instance="home" per_page="3" sort="price_desc" layout="card" columns="3" show_filters="no" show_sort="no" show_count="no" show_header="no" show_pagination="no" url_state="no"]</code></pre>
				</section>
			</div>
		</div>
		<?php
	}

	/**
	 * Logs screen.
	 */
	public function render_logs(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$logs = Logger::recent( 200 );
		?>
		<div class="wrap dinv-admin">
			<?php $this->page_header( __( 'Logs', 'dealer-inventory-for-autoscout24' ), __( 'Recent synchronization and API messages. Credentials are never logged.', 'dealer-inventory-for-autoscout24' ) ); ?>
			<section class="dinv-admin__card dinv-admin__logs">
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Time', 'dealer-inventory-for-autoscout24' ); ?></th>
							<th><?php esc_html_e( 'Level', 'dealer-inventory-for-autoscout24' ); ?></th>
							<th><?php esc_html_e( 'Event', 'dealer-inventory-for-autoscout24' ); ?></th>
							<th><?php esc_html_e( 'Message', 'dealer-inventory-for-autoscout24' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( $logs ) : ?>
							<?php foreach ( $logs as $log ) : ?>
								<tr>
									<td><?php echo esc_html( $this->local_time( (string) $log['created_at'] ) ); ?></td>
									<td><strong><?php echo esc_html( strtoupper( (string) $log['level'] ) ); ?></strong></td>
									<td><code><?php echo esc_html( (string) $log['event'] ); ?></code></td>
									<td><?php echo esc_html( (string) $log['message'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr><td colspan="4"><?php esc_html_e( 'No log entries yet.', 'dealer-inventory-for-autoscout24' ); ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</section>
		</div>
		<?php
	}

	/**
	 * Save any settings screen.
	 */
	public function save_settings(): void {
		$this->guard( 'dinv_save_settings' );

		$return_page = sanitize_key( wp_unslash( $_POST['return_page'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in guard().
		if ( ! in_array( $return_page, self::PAGES, true ) ) {
			$return_page = 'dinv-dashboard';
		}

		$old_connection = Connection::current();
		$old            = Settings::all();
		// Each value is validated against the Schema in Settings::save().
		$new            = Settings::save( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verified in guard(); sanitized per field.
		$new_connection = Connection::current();

		// A different dealer account must never keep showing the previous dealer's cars.
		if ( $old_connection->seller_id !== $new_connection->seller_id || $old_connection->provider !== $new_connection->provider ) {
			Repository::deactivate_connection( $new_connection->id );
		}

		if ( ( $old['detail_base'] ?? '' ) !== ( $new['detail_base'] ?? '' ) ) {
			update_option( 'dinv_flush_rewrite', '1', true );
		}

		if ( $old['sync_mode'] !== $new['sync_mode'] || (int) $old['sync_interval'] !== (int) $new['sync_interval'] || $old['sync_time'] !== $new['sync_time'] ) {
			Scheduler::reschedule();
		}

		$this->redirect( $return_page, 'success', __( 'Settings saved.', 'dealer-inventory-for-autoscout24' ) );
	}

	/**
	 * Reset design.
	 */
	public function reset_design(): void {
		$this->guard( 'dinv_reset_design' );
		Settings::reset_design();
		$this->redirect( 'dinv-design', 'success', __( 'Design reset to the defaults.', 'dealer-inventory-for-autoscout24' ) );
	}

	/**
	 * Test the connection.
	 */
	public function test_connection(): void {
		$this->guard( 'dinv_test_connection' );

		$connection = Connection::current();
		$provider   = $connection->provider();
		$result     = $provider->test_connection( $connection, I18n::content_language( $provider ) );

		if ( is_wp_error( $result ) ) {
			update_option( 'dinv_connection_status', 'error', false );
			$this->redirect( 'dinv-connection', 'error', $result->get_error_message() );
		}

		update_option( 'dinv_connection_status', 'connected', false );
		$this->redirect(
			'dinv-connection',
			'success',
			/* translators: %s: dealer name. */
			sprintf( __( 'Connected to %s.', 'dealer-inventory-for-autoscout24' ), sanitize_text_field( (string) ( $result['name'] ?? __( 'your dealer account', 'dealer-inventory-for-autoscout24' ) ) ) )
		);
	}

	/**
	 * Run a sync now.
	 */
	public function sync_now(): void {
		$this->guard( 'dinv_sync_now' );

		$result = Sync::run( true );
		if ( is_wp_error( $result ) ) {
			$this->redirect( 'dinv-synchronization', 'error', $result->get_error_message() );
		}

		$this->redirect(
			'dinv-synchronization',
			'success',
			/* translators: %s: number of vehicles. */
			sprintf( __( 'Synchronization finished. Active vehicles: %s.', 'dealer-inventory-for-autoscout24' ), number_format_i18n( (int) ( $result['active'] ?? 0 ) ) )
		);
	}

	/**
	 * Schema fields grouped by section (inside a form).
	 *
	 * @param string[] $keys Setting keys.
	 */
	private function settings_fields( array $keys ): void {
		$settings = Settings::all();
		$sections = array();
		foreach ( $keys as $key ) {
			$field                           = Schema::field( $key );
			$sections[ $field['section'] ][] = $key;
		}
		foreach ( $sections as $section => $section_keys ) {
			if ( '' !== $section ) {
				echo '<h3 class="dinv-admin__subheading">' . esc_html( $section ) . '</h3>';
			}
			echo '<table class="form-table" role="presentation">';
			foreach ( $section_keys as $key ) {
				$this->field_row( $key, $settings );
			}
			echo '</table>';
		}
	}

	/**
	 * Form with schema fields grouped by section.
	 *
	 * @param string   $page   Return page slug.
	 * @param string[] $keys   Setting keys.
	 * @param string   $submit Submit label.
	 */
	private function settings_form( string $page, array $keys, string $submit ): void {
		?>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="dinv_save_settings">
			<input type="hidden" name="return_page" value="<?php echo esc_attr( $page ); ?>">
			<?php wp_nonce_field( 'dinv_save_settings' ); ?>
			<?php $this->settings_fields( $keys ); ?>
			<?php submit_button( $submit ); ?>
		</form>
		<?php
	}

	/**
	 * Live preview iframe (rendered on the front end with the theme's styles).
	 *
	 * @param array $atts Fixed shortcode attributes of the preview.
	 */
	private function preview_frame( array $atts ): void {
		?>
		<div class="dinv-preview" data-dinv-preview-frame data-atts="<?php echo esc_attr( (string) wp_json_encode( (object) $atts ) ); ?>">
			<div class="dinv-preview__bar">
				<strong><?php esc_html_e( 'Live preview', 'dealer-inventory-for-autoscout24' ); ?></strong>
				<span class="dinv-preview__devices" role="group" aria-label="<?php esc_attr_e( 'Preview width', 'dealer-inventory-for-autoscout24' ); ?>">
					<button type="button" class="button button-small" data-dinv-device="1200" aria-pressed="true"><?php esc_html_e( 'Desktop', 'dealer-inventory-for-autoscout24' ); ?></button>
					<button type="button" class="button button-small" data-dinv-device="390" aria-pressed="false"><?php esc_html_e( 'Mobile', 'dealer-inventory-for-autoscout24' ); ?></button>
				</span>
			</div>
			<div class="dinv-preview__viewport">
				<iframe title="<?php esc_attr_e( 'Live preview', 'dealer-inventory-for-autoscout24' ); ?>" loading="lazy"></iframe>
			</div>
			<p class="description"><?php esc_html_e( 'Shows unsaved changes with your theme. Links in the preview are disabled.', 'dealer-inventory-for-autoscout24' ); ?></p>
		</div>
		<?php
	}

	/**
	 * One settings row rendered from the schema.
	 *
	 * @param string $key      Setting key.
	 * @param array  $settings Current settings.
	 */
	private function field_row( string $key, array $settings ): void {
		$field    = Schema::field( $key );
		$id       = 'dinv-field-' . $key;
		$value    = $settings[ $key ] ?? $field['default'];
		$constant = ! empty( $field['constant'] ) && defined( $field['constant'] );
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
			<td>
				<?php
				switch ( $field['type'] ) {
					case 'bool':
						?>
						<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="0">
						<input id="<?php echo esc_attr( $id ); ?>" type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( (bool) $value ); ?>>
						<?php
						break;

					case 'enum':
						?>
						<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $key ); ?>" data-dinv-preview>
							<?php foreach ( $field['options'] as $option => $label ) : ?>
								<option value="<?php echo esc_attr( (string) $option ); ?>" <?php selected( (string) $value, (string) $option ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php
						break;

					case 'int':
						?>
						<input id="<?php echo esc_attr( $id ); ?>" type="number" class="small-text" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $constant ? (string) constant( $field['constant'] ) : (string) $value ); ?>"
							<?php echo isset( $field['min'] ) ? 'min="' . esc_attr( (string) $field['min'] ) . '"' : ''; ?>
							<?php echo isset( $field['max'] ) ? 'max="' . esc_attr( (string) $field['max'] ) . '"' : ''; ?>
							<?php disabled( $constant ); ?> data-dinv-preview>
						<?php echo isset( $field['unit'] ) ? esc_html( $field['unit'] ) : ''; ?>
						<?php
						break;

					case 'secret':
						$stored = '' !== (string) ( $settings[ $key ] ?? '' );
						?>
						<input id="<?php echo esc_attr( $id ); ?>" type="password" class="regular-text" name="<?php echo esc_attr( $key ); ?>" value="" autocomplete="new-password" <?php disabled( $constant ); ?>
							placeholder="<?php echo esc_attr( $stored ? __( 'Saved. Leave empty to keep it.', 'dealer-inventory-for-autoscout24' ) : '' ); ?>">
						<?php
						break;

					case 'page':
						wp_dropdown_pages(
							array(
								'name'              => esc_attr( $key ),
								'id'                => esc_attr( $id ),
								'selected'          => (int) $value,
								'show_option_none'  => esc_html__( '— Page of the inventory —', 'dealer-inventory-for-autoscout24' ),
								'option_none_value' => '0',
							)
						);
						break;

					case 'multi':
					case 'list':
						$this->choice_list( $key, $id, $field, (array) $value );
						break;

					case 'time':
						?>
						<input id="<?php echo esc_attr( $id ); ?>" type="time" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( (string) $value ); ?>">
						<?php
						break;

					case 'url':
						?>
						<input id="<?php echo esc_attr( $id ); ?>" type="url" class="regular-text" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( (string) $value ); ?>">
						<?php
						break;

					default:
						?>
						<input id="<?php echo esc_attr( $id ); ?>" type="text" class="regular-text" name="<?php echo esc_attr( $key ); ?>" autocomplete="off"
							value="<?php echo esc_attr( $constant ? __( 'Defined in wp-config.php', 'dealer-inventory-for-autoscout24' ) : (string) $value ); ?>" <?php disabled( $constant ); ?>>
						<?php
				}

				if ( $constant ) {
					echo '<p class="description">' . esc_html__( 'Defined in wp-config.php.', 'dealer-inventory-for-autoscout24' ) . '</p>';
				} elseif ( '' !== $field['help'] ) {
					echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
				}
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Checkbox list for multi / list fields; list fields can be reordered by
	 * drag and drop (or with the keyboard: Alt + arrow keys). The value is
	 * submitted as a comma separated string.
	 *
	 * @param string $name     Input name.
	 * @param string $id       Element id.
	 * @param array  $field    Field definition.
	 * @param array  $selected Selected values in order.
	 * @param string $default_value Builder default ('' outside the builder).
	 */
	private function choice_list( string $name, string $id, array $field, array $selected, string $default_value = '' ): void {
		$options = $field['options'];
		$order   = array_keys( $options );
		if ( 'list' === $field['type'] ) {
			$order = array_merge( array_values( array_intersect( $selected, $order ) ), array_values( array_diff( $order, $selected ) ) );
		}
		$value = $selected ? implode( ',', $selected ) : 'none';
		?>
		<div class="dinv-choices dinv-choices--<?php echo esc_attr( $field['type'] ); ?>" id="<?php echo esc_attr( $id ); ?>" data-dinv-choices role="group" aria-label="<?php echo esc_attr( $field['label'] ); ?>">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" data-dinv-choices-value<?php echo '' !== $default_value ? ' data-default="' . esc_attr( $default_value ) . '"' : ''; ?>>
			<ul>
				<?php foreach ( $order as $option ) : ?>
					<li data-value="<?php echo esc_attr( (string) $option ); ?>">
						<?php if ( 'list' === $field['type'] ) : ?>
							<span class="dinv-choices__handle dashicons dashicons-move" aria-hidden="true"></span>
						<?php endif; ?>
						<label>
							<input type="checkbox" value="<?php echo esc_attr( (string) $option ); ?>" <?php checked( in_array( (string) $option, array_map( 'strval', $selected ), true ) ); ?>>
							<?php echo esc_html( $options[ $option ] ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( 'list' === $field['type'] ) : ?>
				<p class="description"><?php esc_html_e( 'Drag to change the order (keyboard: focus a checkbox and press Alt + arrow up / down).', 'dealer-inventory-for-autoscout24' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Builder input for one shortcode attribute.
	 *
	 * @param string $attr  Attribute name.
	 * @param array  $field Field definition.
	 */
	private function builder_field( string $attr, array $field ): void {
		$default = Schema::SCOPE_BOTH === $field['scope'] ? Settings::get( $field['key'], $field['default'] ) : $field['default'];

		if ( in_array( $field['type'], array( 'multi', 'list' ), true ) ) {
			?>
			<div class="dinv-builder__field dinv-builder__field--wide">
				<span><?php echo esc_html( $field['label'] ); ?></span>
				<?php $this->choice_list( $attr, 'dinv-builder-' . $attr, $field, (array) $default, Schema::to_attr( $default ) ); ?>
			</div>
			<?php
			return;
		}

		if ( 'bool' === $field['type'] ) {
			?>
			<label class="dinv-builder__toggle">
				<span><?php echo esc_html( $field['label'] ); ?></span>
				<input type="checkbox" name="<?php echo esc_attr( $attr ); ?>" data-default="<?php echo $default ? 'yes' : 'no'; ?>" <?php checked( (bool) $default ); ?>>
			</label>
			<?php
			return;
		}
		?>
		<label class="dinv-builder__field">
			<span><?php echo esc_html( $field['label'] ); ?></span>
			<?php if ( 'enum' === $field['type'] ) : ?>
				<select name="<?php echo esc_attr( $attr ); ?>" data-default="<?php echo esc_attr( (string) $default ); ?>">
					<?php foreach ( $field['options'] as $option => $label ) : ?>
						<option value="<?php echo esc_attr( (string) $option ); ?>" <?php selected( (string) $default, (string) $option ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<input type="<?php echo in_array( $field['type'], array( 'int', 'number' ), true ) ? 'number' : 'text'; ?>" name="<?php echo esc_attr( $attr ); ?>" value="<?php echo esc_attr( (string) $default ); ?>" data-default="<?php echo esc_attr( (string) $default ); ?>"
					<?php echo isset( $field['min'] ) ? 'min="' . esc_attr( (string) $field['min'] ) . '"' : ''; ?>
					<?php echo isset( $field['max'] ) ? 'max="' . esc_attr( (string) $field['max'] ) . '"' : ''; ?>>
			<?php endif; ?>
			<?php if ( '' !== $field['help'] ) : ?>
				<small><?php echo esc_html( $field['help'] ); ?></small>
			<?php endif; ?>
		</label>
		<?php
	}

	/**
	 * Allowed values in words.
	 *
	 * @param array $field Field definition.
	 */
	private function value_description( array $field ): string {
		switch ( $field['type'] ) {
			case 'bool':
				return 'yes / no';
			case 'enum':
				return implode( ', ', array_map( 'strval', array_keys( $field['options'] ) ) );
			case 'int':
				return isset( $field['min'], $field['max'] ) ? $field['min'] . '–' . $field['max'] : __( 'number', 'dealer-inventory-for-autoscout24' );
			case 'number':
				return __( 'number', 'dealer-inventory-for-autoscout24' );
			case 'multi':
			case 'list':
				return implode( ', ', array_map( 'strval', array_keys( $field['options'] ) ) ) . ' ' . __( '(comma separated; "none" for none)', 'dealer-inventory-for-autoscout24' );
			default:
				return __( 'text', 'dealer-inventory-for-autoscout24' );
		}
	}

	/**
	 * Printable default value.
	 *
	 * @param mixed $value Value.
	 */
	private function value_text( $value ): string {
		if ( is_array( $value ) ) {
			// Spaces let long lists wrap in the table.
			return str_replace( ',', ', ', Schema::to_attr( $value ) );
		}
		if ( is_bool( $value ) ) {
			return Schema::to_attr( $value );
		}
		return '' === (string) $value ? '—' : (string) $value;
	}

	/**
	 * Warning when the stored secret cannot be decrypted.
	 *
	 * @param Connection $connection Connection.
	 */
	private function secret_warning( Connection $connection ): void {
		if ( $connection->secret_unreadable ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The saved Client Secret can no longer be decrypted, usually because the WordPress security keys changed. Please enter the secret again under Connection.', 'dealer-inventory-for-autoscout24' ) . '</p></div>';
		}
	}

	/**
	 * Page header.
	 *
	 * @param string $title       Title.
	 * @param string $description Description.
	 */
	private function page_header( string $title, string $description ): void {
		?>
		<div class="dinv-admin__header">
			<div>
				<h1><?php echo esc_html( $title ); ?></h1>
				<p><?php echo esc_html( $description ); ?></p>
			</div>
			<span class="dinv-admin__brand"><?php esc_html_e( 'Dealer Inventory for AutoScout24', 'dealer-inventory-for-autoscout24' ); ?></span>
		</div>
		<?php
	}

	/**
	 * Result notice after an admin action.
	 */
	private function notice(): void {
		// The message is stored server-side per user; the URL only carries a
		// flag, so a crafted link cannot show arbitrary text in the admin.
		if ( empty( $_GET['dinv_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
			return;
		}
		$key    = 'dinv_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		delete_transient( $key );
		if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
			return;
		}
		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			'error' === ( $notice['type'] ?? '' ) ? 'notice-error' : 'notice-success',
			esc_html( (string) $notice['message'] )
		);
	}

	/**
	 * Statistic card.
	 *
	 * @param string $label Label.
	 * @param string $value Value.
	 * @param string $meta  Meta text.
	 * @param string $css_class Extra CSS class.
	 */
	private function stat_card( string $label, string $value, string $meta = '', string $css_class = '' ): void {
		?>
		<section class="dinv-admin__statcard <?php echo esc_attr( $css_class ); ?>">
			<span><?php echo esc_html( $label ); ?></span>
			<strong><?php echo esc_html( $value ); ?></strong>
			<?php if ( '' !== $meta ) : ?>
				<small><?php echo esc_html( $meta ); ?></small>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Button that posts a nonce-protected admin action.
	 *
	 * @param string $action Action.
	 * @param string $label  Button label.
	 * @param string $type   Button type.
	 */
	private function action_form( string $action, string $label, string $type ): void {
		?>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
			<?php wp_nonce_field( $action ); ?>
			<?php submit_button( $label, $type, 'submit', false ); ?>
		</form>
		<?php
	}

	/**
	 * UTC MySQL datetime in the site time zone.
	 *
	 * @param string $value Y-m-d H:i:s in UTC.
	 */
	private function local_time( string $value ): string {
		if ( '' === trim( $value ) ) {
			return '—';
		}
		$datetime = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $value, new \DateTimeZone( 'UTC' ) );
		return false === $datetime ? $value : wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $datetime->getTimestamp() );
	}

	/**
	 * Capability and nonce check for admin-post actions.
	 *
	 * @param string $action Nonce action.
	 */
	private function guard( string $action ): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'dealer-inventory-for-autoscout24' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $action );
	}

	/**
	 * Redirect back with a notice.
	 *
	 * @param string $page    Page slug.
	 * @param string $notice  success|error.
	 * @param string $message Message.
	 */
	private function redirect( string $page, string $notice, string $message ): void {
		set_transient(
			'dinv_notice_' . get_current_user_id(),
			array(
				'type'    => $notice,
				'message' => $message,
			),
			MINUTE_IN_SECONDS
		);
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => $page,
					'dinv_notice' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
