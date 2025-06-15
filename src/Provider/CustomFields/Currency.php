<?php

namespace WPFormsTribe\Provider\CustomFields;

/**
 * Currency field.
 *
 * @since 1.2.0
 */
class Currency extends Base {

	/**
	 * Retrieve a field value for delivery to Tribe.
	 *
	 * @since 1.0.0
	 *
	 * @return float|string
	 */
	public function value() {

		$amount_raw = $this->fields[ $this->wpf_field_id ]['amount_raw'];

		return is_numeric( $amount_raw ) ? (float) $amount_raw : '';
	}
}
