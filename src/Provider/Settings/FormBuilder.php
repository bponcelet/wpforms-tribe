<?php

namespace WPFormsTribe\Provider\Settings;

use WPForms\Providers\Provider\Settings\FormBuilder as FormBuilderAbstract;
use WPFormsTribe\Provider\Connection;
use WPFormsTribe\Provider\Api;
use WPFormsTribe\DB\Notice;
use InvalidArgumentException;
use Exception;

/**
 * Class FormBuilder handles functionality inside the form builder.
 *
 * @since 1.0.0
 */
class FormBuilder extends FormBuilderAbstract {

	/**
	 * Notice data with an authorization result.
	 *
	 * @since 1.0.0
	 *
	 * @var array $auth_notice
	 */
	protected $auth_notice;

	/**
	 * Register all hooks (actions and filters).
	 *
	 * @since 1.0.0
	 */
	protected function init_hooks() {

		parent::init_hooks();

		// AJAX-event names.
		static $ajax_events = [
			'ajax_account_save',
			'ajax_account_template_get',
			'ajax_connections_get',
			'ajax_accounts_get',
			'ajax_object_data_get',
		];

		// Register callbacks for AJAX events.
		array_walk(
			$ajax_events,
			static function( $ajax_event, $key, $instance ) {

				add_filter(
					"wpforms_providers_settings_builder_{$ajax_event}_{$instance->core->slug}",
					[ $instance, $ajax_event ]
				);
			},
			$this
		);

		// Register callbacks for hooks.
		add_action( 'wpforms_builder_init', [ '\WPFormsTribe\Provider\Auth', 'init' ] );
		add_action( 'wpforms_tribe_provider_auth_init_no_code', [ $this, 'notice' ] );
		add_filter( 'wpforms_save_form_args', [ $this, 'save_form' ], 11, 3 );
	}

	/**
	 * Show a notice about an authentication result.
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

		// Save notice data in object property for using later.
		$this->auth_notice = Notice::get_by_id( $state );

		// Notice should be shown once.
		Notice::delete( $state );
	}

	/**
	 * Pre-process provider data before saving it in form_data when editing form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $form Form array, usable with wp_update_post.
	 * @param array $data Data retrieved from $_POST and processed.
	 * @param array $args Empty by default, may have custom data not intended to be saved, but used for processing.
	 *
	 * @return array
	 */
	public function save_form( $form, $data, $args ) {

		// Get a filtered (or modified by another addon) form content.
		$form_data = json_decode( stripslashes( $form['post_content'] ), true );

		// Provider exists.
		if ( ! empty( $form_data['providers'][ $this->core->slug ] ) ) {
			$modified_post_content = $this->modify_form_data( $form_data );

			if ( ! empty( $modified_post_content ) ) {
				$form['post_content'] = wpforms_encode( $modified_post_content );

				return $form;
			}
		}

		/*
		 * This part works when modification is locked or current filter was called on NOT Providers panel.
		 * Then we need to restore provider connections from the previous form content.
		 */

		// Get a "previous" form content (current content are still not saved).
		$prev_form = ! empty( $data['id'] ) ? wpforms()->get( 'form' )->get( $data['id'], [ 'content_only' => true ] ) : [];

		if ( ! empty( $prev_form['providers'][ $this->core->slug ] ) ) {
			$provider = $prev_form['providers'][ $this->core->slug ];

			if ( ! isset( $form_data['providers'] ) ) {
				$form_data = array_merge( $form_data, [ 'providers' => [] ] );
			}

			$form_data['providers'] = array_merge( (array) $form_data['providers'], [ $this->core->slug => $provider ] );
			$form['post_content']   = wpforms_encode( $form_data );
		}

		return $form;
	}

	/**
	 * Prepare modifications for form content, if it's not locked.
	 *
	 * @since 1.0.0
	 *
	 * @param array $form_data Form content.
	 *
	 * @return array|null
	 */
	protected function modify_form_data( $form_data ) {

		/**
		 * Connection is locked.
		 * Why? User clicked to the "Save" button, when one of AJAX requests
		 * for retrieving data from API, was in progress or failed.
		 */
		if (
			isset( $form_data['providers'][ $this->core->slug ]['__lock__'] ) &&
			1 === absint( $form_data['providers'][ $this->core->slug ]['__lock__'] )
		) {
			return null;
		}

		// Modify content as we need, done by reference.
		foreach ( $form_data['providers'][ $this->core->slug ] as $connection_id => &$connection ) {

			if ( '__lock__' === $connection_id ) {
				unset( $form_data['providers'][ $this->core->slug ]['__lock__'] );
				continue;
			}

			try {
				$connection = ( new Connection( $connection ) )->get_data();

			} catch ( InvalidArgumentException $e ) {
				continue;
			}
		}
		unset( $connection );

		return $form_data;
	}

	/**
	 * Save the data for a new account and validate it.
	 *
	 * @since 1.0.0
	 */
	public function ajax_account_save() {

		$this->core->ajax_connect();
	}

	/**
	 * Content for Add New Account modal.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function ajax_account_template_get() {

		$redirect_uri = add_query_arg(
			[
				'page' => 'wpforms-builder',
				'view' => 'providers',
			],
			admin_url( 'admin.php' )
		);

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['id'] ) ) {
			$form_id      = (int) $_POST['id'];
			$redirect_uri = add_query_arg( 'form_id', $form_id, $redirect_uri );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return [
			'title'   => esc_html__( 'New Tribe Account', 'wpforms-tribe' ),
			'content' => $this->core->get_new_account_form( $redirect_uri ),
			'type'    => 'blue',
		];
	}

	/**
	 * Get the list of all saved connections.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function ajax_connections_get() {

		$connections = [
			'connections'  => ! empty( $this->get_connections_data() ) ? array_reverse( $this->get_connections_data(), true ) : [],
			'conditionals' => [],
			'objects'      => Connection::get_objects_list(),
		];

		// Get conditional logic for each connection ID.
		foreach ( $connections['connections'] as $connection ) {

			if ( empty( $connection['id'] ) ) {
				continue;
			}

			// This will either return an empty placeholder or complete set of rules, as a DOM.
			$connections['conditionals'][ $connection['id'] ] = wpforms_conditional_logic()->builder_block(
				[
					'form'       => $this->form_data,
					'type'       => 'panel',
					'parent'     => 'providers',
					'panel'      => $this->core->slug,
					'subsection' => $connection['id'],
					'reference'  => esc_html__( 'Marketing provider connection', 'wpforms-tribe' ),
				],
				false
			);
		}

		// Get accounts as well.
		$accounts = $this->ajax_accounts_get();

		return array_merge( $connections, $accounts );
	}

	/**
	 * Get the list of all accounts.
	 *
	 * @since 1.0.0
	 *
	 * @return array May return an empty subarray.
	 */
	public function ajax_accounts_get() {

		return [
			'accounts' => $this->get_accounts_data(),
		];
	}

	/**
	 * Retirieve saved provider connections data.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_connections_data() {

		return isset( $this->form_data['providers'][ $this->core->slug ] ) ? $this->form_data['providers'][ $this->core->slug ] : [];
	}

	/**
	 * Retrieve saved provider accounts data.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_accounts_data() {

		$options = $this->core->get_provider_options();
		$data    = [];
		$errors  = [];

		if ( empty( $options ) ) {
			return $data;
		}

		// We might have several accounts.
		foreach ( $options as $option_id => $option ) {

			// API call.
			try {
				$api                 = new Api( $option );
				$account_id          = $option['resource_owner_id'];
				$data[ $account_id ] = [
					'option_id' => $option_id,
					'email'     => $option['user_email'],
				];

			} catch ( Exception $e ) {
				$errors[] = $e->getMessage() . ' (' . $option['user_email'] . ')';

				continue;
			}
		}

		if ( ! empty( $errors ) && empty( $data ) ) {
			wp_send_json_error( [ 'error_msg' => $errors ] );
		}

		return $data;
	}

	/**
	 * Retrieve Tribe object data.
	 *
	 * @since 1.0.0
	 *
	 * @return array|null Return null on any kind of error. Array of data otherwise.
	 */
	public function ajax_object_data_get() {

		$options = $this->core->get_provider_options();

		// phpcs:disable
		if (
			empty( $options ) ||
			empty( $_POST['option_id'] ) ||
			empty( $_POST['object_name'] )
		) {
			return null;
		}

		$option_id   = sanitize_text_field( wp_unslash( $_POST['option_id'] ) );
		$object_name = sanitize_text_field( wp_unslash( $_POST['object_name'] ) );
		// phpcs:enable

		if ( empty( $options[ $option_id ] ) ) {
			return null;
		}

		try {
			$api    = new Api( $options[ $option_id ] );
			$fields = $api->get_object_fields( $object_name );

			// Pass to JS-templates `name` and `label` pair only.
			if ( ! empty( $fields ) ) {
				$required = array_column( $fields['required'], 'label', 'name' );
				$optional = array_column( $fields['optional'], 'label', 'name' );

				asort( $optional );

				$fields['required'] = $required;
				$fields['optional'] = $optional;
			}
		} catch ( Exception $e ) {
			return [ 'error_msg' => $e->getMessage() ];
		}

		return $fields;
	}

	/**
	 * Use this method to register own templates for form builder.
	 * Make sure, that you have `tmpl-` in template name in `<script id="tmpl-*">`.
	 *
	 * @since 1.0.0
	 */
	public function builder_custom_templates() {

		?>
		<!-- Single SF connection. -->
		<script type="text/html" id="tmpl-wpforms-<?php echo esc_attr( $this->core->slug ); ?>-builder-content-connection">
			<?php $this->get_underscore_template( 'connection' ); ?>
		</script>

		<!-- Single SF connection block: REQUIRED FIELDS -->
		<script type="text/html" id="tmpl-wpforms-<?php echo esc_attr( $this->core->slug ); ?>-builder-content-connection-required-fields">
			<?php $this->get_underscore_template( 'required-fields' ); ?>
		</script>

		<!-- Single SF connection block: ERROR -->
		<script type="text/html" id="tmpl-wpforms-<?php echo esc_attr( $this->core->slug ); ?>-builder-content-connection-error">
			<?php $this->get_underscore_template( 'error' ); ?>
		</script>
		<?php
	}

	/**
	 * Enqueue JavaScript and CSS files.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets() {

		parent::enqueue_assets();

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-tribe-admin-builder',
			WPFORMS_TRIBE_URL . "assets/js/tribe-builder{$min}.js",
			[ 'wpforms-admin-builder-providers' ],
			WPFORMS_TRIBE_VERSION,
			true
		);

		// Prepare data, that will be passed to javascript.
		$data = [
			'l10n' => [
				'provider_placeholder' => __( '--- Select Tribe Field ---', 'wpforms-tribe' ),
			],
		];

		// If we have notice data, pass it to javascript.
		if ( ! empty( $this->auth_notice ) ) {
			$data = array_merge( $data, [ 'authNotice' => $this->auth_notice ] );
		}

		wp_localize_script(
			'wpforms-tribe-admin-builder',
			'wpformsTribeBuilderVars',
			$data
		);

		wp_enqueue_style(
			'wpforms-tribe',
			WPFORMS_TRIBE_URL . "assets/css/tribe{$min}.css",
			[ 'wpforms-builder' ],
			WPFORMS_TRIBE_VERSION
		);
	}

	/**
	 * Get an Underscore JS template.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Template file name.
	 * @param array  $args Arguments.
	 */
	protected function get_underscore_template( $name, $args = [] ) {

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wpforms_render( WPFORMS_TRIBE_PATH . "templates/tmpl/{$name}", $args );
	}
}
