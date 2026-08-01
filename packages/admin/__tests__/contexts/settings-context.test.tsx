import { renderHook, waitFor, act } from '@testing-library/react';
import {
	SettingsProvider,
	useSettings,
} from '@/admin/contexts/settings-context';
import {
	setupWpGraphQLLoginMock,
	resetWpGraphQLLoginMocks,
} from '../mocks/wordpress-global.mock';
import apiFetch from '@wordpress/api-fetch';

vi.mock( '@wordpress/api-fetch' );

interface WpGraphQLLoginGlobal {
	settings: Record< string, unknown >;
	providers: Record< string, unknown >;
}

const REST_ENDPOINT = 'wp-graphql-login/v1/settings';

type SettingType = Record< string, Record< string, unknown > >;

const mockSettings: SettingType = {
	wpgraphql_login_settings: {
		show_advanced_settings: true,
		login_method: 'password',
	},
	provider_settings: {
		oauth2: {
			enabled: true,
			client_id: 'test-id',
		},
	},
};

function renderWithSettingsProvider() {
	const wrapper = ( { children }: { children: React.ReactNode } ) => (
		<SettingsProvider>{ children }</SettingsProvider>
	);

	return { renderHook, wrapper };
}

describe( 'SettingsContext', () => {
	beforeEach( () => {
		setupWpGraphQLLoginMock();
		vi.clearAllMocks();
	} );

	afterEach( () => {
		resetWpGraphQLLoginMocks();
		vi.clearAllMocks();
	} );

	describe( 'Context error handling', () => {
		it( 'returns default context when used outside provider', () => {
			// The context has a default value, so it will always return something
			// even when used outside the provider. This is the expected behavior.
			const wrapperWithoutProvider = ( {
				children,
			}: {
				children: React.ReactNode;
			} ) => <>{ children }</>;

			const { result } = renderHook( () => useSettings(), {
				wrapper: wrapperWithoutProvider,
			} );

			// With default context, we get the stub values
			expect( result.current.settings ).toBeUndefined();
			expect( result.current.isDirty ).toBe( false );
			expect(
				result.current.isConditionMet( {
					settingKey: 'test',
					field: 'test',
				} )
			).toBe( true );
		} );
	} );

	describe( 'useSettings hook provides all context values correctly', () => {
		it( 'provides all context values', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( mockSettings );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current ).toBeDefined();
				expect( result.current.settings ).toBeDefined();
				expect( result.current.updateSettings ).toBeDefined();
				expect( result.current.saveSettings ).toBeDefined();
				expect( result.current.isConditionMet ).toBeDefined();
				expect( result.current.isComplete ).toBeDefined();
				expect( result.current.isDirty ).toBeDefined();
				expect( result.current.isSaving ).toBeDefined();
				expect( result.current.errorMessage ).toBeUndefined();
				expect( result.current.showAdvancedSettings ).toBeDefined();
			} );
		} );
	} );

	describe( 'Settings loading from API', () => {
		it( 'settings undefined initially, then loads from API', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( mockSettings );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			expect( result.current.settings ).toBeUndefined();

			await waitFor( () => {
				expect( result.current.settings ).toEqual( mockSettings );
			} );
		} );

		it( 'API fetch errors set errorMessage', async () => {
			const error = new Error( 'API Error' );
			vi.mocked( apiFetch ).mockRejectedValue( error );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.isComplete ).toBe( true );
			} );

			expect( result.current.errorMessage ).toBe( 'API Error' );
		} );

		it( 'API fetch rejections that are not Errors fall back to a generic message', async () => {
			vi.mocked( apiFetch ).mockRejectedValue( 'just a string' );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.isComplete ).toBe( true );
			} );

			expect( result.current.errorMessage ).toBe(
				'Unable to fetch settings. An unknown error occurred'
			);
		} );

		it( 'Empty settings object handled correctly', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {} );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toEqual( {} );
			} );
		} );
	} );

	describe( 'updateSettings', () => {
		it( 'updates settings state for a given slug', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( mockSettings );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toEqual( mockSettings );
			} );

			act( () => {
				result.current.updateSettings( {
					slug: 'wpgraphql_login_settings',
					values: {
						show_advanced_settings: false,
						new_field: 'new_value',
					},
				} );
			} );

			expect(
				result.current.settings?.[ 'wpgraphql_login_settings' ]
			).toEqual( {
				show_advanced_settings: false,
				new_field: 'new_value',
			} );
		} );

		it( 'updateSettings with non-existent prevSettings creates new object', async () => {
			vi.mocked( apiFetch ).mockRejectedValueOnce( new Error( 'Error' ) );

			const newWrapper = ( {
				children,
			}: {
				children: React.ReactNode;
			} ) => <SettingsProvider>{ children }</SettingsProvider>;

			const { result: newResult } = renderHook( () => useSettings(), {
				wrapper: newWrapper,
			} );

			await waitFor( () => {
				expect( newResult.current.isComplete ).toBe( true );
			} );

			act( () => {
				newResult.current.updateSettings( {
					slug: 'new_slug',
					values: { test: 'value' },
				} );
			} );

			expect( newResult.current.settings ).toEqual( {
				new_slug: { test: 'value' },
			} );
		} );
	} );

	describe( 'saveSettings', () => {
		it( 'successfully saves to REST API and updates state', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( mockSettings );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toEqual( mockSettings );
			} );

			act( () => {
				result.current.updateSettings( {
					slug: 'wpgraphql_login_settings',
					values: { show_advanced_settings: false },
				} );
			} );

			const savePromise = act( async () => {
				return await result.current.saveSettings(
					'wpgraphql_login_settings'
				);
			} );

			const resultValue = await savePromise;
			expect( resultValue ).toBe( true );

			await waitFor( () => {
				expect( apiFetch ).toHaveBeenCalledWith( {
					path: REST_ENDPOINT,
					method: 'POST',
					data: {
						slug: 'wpgraphql_login_settings',
						values: { show_advanced_settings: false },
					},
				} );
			} );
		} );

		it( 'API failure sets errorMessage', async () => {
			vi.mocked( apiFetch ).mockResolvedValueOnce( mockSettings );
			vi.mocked( apiFetch ).mockRejectedValueOnce(
				new Error( 'Save failed' )
			);

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toEqual( mockSettings );
			} );

			act( () => {
				result.current.updateSettings( {
					slug: 'wpgraphql_login_settings',
					values: { show_advanced_settings: false },
				} );
			} );

			const resultValue = await act( async () => {
				return await result.current.saveSettings(
					'wpgraphql_login_settings'
				);
			} );

			expect( resultValue ).toBe( false );
			expect( result.current.errorMessage ).toBe( 'Save failed' );
		} );

		it( 'save rejections that are not Errors leave errorMessage unset', async () => {
			vi.mocked( apiFetch ).mockResolvedValueOnce( mockSettings );
			vi.mocked( apiFetch ).mockRejectedValueOnce( 'just a string' );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toEqual( mockSettings );
			} );

			const resultValue = await act( async () => {
				return await result.current.saveSettings(
					'wpgraphql_login_settings'
				);
			} );

			expect( resultValue ).toBe( false );
			expect( result.current.errorMessage ).toBeUndefined();
			expect( result.current.isComplete ).toBe( true );
		} );
	} );

	describe( 'isDirty', () => {
		it( 'correctly detects changes from server state', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( mockSettings );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toEqual( mockSettings );
			} );

			expect( result.current.isDirty ).toBe( false );

			act( () => {
				result.current.updateSettings( {
					slug: 'wpgraphql_login_settings',
					values: { show_advanced_settings: false },
				} );
			} );

			expect( result.current.isDirty ).toBe( true );
		} );

		it( 'isDirty is false when settings match server state', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( mockSettings );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toEqual( mockSettings );
			} );

			expect( result.current.isDirty ).toBe( false );
		} );
	} );

	describe( 'isSaving', () => {
		it( 'state changes during save operations', async () => {
			vi.mocked( apiFetch ).mockImplementation( ( { method } ) => {
				if ( method === 'POST' ) {
					return new Promise( ( resolve ) => {
						setTimeout( () => resolve( mockSettings ), 100 );
					} );
				}
				return Promise.resolve( mockSettings );
			} );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toEqual( mockSettings );
			} );

			act( () => {
				result.current.updateSettings( {
					slug: 'wpgraphql_login_settings',
					values: { show_advanced_settings: false },
				} );
			} );

			act( () => {
				result.current.saveSettings( 'wpgraphql_login_settings' );
			} );

			expect( result.current.isSaving ).toBe( true );

			await waitFor(
				() => {
					expect( result.current.isSaving ).toBe( false );
				},
				{ timeout: 200 }
			);
		} );
	} );

	describe( 'isComplete', () => {
		it( 'reflects completion status', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( mockSettings );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.isComplete ).toBe( true );
			} );
		} );
	} );

	describe( 'showAdvancedSettings', () => {
		it( 'derives correct value from settings', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				wpgraphql_login_settings: {
					show_advanced_settings: true,
				},
			} );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.showAdvancedSettings ).toBe( true );
			} );

			act( () => {
				result.current.updateSettings( {
					slug: 'wpgraphql_login_settings',
					values: { show_advanced_settings: false },
				} );
			} );

			expect( result.current.showAdvancedSettings ).toBe( false );
		} );

		it( 'returns false when settings are undefined', async () => {
			vi.mocked( apiFetch ).mockRejectedValue( new Error( 'Error' ) );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.isComplete ).toBe( true );
			} );

			expect( result.current.showAdvancedSettings ).toBe( false );
		} );

		it( 'returns false when show_advanced_settings is not set', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				wpgraphql_login_settings: {},
			} );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			expect( result.current.showAdvancedSettings ).toBe( false );
		} );
	} );

	describe( 'isConditionMet', () => {
		it( 'with no conditional logic returns true', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( mockSettings );

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toEqual( mockSettings );
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'wpgraphql_login_settings',
				field: 'login_method',
			} );

			expect( isMet ).toBe( true );
		} );

		it( 'with single condition returns correct boolean', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				wpgraphql_login_settings: {
					login_method: 'password',
				},
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_settings: {
						fields: {
							some_field: {
								conditionalLogic: {
									slug: 'login_method',
									operator: '==',
									value: 'password',
								},
							},
						},
					},
				},
				providers: {},
			};

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'wpgraphql_login_settings',
				field: 'some_field',
			} );

			expect( isMet ).toBe( true );
		} );

		it( 'with array of rules', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				wpgraphql_login_settings: {
					login_method: 'password',
					enabled: true,
				},
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_settings: {
						fields: {
							some_field: {
								conditionalLogic: [
									{
										slug: 'login_method',
										operator: '==',
										value: 'password',
									},
									{
										slug: 'enabled',
										operator: '==',
										value: true,
									},
								],
							},
						},
					},
				},
				providers: {},
			};

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'wpgraphql_login_settings',
				field: 'some_field',
			} );

			expect( isMet ).toBe( true );
		} );

		it( 'with array of rules where one fails', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				wpgraphql_login_settings: {
					login_method: 'password',
					enabled: false,
				},
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_settings: {
						fields: {
							some_field: {
								conditionalLogic: [
									{
										slug: 'login_method',
										operator: '==',
										value: 'password',
									},
									{
										slug: 'enabled',
										operator: '==',
										value: true,
									},
								],
							},
						},
					},
				},
				providers: {},
			};

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'wpgraphql_login_settings',
				field: 'some_field',
			} );

			expect( isMet ).toBe( false );
		} );

		it( 'with nested conditions (parent condition checks)', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				parent_settings: {
					enabled: true,
					advanced_mode: true,
				},
				child_settings: {
					child_field: 'value',
				},
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					parent_settings: {
						fields: {
							advanced_mode: {
								conditionalLogic: {
									slug: 'enabled',
									operator: '==',
									value: true,
								},
							},
						},
					},
					child_settings: {
						fields: {
							child_field: {
								conditionalLogic: {
									slug: 'parent_settings.advanced_mode',
									operator: '==',
									value: true,
								},
							},
						},
					},
				},
				providers: {},
			};

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'child_settings',
				field: 'child_field',
			} );

			expect( isMet ).toBe( true );
		} );

		it( 'nested conditions when parent condition fails', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				parent_settings: {
					enabled: false,
					advanced_mode: true,
				},
				child_settings: {
					child_field: 'value',
				},
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					parent_settings: {
						fields: {
							advanced_mode: {
								conditionalLogic: {
									slug: 'enabled',
									operator: '==',
									value: true,
								},
							},
						},
					},
					child_settings: {
						fields: {
							child_field: {
								conditionalLogic: {
									slug: 'parent_settings.advanced_mode',
									operator: '==',
									value: true,
								},
							},
						},
					},
				},
				providers: {},
			};

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'child_settings',
				field: 'child_field',
			} );

			expect( isMet ).toBe( false );
		} );

		it( 'with dotted slug format (e.g., parent.field)', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				parent_settings: {
					nested_field: 'test_value',
				},
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					some_settings: {
						fields: {
							some_field: {
								conditionalLogic: {
									slug: 'parent_settings.nested_field',
									operator: '==',
									value: 'test_value',
								},
							},
						},
					},
				},
				providers: {},
			};

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'some_settings',
				field: 'some_field',
			} );

			expect( isMet ).toBe( true );
		} );

		it( 'returns false when dotted slug field does not exist', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				parent_settings: {},
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					some_settings: {
						fields: {
							some_field: {
								conditionalLogic: {
									slug: 'parent_settings.nested_field',
									operator: '==',
									value: 'test_value',
								},
							},
						},
					},
				},
				providers: {},
			};

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'some_settings',
				field: 'some_field',
			} );

			expect( isMet ).toBe( false );
		} );

		it( 'with == operator', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				settings: { value: 'test' },
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					settings: {
						fields: {
							field: {
								conditionalLogic: {
									slug: 'value',
									operator: '==',
									value: 'test',
								},
							},
						},
					},
				},
				providers: {},
			};

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'settings',
				field: 'field',
			} );

			expect( isMet ).toBe( true );
		} );

		it( 'with != operator', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				settings: { value: 'other' },
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					settings: {
						fields: {
							field: {
								conditionalLogic: {
									slug: 'value',
									operator: '!=',
									value: 'test',
								},
							},
						},
					},
				},
				providers: {},
			};

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'settings',
				field: 'field',
			} );

			expect( isMet ).toBe( true );
		} );

		it( 'with < operator', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				settings: { value: '3' },
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					settings: {
						fields: {
							field: {
								conditionalLogic: {
									slug: 'value',
									operator: '<',
									value: '5',
								},
							},
						},
					},
				},
				providers: {},
			};

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'settings',
				field: 'field',
			} );

			expect( isMet ).toBe( true );
		} );

		it( 'with >= operator', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				settings: { value: '5' },
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					settings: {
						fields: {
							field: {
								conditionalLogic: {
									slug: 'value',
									operator: '>=',
									value: '5',
								},
							},
						},
					},
				},
				providers: {},
			};

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'settings',
				field: 'field',
			} );

			expect( isMet ).toBe( true );
		} );

		it( 'with <= operator', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				settings: { value: '5' },
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					settings: {
						fields: {
							field: {
								conditionalLogic: {
									slug: 'value',
									operator: '<=',
									value: '5',
								},
							},
						},
					},
				},
				providers: {},
			};

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'settings',
				field: 'field',
			} );

			expect( isMet ).toBe( true );
		} );

		it( 'returns true with unknown operator', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				settings: { value: 'test' },
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					settings: {
						fields: {
							field: {
								conditionalLogic: {
									slug: 'value',
									operator: 'unknown',
									value: 'test',
								},
							},
						},
					},
				},
				providers: {},
			};

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'settings',
				field: 'field',
			} );

			expect( isMet ).toBe( true );
		} );

		it( 'returns false when field value is undefined', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				settings: {},
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					settings: {
						fields: {
							field: {
								conditionalLogic: {
									slug: 'value',
									operator: '==',
									value: 'test',
								},
							},
						},
					},
				},
				providers: {},
			};

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'settings',
				field: 'field',
			} );

			expect( isMet ).toBe( false );
		} );

		it( 'returns false when dotted slug parsing fails', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				settings: {},
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					settings: {
						fields: {
							field: {
								conditionalLogic: {
									slug: '.invalid',
									operator: '==',
									value: 'test',
								},
							},
						},
					},
				},
				providers: {},
			};

			const { wrapper } = renderWithSettingsProvider();
			const { result } = renderHook( () => useSettings(), {
				wrapper,
			} );

			await waitFor( () => {
				expect( result.current.settings ).toBeDefined();
			} );

			const isMet = result.current.isConditionMet( {
				settingKey: 'settings',
				field: 'field',
			} );

			expect( isMet ).toBe( false );
		} );
	} );
} );
