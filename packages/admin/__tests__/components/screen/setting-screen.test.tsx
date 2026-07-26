import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { vi, beforeEach, afterEach } from 'vitest';
import { SettingsScreen } from '@/admin/components/screen/setting-screen';
import { SettingsProvider } from '@/admin/contexts/settings-context';
import {
	setupWpGraphQLLoginMock,
	resetWpGraphQLLoginMocks,
} from '../../mocks/wordpress-global.mock';
import apiFetch from '@wordpress/api-fetch';

vi.mock( '@wordpress/api-fetch' );

const mockDispatch = {
	createNotice: vi.fn(),
	createErrorNotice: vi.fn(),
};

vi.mock( '@wordpress/data', () => ( {
	useDispatch: vi.fn( () => mockDispatch ),
} ) );

vi.mock( '@wordpress/notices', () => ( {
	store: {},
} ) );

vi.mock( '@/admin/components/fields', () => ( {
	Fields: ( {
		fields,
		values,
		setValue,
	}: {
		fields: Record< string, unknown >;
		values: Record< string, unknown >;
		setValue: ( value: Record< string, unknown > ) => void;
	} ) => (
		<div
			data-testid="fields-component"
			data-fields={ JSON.stringify( Object.keys( fields ) ) }
			data-values={ JSON.stringify( values ) }
		>
			<button
				type="button"
				onClick={ () => setValue( { test_field: 'updated-value' } ) }
			>
				Update Value
			</button>
		</div>
	),
} ) );

describe( 'SettingsScreen Component', () => {
	beforeEach( () => {
		setupWpGraphQLLoginMock();
		vi.clearAllMocks();
		// `clearAllMocks` leaves unconsumed `*Once` queues behind, which would
		// otherwise shadow the next test's response.
		vi.mocked( apiFetch ).mockReset().mockResolvedValue( {} );
	} );

	afterEach( () => {
		resetWpGraphQLLoginMocks();
		vi.clearAllMocks();
	} );

	const getWpGlobal = () =>
		global as typeof globalThis & {
			wpGraphQLLogin?: {
				settings?: {
					providers?: Record< string, unknown >;
					test_settings?: {
						fields?: Record< string, unknown >;
						title?: string;
					};
					existing_settings?: {
						fields?: Record< string, unknown >;
						label?: string;
					};
				};
				providers?: Record< string, unknown >;
			};
		};

	describe( 'Renders Fields component with correct settings', () => {
		it( 'renders Fields component with correct settings schema', async () => {
			const wpGraphQLLogin = getWpGlobal().wpGraphQLLogin;
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings.test_settings = {
					fields: {
						field1: {
							label: 'Field 1',
							type: 'string',
						},
						field2: {
							label: 'Field 2',
							type: 'string',
						},
					},
				};
			}

			vi.mocked( apiFetch ).mockResolvedValue( {
				test_settings: {
					field1: 'value1',
					field2: 'value2',
				},
			} );

			const { container } = render(
				<SettingsProvider>
					<SettingsScreen settingKey="test_settings" />
				</SettingsProvider>
			);

			await waitFor( () => {
				const fieldsComponent = container.querySelector(
					'[data-testid="fields-component"]'
				);
				expect( fieldsComponent ).toBeInTheDocument();
				expect( fieldsComponent ).toHaveAttribute(
					'data-fields',
					JSON.stringify( [ 'field1', 'field2' ] )
				);
			} );
		} );
	} );

	describe( 'Renders save button', () => {
		it( 'renders save button', async () => {
			const wpGraphQLLogin = getWpGlobal().wpGraphQLLogin;
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings.test_settings = {
					fields: {
						field1: {
							label: 'Field 1',
							type: 'string',
						},
					},
				};
			}

			vi.mocked( apiFetch ).mockResolvedValue( {
				test_settings: {
					field1: 'value1',
				},
			} );

			render(
				<SettingsProvider>
					<SettingsScreen settingKey="test_settings" />
				</SettingsProvider>
			);

			const saveButton = await screen.findByRole( 'button', {
				name: /save/i,
			} );
			expect( saveButton ).toBeInTheDocument();
		} );

		it( 'save button is disabled when not dirty', async () => {
			const wpGraphQLLogin = getWpGlobal().wpGraphQLLogin;
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings.test_settings = {
					fields: {
						field1: {
							label: 'Field 1',
							type: 'string',
						},
					},
				};
			}

			vi.mocked( apiFetch ).mockResolvedValue( {
				test_settings: {
					field1: 'value1',
				},
			} );

			render(
				<SettingsProvider>
					<SettingsScreen settingKey="test_settings" />
				</SettingsProvider>
			);

			const saveButton = await screen.findByRole( 'button', {
				name: /save/i,
			} );
			expect( saveButton ).toBeDisabled();
		} );

		it( 'save button is enabled when dirty', async () => {
			const wpGraphQLLogin = getWpGlobal().wpGraphQLLogin;
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings.test_settings = {
					fields: {
						field1: {
							label: 'Field 1',
							type: 'string',
						},
					},
				};
			}

			vi.mocked( apiFetch ).mockResolvedValue( {
				test_settings: {
					field1: 'value1',
				},
			} );

			render(
				<SettingsProvider>
					<SettingsScreen settingKey="test_settings" />
				</SettingsProvider>
			);

			const updateButton = await screen.findByText( 'Update Value' );
			fireEvent.click( updateButton );

			const saveButton = await screen.findByRole( 'button', {
				name: /save/i,
			} );
			expect( saveButton ).not.toBeDisabled();
		} );
	} );

	describe( 'Save button triggers saveSettings', () => {
		it( 'calls saveSettings when save button is clicked', async () => {
			const wpGraphQLLogin = getWpGlobal().wpGraphQLLogin;
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings.test_settings = {
					fields: {
						field1: {
							label: 'Field 1',
							type: 'string',
						},
					},
				};
			}

			vi.mocked( apiFetch )
				.mockResolvedValueOnce( {
					test_settings: {
						field1: 'value1',
					},
				} )
				.mockResolvedValueOnce( {
					test_settings: {
						field1: 'updated-value',
					},
				} );

			render(
				<SettingsProvider>
					<SettingsScreen settingKey="test_settings" />
				</SettingsProvider>
			);

			const updateButton = await screen.findByText( 'Update Value' );
			fireEvent.click( updateButton );

			const saveButton = await screen.findByRole( 'button', {
				name: /save/i,
			} );
			fireEvent.click( saveButton );

			await waitFor( () => {
				expect( apiFetch ).toHaveBeenCalledWith( {
					path: 'wp-graphql-login/v1/settings',
					method: 'POST',
					data: {
						slug: 'test_settings',
						values: expect.any( Object ),
					},
				} );
			} );
		} );
	} );

	describe( 'Shows success notice on save', () => {
		it( 'creates success notice after successful save', async () => {
			const wpGraphQLLogin = getWpGlobal().wpGraphQLLogin;
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings.test_settings = {
					fields: {
						field1: {
							label: 'Field 1',
							type: 'string',
						},
					},
				};
			}

			vi.mocked( apiFetch )
				.mockResolvedValueOnce( {
					test_settings: {
						field1: 'value1',
					},
				} )
				.mockResolvedValueOnce( {
					test_settings: {
						field1: 'updated-value',
					},
				} );

			render(
				<SettingsProvider>
					<SettingsScreen settingKey="test_settings" />
				</SettingsProvider>
			);

			const updateButton = await screen.findByText( 'Update Value' );
			fireEvent.click( updateButton );

			const saveButton = await screen.findByRole( 'button', {
				name: /save/i,
			} );
			fireEvent.click( saveButton );

			await waitFor( () => {
				expect( mockDispatch.createNotice ).toHaveBeenCalledWith(
					'success',
					'Settings saved',
					expect.objectContaining( {
						type: 'snackbar',
						isDismissible: true,
					} )
				);
			} );
		} );
	} );

	describe( 'Shows error notice on save failure', () => {
		it( 'creates error notice when save fails', async () => {
			const wpGraphQLLogin = getWpGlobal().wpGraphQLLogin;
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings.test_settings = {
					fields: {
						field1: {
							label: 'Field 1',
							type: 'string',
						},
					},
				};
			}

			vi.mocked( apiFetch )
				.mockResolvedValueOnce( {
					test_settings: {
						field1: 'value1',
					},
				} )
				.mockRejectedValueOnce( new Error( 'Save failed' ) );

			render(
				<SettingsProvider>
					<SettingsScreen settingKey="test_settings" />
				</SettingsProvider>
			);

			const updateButton = await screen.findByText( 'Update Value' );
			fireEvent.click( updateButton );

			const saveButton = await screen.findByRole( 'button', {
				name: /save/i,
			} );
			fireEvent.click( saveButton );

			await waitFor( () => {
				expect( mockDispatch.createErrorNotice ).toHaveBeenCalled();
			} );
		} );
	} );

	describe( 'Empty settings', () => {
		it( 'returns null when settings are empty', async () => {
			const wpGraphQLLogin = getWpGlobal().wpGraphQLLogin;
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings.test_settings = {
					fields: {
						field1: {
							label: 'Field 1',
							type: 'string',
						},
					},
				};
			}

			vi.mocked( apiFetch ).mockResolvedValue( {} );

			const { container } = render(
				<SettingsProvider>
					<SettingsScreen settingKey="test_settings" />
				</SettingsProvider>
			);

			await waitFor( () => {
				const fieldsComponent = container.querySelector(
					'[data-testid="fields-component"]'
				);
				expect( fieldsComponent ).not.toBeInTheDocument();
			} );
		} );
	} );

	describe( 'Missing setting key', () => {
		it( 'returns null when settingKey does not exist in global config', async () => {
			const wpGraphQLLogin = getWpGlobal().wpGraphQLLogin;
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings.existing_settings = {
					fields: {},
				};
			}

			vi.mocked( apiFetch ).mockResolvedValue( {} );

			const { container } = render(
				<SettingsProvider>
					<SettingsScreen settingKey="missing_settings" />
				</SettingsProvider>
			);

			await waitFor( () => {
				const fieldsComponent = container.querySelector(
					'[data-testid="fields-component"]'
				);
				expect( fieldsComponent ).not.toBeInTheDocument();
			} );
		} );

		it( 'returns null when settingKey exists but has no fields', async () => {
			const wpGraphQLLogin = getWpGlobal().wpGraphQLLogin;
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings.test_settings = {
					title: 'Test Settings',
				} as Record< string, unknown >;
			}

			vi.mocked( apiFetch ).mockResolvedValue( {
				test_settings: {
					field1: 'value1',
				},
			} );

			const { container } = render(
				<SettingsProvider>
					<SettingsScreen settingKey="test_settings" />
				</SettingsProvider>
			);

			await waitFor( () => {
				const fieldsComponent = container.querySelector(
					'[data-testid="fields-component"]'
				);
				expect( fieldsComponent ).not.toBeInTheDocument();
			} );
		} );
	} );

	describe( 'Save failure', () => {
		it( 'handles save failure gracefully', async () => {
			const wpGraphQLLogin = getWpGlobal().wpGraphQLLogin;
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings.test_settings = {
					fields: {
						field1: {
							label: 'Field 1',
							type: 'string',
						},
					},
				};
			}

			vi.mocked( apiFetch )
				.mockResolvedValueOnce( {
					test_settings: {
						field1: 'value1',
					},
				} )
				.mockRejectedValueOnce( new Error( 'API Error' ) );

			render(
				<SettingsProvider>
					<SettingsScreen settingKey="test_settings" />
				</SettingsProvider>
			);

			const updateButton = await screen.findByText( 'Update Value' );
			fireEvent.click( updateButton );

			const saveButton = await screen.findByRole( 'button', {
				name: /save/i,
			} );
			fireEvent.click( saveButton );

			await waitFor( () => {
				const saveButtonAfterWait = screen.queryByRole( 'button', {
					name: /save/i,
				} );
				expect( saveButtonAfterWait ).toBeInTheDocument();
			} );
		} );

		it( 'does not create success notice on failed save', async () => {
			const wpGraphQLLogin = getWpGlobal().wpGraphQLLogin;
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings.test_settings = {
					fields: {
						field1: {
							label: 'Field 1',
							type: 'string',
						},
					},
				};
			}

			vi.mocked( apiFetch )
				.mockResolvedValueOnce( {
					test_settings: {
						field1: 'value1',
					},
				} )
				.mockRejectedValueOnce( new Error( 'Save failed' ) );

			render(
				<SettingsProvider>
					<SettingsScreen settingKey="test_settings" />
				</SettingsProvider>
			);

			const updateButton = await screen.findByText( 'Update Value' );
			fireEvent.click( updateButton );

			const saveButton = await screen.findByRole( 'button', {
				name: /save/i,
			} );
			fireEvent.click( saveButton );

			await waitFor( () => {
				expect( mockDispatch.createNotice ).not.toHaveBeenCalled();
			} );
		} );

		it( 'prevents multiple save requests when isSaving', async () => {
			const wpGraphQLLogin = getWpGlobal().wpGraphQLLogin;
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings.test_settings = {
					fields: {
						field1: {
							label: 'Field 1',
							type: 'string',
						},
					},
				};
			}

			// Slow API response to simulate saving state
			let resolveFirst: ( value: unknown ) => void;
			const firstSavePromise = new Promise( ( resolve ) => {
				resolveFirst = resolve;
			} );

			vi.mocked( apiFetch )
				.mockResolvedValueOnce( {
					test_settings: {
						field1: 'value1',
					},
				} )
				.mockImplementationOnce(
					() => firstSavePromise as Promise< unknown >
				)
				.mockResolvedValueOnce( {
					test_settings: {
						field1: 'updated',
					},
				} );

			render(
				<SettingsProvider>
					<SettingsScreen settingKey="test_settings" />
				</SettingsProvider>
			);

			const updateButton = await screen.findByText( 'Update Value' );
			fireEvent.click( updateButton );

			const saveButton = await screen.findByRole( 'button', {
				name: /save/i,
			} );

			// First save click
			fireEvent.click( saveButton );

			// Immediately click again while saving
			fireEvent.click( saveButton );

			// Resolve the first save
			resolveFirst!( { test_settings: { field1: 'updated' } } );

			await waitFor( () => {
				expect( saveButton ).toBeInTheDocument();
			} );
		} );
	} );

	describe( 'Conditional logic validation', () => {
		it( 'passes validateConditionalLogic to Fields component', async () => {
			const wpGraphQLLogin = getWpGlobal().wpGraphQLLogin;
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings.test_settings = {
					fields: {
						field1: {
							label: 'Field 1',
							type: 'string',
							conditionalLogic: {
								slug: 'other_field',
								operator: '==',
								value: 'test',
							},
						},
					},
				};
			}

			vi.mocked( apiFetch ).mockResolvedValue( {
				test_settings: {
					field1: 'value1',
					other_field: 'test',
				},
			} );

			render(
				<SettingsProvider>
					<SettingsScreen settingKey="test_settings" />
				</SettingsProvider>
			);

			await waitFor( () => {
				const fieldsComponent =
					screen.getByTestId( 'fields-component' );
				expect( fieldsComponent ).toBeInTheDocument();
			} );
		} );

		it( 'explains what unlocks the screen when every field is hidden', async () => {
			const wpGraphQLLogin = getWpGlobal().wpGraphQLLogin;
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings.existing_settings = {
					label: 'Other Screen',
					fields: {
						gate: { label: 'The Gate', type: 'boolean' },
					},
				};
				wpGraphQLLogin.settings.test_settings = {
					fields: {
						field1: {
							label: 'Field 1',
							type: 'string',
							conditionalLogic: {
								slug: 'existing_settings.gate',
								operator: '==',
								value: true,
							},
						},
					},
				};
			}

			vi.mocked( apiFetch ).mockResolvedValue( {
				existing_settings: { gate: false },
				test_settings: { field1: 'value1' },
			} );

			render(
				<SettingsProvider>
					<SettingsScreen settingKey="test_settings" />
				</SettingsProvider>
			);

			await waitFor( () => {
				expect(
					screen.getAllByText(
						/“The Gate” is enabled under Other Screen/
					).length
				).toBeGreaterThan( 0 );
			} );

			expect(
				screen.queryByTestId( 'fields-component' )
			).not.toBeInTheDocument();
		} );
	} );
} );
