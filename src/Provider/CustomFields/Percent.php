<?php

namespace WPFormsTribe\Provider\CustomFields;

/**
 * Percent field.
 *
 * @since 1.0.0
 */
class Percent extends Double {

	/**
	 * Retrieve a field value for delivery to Tribe.
	 *
	 * @since 1.0.0
	 *
	 * @return float|string
	 */
	public function value() {

		$maybe_percent = parent::value();

		// Probability must be between 0 and 100.
		if ( $maybe_percent < 0 || $maybe_percent > 100 ) {
			return '';
		}

		return $maybe_percent;
	}
}
