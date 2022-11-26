<?php
/**
 * The Headless Login Client model
 *
 * @package WPGraphQL\Login\Model
 */

namespace WPGraphQL\Login\Model;

use WPGraphQL\Login\Auth\Client as AuthClient;
use WPGraphQL\Model\Model;

/**
 * Class - Client
 *
 * @property ?string $authorizationUrl
 * @property array   $clientOptions
 * @property ?string $id
 * @property boolean $isEnabled
 * @property array   $loginOptions
 * @property ?string $name
 * @property ?int    $order
 * @property string  $provider
 */
class Client extends Model {

	/**
	 * Stores the incoming Client to be modeled
	 *
	 * @var AuthClient $data
	 */
	protected $data;

	/**
	 * Client constructor.
	 *
	 * @param AuthClient $client The incoming Client to be modeled.
	 *
	 * @return void
	 */
	public function __construct( AuthClient $client ) {
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
				'authorizationUrl' => fn() => $this->data->get_authorization_url(),
				'clientOptions'    => fn() => $config['clientOptions'] + [ '__typename' => $slug ],
				'clientId'         => static fn() => $config['clientOptions']['clientId'] ?? null,
				'isEnabled'        => static fn() => ! empty( $config['isEnabled'] ),
				'loginOptions'     => fn() => $config['loginOptions'] + [ '__typename' => $slug ],
				'name'             => static fn() => $config['name'] ?? null,
				'order'            => static fn() => $config['order'] ?? null,
				'provider'         => fn() => $slug,
			];
		}
	}
}
