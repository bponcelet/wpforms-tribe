<?php

namespace WPFormsTribe\Provider;

use WPFormsTribe\Vendor\League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use WPFormsTribe\Vendor\League\OAuth2\Client\Provider\GenericProvider;
use WPFormsTribe\Vendor\League\OAuth2\Client\Token\AccessToken;
use WPFormsTribe\DB\Notice;
use WPFormsTribe\DB\Creds;
use WPFormsTribe\Helpers;
use Exception;
use WP_Error;

/**
 * Class Auth.
 *
 * @since 1.0.0
 */
class Auth {

	/**
	 * Scopes that we need to authentication.
	 *
	 * @since 1.0.0
	 */
	const SCOPES = [ //phpcs:ignore PHPCompatibility.InitialValue.NewConstantArraysUsingConst.Found
		'read',
		'write',
		'offline',
	];

	/**
	 * Prompts that we need to authentication.
	 *
	 * @since 1.0.0
	 */
	const PROMPTS = [ //phpcs:ignore PHPCompatibility.InitialValue.NewConstantArraysUsingConst.Found
		'login',
		'consent',
	];

	/**
	 * Base domain.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected static $domain = 'https://auth.tribecrm.nl';

	/**
	 * Client.
	 *
	 * @since 1.0.0
	 *
	 * @var mixed
	 */
	protected $client = null;

	/**
	 * Account options.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $account;

	/**
	 * Redirect URI.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $redirect_uri;

	/**
	 * Authentication process error.
	 *
	 * @since 1.0.0
	 *
	 * @var WP_Error
	 */
	protected $error;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $options Options data.
	 */
	public function __construct( $options ) {

		$this->account = wp_parse_args(
			(array) $options,
			[
				'date'  => time(),
				'label' => '',
			]
		);

		$this->error = new WP_Error();
	}

	/**
	 * Retrieve the redirect URI.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_redirect_uri() {

		if ( empty( $this->redirect_uri ) ) {
			$this->redirect_uri = admin_url( 'admin.php' );
		}

		return $this->redirect_uri;
	}

	/**
	 * Init and get the AccessToken object.
	 *
	 * @since 1.0.0
	 *
	 * @return AccessToken|null
	 */
	public function get_access_token() {

		return ! empty( $this->account['access_token'] ) ? new AccessToken( (array) $this->account ) : null;
	}

	/**
	 * Init and get the OAuth2 Client object.
	 *
	 * @since 1.0.0
	 *
	 * @return GenericProvider|WP_Error
	 */
	public function get_client() {

		// Doesn't load client twice.
		if ( ! empty( $this->client ) ) {
			return $this->client;
		}

		// Do not process if we don't have both Client ID & Client Secret.
		if ( ! $this->is_client_saved() ) {
			return $this->client;
		}

		$this->client = new GenericProvider(
			[
				'clientId'                => $this->account['client_id'],
				'clientSecret'            => $this->account['client_secret'],
				'state'                   => $this->account['state'],
				'redirectUri'             => $this->get_redirect_uri(),
				'urlAuthorize'            => self::get_authorization_url(),
				'urlAccessToken'          => $this->get_access_token_url(),
				'urlResourceOwnerDetails' => $this->get_owner_details_url(),
				'scopes'                  => self::SCOPES,
				'scopeSeparator'          => ' ',
			]
		);

		// Token is valid.
		if ( $this->is_valid_token() ) {
			return $this->client;
		}

		$this->token_process();

		// If we receive any errors.
		if ( $this->has_error() ) {
			return $this->error;
		}

		return $this->client;
	}

	/**
	 * Token process.
	 *
	 * @since 1.0.0
	 */
	protected function token_process() {

		if (
			$this->is_auth_required() &&
			! empty( $this->account['auth_code'] )
		) {
			// We don't have tokens, but have auth code.
			$this->generate_token();

		} else {
			$this->refresh_token();
		}

		do_action( 'wpforms_tribe_provider_auth_token_process', $this->error );
	}

	/**
	 * Generate an access token.
	 *
	 * @since 1.0.0
	 */
	protected function generate_token() {

		// Try to get an access token using the authorization code grant.
		try {
			$access_token = $this->client->getAccessToken(
				'authorization_code',
				[ 'code' => $this->account['auth_code'] ]
			);

			if ( $this->update_account( $access_token, true ) ) {
				$this->error->add(
					'auth_success',
					esc_html__( 'Tribe Integration: account was connected successfully.', 'wpforms-tribe' ),
					$this->account['state']
				);
			}
		} catch ( IdentityProviderException $e ) {
			$response = $e->getResponseBody();

			$this->error->add(
				'auth_provider_error',
				/* translators: %s - response error description. */
				sprintf( esc_html__( 'Tribe Integration: account connection failed (%s).', 'wpforms-tribe' ), rtrim( $response['error_description'], '.' ) ),
				$this->account['state']
			);

		} catch ( Exception $e ) {
			$this->error->add(
				'auth_general_error',
				/* translators: %s - response error description. */
				sprintf( esc_html__( 'Tribe Integration: account connection failed (%s).', 'wpforms-tribe' ), rtrim( $e->getMessage(), '.' ) ),
				$this->account['state']
			);
		}

		// Reset Auth code. It's valid for 15 minutes anyway.
		$this->update_auth_code( '' );
	}

	/**
	 * Generate a refresh token.
	 *
	 * @since 1.0.0
	 */
	protected function refresh_token() {

		$access_token = $this->get_access_token();

		if ( empty( $access_token ) ) {
			$this->error->add(
				'empty_access_token',
				esc_html__( 'Tribe Integration: account connection update failed (no access token found).', 'wpforms-tribe' ),
				$this->account['state']
			);
			return;
		}

		if ( empty( $access_token->getRefreshToken() ) ) {
			$this->error->add(
				'empty_refresh_token',
				esc_html__( 'Tribe Integration: account connection update failed (no refresh token found).', 'wpforms-tribe' ),
				$this->account['state']
			);
			return;
		}

		try {
			$new_access_token = $this->client->getAccessToken(
				'refresh_token',
				[ 'refresh_token' => $access_token->getRefreshToken() ]
			);

			if ( $this->update_account( $new_access_token ) ) {
				$this->error->add(
					'auth_success',
					esc_html__( 'Tribe Integration: account connection updated successfully.', 'wpforms-tribe' ),
					$this->account['state']
				);
			}
		} catch ( IdentityProviderException $e ) {
			$response = $e->getResponseBody();

			$this->error->add(
				'auth_provider_error',
				/* translators: %s - response error description. */
				sprintf( esc_html__( 'Tribe Integration: account connection update failed (%s).', 'wpforms-tribe' ), rtrim( $response['error_description'], '.' ) ),
				$this->account['state']
			);

		} catch ( Exception $e ) {
			$this->error->add(
				'auth_general_error',
				/* translators: %s - response error description. */
				sprintf( esc_html__( 'Tribe Integration: account connection update failed (%s).', 'wpforms-tribe' ), rtrim( $e->getMessage(), '.' ) ),
				$this->account['state']
			);
		}
	}

	/**
	 * Get authorization url to begin OAuth flow.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_authorization_url() {

		return self::$domain . '/oauth2/auth';
	}

	/**
	 * Get access token url to retrieve token.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_access_token_url() {

		return self::$domain . '/oauth2/token';
	}

	/**
	 * Get provider url to fetch user details.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_owner_details_url() {

		return self::$domain . '/services/oauth2/userinfo';
	}

	/**
	 * Complete the auth process.
	 *
	 * @since 1.0.0
	 */
	public static function init() {

		/**
		 * Checking several conditions:
		 *
		 * 1. We should have permissions
		 * 2. We should have required data
		 * 3. We should be coming from somewhere
		 * 4. Ajax is not supported
		 */
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if (
			! wpforms_current_user_can( 'create_forms' ) ||
			empty( $_GET['state'] ) ||
			empty( $_SERVER['HTTP_REFERER'] ) ||
			wp_doing_ajax()
		) {
			return;
		}

		$state = sanitize_text_field( wp_unslash( $_GET['state'] ) );
		$code  = ! empty( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		$error = ! empty( $_GET['error_description'] ) ? sanitize_text_field( wp_unslash( $_GET['error_description'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! empty( $error ) ) {
			do_action(
				'wpforms_tribe_provider_auth_init_has_error',
				new WP_Error(
					'auth_has_error',
					/* translators: %s - response error description. */
					sprintf( esc_html__( 'Tribe Integration: account connection failed (%s).', 'wpforms-tribe' ), rtrim( $error, '.' ) ),
					$state
				)
			);

			wp_safe_redirect( remove_query_arg( [ 'code', 'error_description' ] ) );
			exit;
		}

		// We should have an auth code in query string.
		if ( empty( $code ) ) {
			do_action(
				'wpforms_tribe_provider_auth_init_no_code',
				new WP_Error(
					'auth_no_code',
					sprintf( esc_html__( 'Tribe Integration: account connection failed (authorization code not passed).', 'wpforms-tribe' ) ),
					$state
				)
			);
			return;
		}

		$creds = Creds::get();

		// All things are ready for saving an auth code.
		if ( ! empty( $creds[ $state ] ) ) {
			( new self( $creds[ $state ] ) )->process_auth( $code );
		}

		wp_safe_redirect( remove_query_arg( [ 'code', 'error_description' ] ) );
		exit;
	}

	/**
	 * Check and process the auth code for this provider.
	 *
	 * @since 1.0.0
	 *
	 * @param string $code Auth code.
	 */
	public function process_auth( $code ) {

		$this->update_auth_code( $code );

		// Remove old notices and cached accounts.
		Notice::clear();

		// Retrieve the token and user details, save errors if any.
		$this->get_client();
	}

	/**
	 * Update an authorization code.
	 *
	 * @since 1.0.0
	 *
	 * @param string $code Authorization code.
	 */
	protected function update_auth_code( $code ) {

		// To save in currently retrieved options array.
		$this->account['auth_code'] = $code;
	}

	/**
	 * Update an account options.
	 *
	 * @since 1.0.0
	 *
	 * @param AccessToken $access_token Access token.
	 * @param bool        $is_new       True if it a new account.
	 *
	 * @return bool Whether update was successful.
	 */
	protected function update_account( $access_token, $is_new = false ) {

		$this->account['access_token'] = $access_token->getToken();

		/**
		 * Update a refresh token if it not empty.
		 *
		 * @link https://www.oauth.com/oauth2-servers/access-tokens/refreshing-access-tokens/#response
		 */
		$refresh_token = $access_token->getRefreshToken();
		if ( ! empty( $refresh_token ) ) {
			$this->account['refresh_token'] = $access_token->getRefreshToken();
		}

		$token_data = $access_token->getValues();
		if ( ! empty( $token_data['instance_url'] ) ) {
			$this->account['instance_url'] = esc_url_raw( $token_data['instance_url'] );
		}

		// Update account details if it a new.
		$updated = true;
		if ( $is_new ) {
			$updated = $this->update_owner_details( $access_token );
		}

		// Reset Auth code.
		$this->update_auth_code( '' );

		// Save account data in DB.
		if ( $updated ) {
			wpforms_update_providers_options(
				wpforms_tribe()->provider->slug,
				$this->account,
				$this->account['state']
			);
		}

		return $updated;
	}

	/**
	 * Get and update user-related details.
	 *
	 * @since 1.0.0
	 *
	 * @param AccessToken $access_token Access token.
	 *
	 * @return bool Whether update was successful.
	 */
	protected function update_owner_details( $access_token ) {

		$owner_data = $this->get_owner_details( $access_token );

		if ( ! $owner_data ) {
			return false;
		}

		$user_id = Helpers::sanitize_resource_id( $owner_data['user_id'] );

		// This account already connected.
		if ( $this->is_already_connected( $user_id ) ) {
			$this->error->add(
				'account_already_exists',
				esc_html__( 'Tribe Integration: account connection failed (this account already connected).', 'wpforms-tribe' ),
				$this->account['state']
			);

			return false;
		}

		// Save account data in property.
		$user_email                         = sanitize_email( $owner_data['email'] );
		$this->account['resource_owner_id'] = $user_id;
		$this->account['user_email']        = $user_email;
		$this->account['label']             = $user_email;

		return true;
	}

	/**
	 * Request and return the resource owner of given access token.
	 *
	 * @since 1.0.0
	 *
	 * @param AccessToken $access_token Access token.
	 *
	 * @return array
	 */
	protected function get_owner_details( $access_token ) {

		$result = [];

		try {
			$resource_owner = $this->client->getResourceOwner( $access_token );
			$result         = $resource_owner->toArray();

			// Resource error.
			if ( empty( $result['user_id'] ) ) {
				$this->error->add(
					'resource_owner_error',
					esc_html__( 'Tribe Integration: data retrieval failed (resource owner error).', 'wpforms-tribe' ),
					$this->account['state']
				);
				$result = [];
			}
		} catch ( IdentityProviderException $e ) {
			$response = $e->getResponseBody();

			$this->error->add(
				'resource_owner_provider_error',
				/* translators: %s - response error description. */
				sprintf( esc_html__( 'Tribe Integration: data retrieval failed (%s).', 'wpforms-tribe' ), rtrim( $response['error_description'], '.' ) ),
				$this->account['state']
			);

		} catch ( Exception $e ) {
			$this->error->add(
				'resource_owner_general_error',
				/* translators: %s - response error description. */
				sprintf( esc_html__( 'Tribe Integration: data retrieval failed (%s).', 'wpforms-tribe' ), rtrim( $e->getMessage(), '.' ) ),
				$this->account['state']
			);
		}

		return $result;
	}

	/**
	 * Check if we received any errors in auth process.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function has_error() {

		return ! in_array( 'auth_success', $this->error->get_error_codes(), true );
	}

	/**
	 * Determine if account already connected.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Account ID.
	 *
	 * @return bool
	 */
	protected function is_already_connected( $id ) {

		// Get all connected accounts.
		$acc_ids = array_column( wpforms_tribe()->provider->get_provider_options(), 'resource_owner_id' );

		return in_array( $id, $acc_ids, true );
	}

	/**
	 * Whether user saved Client ID and Client Secret or not.
	 * Both options are required.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function is_client_saved() {

		return ! empty( $this->account['client_id'] ) && ! empty( $this->account['client_secret'] );
	}

	/**
	 * Whether we have an access and refresh tokens.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function is_auth_required() {

		return empty( $this->account['access_token'] ) || empty( $this->account['refresh_token'] );
	}

	/**
	 * Check if access token is valid.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether access token exists and we can retrieve an external data.
	 */
	protected function is_valid_token() {

		$access_token = $this->get_access_token();

		if ( ! ( $access_token instanceof AccessToken ) ) {
			return false;
		}

		// Try to receive user details data.
		if ( empty( $this->get_owner_details( $access_token ) ) ) {
			return false;
		}

		return true;
	}
}
