// eslint-disable-next-line import/no-extraneous-dependencies
import { vi } from 'vitest';
import type { WpGraphQLLogin, SettingSchema, FieldSchema } from '../../types.d';

// Default settings schema for tests
export const defaultSettingsSchema: SettingSchema = {
	plugin_settings: {
		title: 'Plugin Settings',
		description: 'Configure the plugin settings.',
		label: 'Plugin Settings',
		fields: {
			delete_data_on_deactivate: {
				description: 'Delete all plugin data on deactivation.',
				label: 'Delete Data on Deactivation',
				type: 'boolean',
				default: false,
				order: 1,
			},
			show_advanced_settings: {
				description: 'Show advanced settings.',
				label: 'Show Advanced Settings',
				type: 'boolean',
				default: false,
				order: 2,
			},
		},
	},
	access_control: {
		title: 'Access Control',
		description: 'Configure access control settings.',
		label: 'Access Control',
		fields: {
			hasAccessControlAllowCredentials: {
				description: 'Allow credentials in CORS requests.',
				label: 'Allow Credentials',
				type: 'boolean',
				default: false,
				order: 1,
			},
			hasSiteAddressInOrigin: {
				description: 'Include site address in allowed origins.',
				label: 'Site Address in Origin',
				type: 'boolean',
				default: true,
				order: 2,
			},
			shouldBlockUnauthorizedDomains: {
				description: 'Block unauthorized domains.',
				label: 'Block Unauthorized Domains',
				type: 'boolean',
				default: false,
				order: 3,
				isAdvanced: true,
			},
			additionalAuthorizedDomains: {
				description: 'Additional authorized domains.',
				label: 'Additional Authorized Domains',
				type: 'array',
				default: [],
				order: 4,
			},
			customHeaders: {
				description: 'Custom headers to add to responses.',
				label: 'Custom Headers',
				type: 'array',
				default: [],
				order: 5,
				isAdvanced: true,
			},
		},
	},
	jwt_settings: {
		title: 'JWT Settings',
		description: 'Configure JWT authentication settings.',
		label: 'JWT Settings',
		fields: {
			jwt_secret_key: {
				description: 'The secret key used to sign JWT tokens.',
				label: 'JWT Secret Key',
				type: 'jwtSecret',
				default: '',
				order: 1,
			},
		},
	},
};

// Default providers schema for tests
export const defaultProvidersSchema: Record<
	string,
	Record<
		string,
		FieldSchema & {
			properties: Record< string, FieldSchema >;
		}
	>
> = {
	oauth2: {
		name: {
			description: 'The display name of the provider.',
			label: 'Provider Name',
			type: 'string',
			default: 'OAuth2',
			properties: {},
		},
		clientOptions: {
			description: 'OAuth2 client options.',
			label: 'Client Options',
			type: 'object',
			properties: {
				clientId: {
					description: 'The OAuth2 client ID.',
					label: 'Client ID',
					type: 'string',
					default: '',
				},
				clientSecret: {
					description: 'The OAuth2 client secret.',
					label: 'Client Secret',
					type: 'string',
					default: '',
				},
				redirectUri: {
					description: 'The OAuth2 redirect URI.',
					label: 'Redirect URI',
					type: 'string',
					default: '',
				},
			},
		},
		loginOptions: {
			description: 'Login options.',
			label: 'Login Options',
			type: 'object',
			properties: {
				createUserIfNoneExists: {
					description: 'Create a new user if none exists.',
					label: 'Create User if None Exists',
					type: 'boolean',
					default: false,
				},
				linkExistingUsers: {
					description: 'Link existing users.',
					label: 'Link Existing Users',
					type: 'boolean',
					default: false,
				},
			},
		},
	},
	siteToken: {
		name: {
			description: 'The display name of the provider.',
			label: 'Provider Name',
			type: 'string',
			default: 'Site Token',
			properties: {},
		},
		clientOptions: {
			description: 'Site token client options.',
			label: 'Client Options',
			type: 'object',
			properties: {
				headerKey: {
					description: 'The header key for the site token.',
					label: 'Header Key',
					type: 'string',
					default: 'X-WPGraphQL-Login',
				},
				secretKey: {
					description: 'The secret key for validation.',
					label: 'Secret Key',
					type: 'string',
					default: '',
				},
			},
		},
		loginOptions: {
			description: 'Login options.',
			label: 'Login Options',
			type: 'object',
			properties: {
				metaKey: {
					description: 'The user meta key.',
					label: 'Meta Key',
					type: 'string',
					default: '',
				},
			},
		},
	},
};

// Create mock hooks object
const createMockHooks = () => ( {
	applyFilters: vi.fn(
		< T >( _hookName: string, value: T, ..._args: unknown[] ): T => value
	),
	addFilter: vi.fn(),
	removeFilter: vi.fn(),
	hasFilter: vi.fn( () => false ),
	doAction: vi.fn(),
	addAction: vi.fn(),
	removeAction: vi.fn(),
	hasAction: vi.fn( () => false ),
	doingAction: vi.fn( () => false ),
	doingFilter: vi.fn( () => false ),
	currentAction: vi.fn( () => null ),
	currentFilter: vi.fn( () => null ),
	actions: {},
	filters: {},
} );

// Default wpGraphQLLogin object
export const createDefaultWpGraphQLLogin = (): WpGraphQLLogin => ( {
	hooks: createMockHooks(),
	settings: {
		...defaultSettingsSchema,
		providers: defaultProvidersSchema,
	} as WpGraphQLLogin[ 'settings' ],
	nonce: 'test-nonce-12345',
	secret: {
		hasKey: true,
		isConstant: false,
	},
} );

// Global reference type
type GlobalWithWpGraphQLLogin = typeof globalThis & {
	wpGraphQLLogin: WpGraphQLLogin;
};

/**
 * Sets up the wpGraphQLLogin global mock with default values.
 * Called automatically by vitest.setup.ts
 */
export const setupWpGraphQLLoginMock = (): void => {
	( globalThis as GlobalWithWpGraphQLLogin ).wpGraphQLLogin =
		createDefaultWpGraphQLLogin();
};

/**
 * Resets the wpGraphQLLogin global mock to default values.
 * Useful for resetting state between tests.
 */
export const resetWpGraphQLLoginMock = (): void => {
	( globalThis as GlobalWithWpGraphQLLogin ).wpGraphQLLogin =
		createDefaultWpGraphQLLogin();
};

// Alias for compatibility with existing tests
export const resetWpGraphQLLoginMocks = resetWpGraphQLLoginMock;

/**
 * Updates specific properties on the wpGraphQLLogin global.
 * Useful for testing different configurations.
 */
export const updateWpGraphQLLoginMock = (
	updates: Partial< WpGraphQLLogin >
): void => {
	const current = ( globalThis as GlobalWithWpGraphQLLogin ).wpGraphQLLogin;
	( globalThis as GlobalWithWpGraphQLLogin ).wpGraphQLLogin = {
		...current,
		...updates,
		settings: {
			...current.settings,
			...( updates.settings || {} ),
		},
	};
};

/**
 * Gets the current wpGraphQLLogin global.
 */
export const getWpGraphQLLoginMock = (): WpGraphQLLogin => {
	return ( globalThis as GlobalWithWpGraphQLLogin ).wpGraphQLLogin;
};
