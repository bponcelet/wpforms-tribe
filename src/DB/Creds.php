<?php

namespace WPFormsTribe\DB;

/**
 * Credentials class.
 *
 * @since 1.0.0
 */
class Creds {

	/**
	 * Key for options table where all credentials will be temporary saved to.
	 *
	 * @since 1.0.0
	 */
	const OPTION_KEY = 'wpforms_tribe_consumer_secrets';

	/**
	 * Save creds data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $creds      Creds data.
	 * @param int   $expiration Time until expiration in seconds.
	 */
	public static function set( $creds, $expiration = HOUR_IN_SECONDS ) {

		if ( empty( $creds ) ) {
			return;
		}

		$all  = self::get();
		$all += (array) $creds;

		set_transient( self::OPTION_KEY, $all, $expiration );
	}

	/**
	 * Retrieve all creds data.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get() {

		$all = get_transient( self::OPTION_KEY );

		return empty( $all ) ? [] : (array) $all;
	}
}
