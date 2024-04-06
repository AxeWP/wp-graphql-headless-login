<?php
/**
 * The Password provider class.
 *
 * @package WPGraphQL\Login\Auth\ProviderConfig
 * @since 0.0.6
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth\ProviderConfig;

use GraphQL\Error\UserError;

/**
 * Class - Password
 */
class Password extends ProviderConfig {
	/**
	 * {@inheritdoc}
	 */
	public static function get_type(): string {
		return 'password';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_name(): string {
		return __( 'Password', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_slug(): string {
		return 'password';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return \WP_User|\WP_Error|false
	 */
	public function authenticate_and_get_user_data( array $input ) {
		// Get the args from the input.
		$args = $this->prepare_mutation_input( $input );

		if ( empty( $args['username'] ) || empty( $args['password'] ) ) {
			return new \WP_Error(
				'graphql-headless-login-missing-credentials',
				__( 'Missing username or password.', 'wp-graphql-headless-login' )
			);
		}

		$user = wp_authenticate( $args['username'], $args['password'] );

		// Obsfucate any authentication errors.
		if ( $user instanceof \WP_Error ) {
			graphql_debug( wp_strip_all_tags( $user->get_error_message() ) );

			return false;
		}

		return $user;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_user_from_data( $user ) {
		if ( $user instanceof \WP_Error || $user instanceof \WP_User ) {
			return $user;
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array{username: ?string, password: ?string}
	 *
	 * @throws \GraphQL\Error\UserError
	 */
	protected function prepare_mutation_input( array $input ): array {
		if ( ! isset( $input['credentials'] ) ) {
			throw new UserError(
				esc_html__( 'The PASSWORD provider requires the use of the `credentials` input arg.', 'wp-graphql-headless-login' )
			);
		}

		return [
			'username' => ! empty( $input['credentials']['username'] ) ? sanitize_text_field( $input['credentials']['username'] ) : null,
			'password' => ! empty( $input['credentials']['password'] ) ? trim( $input['credentials']['password'] ) : null,
		];
	}
}
