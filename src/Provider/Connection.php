<?php

namespace WPFormsTribe\Provider;

use WPFormsTribe\Helpers;

/**
 * Class Connection.
 *
 * @since 1.0.0
 */
class Connection {

	/**
	 * Connection data.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Constructor method.
	 *
	 * @since 1.0.0
	 *
	 * @param array $raw_data Connection data.
	 *
	 * @throws \InvalidArgumentException Emitted when something went wrong.
	 */
	public function __construct( $raw_data ) {

		if ( ! is_array( $raw_data ) || empty( $raw_data ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Unexpected connection data.', 'wpforms-tribe' ) );
		}

		$this->set_data( $raw_data );
	}

	/**
	 * Sanitize and set connection data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $raw_data Connection data.
	 */
	protected function set_data( $raw_data ) {

		$this->data = wp_parse_args( $raw_data, $this->get_required_data() );

		$this->set_id()
			 ->set_name()
			 ->set_object()
			 ->set_account_id()
			 ->set_fields_required()
			 ->set_fields_meta();
	}

	/**
	 * Sanitize and set connection ID.
	 *
	 * @since 1.0.0
	 *
	 * @return \WPFormsTribe\Provider\Connection
	 */
	protected function set_id() {

		$this->data['id'] = sanitize_key( $this->data['id'] );

		return $this;
	}

	/**
	 * Sanitize and set connection name.
	 *
	 * @since 1.0.0
	 *
	 * @return \WPFormsTribe\Provider\Connection
	 */
	protected function set_name() {

		$this->data['name'] = sanitize_text_field( $this->data['name'] );

		return $this;
	}

	/**
	 * Sanitize and set connection object name.
	 *
	 * @since 1.0.0
	 *
	 * @return \WPFormsTribe\Provider\Connection
	 */
	protected function set_object() {

		$this->data['object'] = sanitize_text_field( $this->data['object'] );

		return $this;
	}

	/**
	 * Sanitize and set connection account ID.
	 *
	 * @since 1.0.0
	 *
	 * @return \WPFormsTribe\Provider\Connection
	 */
	protected function set_account_id() {

		$this->data['account_id'] = Helpers::sanitize_resource_id( $this->data['account_id'] );

		return $this;
	}

	/**
	 * Sanitize and set connection required fields.
	 *
	 * @since 1.0.0
	 *
	 * @return \WPFormsTribe\Provider\Connection
	 */
	protected function set_fields_required() {

		$fields_required = [];

		if ( empty( $this->data['fields_required'] ) || ! is_array( $this->data['fields_required'] ) ) {
			$this->data['fields_required'] = $fields_required;

			return $this;
		}

		foreach ( $this->data['fields_required'] as $field ) {
			$name     = isset( $field['name'] ) ? sanitize_text_field( $field['name'] ) : '';
			$field_id = isset( $field['field_id'] ) ? (int) $field['field_id'] : '';

			if ( wpforms_is_empty_string( $name ) || wpforms_is_empty_string( $field_id ) ) {
				continue;
			}

			$fields_required[] = [
				'name'     => $name,
				'field_id' => $field_id,
			];
		}

		$this->data['fields_required'] = $fields_required;

		return $this;
	}

	/**
	 * Sanitize and set connection optional fields.
	 *
	 * @since 1.0.0
	 *
	 * @return \WPFormsTribe\Provider\Connection
	 */
	protected function set_fields_meta() {

		$fields_meta = [];

		if ( empty( $this->data['fields_meta'] ) || ! is_array( $this->data['fields_meta'] ) ) {
			$this->data['fields_meta'] = $fields_meta;

			return $this;
		}

		foreach ( $this->data['fields_meta'] as $field ) {
			$name     = isset( $field['name'] ) ? sanitize_text_field( $field['name'] ) : '';
			$field_id = isset( $field['field_id'] ) && ! wpforms_is_empty_string( $field['field_id'] ) ? (int) $field['field_id'] : '';

			if ( wpforms_is_empty_string( $name ) && wpforms_is_empty_string( $field_id ) ) {
				continue;
			}

			$fields_meta[] = [
				'name'     => $name,
				'field_id' => $field_id,
			];
		}

		$this->data['fields_meta'] = $fields_meta;

		return $this;
	}

	/**
	 * Retrieve connection data.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_data() {

		return $this->data;
	}

	/**
	 * Retrieve defaults for connection data.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_required_data() {

		return [
			'id'         => '',
			'name'       => '',
			'account_id' => '',
			'object'     => '',
		];
	}

	/**
	 * Retrieve available object lists.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_objects_list() {

		return [
			'Account'     => esc_html__( 'Account', 'wpforms-tribe' ),
			'Campaign'    => esc_html__( 'Campaign', 'wpforms-tribe' ),
			'Case'        => esc_html__( 'Case', 'wpforms-tribe' ),
			'Contact'     => esc_html__( 'Contact', 'wpforms-tribe' ),
			'Lead'        => esc_html__( 'Lead', 'wpforms-tribe' ),
			'Opportunity' => esc_html__( 'Opportunity', 'wpforms-tribe' ),
			'Product2'    => esc_html__( 'Product', 'wpforms-tribe' ),
		];
	}

	/**
	 * Determine if connection data is valid.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_valid() {

		$valid = true;

		foreach ( $this->get_required_data() as $key => $value ) {

			if (
				! isset( $this->data[ $key ] ) ||
				wpforms_is_empty_string( $this->data[ $key ] )
			) {
				$valid = false;
				break;
			}
		}

		return $valid;
	}
}
