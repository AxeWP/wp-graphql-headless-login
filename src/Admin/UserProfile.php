<?php
/**
 * Registers Actions to the user profile page.
 *
 * @package WPGraphQL\Login\Admin
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Admin;

use WPGraphQL\Login\Auth\ProviderConfig\Password;
use WPGraphQL\Login\Auth\ProviderRegistry;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Auth\User;

/**
 * Class - UserProfile
 */
class UserProfile {
	/**
	 * {@inheritDoc}
	 */
	public static function init(): void {
		add_action( 'show_user_profile', [ self::class, 'user_identity_fields' ] );
		add_action( 'edit_user_profile', [ self::class, 'user_identity_fields' ] );
		// Add admin ajax for unlinking user identities.
		add_action( 'wp_ajax_graphql_login_unlink_identity', [ self::class, 'unlink_identity' ] );
		// Add admin ajax for revoking user secret.
		add_action( 'wp_ajax_graphql_login_revoke_user_secret_key', [ self::class, 'revoke_secret' ] );
	}

	/**
	 * Add the user identity fields to the user profile page.
	 *
	 * @param \WP_User $user The WP_User object.
	 */
	public static function user_identity_fields( \WP_User $user ): void {
		$providers = ProviderRegistry::get_instance()->get_providers();

		$identities = User::get_user_identities( $user->ID );

		?>
		<style type="text/css">
			.button.is-destructive {
				color: #cc1818;
				box-shadow: none;
				white-space: nowrap;
				background: transparent;
				padding: 6px;
				outline: 1px solid transparent;
				border: none;
			}
			.button.is-destructive:hover:not(:disabled) {
				box-shadow: inset 0 0 0 1px #cc1818;
				color: #cc1818
			}
			.button.is-destructive:focus:not(:disabled) {
				box-shadow: inset 0 0 0 1px #fff,0 0 0 var(--wp-admin-border-width-focus) #cc1818;
				color: #cc1818
			}
			.button.is-destructive:active:not(:disabled) {
				background: #ccc;
				box-shadow: none
			}
			.button.is-tertiary:disabled,.button.is-tertiary[aria-disabled=true],.button.is-tertiary[aria-disabled=true]:hover {
				color: #828282;
				background: #eaeaea;
				transform: none;
				opacity: 1;
				box-shadow: none;
				outline: none
			}
		</style>
		<h2 style="padding-top:1rem"><?php echo esc_html__( 'Headless Login - JWT Secret', 'wp-graphql-headless-login' ); ?></h2>

		<?php self::revoke_user_secret_key_field( $user->ID ); ?>

		<h2><?php echo esc_html__( 'Linked User Identities', 'wp-graphql-headless-login' ); ?></h2>

		<table class="form-table">
			<tbody>
				<?php
				foreach ( array_keys( $providers ) as $provider ) {
					// Exclude the password provider.
					if ( Password::get_slug() === $provider ) {
						continue;
					}

					self::provider_identity_field( $user->ID, $provider, $providers[ $provider ]::get_name(), $identities[ $provider ] ?? '' );
				}
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Renders the row for a single provider identity.
	 *
	 * @param int    $user_id The user ID.
	 * @param string $provider_slug The provider slug.
	 * @param string $provider_name The provider name.
	 * @param string $identity The identity.
	 */
	protected static function provider_identity_field( int $user_id, string $provider_slug, string $provider_name, string $identity ): void {
		$meta_key = User::get_identity_meta_key( $provider_slug );

		?>
		<tr>
			<th>
				<label for="<?php echo esc_attr( $meta_key ); ?>">
					<?php echo esc_html( $provider_name ); ?>
				</label>
			</th>
			<td>
				<input
					type="text"
					class="regular-text"
					id="<?php echo esc_attr( $meta_key ); ?>"
					name="<?php echo esc_attr( $meta_key ); ?>"
					value="<?php echo ! empty( $identity ) ? esc_attr( $identity ) : esc_attr__( 'Not linked', 'wp-graphql-headless-login' ); ?>"
					disabled
				/>
			</td>
			<?php
			if ( ! empty( $identity ) && get_current_user_id() === $user_id ) {
				self::unlink_identity_button( $user_id, $provider_slug );
			}
			?>
		</tr>
		<?php
	}

	/**
	 * Renders the unlink identity button.
	 * When clicked, the button will unlink the user from the provider.
	 *
	 * @param int    $user_id  The user ID.
	 * @param string $provider The provider slug.
	 */
	protected static function unlink_identity_button( int $user_id, string $provider ): void {
		?>
		<td>
			<button
				type="button"
				class="button is-tertiary is-destructive"
				id="unlink-user-identity-<?php echo esc_attr( $provider ); ?>"
				data-user-id="<?php echo absint( $user_id ); ?>"
				data-provider="<?php echo esc_attr( $provider ); ?>"
			>
				<?php esc_html_e( 'Unlink', 'wp-graphql-headless-login' ); ?>
			</button>
		</td>
		<script type="text/javascript">
			(function() {
				const button = document.querySelector( 'button#unlink-user-identity-<?php echo esc_attr( $provider ); ?>' );
				button.addEventListener( 'click', function( event ) {
					event.preventDefault();

					const buttonRow = button.closest( 'tr' );

					// Change the button text to a spinner.
					button.innerHTML = '<span class="spinner is-active"></span>';

					const data = {
						'action': 'graphql_login_unlink_identity',
						'user_id': <?php echo absint( $user_id ); ?>,
						'provider': '<?php echo esc_attr( $provider ); ?>',
						'nonce': '<?php echo esc_attr( wp_create_nonce( 'wp-graphql-headless-login-unlink-identity' ) ); ?>',
					};

					function displayFailureNotice() {
						// If the response is not okay, show a notice at the the first row of the table.
						button.innerHTML = '<?php esc_html_e( 'Unlink', 'wp-graphql-headless-login' ); ?>';
						const notice = document.createElement( 'tr' );
						notice.classList.add( 'notice', 'notice-error', 'is-dismissible' );
						notice.innerHTML = '<td colspan="3"><?php esc_html_e( 'There was an error unlinking the identity.', 'wp-graphql-headless-login' ); ?></td>';
						// add the notice after the current row
						buttonRow.parentNode.insertBefore( notice, buttonRow.nextSibling );
					}

					fetch( ajaxurl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded',
						},
						body: Object.keys( data ).map( function( key ) {
							return encodeURIComponent( key ) + '=' + encodeURIComponent( data[key] );
						}).join( '&' ),
					}).then( function( response ) {
						// If the response is okay, replace the second column in the row with a success notice.
						if ( response.ok ) {
							const notice = document.createElement( 'td' );
							notice.setAttribute( 'colspan', '2' );
							notice.innerHTML = '<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Identity unlinked.', 'wp-graphql-headless-login' ); ?></p></div>';

							// Remove the second and third columns in the current row
							buttonRow.removeChild( buttonRow.children[1] );
							buttonRow.removeChild( buttonRow.children[1] );
							// Add the notice to the row.
							buttonRow.appendChild( notice );
						} else {
							displayFailureNotice()
						}
					}).catch( function( error ) {
						console.warn( error );
						displayFailureNotice();
					});
				});
			})();
		</script>
		<?php
	}

	/**
	 * Renders field to revoke user secret key.
	 *
	 * @param int $user_id The user ID.
	 */
	protected static function revoke_user_secret_key_field( int $user_id ): void {
		?>
		<table class="form-table">
			<tr>
				<th>
					<label for="<?php echo esc_attr( 'revoke-user-secret-key' ); ?>">
						<?php echo esc_attr__( 'Revoke User Secret', 'wp-graphql-headless-login' ); ?>
					</label>
				</th>
				<td>
					<button
						type="button"
						class="button is-tertiary is-destructive"
						id="revoke-user-secret-key"
						data-user-id="<?php echo absint( $user_id ); ?>"
					>
						<?php esc_html_e( 'Revoke', 'wp-graphql-headless-login' ); ?>
					</button>
					<p class="description">
						<?php esc_html_e( 'Revokes the user secret key. This will invalidate all existing tokens,and log the user out of the frontend on all devices.', 'wp-graphql-headless-login' ); ?>
				</td>
			</tr>
		</table>
		<script type="text/javascript">
			(function() {
				const button = document.querySelector( 'button#revoke-user-secret-key' );
				button.addEventListener( 'click', function( event ) {
					event.preventDefault();

					const buttonRow = button.closest( 'tr' );

					// Change the button text to a spinner.
					button.innerHTML = '<span class="spinner is-active"></span>';

					const data = {
						'action': 'graphql_login_revoke_user_secret_key',
						'user_id': <?php echo absint( $user_id ); ?>,
						'nonce': '<?php echo esc_attr( wp_create_nonce( 'wp-graphql-headless-login-revoke-user-secret-key' ) ); ?>',
					};

					function displayFailureNotice() {
						// If the response is not okay, replace the description below the button with a failure notice.
						button.innerHTML = '<?php esc_html_e( 'Revoke', 'wp-graphql-headless-login' ); ?>';
						// Get the description element.
						const description = buttonRow.querySelector( 'p.description' );
						// Replace the description with a failure notice.
						description.innerHTML = '<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'There was an error revoking the user secret key.', 'wp-graphql-headless-login' ); ?></p></div>';
					}

					fetch( ajaxurl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded',
						},
						body: Object.keys( data ).map( function( key ) {
							return encodeURIComponent( key ) + '=' + encodeURIComponent( data[key] );
						}).join( '&' ),
					}).then( function( response ) {
						// If the response is okay, replace the description below the buttion with a success notice.
						if ( response.ok ) {
							button.innerHTML = '<?php esc_html_e( 'Revoke', 'wp-graphql-headless-login' ); ?>';
							// Get the description element.
							const description = buttonRow.querySelector( 'p.description' );
							// Replace the description with a success notice.
							description.innerHTML = '<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'User secret revoked.', 'wp-graphql-headless-login' ); ?></p></div>';
						} else {
							displayFailureNotice()
						}
					}).catch( function( error ) {
						console.warn( error );
						displayFailureNotice();
					});
				});
			})();
		</script>
		<?php
	}

	/**
	 * Unlinks the user from the provider.
	 */
	public static function unlink_identity(): void {
		// Check the nonce.
		check_ajax_referer( 'wp-graphql-headless-login-unlink-identity', 'nonce' );

		// Get the user ID.
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		if ( empty( $user_id ) ) {
			wp_send_json_error( __( 'Invalid user ID.', 'wp-graphql-headless-login' ) );
		}

		// If the user ID is not the current user, check if the current user has the capability to edit users.
		if ( get_current_user_id() !== $user_id && ! current_user_can( 'edit_users' ) ) {
			wp_send_json_error( __( 'You do not have permission to unlink identities for this user.', 'wp-graphql-headless-login' ) );
		}

		// Get the provider.
		$provider = isset( $_POST['provider'] ) ? sanitize_text_field( $_POST['provider'] ) : '';
		if ( empty( $provider ) ) {
			wp_send_json_error( __( 'Invalid provider.', 'wp-graphql-headless-login' ) );
		}

		// Unlink the user.
		$status = User::unlink_user_identity( $user_id, $provider );
		if ( false === $status ) {
			wp_send_json_error( __( 'There was an error unlinking the identity.', 'wp-graphql-headless-login' ) );
		}

		wp_send_json_success();
	}

	/**
	 * Revokes the user secret key.
	 */
	public static function revoke_secret(): void {
		// Check the nonce.
		check_ajax_referer( 'wp-graphql-headless-login-revoke-user-secret-key', 'nonce' );

		// Get the user ID.
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		if ( empty( $user_id ) ) {
			wp_send_json_error( __( 'Invalid user ID.', 'wp-graphql-headless-login' ) );
		}

		// Revoke the user secret key.
		$status = TokenManager::refresh_user_secret( $user_id, true );
		if ( is_wp_error( $status ) ) {
			wp_send_json_error(
				sprintf(
					/* translators: %s: Error message. */
					__( 'There was an error revoking the user secret key: %s.', 'wp-graphql-headless-login' ),
					$status->get_error_message()
				)
			);
		}

		wp_send_json_success();
	}
}
