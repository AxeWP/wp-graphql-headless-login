import { describe, it, expect, beforeEach } from 'vitest';
import {
	getScreenForSetting,
	getSettingForScreen,
	isAllowedScreen,
} from '@/admin/components/screen/utils';

describe( 'Screen utils', () => {
	const wpGlobal = global as typeof globalThis & {
		wpGraphQLLogin?: {
			settings?: Record< string, unknown >;
			providers?: Record< string, unknown >;
		};
	};

	beforeEach( () => {
		// Setup global wpGraphQLLogin mock
		wpGlobal.wpGraphQLLogin = {
			settings: {
				wpgraphql_login_settings: {
					title: 'General Settings',
					description: 'General plugin settings',
					fields: {},
				},
				wpgraphql_login_access_control: {
					title: 'Access Control',
					description: 'Access control settings',
					fields: {},
				},
			},
			providers: {
				wpgraphql_login_provider_oauth2: {
					name: { default: 'OAuth2' },
					clientOptions: { properties: {} },
					loginOptions: { properties: {} },
				},
			},
		};
	} );

	describe( 'getScreenForSetting', () => {
		it( 'converts wpgraphql_login_settings to settings', () => {
			expect( getScreenForSetting( 'wpgraphql_login_settings' ) ).toBe(
				'settings'
			);
		} );

		it( 'converts wpgraphql_login_access_control to access-control', () => {
			expect(
				getScreenForSetting( 'wpgraphql_login_access_control' )
			).toBe( 'access-control' );
		} );

		it( 'strips wpgraphql_login_ prefix', () => {
			expect(
				getScreenForSetting( 'wpgraphql_login_test_setting' )
			).toBe( 'test-setting' );
		} );

		it( 'converts underscores to hyphens', () => {
			expect(
				getScreenForSetting( 'wpgraphql_login_access_control' )
			).toBe( 'access-control' );
			expect(
				getScreenForSetting( 'wpgraphql_login_advanced_settings' )
			).toBe( 'advanced-settings' );
		} );

		it( 'handles multiple underscores correctly', () => {
			expect(
				getScreenForSetting( 'wpgraphql_login_some_custom_setting' )
			).toBe( 'some-custom-setting' );
		} );
	} );

	describe( 'getSettingForScreen', () => {
		it( 'converts settings to wpgraphql_login_settings', () => {
			expect( getSettingForScreen( 'settings' ) ).toBe(
				'wpgraphql_login_settings'
			);
		} );

		it( 'converts access-control to wpgraphql_login_access_control', () => {
			expect( getSettingForScreen( 'access-control' ) ).toBe(
				'wpgraphql_login_access_control'
			);
		} );

		it( 'adds wpgraphql_login_ prefix', () => {
			expect( getSettingForScreen( 'test-setting' ) ).toBe(
				'wpgraphql_login_test_setting'
			);
		} );

		it( 'converts hyphens to underscores', () => {
			expect( getSettingForScreen( 'access-control' ) ).toBe(
				'wpgraphql_login_access_control'
			);
			expect( getSettingForScreen( 'advanced-settings' ) ).toBe(
				'wpgraphql_login_advanced_settings'
			);
		} );

		it( 'ensures lowercase output', () => {
			expect( getSettingForScreen( 'MixedCase-Screen' ) ).toBe(
				'wpgraphql_login_mixedcase_screen'
			);
		} );
	} );

	describe( 'isAllowedScreen', () => {
		beforeEach( () => {
			(
				global as unknown as {
					wpGraphQLLogin: Record< string, unknown >;
				}
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_settings: {},
					wpgraphql_login_access_control: {},
				},
				providers: {},
			};
		} );

		it( 'returns true for screens in wpGraphQLLogin.settings', () => {
			expect( isAllowedScreen( 'settings' ) ).toBe( true );
			expect( isAllowedScreen( 'access-control' ) ).toBe( true );
		} );

		it( 'returns false for screens not in wpGraphQLLogin.settings', () => {
			expect( isAllowedScreen( 'nonexistent-screen' ) ).toBe( false );
			expect( isAllowedScreen( 'invalid-screen' ) ).toBe( false );
		} );

		it( 'handles empty settings object gracefully', () => {
			(
				global as unknown as {
					wpGraphQLLogin: Record< string, unknown >;
				}
			 ).wpGraphQLLogin = {};
			expect( isAllowedScreen( 'settings' ) ).toBe( false );
		} );

		it( 'converts screen using getSettingForScreen', () => {
			(
				global as unknown as {
					wpGraphQLLogin: Record< string, unknown >;
				}
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_test_setting: {},
				},
				providers: {},
			};
			expect( isAllowedScreen( 'test-setting' ) ).toBe( true );
		} );

		it( 'returns false when wpGraphQLLogin.settings is undefined', () => {
			(
				global as unknown as {
					wpGraphQLLogin: Record< string, unknown >;
				}
			 ).wpGraphQLLogin = {
				providers: {},
			};
			expect( isAllowedScreen( 'settings' ) ).toBe( false );
		} );
	} );

	describe( 'Edge cases', () => {
		it( 'handles empty setting string in getScreenForSetting', () => {
			expect( getScreenForSetting( '' ) ).toBe( '' );
		} );

		it( 'handles empty screen string in getSettingForScreen', () => {
			expect( getSettingForScreen( '' ) ).toBe( 'wpgraphql_login_' );
		} );

		it( 'handles screen without hyphens in getSettingForScreen', () => {
			expect( getSettingForScreen( 'settings' ) ).toBe(
				'wpgraphql_login_settings'
			);
		} );

		it( 'handles setting without prefix in getScreenForSetting', () => {
			expect( getScreenForSetting( 'my_setting' ) ).toBe( 'my-setting' );
		} );

		it( 'handles screen in snake_case format in getSettingForScreen', () => {
			expect( getSettingForScreen( 'snake_case_screen' ) ).toBe(
				'wpgraphql_login_snake_case_screen'
			);
		} );
	} );
} );
