<?php

namespace WPFormsTribe\Provider\Settings;

use WPForms\Providers\Provider\Settings\PageIntegrations as PageIntegrationsAbstract;
use WPFormsTribe\Provider\Core;
use WPFormsTribe\Provider\Auth;
use WPFormsTribe\DB\Notice;

/**
 * Class PageIntegrations handles functionality inside the Settings > Integrations page.
 *
 * @since 1.0.0
 */
class PageIntegrations extends PageIntegrationsAbstract {

	/**
	 * Integrations constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param \WPFormsTribe\Provider\Core $core Core provider object.
	 */
	public function __construct( Core $core ) {

		parent::__construct( $core );

		$this->hooks();
	}

	/**
	 * Register all hooks.
	 *
	 * @since 1.0.0
	 */
	protected function hooks() {

		add_action( 'wpforms_settings_init', [ '\WPFormsTribe\Provider\Auth', 'init' ] );
		add_action( 'wpforms_tribe_provider_auth_init_no_code', [ $this, 'notice' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Show a notice about an authorization result.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Error $wp_error Error data.
	 */
	public function notice( $wp_error ) {

		if ( ! is_wp_error( $wp_error ) ) {
			return;
		}

		// Get a state, which used in an auth code request.
		$state = $wp_error->get_error_data();
		if ( empty( $state ) ) {
			return;
		}

		// Get notice data.
		$notice = Notice::get_by_id( $state );

		// Add a WPForms notice.
		if ( ! empty( $notice['message'] )
			&& class_exists( '\WPForms\Admin\Notice', false )
			&& method_exists( '\WPForms\Admin\Notice', $notice['type'] )
		) {
			call_user_func( [ '\WPForms\Admin\Notice', $notice['type'] ], $notice['message'] );
		}

		// Notice should be shown once.
		Notice::delete( $state );
	}

	/**
	 * Enqueue JavaScript and CSS files.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-tribe-integration',
			WPFORMS_TRIBE_URL . "assets/js/tribe-integration{$min}.js",
			[ 'wpforms-admin' ],
			WPFORMS_TRIBE_VERSION,
			true
		);

		wp_localize_script(
			'wpforms-tribe-integration',
			'wpformsTribeIntegrationVars',
			[
				'required_data' => esc_html__( 'Please provide valid Consumer Key and Consumer Secret.', 'wpforms-tribe' ),
			]
		);

		wp_enqueue_style(
			'wpforms-tribe',
			WPFORMS_TRIBE_URL . "assets/css/tribe{$min}.css",
			[ 'wpforms-admin' ],
			WPFORMS_TRIBE_VERSION
		);
	}

	/**
	 * AJAX to add a provider from the settings integrations tab.
	 *
	 * @since 1.0.0
	 */
	public function ajax_connect() {

		parent::ajax_connect();

		$this->core->ajax_connect();
	}

	/**
	 * Any new connection should be added.
	 * So display the content of that.
	 *
	 * @since 1.0.0
	 */
	protected function display_add_new() {

		/* translators: %s - provider name. */
		$title = sprintf( esc_html__( 'Connect to %s', 'wpforms-tribe' ), $this->core->name );
		?>
		<p class="wpforms-settings-provider-accounts-toggle">
			<a class="wpforms-btn wpforms-btn-md wpforms-btn-light-grey" href="#" data-provider="<?php echo esc_attr( $this->core->slug ); ?>">
				<i class="fa fa-plus"></i> <?php esc_html_e( 'Add New Account', 'wpforms-tribe' ); ?>
			</a>
		</p>

		<div class="wpforms-settings-provider-accounts-connect">
			<p><?php esc_html_e( 'Please fill out all of the fields below to add your new provider account.', 'wpforms-tribe' ); ?></p>
			<?php $this->display_add_new_connection_fields(); ?>

			<button type="button" class="wpforms-btn wpforms-btn-md wpforms-btn-orange wpforms-settings-provider-submit" form="wpforms-tribe-new-account-connection-form" title="<?php echo esc_attr( $title ); ?>">
				<?php echo esc_html( $title ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Display fields that will use for connect a new Tribe account.
	 *
	 * @since 1.0.0
	 */
	protected function display_add_new_connection_fields() {

		$redirect_uri = add_query_arg(
			[
				'page' => 'wpforms-settings',
				'view' => 'integrations',
			],
			admin_url( 'admin.php' )
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->core->get_new_account_form( $redirect_uri );
	}
}
