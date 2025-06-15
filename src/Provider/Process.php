<?php

namespace WPFormsTribe\Provider;

use Exception;
use WPForms\Tasks\Meta;
use WPFormsTribe\Helpers;
use WPFormsTribe\Provider\CustomFields\Base as FieldFormat;

/**
 * Class Process handles entries processing using the provider settings and configuration.
 *
 * @since 1.0.0
 */
class Process extends \WPForms\Providers\Provider\Process {

	/**
	 * Async task action.
	 *
	 * @since 1.0.0
	 */
	const ACTION = 'wpforms_tribe_process_action_objects_create';

	/**
	 * Connection data.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $connection;

	/**
	 * Main class that communicates with the Tribe API.
	 *
	 * @since 1.0.0
	 *
	 * @var \WPFormsTribe\Provider\Api
	 */
	private $api;

	/**
	 * Process constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param \WPFormsTribe\Provider\Core $core Core instance of the provider class.
	 */
	public function __construct( Core $core ) {

		parent::__construct( $core );

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		// Register async task handlers.
		add_action( self::ACTION, [ $this, 'task_async_action_trigger' ] );
	}

	/**
	 * Receive all wpforms_process_complete params and do the actual processing.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields    Array of form fields.
	 * @param array $entry     Submitted form content.
	 * @param array $form_data Form data and settings.
	 * @param int   $entry_id  ID of a saved entry.
	 */
	public function process( $fields, $entry, $form_data, $entry_id ) {

		if ( empty( $form_data['providers'][ $this->core->slug ] ) ) {
			return;
		}

		$this->fields    = $fields;
		$this->entry     = $entry;
		$this->form_data = $form_data;
		$this->entry_id  = $entry_id;

		$this->process_each_connection();
	}

	/**
	 * Iteration loop for connections - call action for each connection.
	 *
	 * @since 1.0.0
	 */
	protected function process_each_connection() {

		foreach ( $this->form_data['providers'][ $this->core->slug ] as $connection_id => $connection_data ) :

			try {
				$connection = new Connection( $connection_data );
			} catch ( Exception $e ) {
				continue;
			}

			if ( ! $connection->is_valid() ) {
				continue;
			}

			$this->connection = $connection->get_data();

			// Check for conditional logic.
			if ( ! $this->is_conditionals_passed() ) {
				continue;
			}

			wpforms()->get( 'tasks' )
					 ->create( self::ACTION )->async()
					 ->params( $this->connection, $this->fields, $this->form_data, $this->entry_id )
					 ->register();

		endforeach;
	}

	/**
	 * Process Conditional Logic for the provided connection.
	 *
	 * @since 1.0.0
	 *
	 * @return bool False if CL rules stopped the connection execution, true otherwise.
	 */
	protected function is_conditionals_passed() {

		$pass = $this->process_conditionals( $this->fields, $this->form_data, $this->connection );

		// Check for conditional logic.
		if ( ! $pass ) {
			wpforms_log(
				sprintf( 'The Tribe connection %s was not processed due to conditional logic.', $this->connection['name'] ?? '' ),
				$this->fields,
				[
					'type'    => [ 'provider', 'conditional_logic' ],
					'parent'  => $this->entry_id,
					'form_id' => $this->form_data['id'],
				]
			);
		}

		return $pass;
	}

	/**
	 * Process the addon async tasks.
	 *
	 * @since 1.0.0
	 *
	 * @param int $meta_id Task meta ID.
	 */
	public function task_async_action_trigger( $meta_id ) {

		$meta = $this->get_task_meta( $meta_id );

		// We expect a certain type and number of params.
		if ( ! is_array( $meta ) || count( $meta ) !== 4 ) {
			return;
		}

		// We expect a certain meta data structure for this task.
		list( $this->connection, $this->fields, $this->form_data, $this->entry_id ) = $meta;

		$this->api = $this->get_api();

		if ( null === $this->api ) {
			return;
		}

		// Finally, fire the actual action processing.
		$this->task_async_action_object_create();
	}

	/**
	 * Object: Create action.
	 *
	 * @since 1.0.0
	 */
	protected function task_async_action_object_create() {

		// Firstly, we need to receive object fields.
		$object_fields = $this->api->get_object_fields( $this->connection['object'] );

		if ( ! is_array( $object_fields ) ) {
			$this->log_errors(
				esc_html__( 'Something went wrong while performing API request for retrieving object fields.', 'wpforms-tribe' ),
				$this->connection
			);
			return;
		}

		$object_data = $this->get_object_data( $object_fields );

		if ( ! $this->required_fields_is_ok( $object_fields, $object_data ) ) {
			$this->log_errors(
				esc_html__( 'Missing valid values for all required fields.', 'wpforms-tribe' ),
				$this->connection,
				$object_data
			);
			return;
		}

		// API call.
		$response = $this->api->create_object(
			$this->connection['object'],
			$object_data
		);

		// Request or response error.
		if (
			! is_array( $response ) ||
			! isset( $response['success'] ) ||
			! $response['success']
		) {
			$this->log_errors( $response, $this->connection, $object_data );
		}

		/**
		 * Fire when request was sent successfully or not.
		 *
		 * @since 1.0.0
		 *
		 * @param object $response     Response data.
		 * @param array  $request_args Request arguments.
		 * @param array  $contact      GetResponse contact data.
		 * @param array  $connection   Connection data.
		 * @param array  $args         Additional arguments.
		 */
		do_action(
			'wpforms_tribe_provider_process_task_async_action_object_create_after',
			$response,
			$this->connection,
			[
				'form_data' => $this->form_data,
				'fields'    => $this->fields,
				'entry'     => $this->entry,
			]
		);
	}

	/**
	 * Determine if we have not empty values for all required fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields Available object fields.
	 * @param array $data   Object data.
	 *
	 * @return bool
	 */
	protected function required_fields_is_ok( $fields, $data ) {

		$result = true;

		// Firstly, we need to check if object has required fields.
		if ( empty( $fields['required'] ) ) {
			return $result;
		}

		foreach ( $fields['required'] as $field ) {

			if ( ! $this->is_valid_compound_fields( $field, $data ) ) {
				$result = true;
				break;
			}
		}

		return $result;
	}

	/**
	 * Determine if we have not empty values for all compound fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $maybe_compound_field Object field or compound with fields.
	 * @param array $object_data          Object data.
	 *
	 * @return bool
	 */
	protected function is_valid_compound_fields( $maybe_compound_field, $object_data ) {

		$fields = ! empty( $maybe_compound_field['compound_fields'] ) ? $maybe_compound_field['compound_fields'] : [ $maybe_compound_field ];
		$result = true;

		foreach ( $fields as $fld ) {

			if (
				Helpers::is_required_field( $fld ) &&
				( ! isset( $object_data[ $fld['name'] ] ) || wpforms_is_empty_string( $object_data[ $fld['name'] ] ) )
			) {
				$result = false;
				break;
			}
		}

		return $result;
	}

	/**
	 * Prepare data for creating a new object - custom fields mapping.
	 *
	 * @since 1.0.0
	 *
	 * @param array $object_fields Available object fields.
	 *
	 * @return array
	 */
	protected function get_object_data( $object_fields ) {

		$data = [];

		if ( ! empty( $this->connection['fields_required'] ) ) {
			$data += $this->format_fields( $this->connection['fields_required'], $object_fields['required'] );
		}

		if ( ! empty( $this->connection['fields_meta'] ) ) {
			$data += $this->format_fields( $this->connection['fields_meta'], $object_fields['optional'] );
		}

		return $data;
	}

	/**
	 * Apply formatting for convert submitted data to object fields data, considering a type.
	 *
	 * @since 1.0.0
	 *
	 * @param array $connection_fields Connection mapped fields.
	 * @param array $object_fields     Available object fields.
	 *
	 * @return array
	 */
	protected function format_fields( $connection_fields, $object_fields ) {

		$result = [];

		foreach ( $connection_fields as $meta ) {

			if (
				! isset( $meta['name'], $meta['field_id'] ) ||
				wpforms_is_empty_string( $meta['name'] ) ||
				wpforms_is_empty_string( $meta['field_id'] )
			) {
				continue;
			}

			$field_name = $meta['name'];
			$field_id   = $meta['field_id'];

			if ( ! isset( $this->fields[ $field_id ]['value'] ) ) {
				continue;
			}

			$format      = ( new FieldFormat( $this->fields, $this->form_data ) )->init( $object_fields[ $field_name ], $field_id );
			$field_type  = $format->get_type();
			$field_value = $format->value();

			/**
			 * Dynamic filter is allow for theme/plugin devs to change formatted value by type.
			 *
			 * @since 1.0.0
			 *
			 * @param mixed  $field_value
			 * @param object $format
			 */
			$field_value = apply_filters(
				"wpforms_tribe_provider_process_format_field_{$field_type}_value",
				$field_value,
				$format
			);

			if ( is_array( $field_value ) && ! empty( $object_fields[ $field_name ]['compound_fields'] ) ) {
				$result += $field_value;
				continue;
			}

			// We might not to pass values like empty string for creating a new object.
			if ( '' !== $field_value ) {
				$result[ $field_name ] = $field_value;
			}
		}

		return $result;
	}

	/**
	 * Get task meta data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $meta_id Task meta ID.
	 *
	 * @return array|null Null when no data available.
	 */
	protected function get_task_meta( $meta_id ) {

		$task_meta = new Meta();
		$meta      = $task_meta->get( (int) $meta_id );

		// We should actually receive something.
		if ( empty( $meta ) || empty( $meta->data ) ) {
			return null;
		}

		return $meta->data;
	}

	/**
	 * Below are API related methods and their helpers.
	 */

	/**
	 * Get the API client based on connection and provider options.
	 *
	 * @since 1.0.0
	 *
	 * @return \WPFormsTribe\Provider\Api|null Null on error.
	 */
	protected function get_api() {

		$options  = $this->core->get_provider_options();
		$accounts = array_column(
			$options,
			'state',
			'resource_owner_id'
		);

		// Validate existence of required data.
		if ( empty( $accounts[ $this->connection['account_id'] ] ) ) {
			return null;
		}

		$option_id = $accounts[ $this->connection['account_id'] ];

		// Prepare an API client.
		try {
			return new Api( $options[ $option_id ] );

		} catch ( Exception $e ) {
			$this->log_errors( $e->getMessage(), $this->connection );
			return null;
		}
	}

	/**
	 * Log an API-related error with all the data.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $response   Response data.
	 * @param array $connection Specific connection data that errored.
	 * @param mixed $extra      Extra request data.
	 */
	protected function log_errors( $response, $connection, $extra = [] ) {

		wpforms_log(
			esc_html__( 'Submission to Tribe failed.', 'wpforms-tribe' ) . "(#{$this->entry_id})",
			[
				'response'   => $response,
				'connection' => $connection,
				'extra'      => $extra,
			],
			[
				'type'    => [ 'provider', 'error' ],
				'parent'  => $this->entry_id,
				'form_id' => $this->form_data['id'],
			]
		);
	}
}
