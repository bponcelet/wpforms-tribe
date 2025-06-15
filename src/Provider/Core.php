<?php

namespace WPFormsTribe\Provider;

use WPFormsTribe\DB\Creds;
use WPFormsTribe\DB\Notice;
use WPForms\Providers\Provider\Core as CoreAbstract;

/**
 * Class Core registers all the handlers for
 * Form Builder, Settings > Integrations page, Processing etc.
 *
 * @since 1.0.0
 */
class Core extends CoreAbstract {

	/**
	 * Priority for a provider, that will affect loading/placement order.
	 *
	 * @since 1.0.0
	 */
	const PRIORITY = 50;

	/**
	 * Core constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			[
				'slug' => 'tribe',
				'name' => esc_html__( 'Tribe', 'wpforms-tribe' ),
				'icon' => WPFORMS_TRIBE_URL . 'assets/images/addon-icon-tribe.png',
			]
		);

		$this->hooks();
	}

	/**
	 * Register all hooks.
	 *
	 * @since 1.0.0
	 */
	protected function hooks() {

		// Action names.
		static $actions = [
			'wpforms_tribe_provider_auth_init_has_error',
			'wpforms_tribe_provider_auth_token_process',
		];

		// Register callbacks for actions.
		array_walk(
			$actions,
			static function( $action, $key, $instance ) {

				add_action( $action, [ $instance, 'error_handler' ] );
			},
			$this
		);
	}

	/**
	 * Error handler.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Error $wp_error Error data.
	 */
	public function error_handler( $wp_error ) {

		if ( ! is_wp_error( $wp_error ) ) {
			return;
		}

		$error_code = $wp_error->get_error_code();
		if ( empty( $error_code ) ) {
			return;
		}

		// Get a state, which used in an auth code request.
		$state = $wp_error->get_error_data( $error_code );
		if ( empty( $state ) ) {
			return;
		}

		// Prepare notice data.
		$notice = [
			'message' => $wp_error->get_error_message( $error_code ),
			'type'    => 'auth_success' === $error_code ? 'success' : 'error',
		];

		// Save notice data.
		Notice::set( [ $state => $notice ] );
	}

	/**
	 * Provide an instance of the object, that should process the submitted entry.
	 * It will use data from an already saved entry to pass it further to a Provider.
	 *
	 * @since 1.0.0
	 *
	 * @return null|\WPFormsTribe\Provider\Process
	 */
	public function get_process() {

		static $process = null;

		if ( ! ( $process instanceof Process ) ) {
			$process = new Process( static::get_instance() );
		}

		return $process;
	}

	/**
	 * Provide an instance of the object, that should display provider settings
	 * on Settings > Integrations page in admin area.
	 *
	 * @since 1.0.0
	 *
	 * @return null|\WPFormsTribe\Provider\Settings\PageIntegrations
	 */
	public function get_page_integrations() {

		static $integration = null;

		if (
			! ( $integration instanceof Settings\PageIntegrations ) &&
			( wpforms_is_admin_page( 'settings', 'integrations' ) || wp_doing_ajax() )
		) {
			$integration = new Settings\PageIntegrations( static::get_instance() );
		}

		return $integration;
	}

	/**
	 * Provide an instance of the object, that should display provider settings in the Form Builder.
	 *
	 * @since 1.0.0
	 *
	 * @return null|\WPFormsTribe\Provider\Settings\FormBuilder
	 */
	public function get_form_builder() {

		static $builder = null;

		if (
			! ( $builder instanceof Settings\FormBuilder ) &&
			( wpforms_is_admin_page( 'builder' ) || wp_doing_ajax() )
		) {
			$builder = new Settings\FormBuilder( static::get_instance() );
		}

		return $builder;
	}

	/**
	 * Get provider options.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_provider_options() {

		$providers = wpforms_get_providers_options();

		return ! empty( $providers[ $this->slug ] ) ? $providers[ $this->slug ] : [];
	}

	/**
	 * Retrieve a form for connect a new account.
	 *
	 * @since 1.0.0
	 *
	 * @param string $redirect_uri Page redirect URI.
	 *
	 * @return string
	 */
	public function get_new_account_form( $redirect_uri ) {

		return wpforms_render(
			WPFORMS_TRIBE_PATH . 'templates/settings/new-account-form',
			[
				'authorization_url' => Auth::get_authorization_url(),
				'redirect_uri'      => $redirect_uri,
				'scope'             => implode( ' ', Auth::SCOPES ),
				'prompt'            => implode( ' ', Auth::PROMPTS ),
			]
		);
	}

	/**
	 * AJAX-callback to add a provider from the Integrations page and from the Builder.
	 *
	 * @since 1.0.0
	 */
	public function ajax_connect() {

		// Checking required data.
		$creds = wp_parse_args( wp_unslash( $_POST['data'] ), [ 'client_id' => '', 'client_secret' => '' ] ); // phpcs:ignore

		// Prepare creds for saving.
		$uniqid = uniqid( '', true );
		$data   = [
			$uniqid => [
				'client_id'     => $creds['client_id'],
				'client_secret' => $creds['client_secret'],
				'state'         => $uniqid,
			],
		];

		// Save creds.
		Creds::set( $data );

		// Return a state value.
		wp_send_json_success( [ 'state' => $uniqid ] );
	}
}
