/* global wpforms_admin, wpformsTribeIntegrationVars */
'use strict';

/**
 * WPForms Tribe integration module.
 *
 * @since 1.0.0
 */
var WPFormsTribeIntegration = window.WPFormsTribeIntegration || ( function( document, window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 1.0.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.0.0
		 */
		init: function() {

			$( document ).on( 'wpformsReady', app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.0.0
		 */
		ready: function() {

			app.events();
		},

		/**
		 * Register JS events.
		 *
		 * @since 1.0.0
		 */
		events: function() {

			$( '#wpforms-integration-tribe' )
				.on( 'click', '.wpforms-settings-provider-submit', app.callbacks.connectClick )
				.on( 'click', '.wpforms-tribe-setting-field-redirect-copy', app.callbacks.copyUrlClick );
		},

		/**
		 * Event callbacks.
		 *
		 * @since 1.0.0
		 */
		callbacks: {

			/**
			 * Connection button was clicked.
			 *
			 * @since 1.0.0
			 */
			connectClick: function() {

				var $btn               = $( this ),
					$form              = $( $btn.prop( 'form' ) ),
					$clientIdField     = $form.find( 'input[name="client_id"]' ),
					$clientSecretField = $form.find( 'input[name="client_secret"]' ),
					clientId           = $clientIdField.val().trim(),
					clientSecret       = $clientSecretField.val().trim();

				// Checking a required data.
				if ( '' === clientId || '' === clientSecret ) {
					$form.find( '.wpforms-settings-provider-accounts-connect-fields' ).addClass( 'form-invalid' );

					var errorMessage = wpforms_admin.provider_auth_error + '<br>' + wpformsTribeIntegrationVars.required_data;
					app.modals.integrationError( errorMessage );

					return;
				}

				app.requests.integrationConnect( $btn, {
					'client_id': clientId,
					'client_secret': clientSecret,
				} );
			},

			/**
			 * Copy URL button was clicked.
			 *
			 * @since 1.0.0
			 */
			copyUrlClick: function() {

				var $this  = $( this ),
					target = $( '#' + $this.data( 'source_id' ) ).get( 0 );

				target.select();
				document.execCommand( 'Copy' );

				// Removing the class.
				$this.removeClass( 'animate' );

				// Restart a CSS animation - the actual magic.
				void this.offsetWidth;

				// Re-adding the class.
				$this.addClass( 'animate' );
			},
		},

		/**
		 * Requests.
		 *
		 * @since 1.0.0
		 */
		requests: {

			/**
			 * AJAX-request for creating a new connection.
			 *
			 * @since 1.0.0
			 *
			 * @param {object} $btn jQuery selector.
			 * @param {object} creds Credentials data.
			 */
			integrationConnect: function( $btn, creds ) {

				var $form        = $( $btn.prop( 'form' ) ),
					buttonWidth  = $btn.outerWidth(),
					buttonLabel  = $btn.text(),
					errorMessage = wpforms_admin.provider_auth_error,
					settings     = {
						url: wpforms_admin.ajax_url,
						type: 'post',
						dataType: 'json',
						data: {
							action: 'wpforms_settings_provider_add_tribe',
							nonce: wpforms_admin.nonce,
							data: creds,
						},
						beforeSend: function() {

							$form.find( '.wpforms-settings-provider-accounts-connect-fields' ).removeClass( 'form-invalid' );
							$btn
								.css( 'width', buttonWidth )
								.html( 'Connecting...' )
								.prop( 'disabled', true );
						},
					};

				// Perform an Ajax request.
				$.ajax( settings )
					.done( function( response, textStatus, jqXHR ) {

						if ( response.success ) {
							$form.find( 'input[name="state"]' ).val( app.escapeTextString( response.data.state ) );
							$form.submit();
							return;
						}

						if (
							Object.prototype.hasOwnProperty.call( response, 'data' ) &&
							Object.prototype.hasOwnProperty.call( response.data, 'error_msg' )
						) {
							errorMessage += '<br>' + response.data.error_msg;
						}

						app.modals.integrationError( errorMessage );
					} )
					.fail( function( jqXHR, textStatus, errorThrown ) {

						app.modals.integrationError( errorMessage );
					} )
					.always( function( dataOrjqXHR, textStatus, jqXHROrerrorThrown ) {

						$btn
							.css( 'width', 'auto' )
							.html( buttonLabel )
							.prop( 'disabled', false );
					} );
			},
		},

		/**
		 * Modals.
		 *
		 * @since 1.0.0
		 */
		modals: {

			/**
			 * Error handling.
			 *
			 * @since 1.0.0
			 *
			 * @param {string} error Error message.
			 */
			integrationError: function( error ) {

				$.alert( {
					title: false,
					content: error,
					icon: 'fa fa-exclamation-circle',
					type: 'orange',
					buttons: {
						confirm: {
							text: wpforms_admin.ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ],
						},
					},
				} );
			},
		},

		/**
		 * Replaces &, <, >, ", `, and ' with their escaped counterparts.
		 *
		 * @since 1.0.0
		 *
		 * @param {string} string String to escape.
		 *
		 * @returns {string} Escaped string.
		 */
		escapeTextString: function( string ) {

			return $( '<span></span>' ).text( string ).html();
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
WPFormsTribeIntegration.init();
