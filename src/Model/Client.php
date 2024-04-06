<?php
/**
 * The Headless Login Client model
 *
 * @package WPGraphQL\Login\Model
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Model;

use WPGraphQL\Model\Model;

/**
 * Class - Client
 *
 * @property ?string $authorizationUrl
 * @property array   $clientOptions
 * @property ?string $id
 * @property bool    $isEnabled
 * @property array   $loginOptions
 * @property ?string $name
 * @property ?int    $order
 * @property string  $provider
 */
class Client extends Model {
	/**
	 * Stores the incoming Client to be modeled
	 *
	 * @var \WPGraphQL\Login\Auth\Client $data
	 */
	protected $data;

	/**
	 * Client constructor.
	 *
	 * @param \WPGraphQL\Login\Auth\Client $client The incoming Client to be modeled.
	 *
	 * @return void
	 */
	public function __construct( \WPGraphQL\Login\Auth\Client $client ) {
		$this->data = $client;

		$allowed_restricted_field = [
			'authorizationUrl',
			'clientId',
			'isEnabled',
			'name',
			'order',
			'provider',
			'type',
		];

		parent::__construct( 'manage_options', $allowed_restricted_field );
	}

	/**
	 * Initialize the object
	 *
	 * @return void
	 */
	protected function init() {
		if ( empty( $this->fields ) ) {
			$config = $this->data->get_config();

			$slug = $this->data->get_provider_slug();

			$this->fields = [
				'authorizationUrl' => fn () => $this->data->get_authorization_url(),
				'clientOptions'    => static fn () => $config['clientOptions'] + [ '__typename' => $slug ],
				'clientId'         => static fn () => $config['clientOptions']['clientId'] ?? null,
				'isEnabled'        => static fn () => ! empty( $config['isEnabled'] ),
				'loginOptions'     => static fn () => $config['loginOptions'] + [ '__typename' => $slug ],
				'name'             => static fn () => $config['name'] ?? null,
				'order'            => static fn () => $config['order'] ?? null,
				'provider'         => static fn () => $slug,
			];
		}
	}
}
