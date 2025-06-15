<?php

namespace WPFormsTribe\Provider\CustomFields;

/**
 * Picklist field.
 *
 * @since 1.0.0
 */
class Picklist extends Base {

	/**
	 * Retrieve a field value for delivery to Tribe.
	 *
	 * @since 1.0.0
	 *
	 * @return array|string
	 */
	public function value() {

		$value = $this->fields[ $this->wpf_field_id ]['value'];

		// We need to check if a result value is available for restricted picklist fields.
		if (
			isset( $this->sf_props['restrictedPicklist'] ) &&
			$this->sf_props['restrictedPicklist'] &&
			! in_array( $value, array_column( $this->sf_props['picklistValues'], 'value' ), true )
		) {
			$value = '';
		}

		return $value;
	}
}
