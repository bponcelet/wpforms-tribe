<?php

namespace WPFormsTribe;

/**
 * Tribe related helper methods.
 *
 * @since 1.0.0
 */
class Helpers {

	/**
	 * Sanitize resource ID (users, objects, etc.).
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $id Resource ID.
	 *
	 * @return string The sanitized resource ID.
	 */
	public static function sanitize_resource_id( $id ) {

		// The ID it's a combination of lower and upper case letters and digits (no special characters).
		return preg_replace( '/[^a-zA-Z0-9]/', '', $id );
	}

	/**
	 * Determine if it's a required field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Tribe field data/properties.
	 *
	 * @return bool
	 */
	public static function is_required_field( $data ) {

		if ( empty( $data ) || ! is_array( $data ) ) {
			return false;
		}

		if ( ! isset( $data['defaultedOnCreate'], $data['nillable'] ) ) {
			return false;
		}

		return ! $data['defaultedOnCreate'] && ! $data['nillable'];
	}
}
