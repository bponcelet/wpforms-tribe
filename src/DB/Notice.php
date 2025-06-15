<?php

namespace WPFormsTribe\DB;

/**
 * Class Notice.
 *
 * @since 1.0.0
 */
class Notice {

	/**
	 * Key for options table where all notices will be saved to.
	 *
	 * @since 1.0.0
	 */
	const OPTION_KEY = 'wpforms_tribe_auth_process_notices';

	/**
	 * Save notice data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $notice Notice data.
	 */
	public static function set( $notice ) {

		if ( empty( $notice ) ) {
			return;
		}

		$notice = (array) $notice;

		$key            = current( array_keys( $notice ) );
		$notice[ $key ] = wp_parse_args(
			$notice[ $key ],
			[
				'message' => '',
				'type'    => '',
			]
		);

		$notices  = self::get();
		$notices += $notice;

		update_option( self::OPTION_KEY, $notices, false );
	}

	/**
	 * Retrieve all notices.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get() {

		$all = get_option( self::OPTION_KEY, [] );

		return empty( $all ) ? [] : (array) $all;
	}

	/**
	 * Retrieve single notice data by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Unique ID.
	 *
	 * @return array Notice data (empty array if fail).
	 */
	public static function get_by_id( $id ) {

		$notices = self::get();

		if ( isset( $notices[ $id ] ) ) {

			return wp_parse_args(
				(array) $notices[ $id ],
				[
					'message' => '',
					'type'    => '',
				]
			);
		}

		return [];
	}

	/**
	 * Unset a single notice data by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Unique ID.
	 */
	public static function delete( $id ) {

		$notices = self::get();

		if ( isset( $notices[ $id ] ) ) {
			unset( $notices[ $id ] );
			update_option( self::OPTION_KEY, $notices, false );
		}
	}

	/**
	 * Remove all notices.
	 *
	 * @since 1.0.0
	 */
	public static function clear() {

		update_option( self::OPTION_KEY, [], false );
	}
}
