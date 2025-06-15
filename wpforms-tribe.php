<?php
/**
 * Plugin Name:       WPForms Trib
 * Plugin URI:        https://wpforms.com
 * Description:       Trib integration with WPForms.
 * Author:            WPForms
 * Author URI:        https://wpforms.com
 * Version:           1.0.0
 * Requires at least: 5.5
 * Requires PHP:      7.0
 * Text Domain:       wpforms-tribe
 * Domain Path:       languages
 *
 * WPForms is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WPForms is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WPForms. If not, see <https://www.gnu.org/licenses/>.
 *
 * @since     1.0.0
 * @author    WPForms
 * @package   WPFormsTribe
 * @license   GPL-2.0+
 * @copyright Copyright (c) 2020, WPForms LLC
 */

use WPFormsTribe\Plugin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @since 1.3.0
 */
const WPFORMS_TRIBE_VERSION = '1.0.0';

/**
 * Plugin file.
 *
 * @since 1.3.0
 */
const WPFORMS_TRIBE_FILE = __FILE__;

/**
 * Plugin path.
 *
 * @since 1.3.0
 */
define( 'WPFORMS_TRIBE_PATH', plugin_dir_path( WPFORMS_TRIBE_FILE ) );

/**
 * Plugin URL.
 *
 * @since 1.3.0
 */
define( 'WPFORMS_TRIBE_URL', plugin_dir_url( WPFORMS_TRIBE_FILE ) );

/**
 * Check addon requirements.
 *
 * @since 1.0.0
 * @since 1.3.0 Renamed from wpforms_tribe_required to wpforms_tribe_load.
 * @since 1.3.0 Uses requirements feature.
 */
function wpforms_tribe_load() {

	$requirements = [
		'file'    => WPFORMS_TRIBE_FILE,
		'wpforms' => '1.9.0',
	];

	if ( ! function_exists( 'wpforms_requirements' ) || ! wpforms_requirements( $requirements ) ) {
		return;
	}

	wpforms_tribe();
}

add_action( 'wpforms_loaded', 'wpforms_tribe_load' );

/**
 * Get the instance of the addon main class.
 *
 * @since 1.0.0
 * @since 1.3.0 Renamed from wpforms_tribe_plugin to wpforms_tribe.
 *
 * @return Plugin
 */
function wpforms_tribe() {

	require_once WPFORMS_TRIBE_PATH . 'vendor/autoload.php';

	return Plugin::get_instance();
}
