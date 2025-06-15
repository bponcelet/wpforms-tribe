<?php

namespace WPFormsTribe\Provider\CustomFields;

/**
 * Integer field.
 *
 * @since 1.0.0
 */
class Integer extends Base {

	/**
	 * Retrieve a field value for delivery to Tribe.
	 *
	 * @since 1.0.0
	 *
	 * @return int|string
	 */
	public function value() {

		return is_numeric( $this->fields[ $this->wpf_field_id ]['value'] ) ? (int) $this->fields[ $this->wpf_field_id ]['value'] : '';
	}
}
