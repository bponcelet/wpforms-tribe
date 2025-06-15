<?php

namespace WPFormsTribe\Provider\CustomFields;

/**
 * Double field.
 *
 * @since 1.0.0
 */
class Double extends Base {

	/**
	 * Retrieve a field value for delivery to Tribe.
	 *
	 * @since 1.0.0
	 *
	 * @return float|string
	 */
	public function value() {

		return is_numeric( $this->fields[ $this->wpf_field_id ]['value'] ) ? (float) $this->fields[ $this->wpf_field_id ]['value'] : '';
	}
}
