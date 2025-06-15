<?php

namespace WPFormsTribe;

use WPForms\Providers\Providers;

/**
 * Class Plugin that loads the whole plugin.
 *
 * @since 1.0.0
 */
final class Plugin {

	/**
	 * Provider Core instance.
	 *
	 * @since 1.0.0
	 *
	 * @var \WPFormsTribe\Provider\Core
	 */
	public $provider;

	/**
	 * Plugin constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {}

	/**
	 * Get a single instance of the addon.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	public static function get_instance() {

		static $instance;

		if ( ! $instance ) {
			$instance = new self();

			$instance->init();
		}

		return $instance;
	}

	/**
	 * All the actual plugin loading is done here.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->hooks();

		return $this;
	}

	/**
	 * Hooks.
	 *
	 * @since 1.0.0
	 */
	protected function hooks() {

		add_action( 'wpforms_loaded', [ $this, 'init_components' ], 20 );
		add_filter( 'wpforms_helpers_templates_include_html_located', [ $this, 'templates' ], 10, 4 );
	}

	/**
	 * Init components.
	 *
	 * @since 1.0.0
	 */
	public function init_components() {

		$this->provider = Provider\Core::get_instance();

		Providers::get_instance()->register(
			$this->provider
		);
	}

	/**
	 * Load the plugin updater.
	 *
	 * @since 1.0.0
	 * @deprecated 1.4.0
	 *
	 * @todo Remove with core 1.9.2
	 *
	 * @param string $key License key.
	 */
	public function updater( $key ) {

		_deprecated_function( __METHOD__, '1.4.0 of the WPForms Tribe plugin' );

		new \WPForms_Updater(
			[
				'plugin_name' => 'WPForms Tribe',
				'plugin_slug' => 'wpforms-tribe',
				'plugin_path' => plugin_basename( WPFORMS_TRIBE_FILE ),
				'plugin_url'  => trailingslashit( WPFORMS_TRIBE_URL ),
				'remote_url'  => WPFORMS_UPDATER_API,
				'version'     => WPFORMS_TRIBE_VERSION,
				'key'         => $key,
			]
		);
	}

	/**
	 * Change a template location.
	 *
	 * @since 1.0.0
	 *
	 * @param string $located  Template location.
	 * @param string $template Template.
	 * @param array  $args     Arguments.
	 * @param bool   $extract  Extract arguments.
	 *
	 * @return string
	 */
	public function templates( $located, $template, $args, $extract ) {

		// Checking if `$template` is an absolute path and passed from this plugin.
		if (
			( 0 === strpos( $template, WPFORMS_TRIBE_PATH ) ) &&
			is_readable( $template )
		) {
			return $template;
		}

		return $located;
	}
}
