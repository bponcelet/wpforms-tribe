<?php

namespace WPFormsTribe\Provider\CustomFields;

/**
 * Url field.
 *
 * @since 1.0.0
 */
class Url extends Base {

	/**
	 * Retrieve a field value for delivery to Tribe.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function value() {

		return wpforms_is_url( $this->fields[ $this->wpf_field_id ]['value'] ) ? $this->fields[ $this->wpf_field_id ]['value'] : '';
	}
}
