<?php

namespace WPFormsTribe\Provider\CustomFields;

/**
 * Email field.
 *
 * @since 1.0.0
 */
class Email extends Base {

	/**
	 * Retrieve a field value for delivery to Tribe.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function value() {

		$field_id = $this->wpf_field_id;

		if ( ! empty( $this->fields[ $field_id ]['type'] ) && 'email' === $this->fields[ $field_id ]['type'] ) {
			return $this->fields[ $field_id ]['value'];
		}

		$email = sanitize_email( $this->fields[ $field_id ]['value'] );

		return is_email( $email ) ? $email : '';
	}
}
