/* global WPForms, wpforms_builder, wpf, wpformsTribeBuilderVars */
'use strict';

/**
 * WPForms Providers Builder Tribe module.
 *
 * @since 1.0.0
 */
WPForms.Admin.Builder.Providers.Tribe = WPForms.Admin.Builder.Providers.Tribe || ( function( document, window, $ ) {

	/**
	 * Private functions and properties.
	 *
	 * @since 1.0.0
	 *
	 * @type {object}
	 */
	var __private = {

		/**
		 * jQuery object for holder.
		 *
		 * @since 1.0.0
		 *
		 * @type {jQuery}
		 */
		$holder: null,

		/**
		 * Config contains all configuration properties.
		 *
		 * @since 1.0.0
		 *
		 * @type {object.<string, *>}
		 */
		config: {

			/**
			 * List of Tribe templates that should be compiled.
			 *
			 * @since 1.0.0
			 *
			 * @type {object}
			 */
			templates: [
				'wpforms-tribe-builder-content-connection',
				'wpforms-tribe-builder-content-connection-required-fields',
				'wpforms-tribe-builder-content-connection-error',
				'wpforms-tribe-builder-content-connection-conditionals',
			],
		},

		/**
		 * Sometimes in DOM we might have placeholders or temporary connection IDs.
		 * We need to replace them with actual values.
		 *
		 * @since 1.0.0
		 *
		 * @param {string} connectionId New connection ID to replace to.
		 * @param {object} $connection  jQuery DOM connection element.
		 */
		replaceConnectionIds: function( connectionId, $connection ) {

			// Replace old temporary %connection_id% from PHP code with the new one.
			$connection
				.find( 'input, textarea, select, label' ).each( function() {

					var $this = $( this );

					if ( $this.attr( 'name' ) ) {
						$this.attr( 'name', $this.attr( 'name' ).replace( /%connection_id%/gi, connectionId ) );
					}

					if ( $this.attr( 'id' ) ) {
						$this.attr( 'id', $this.attr( 'id' ).replace( /%connection_id%/gi, connectionId ) );
					}

					if ( $this.attr( 'for' ) ) {
						$this.attr( 'for', $this.attr( 'for' ).replace( /%connection_id%/gi, connectionId ) );
					}

					if ( $this.attr( 'data-name' ) ) {
						$this.attr( 'data-name', $this.attr( 'data-name' ).replace( /%connection_id%/gi, connectionId ) );
					}
				} );
		},

		/**
		 * Whether we have an account ID in a list of all available accounts.
		 *
		 * @since 1.0.0
		 *
		 * @param {string} connectionAccID Connection account ID to check.
		 * @param {Array}  accounts        Array of objects, usually received from API.
		 *
		 * @returns {boolean} True if connection already exists.
		 */
		connectionAccountExists: function( connectionAccID, accounts ) {

			if ( _.isEmpty( accounts ) ) {
				return false;
			}

			// New connections, that have not been saved don't have the account ID yet.
			if ( _.isEmpty( connectionAccID ) ) {
				return true;
			}

			return _.has( accounts, connectionAccID );
		},
	};

	/**
	 * Public functions and properties.
	 *
	 * @since 1.0.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Current provider slug.
		 *
		 * @since 1.0.0
		 *
		 * @type {string}
		 */
		provider: 'tribe',

		/**
		 * This is a shortcut to the WPForms.Admin.Builder.Providers object,
		 * that handles the parent all-providers functionality.
		 *
		 * @since 1.0.0
		 *
		 * @type {object}
		 */
		Providers: {},

		/**
		 * This is a shortcut to the WPForms.Admin.Builder.Templates object,
		 * that handles all the template management.
		 *
		 * @since 1.0.0
		 *
		 * @type {object}
		 */
		Templates: {},

		/**
		 * This is a shortcut to the WPForms.Admin.Builder.Providers.cache object,
		 * that handles all the cache management.
		 *
		 * @since 1.0.0
		 *
		 * @type {object}
		 */
		Cache: {},

		/**
		 * This is a flag for ready state.
		 *
		 * @since 1.0.0
		 *
		 * @type {boolean}
		 */
		isReady: false,

		/**
		 * Start the engine.
		 *
		 * Run initialization on providers panel only.
		 *
		 * @since 1.0.0
		 */
		init: function() {

			// DOM is ready.
			$( function() {

				// Show notice (if we have) about authorization result.
				if (
					_.isObject( window.wpformsTribeBuilderVars ) &&
					_.has( window.wpformsTribeBuilderVars, 'authNotice' )
				) {
					app.modal( wpformsTribeBuilderVars.authNotice );
				}
			} );

			// We are requesting/loading a Providers panel.
			if ( 'providers' === wpf.getQueryString( 'view' ) ) {
				$( '#wpforms-panel-providers' ).on( 'WPForms.Admin.Builder.Providers.ready', app.ready );
			}

			// We have switched to Providers panel.
			$( document ).on( 'wpformsPanelSwitched', function( e, panel ) {

				if ( 'providers' === panel ) {
					app.ready();
				}
			} );
		},

		/**
		 * Initialized once the DOM and Providers are fully loaded.
		 *
		 * @since 1.0.0
		 */
		ready: function() {

			if ( app.isReady ) {
				return;
			}

			// Done by reference, so we are not doubling memory usage.
			app.Providers = WPForms.Admin.Builder.Providers;
			app.Templates = WPForms.Admin.Builder.Templates;
			app.Cache     = app.Providers.cache;

			// Save a jQuery object for provider holder.
			__private.$holder = app.Providers.getProviderHolder( app.provider ).find( '.wpforms-builder-provider-connections' );

			// Register custom Underscore.js templates.
			app.Templates.add( __private.config.templates );

			// Register a handler for "Add New Account" process.
			app.Providers.ui.account.registerAddHandler( app.provider, app.processAccountAdd );

			// Events registration.
			app.bindUIActions();
			app.bindTriggers();

			app.processInitial();

			// Save a flag for ready state.
			app.isReady = true;
		},

		/**
		 * Process various events as a response to UI interactions.
		 *
		 * @since 1.0.0
		 */
		bindUIActions: function() {

			app.Providers.getProviderHolder( app.provider )
				.on( 'connectionCreate', app.connection.callbacks.create )
				.on( 'connectionDelete', app.connection.callbacks.delete )
				.on( 'change', '.js-wpforms-builder-tribe-provider-connection-account', app.ui.account.changeCallback )
				.on( 'change', '.js-wpforms-builder-tribe-provider-connection-object', app.ui.object.changeCallback );

			$( document ).on( 'click', '.wpforms-tribe-setting-field-redirect-copy', app.ui.copyUrlClick );
		},

		/**
		 * Fire certain events on certain actions, specific for related connections.
		 * These are not directly caused by user manipulations.
		 *
		 * @since 1.0.0
		 */
		bindTriggers: function() {

			// Connection data was loaded.
			__private.$holder.on( 'connectionsDataLoaded', function( e, data ) {

				if ( _.isEmpty( data.connections ) ) {
					return;
				}

				for ( var connectionId in data.connections ) {
					app.connection.callbacks.generate( {
						connection: data.connections[ connectionId ],
						conditional: data.conditionals[ connectionId ],
					} );
				}
			} );

			// Connection was generated.
			__private.$holder.on( 'connectionGenerated', function( e, data ) {

				var $connection = app.connection.getById( data.connection.id );

				if ( ( _.has( data.connection, 'isNew' ) && data.connection.isNew ) ) {

					// Run replacing temporary connection ID, if it a new connection.
					__private.replaceConnectionIds( data.connection.id, $connection );
					return;
				}

				$( '.js-wpforms-builder-tribe-provider-connection-object', $connection ).trigger( 'change', [ $connection ] );
			} );
		},

		/**
		 * Compile template with data if any and display them on a page.
		 *
		 * @since 1.0.0
		 */
		processInitial: function() {

			__private.$holder.prepend( app.tmpl.callbacks.commonsHTML() );
			app.connection.callbacks.dataLoad();
		},

		/**
		 * Process the account creation in FormBuilder.
		 *
		 * @since 1.0.0
		 *
		 * @param {object} modal jQuery-Confirm modal object.
		 *
		 * @returns {boolean} False.
		 */
		processAccountAdd: function( modal ) {

			var $form              = modal.$content.find( 'form' ),
				$error             = modal.$content.find( '.error' ),
				$clientIdField     = $form.find( 'input[name="client_id"]' ),
				$clientSecretField = $form.find( 'input[name="client_secret"]' ),
				clientId           = $clientIdField.val().trim(),
				clientSecret       = $clientSecretField.val().trim();

			// Checking a required data.
			if ( _.isEmpty( clientId ) || _.isEmpty( clientSecret ) ) {
				$error.show();
				modal.setType( 'red' );
				$clientIdField.addClass( 'wpforms-error' );
				$clientSecretField.addClass( 'wpforms-error' );

				return false;
			}

			// Required data are exist and not empty.
			$error.hide();
			modal.setType( 'blue' );
			$clientIdField.removeClass( 'wpforms-error' );
			$clientSecretField.removeClass( 'wpforms-error' );

			app.Providers.ajax
				.request( app.provider, {
					data: {
						'task': 'account_save',
						data: {
							'client_id': clientId,
							'client_secret': clientSecret,
						},
					},
				} )
				.done( function( response ) {

					if ( response.success ) {
						$form.find( 'input[name="state"]' ).val( wpf.sanitizeHTML( response.data.state ) );
						wpf.savedState = wpf.getFormState( '#wpforms-builder-form' );
						$form.submit();
						return;
					}

					if (
						_.has( response, 'data' ) &&
						_.has( response.data, 'error_msg' )
					) {
						$error.html( response.data.error_msg );
					}

					$error.show();
				} );

			return false;
		},

		/**
		 * Connection property.
		 *
		 * @since 1.0.0
		 */
		connection: {

			/**
			 * Sometimes we might need to a get a connection DOM element by its ID.
			 *
			 * @since 1.0.0
			 *
			 * @param {string} connectionId Connection ID to search for a DOM element by.
			 *
			 * @returns {jQuery} jQuery object for connection.
			 */
			getById: function( connectionId ) {

				return __private.$holder.find( '.wpforms-builder-provider-connection[data-connection_id="' + connectionId + '"]' );
			},

			/**
			 * Connection methods.
			 *
			 * @since 1.0.0
			 */
			callbacks: {

				/**
				 * Create a connection using the user entered name.
				 *
				 * @since 1.0.0
				 *
				 * @param {object} event Event object.
				 * @param {string} name  Connection name.
				 */
				create: function( event, name ) {

					var connectionId = ( new Date().getTime() ).toString( 16 ),
						connection   = {
							id: connectionId,
							name: name,
							isNew: true,
						};

					app.Cache.addTo( app.provider, 'connections', connectionId, connection );

					app.connection.callbacks.generate( {
						connection: connection,
					} );
				},

				/**
				 * Connection is deleted - delete a cache as well.
				 *
				 * @since 1.0.0
				 *
				 * @param {object} event       Event object.
				 * @param {object} $connection jQuery DOM element for a connection.
				 */
				delete: function( event, $connection ) {

					var $eHolder = app.Providers.getProviderHolder( app.provider );

					if ( ! $connection.closest( $eHolder ).length ) {
						return;
					}

					var connectionId = $connection.data( 'connection_id' );

					if ( _.isString( connectionId ) ) {
						app.Cache.deleteFrom( app.provider, 'connections', connectionId );
					}
				},

				/**
				 * Get the template and data for a connection and process it.
				 *
				 * @since 1.0.0
				 *
				 * @param {Object} data Connection data.
				 */
				generate( data ) {
					const tmplConnection = app.Templates.get( `wpforms-${ app.provider }-builder-content-connection` );
					const tmplConditional = $( `#tmpl-wpforms-${ app.provider }-builder-content-connection-conditionals` ).length
						? app.Templates.get( `wpforms-${ app.provider }-builder-content-connection-conditionals` )
						: app.Templates.get( `wpforms-providers-builder-content-connection-conditionals` );
					const conditional = ( _.has( data.connection, 'isNew' ) && data.connection.isNew ) ? tmplConditional() : data.conditional;
					const accounts = app.Cache.get( app.provider, 'accounts' );

					/**
					 * Add connection to the holder.
					 */
					function addConnectionToHolder() {
						__private.$holder.prepend(
							tmplConnection( {
								objects: app.Cache.get( app.provider, 'objects' ),
								accounts,
								connection: data.connection,
								conditional,
								provider: app.provider,
							} )
						);
						__private.$holder.trigger( 'connectionGenerated', [ data ] );
					}

					/**
					 * Handle AJAX response.
					 *
					 * @param {Object} response AJAX response.
					 */
					function handleAjaxResponse( response ) {
						if ( response.success && _.has( response.data, 'accounts' ) ) {
							app.Cache.set( app.provider, 'accounts', response.data.accounts );
							if ( __private.connectionAccountExists( data.connection.account_id, response.data.accounts ) ) {
								addConnectionToHolder();
							}
							return;
						}

						if ( _.has( response.data, 'error_msg' ) ) {
							const errorContainer = __private.$holder.find( '.wpforms-builder-provider-connections-error' );
							for ( const errorMsg of response.data.error_msg ) {
								errorContainer.append( errorMsg );
							}
							errorContainer.show();
						}
					}

					/*
					 * We may or may not receive accounts previously.
					 * If yes - render instantly, if no - request them via AJAX.
					 */
					if ( ! _.isEmpty( accounts ) ) {
						if ( __private.connectionAccountExists( data.connection.account_id, accounts ) ) {
							addConnectionToHolder();
						}
					} else {
						// We need to get the live list of accounts, as nothing is in cache.
						app.Providers.ajax.request( app.provider, {
							data: { task: 'accounts_get' },
						} ).done( handleAjaxResponse );
					}
				},

				/**
				 * Fire AJAX-request to retrieve the list of all saved connections.
				 *
				 * @since 1.0.0
				 */
				dataLoad: function() {

					app.Providers.ajax
						.request( app.provider, {
							data: {
								task: 'connections_get',
							},
						} )
						.done( function( response ) {

							if (
								! response.success ||
								! _.has( response.data, 'connections' )
							) {
								return;
							}

							// Save response data to "cache" as a copy.
							app.Cache.set( app.provider, 'connections', jQuery.extend( {}, response.data.connections ) );
							app.Cache.set( app.provider, 'conditionals', jQuery.extend( {}, response.data.conditionals ) );
							app.Cache.set( app.provider, 'objects', jQuery.extend( {}, response.data.objects ) );

							if ( ! _.isEmpty( response.data.accounts ) ) {
								app.Cache.set( app.provider, 'accounts', jQuery.extend( {}, response.data.accounts ) );
							}

							__private.$holder.trigger( 'connectionsDataLoaded', [ response.data ] );
						} );
				},
			},
		},

		/**
		 * All methods that modify UI of a page.
		 *
		 * @since 1.0.0
		 */
		ui: {

			/**
			 * Account methods.
			 *
			 * @since 1.0.0
			 */
			account: {

				/**
				 * Callback-function on change event.
				 *
				 * @since 1.0.0
				 */
				changeCallback: function() {

					var $this       = $( this ),
						$connection = $this.closest( '.wpforms-builder-provider-connection' ),
						$object     = $( '.js-wpforms-builder-tribe-provider-connection-object', $connection );

					// Clear all connection data if account was changed.
					$( '.wpforms-builder-tribe-provider-actions-data', $connection ).empty();
					$object.prop( 'selectedIndex', 0 );

					// If account is empty.
					if ( _.isEmpty( $this.val() ) ) {

						// Block `Object` select box.
						$object.prop( 'disabled', true );

					} else {

						// Unblock `Object` select box.
						$object.prop( 'disabled', false );
						$this.removeClass( 'wpforms-error' );
					}
				},
			},

			/**
			 * Object methods.
			 *
			 * @since 1.0.0
			 */
			object: {

				/**
				 * Callback-function on change event.
				 *
				 * @since 1.0.0
				 */
				changeCallback: function() {

					var $this       = $( this ),
						$connection = $this.closest( '.wpforms-builder-provider-connection' ),
						$account    = $( '.js-wpforms-builder-tribe-provider-connection-account', $connection );

					$( '.wpforms-builder-tribe-provider-actions-data', $connection ).empty();
					$this.removeClass( 'wpforms-error' );

					app.actions.init( {
						'action': 'object_create',
						'target': $this,
						'option_id': $account.find( 'option:selected' ).data( 'option_id' ),
						'account_id': $account.val(),
						'connection_id': $connection.data( 'connection_id' ),
					} );
				},
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
		 * Actions property.
		 *
		 * @since 1.0.0
		 */
		actions: {

			/**
			 * Actions initialization.
			 *
			 * @since 1.0.0
			 *
			 * @param {object} args Arguments.
			 */
			init: function( args ) {

				if ( 'object_create' === args.action ) {
					app.actions.object.create.init( args );
				}
			},

			/**
			 * Object actions.
			 *
			 * @since 1.0.0
			 */
			object: {

				/**
				 * Create action.
				 *
				 * @since 1.0.0
				 */
				create: {

					/**
					 * Create object initialization.
					 *
					 * @since 1.0.0
					 *
					 * @param {object} args Arguments.
					 */
					init: function( args ) {

						if ( _.isEmpty( args.account_id ) || _.isEmpty( args.target.val() ) ) {
							return;
						}

						var self         = this,
							customFields = app.Cache.get( app.provider, 'customFields' );

						if (
							! _.isObject( customFields ) ||
							_.isEmpty( customFields ) ||
							! _.has( customFields, args.account_id ) ||
							! _.has( customFields[ args.account_id ], args.target.val() )
						) {

							self.request( args );
						} else {
							self.render( args );
						}
					},

					/**
					 * AJAX request.
					 *
					 * @since 1.0.0
					 *
					 * @param {Object} args Arguments.
					 */
					request( args ) {
						const self = this,
							$error = __private.$holder.find( '.wpforms-builder-provider-connections-error' ),
							objectName = args.target.val();

						// Make ajax request to get object data (fields).
						app.Providers.ajax
							.request( app.provider, {
								data: {
									task: 'object_data_get',
									// eslint-disable-next-line camelcase
									object_name: objectName,
									// eslint-disable-next-line camelcase
									option_id: args.option_id,
								},
							} )
							.done( function( response ) {
								if (
									! response.success ||
									_.isEmpty( response.data )
								) {
									// If request error message is not empty replace default error message.
									if ( ! _.isEmpty( response.data.error_msg ) ) {
										$error.html( response.data.error_msg );
									}

									__private.$holder.find( '.wpforms-builder-provider-connections-error' ).show();
									return;
								}

								// Cache response data.
								var customFields = app.Cache.get( app.provider, 'customFields' ),
									result = {};

								if ( _.isUndefined( customFields ) ) {
									customFields = {};
									app.Cache.set( app.provider, 'customFields', customFields );
								}

								if ( ! _.has( customFields, args.account_id ) ) {
									customFields[ args.account_id ] = {};
									app.Cache.set( app.provider, 'customFields', customFields );
								}

								result = customFields[ args.account_id ];
								result[ objectName ] = response.data;

								app.Cache.addTo( app.provider, 'customFields', args.account_id, result );

								// Render template.
								self.render( args );
							} );
					},

					/**
					 * Render HTML.
					 *
					 * @since 1.0.0
					 *
					 * @param {object} args Arguments.
					 */
					render: function( args ) {

						var fields         = wpf.getFields(),
							optionalFields = app.tmpl.callbacks.optionalFieldsHTML( args, fields ),
							requiredFields = app.tmpl.callbacks.requiredFieldsHTML( args, fields ),
							$connection    = app.connection.getById( args.connection_id );

						// Display compiled template with custom data.
						$connection
							.find( '.wpforms-builder-tribe-provider-actions-data' )
							.html( optionalFields );

						if ( ! _.isEmpty( requiredFields ) ) {
							$connection
								.find( '.wpforms-builder-provider-connection-fields-table tbody' )
								.prepend( requiredFields );
						}

						__private.$holder.trigger( 'connectionRendered', [ app.provider, args.connection_id ] );
					},
				},
			},
		},

		/**
		 * All methods for JavaScript templates.
		 *
		 * @since 1.0.0
		 */
		tmpl: {

			/**
			 * Wrap functions for compile JavaScript templates and receive their HTML.
			 *
			 * @since 1.0.0
			 */
			callbacks: {

				/**
				 * Compile and retrieve a HTML for common elements.
				 *
				 * @since 1.0.0
				 *
				 * @return {string} Compiled HTML.
				 */
				commonsHTML() {
					const tmplError = app.Templates.get( 'wpforms-' + app.provider + '-builder-content-connection-error' );

					return tmplError();
				},

				/**
				 * Compile and retrieve a HTML for "Custom Fields Table".
				 *
				 * @since 1.0.0
				 *
				 * @param {Object} args   Arguments
				 * @param {Object} fields Fields
				 *
				 * @return {string} Compiled HTML.
				 */
				optionalFieldsHTML( args, fields ) {
					const objectName = args.target.val(),
						tmplFields = app.Templates.get( 'wpforms-providers-builder-content-connection-fields' ),
						customFields = app.Cache.getById( app.provider, 'customFields', args.account_id ),
						connectionCache = app.Cache.getById( app.provider, 'connections', args.connection_id );
					let connectionData;

					if ( connectionCache.object !== objectName ) {
						connectionData = _.clone( connectionCache );
						// eslint-disable-next-line camelcase
						connectionData.fields_meta = [];
						// eslint-disable-next-line camelcase
						connectionData.fields_required = [];
					} else {
						connectionData = connectionCache;
					}

					return tmplFields( {
						connection: connectionData,
						fields,
						provider: {
							placeholder: wpformsTribeBuilderVars.l10n.provider_placeholder,
							slug: app.provider,
							fields: customFields[ objectName ].optional,
						},
					} );
				},

				/**
				 * Compile and retrieve a HTML for "Custom Fields Table".
				 *
				 * @since 1.0.0
				 *
				 * @param {object} args Arguments
				 * @param {object} fields Fields
				 *
				 * @returns {string} Compiled HTML.
				 */
				requiredFieldsHTML: function( args, fields ) {

					var tmplFields   = app.Templates.get( 'wpforms-' + app.provider + '-builder-content-connection-required-fields' ),
						customFields = app.Cache.getById( app.provider, 'customFields', args.account_id );

					if ( ! _.has( customFields[ args.target.val() ], 'required' )
					) {
						return '';
					}

					return tmplFields( {
						'connection': app.Cache.getById( app.provider, 'connections', args.connection_id ),
						'fields': fields,
						'provider': {
							'slug': app.provider,
							'fields': customFields[ args.target.val() ].required,
						},
					} );
				},
			},
		},

		/**
		 * Modal.
		 *
		 * @since 1.0.0
		 *
		 * @param {object} data Modal data (type and message).
		 */
		modal: function( data ) {

			var title = wpforms_builder.heads_up,
				icon  = 'fa fa-exclamation-circle',
				color = 'orange';

			// Checking required data.
			if (
				! _.has( data, 'type' ) ||
				! _.has( data, 'message' )
			) {
				return;
			}

			// Change modal attributes for success type.
			if ( 'success' === data.type ) {
				title = wpforms_builder.saved;
				icon  = 'fa fa-check-circle';
				color = 'green';
			}

			$.alert( {
				title: title,
				content: data.message,
				icon: icon,
				type: color,
				buttons: {
					confirm: {
						text: wpforms_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				},
			} );
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
WPForms.Admin.Builder.Providers.Tribe.init();
