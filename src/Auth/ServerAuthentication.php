<?php
/**
 * Handles authentication on the WordPress server, even before our plugin is loaded.
 *
 * @package WPGraphQL\Login\Auth
 * @since 0.2.0
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth;

use WPGraphQL\Login\Vendor\AxeWP\Common\Contracts\Interfaces\Registrable;

/**
 * Class - ServerAuthentication
 */
class ServerAuthentication implements Registrable {
	/**
	 * Whether the determine_current_user filter is being run.
	 *
	 * This is used to avoid an infinite loop.
	 *
	 * @var bool
	 */
	private bool $is_determine_current_user_filter = false;

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void {
		// Filter how WordPress determines the current user.
		add_filter( 'determine_current_user', [ $this, 'determine_current_user' ], 99 );
	}

	/**
	 * Filters the current user for the request.
	 *
	 * @param int|mixed $user_id The user ID.
	 *
	 * @return int|mixed The user ID.
	 */
	public function determine_current_user( $user_id ) {
		// Bail if this is already being run.
		if ( $this->is_determine_current_user_filter ) {
			return $user_id;
		}

		// Set the flag to true.
		$this->is_determine_current_user_filter = true;

		// Validate the token.
		try {
			$token = TokenManager::validate_token();

			// If the token is invalid, return the existing user.
			if ( empty( $token ) || is_wp_error( $token ) ) {
				return $user_id;
			}

			// Get the user from the token.
			return empty( $token->data->user->id ) ? $user_id : absint( $token->data->user->id );
		} finally {
			// Set the flag to false.
			$this->is_determine_current_user_filter = false;
		}
	}
}
