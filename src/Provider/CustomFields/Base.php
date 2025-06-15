<?php

namespace WPFormsTribe\Provider\CustomFields;

/**
 * Base field template.
 *
 * @since 1.0.0
 */
class Base {

	/**
	 * Array of form fields.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $fields;

	/**
	 * Form data and settings.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $form_data;

	/**
	 * Tribe field properties.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $sf_props;

	/**
	 * WPForms field ID.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $wpf_field_id;

	/**
	 * Base constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields    Array of form fields.
	 * @param array $form_data Form data and settings.
	 */
	public function __construct( $fields, $form_data ) {

		$this->fields    = $fields;
		$this->form_data = $form_data;
	}

	/**
	 * Initialization.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sf_props     Tribe field properties.
	 * @param int   $wpf_field_id WPForms field ID.
	 *
	 * @return object
	 */
	public function init( $sf_props, $wpf_field_id ) {

		$this->sf_props     = $sf_props;
		$this->wpf_field_id = $wpf_field_id;

		// Getting the class name.
		$class_name = ucfirst( $this->get_type() );
		$class_name = '\WPFormsTribe\Provider\CustomFields\\' . $class_name;

		if ( ! class_exists( $class_name ) ) {
			return $this;
		}

		$instance               = new $class_name( $this->fields, $this->form_data );
		$instance->sf_props     = $sf_props;
		$instance->wpf_field_id = $wpf_field_id;

		return $instance;
	}

	/**
	 * Retrieve a field value for delivery to Tribe.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed
	 */
	public function value() {

		return $this->fields[ $this->wpf_field_id ]['value'];
	}

	/**
	 * Retrieve a Tribe field type.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_type() {

		// This mapping helps to avoid a DRY for some fields.
		$types = [
			'int'      => 'integer',
			'datetime' => 'date',
		];

		if ( empty( $this->sf_props['type'] ) ) {
			return 'undefined';
		}

		if ( isset( $types[ $this->sf_props['type'] ] ) ) {
			return $types[ $this->sf_props['type'] ];
		}

		// Actually, a `Name` field has `string` type, but we want to change it to `name` (for better handling).
		if ( 'Name' === $this->sf_props['name'] ) {
			return 'name';
		}

		return $this->sf_props['type'];
	}

	/**
	 * Gets a prop for a getter method.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Name of prop to get.
	 *
	 * @return mixed
	 */
	public function get_prop( $name ) {

		return isset( $this->{$name} ) ? $this->{$name} : null;
	}
}
