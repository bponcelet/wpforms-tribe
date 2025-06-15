<?php

namespace WPFormsTribe\Provider;

use Exception;
use InvalidArgumentException;
use WPFormsTribe\Helpers;
use WPFormsTribe\Vendor\League\OAuth2\Client\Token\AccessToken;

/**
 * Class API.
 *
 * @since 1.0.0
 */
class Api {

	/**
	 * Base URL for API requests.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $instance_url;

	/**
	 * Actual API version.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $version = '48.0';

	/**
	 * Auth instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Auth
	 */
	protected $client;

	/**
	 * AccessToken instance.
	 *
	 * @since 1.0.0
	 *
	 * @var AccessToken
	 */
	protected $access_token;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $acc_options Account options.
	 *
	 * @throws \InvalidArgumentException Emitted when something went wrong.
	 */
	public function __construct( $acc_options ) {

		if (
			! is_array( $acc_options ) ||
			empty( $acc_options['state'] ) ||
			empty( $acc_options['instance_url'] )
		) {
			throw new InvalidArgumentException( esc_html__( 'Unexpected account options.', 'wpforms-tribe' ) );
		}

		// Auth call.
		$this->client = ( new Auth( $acc_options ) )->get_client();

		// If we received some error in auth processing.
		if ( is_wp_error( $this->client ) ) {
			throw new InvalidArgumentException( $this->client->get_error_message() );
		}

		// Access Token could be refreshed, so we should receive an account option from DB.
		$provider_options = wpforms_tribe()->provider->get_provider_options();

		if ( empty( $provider_options[ $acc_options['state'] ] ) ) {
			throw new InvalidArgumentException( esc_html__( 'Unexpected account options.', 'wpforms-tribe' ) );
		}

		$this->access_token = new AccessToken( $provider_options[ $acc_options['state'] ] );
		$this->instance_url = ltrim( $acc_options['instance_url'], '/' );
	}

	/**
	 * Send request for retrieving object fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Object name.
	 *
	 * @throws Exception Emits exception on requests failure.
	 *
	 * @return array|null Array if we received an successfully result, otherwise - null.
	 */
	public function get_object_fields( $name ) {

		try {
			$response = $this->request( 'GET', "sobjects/{$name}/describe" );

			if ( is_array( $response ) && ! empty( $response[0]['errorCode'] ) ) {
				throw new Exception( esc_html( $response[0]['message'] ) );
			}
		} catch ( Exception $e ) {
			throw new Exception( esc_html( $e->getMessage() ) );
		}

		if ( ! is_array( $response ) || empty( $response['fields'] ) ) {
			return null;
		}

		return $this->filter_object_fields( $response['fields'] );
	}

	/**
	 * Filter object fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields Object fields data.
	 *
	 * @return array
	 */
	protected function filter_object_fields( $fields ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$result = [
			'required' => [],
			'optional' => [],
		];

		$types     = array_column( $fields, 'type', 'name' );
		$labels    = array_column( $fields, 'label', 'name' );
		$compounds = [];

		foreach ( $fields as $field ) {

			// Skip fields with those parameters (they are not available for filling).
			if ( ! $field['createable'] || $field['deprecatedAndHidden'] ) {
				continue;
			}

			$key = Helpers::is_required_field( $field ) ? 'required' : 'optional';

			if ( empty( $field['compoundFieldName'] ) ) {
				$result[ $key ][ $field['name'] ] = $field;
				continue;
			}

			// Compound - it's group of fields for Tribe's Address, Name, Location.
			$compound_name  = $field['compoundFieldName'];
			$compound_group = [
				'name'            => $compound_name,
				'label'           => $labels[ $compound_name ],
				'type'            => $types[ $compound_name ],
				'compound_fields' => [],
			];

			// Mark all fields from one compound and with different types (required|optional) like required fields.
			if (
				! empty( $compounds[ $compound_name ] ) &&
				$key !== $compounds[ $compound_name ]
			) {
				if ( 'required' !== $key ) {
					$key = 'required';
				} else {
					$compound_group              = $result[ $compounds[ $compound_name ] ][ $compound_name ];
					$compounds[ $compound_name ] = $key;
				}
			}

			if ( empty( $result[ $key ][ $compound_name ] ) ) {
				$result[ $key ][ $compound_name ] = $compound_group;
				$compounds[ $compound_name ]      = $key;
			}

			$result[ $key ][ $compound_name ]['compound_fields'][] = $field;
		}

		return $result;
	}

	/**
	 * Send a request for creating a new object.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Object name.
	 * @param array  $args Request arguments.
	 *
	 * @return mixed
	 */
	public function create_object( $name, $args ) {

		try {
			$response = $this->request( 'POST', "sobjects/{$name}", $args );
		} catch ( Exception $e ) {
			$response = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Wrapper-method for sending a remote request.
	 *
	 * @since 1.0.0
	 *
	 * @param string $method Request method.
	 * @param string $path   Request path.
	 * @param array  $data   Request arguments.
	 *
	 * @return mixed
	 * @throws Exception Emits exception on requests failure.
	 */
	protected function request( $method, $path, $data = null ) {

		$path        = rtrim( $path, '/' );
		$request_url = "{$this->instance_url}/services/data/v{$this->version}/{$path}";

		if ( is_array( $data ) ) {
			$data = (object) $data;
		}

		if ( null !== $data ) {
			$data = wp_json_encode( $data );
		}

		$options = [
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body'    => $data,
		];
		$options = apply_filters( 'wpforms_tribe_provider_api_request_options', $options, $method, $path );

		$request = $this->client->getAuthenticatedRequest(
			$method,
			esc_url( $request_url ),
			$this->access_token,
			$options
		);

		$response = $this->client->getParsedResponse( $request );

		if ( ! is_array( $response ) ) {
			throw new Exception(
				'Invalid response received from Authorization Server. Expected JSON.'
			);
		}

		return $response;
	}
}
