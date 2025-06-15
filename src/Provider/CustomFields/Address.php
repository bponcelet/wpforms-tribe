<?php

namespace WPFormsTribe\Provider\CustomFields;

/**
 * Address field.
 *
 * @since 1.0.0
 */
class Address extends Base {

	/**
	 * Retrieve a field value for delivery to Tribe.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function value() {

		$field_id = $this->wpf_field_id;

		// Apply a special formatting for `Address` WPForms field.
		if ( ! empty( $this->fields[ $field_id ]['type'] ) && 'address' === $this->fields[ $field_id ]['type'] ) {
			return $this->format_address();
		}

		$value = [];
		foreach ( $this->sf_props['compound_fields'] as $field ) {

			if ( empty( $field['name'] ) ) {
				continue;
			}

			$value[ $field['name'] ] = $this->fields[ $field_id ]['value'];
			break;
		}

		return $value;
	}

	/**
	 * Special formatting for `Address` WPForms field.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function format_address() {

		$field_submit   = $this->fields[ $this->wpf_field_id ];
		$available_keys = [ 'city', 'state', 'postal', 'country' ];
		$result         = [];

		foreach ( $this->sf_props['compound_fields'] as $field ) {

			if ( empty( $field['name'] ) ) {
				continue;
			}

			$field_name_lowercase = strtolower( $field['name'] );

			if ( false !== strpos( $field_name_lowercase, 'street' ) ) {
				$address1 = ! empty( $field_submit['address1'] ) ? $field_submit['address1'] : '';
				$address2 = ! empty( $field_submit['address2'] ) ? $field_submit['address2'] : '';

				$field_value  = '';
				$field_value .= ! empty( $address1 ) ? $address1 . ' ' : '';
				$field_value .= ! empty( $address2 ) ? $address2 : '';

				$result[ $field['name'] ] = $field_value;
				continue;
			}

			foreach ( $available_keys as $key_name ) {

				if (
					false !== strpos( $field_name_lowercase, $key_name ) &&
					! wpforms_is_empty_string( $field_submit[ $key_name ] )
				) {
					$result[ $field['name'] ] = $field_submit[ $key_name ];
					break;
				}
			}
		}

		return $result;
	}
}
