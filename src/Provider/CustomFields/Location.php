<?php

namespace WPFormsTribe\Provider\CustomFields;

/**
 * Location field.
 *
 * @since 1.0.0
 */
class Location extends Base {

	/**
	 * Retrieve a field value for delivery to Tribe.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function value() {

		// See https://developer.tribe.com/docs/atlas.en-us.api.meta/api/compound_fields_geolocation.htm#highlighter_820765.
		$pattern = '/([-+]?\d{1,2}([.]\d+)?) ([-+]?\d{1,3}([.]\d+)?)/';

		preg_match( $pattern, $this->fields[ $this->wpf_field_id ]['value'], $matches );

		if ( ! is_array( $matches ) || ! isset( $matches[1], $matches[3] ) ) {
			return [];
		}

		// Latitude values must be within -90 and 90. Longitude values must be within -180 and 180.
		// See https://help.tribe.com/articleView?id=custom_field_geolocate_overview.htm&type=5.
		if (
			90 < abs( $matches[1] ) ||
			180 < abs( $matches[3] )
		) {
			return [];
		}

		$coords = [
			'__latitude__'  => $matches[1],
			'__longitude__' => $matches[3],
		];

		return $this->format_location( $coords );
	}

	/**
	 * Prepare location values.
	 *
	 * @since 1.0.0
	 *
	 * @param array $coords Location data.
	 *
	 * @return array
	 */
	protected function format_location( $coords ) {

		$result = [];

		foreach ( $this->sf_props['compound_fields'] as $field ) {

			if ( empty( $field['name'] ) ) {
				continue;
			}

			$field_name_lowercase = strtolower( $field['name'] );

			foreach ( $coords as $coord_name => $coord_value ) {

				if ( false !== strpos( $field_name_lowercase, $coord_name ) ) {
					$result[ $field['name'] ] = $coord_value;
					break;
				}
			}
		}

		return $result;
	}
}
