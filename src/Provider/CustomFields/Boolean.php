<?php

namespace WPFormsTribe\Provider\CustomFields;

/**
 * Boolean field.
 *
 * @since 1.0.0
 */
class Boolean extends Base {

	/**
	 * Retrieve a field value for delivery to Tribe.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function value() {

		return filter_var( $this->fields[ $this->wpf_field_id ]['value'], FILTER_VALIDATE_BOOLEAN );
	}
}
