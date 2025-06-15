<?php

namespace WPFormsTribe\Provider\CustomFields;

use WPFormsTribe\Helpers;

/**
 * Name field.
 *
 * @since 1.0.0
 */
class Name extends Base {

	/**
	 * Retrieve a field value for delivery to Tribe.
	 *
	 * @since 1.0.0
	 *
	 * @return array|string
	 */
	public function value() {

		$has_compound_fields = ! empty( $this->sf_props['compound_fields'] );

		// Apply a special formatting for `Name` WPForms field.
		if (
			! empty( $this->fields[ $this->wpf_field_id ]['type'] ) &&
			'name' === $this->fields[ $this->wpf_field_id ]['type'] &&
			$has_compound_fields
		) {
			return $this->format_name();
		}

		// Return a value if it's NOT a compound field.
		if ( ! $has_compound_fields ) {
			return $this->fields[ $this->wpf_field_id ]['value'];
		}

		$value = [];
		foreach ( $this->sf_props['compound_fields'] as $field ) {

			if ( empty( $field['name'] ) ) {
				continue;
			}

			$value[ $field['name'] ] = $this->fields[ $this->wpf_field_id ]['value'];
			break;
		}

		return $value;
	}

	/**
	 * Special formatting for `Name` WPForms field.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function format_name() {

		$field_submit   = $this->fields[ $this->wpf_field_id ];
		$available_keys = [ 'first', 'last', 'middle' ];
		$result         = [];

		foreach ( $this->sf_props['compound_fields'] as $field ) {

			if ( empty( $field['name'] ) ) {
				continue;
			}

			// Convert "FirstName", "LastName", etc. to lowercase.
			$field_name_lowercase = strtolower( $field['name'] );

			foreach ( $available_keys as $key_name ) {

				if (
					false !== strpos( $field_name_lowercase, $key_name ) &&
					! wpforms_is_empty_string( $field_submit[ $key_name ] )
				) {
					$result[ $field['name'] ] = $field_submit[ $key_name ];
					break;
				}
			}

			// If we didn't find anything and it's a required field - return a submitted value.
			if ( empty( $result ) && Helpers::is_required_field( $field ) ) {
				$result[ $field['name'] ] = $field_submit['value'];
			}
		}

		return $result;
	}
}
