<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form action="<?php echo esc_url( $args['authorization_url'] ); ?>" id="wpforms-tribe-new-account-connection-form" method="get">
	<input type="hidden" name="response_type" value="code">
	<input type="hidden" name="scope" value="<?php echo esc_attr( $args['scope'] ); ?>">
	<!-- <input type="hidden" name="prompt" value="<?php echo esc_attr( $args['prompt'] ); ?>"> -->
	<input type="hidden" name="organization_id" value="83587192-a771-4a20-ba5e-c95904c2d65d">
	<input type="hidden" name="state" value="">
	<p class="wpforms-settings-provider-accounts-connect-fields">
		<input type="text" name="client_id" class="wpforms-required" placeholder="<?php esc_attr_e( 'Consumer Key', 'wpforms-tribe' ); ?>">
		<input type="text" name="client_secret" class="wpforms-required" placeholder="<?php esc_attr_e( 'Consumer Secret', 'wpforms-tribe' ); ?>">
	</p>
	<div class="wpforms-tribe-setting-field-redirect">
		<label for="wpforms-tribe-redirect-uri"><?php esc_html_e( 'Callback URL:', 'wpforms-tribe' ); ?></label>
		<input type="url" name="redirect_uri" id="wpforms-tribe-redirect-uri" class="wpforms-tribe-setting-field-redirect-input" value="<?php echo esc_attr( $args['redirect_uri'] ); ?>" readonly>
		<button type="button" class="wpforms-tribe-setting-field-redirect-copy" data-source_id="wpforms-tribe-redirect-uri">
			<span class="dashicons dashicons-admin-page"></span>
			<span class="dashicons dashicons-yes"></span>
		</button>
	</div>
	<p class="description">
		<?php
		printf(
			wp_kses( /* translators: %s - URL to the Tribe documentation page on WPForms.com. */
				__( 'Click <a href="%s" target="_blank" rel="noopener noreferrer">here</a> to learn how to connect WPForms with Tribe.', 'wpforms-tribe' ),
				[
					'a' => [
						'href'   => [],
						'target' => [],
						'rel'    => [],
					],
				]
			),
			esc_url( wpforms_utm_link( 'https://wpforms.com/docs/how-to-install-and-use-the-tribe-addon-with-wpforms/', 'Settings - Integration', 'Tribe Documentation' ) )
		);
		?>
	</p>
	<p class="error hidden">
		<?php esc_html_e( 'Something went wrong while performing an AJAX request.', 'wpforms-tribe' ); ?>
	</p>
</form>
