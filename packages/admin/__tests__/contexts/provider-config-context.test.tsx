import { renderHook, waitFor, act } from '@testing-library/react';
import {
	ProviderConfigProvider,
	useClientContext,
} from '@/admin/contexts/provider-config-context';
import type {
	ClientOptionsType,
	LoginOptionsType,
	ProviderSettingType,
} from '@/admin/types';
import {
	setupWpGraphQLLoginMock,
	resetWpGraphQLLoginMocks,
} from '../mocks/wordpress-global.mock';
import { useEntityProp } from '@wordpress/core-data';

vi.mock('@wordpress/core-data', () => ({
	useEntityProp: vi.fn(),
}));

function renderWithProviderConfigProvider() {
	const wrapper = ({ children }: { children: React.ReactNode }) => (
		<ProviderConfigProvider>{children}</ProviderConfigProvider>
	);

	return { renderHook, wrapper };
}

describe('ProviderConfigContext', () => {
	beforeEach(() => {
		setupWpGraphQLLoginMock();
	});

	afterEach(() => {
		resetWpGraphQLLoginMocks();
		vi.clearAllMocks();
	});

	describe('Context error handling', () => {
		it('returns default context when used outside provider', () => {
			// The context has a default value, so it will always return something
			// even when used outside the provider. This is the expected behavior.
			const wrapperWithoutProvider = ({
				children,
			}: {
				children: React.ReactNode;
			}) => <>{children}</>;

			const { result } = renderHook(() => useClientContext(), {
				wrapper: wrapperWithoutProvider,
			});

			// With default context, we get the stub values
			expect(result.current.activeClient).toBe('');
			expect(typeof result.current.setActiveClient).toBe('function');
		});
	});

	describe('useClientContext hook', () => {
		it('provides all context values', async () => {
			const mockClientConfig: ProviderSettingType = {
				name: 'OAuth2',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
				clientOptions: {
					clientId: 'test-client-id',
					clientSecret: 'test-client-secret',
					redirectUri: 'https://example.com/callback',
				} as unknown as ClientOptionsType,
				loginOptions: {
					useAuthenticationCookie: true,
				} as unknown as LoginOptionsType,
			};

			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				vi.fn(),
				undefined,
			]);

			const { wrapper } = renderWithProviderConfigProvider();
			const { result } = renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(result.current).toBeDefined();
				expect(result.current.activeClient).toBeDefined();
				expect(result.current.clientConfig).toBeDefined();
				expect(typeof result.current.setActiveClient).toBe('function');
				expect(typeof result.current.setClientConfig).toBe('function');
				expect(typeof result.current.updateClient).toBe('function');
				expect(typeof result.current.setClientOption).toBe('function');
				expect(typeof result.current.setLoginOption).toBe('function');
			});
		});
	});

	describe('Provider initialization with default values', () => {
		it('initializes with first provider key as active client', async () => {
			const mockClientConfig: ProviderSettingType = {
				name: 'OAuth2',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
				clientOptions: {
					clientId: 'test-client-id',
					clientSecret: 'test-client-secret',
					redirectUri: 'https://example.com/callback',
				} as unknown as ClientOptionsType,
				loginOptions: {
					useAuthenticationCookie: true,
				} as unknown as LoginOptionsType,
			};

			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				vi.fn(),
				undefined,
			]);

			const { wrapper } = renderWithProviderConfigProvider();
			const { result } = renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(result.current.activeClient).toBe(
					'wpgraphql_login_provider_oauth2'
				);
			});
		});
	});

	describe('clientConfig loads from entity props', () => {
		it('loads clientConfig from useEntityProp', async () => {
			const mockClientConfig: ProviderSettingType = {
				name: 'Test Provider',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
				clientOptions: {} as unknown as ClientOptionsType,
				loginOptions: {} as unknown as LoginOptionsType,
			};

			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				vi.fn(),
				undefined,
			]);

			const { wrapper } = renderWithProviderConfigProvider();
			const { result } = renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(result.current.clientConfig).toEqual(mockClientConfig);
			});
		});
	});

	describe('Empty providers list', () => {
		it('handles empty providers list gracefully', async () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: { providers: Record<string, unknown> };
					};
				}
			).wpGraphQLLogin.settings.providers = {};

			vi.mocked(useEntityProp).mockReturnValue([
				undefined,
				vi.fn(),
				undefined,
			]);

			const { wrapper } = renderWithProviderConfigProvider();
			const { result } = renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(result.current.activeClient).toBe(
					'wpgraphql_login_provider_'
				);
			});
		});
	});

	describe('clientDefaults applied when config is empty', () => {
		it('applies clientDefaults when clientConfig is empty', async () => {
			const setClientConfigMock = vi.fn();

			vi.mocked(useEntityProp).mockReturnValue([
				{},
				setClientConfigMock,
				undefined,
			]);

			const { wrapper } = renderWithProviderConfigProvider();
			renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(setClientConfigMock).toHaveBeenCalledWith(
					expect.objectContaining({
						name: '',
						order: 0,
						isEnabled: false,
						// The bare slug, not the prefixed option name — the REST schema only accepts registered provider slugs.
						slug: 'oauth2',
						loginOptions: {
							useAuthenticationCookie: false,
						},
					})
				);
			});
		});
	});

	describe('Empty clientConfig initializes with defaults', () => {
		it('initializes with defaults when clientConfig is empty object', async () => {
			const setClientConfigMock = vi.fn();

			vi.mocked(useEntityProp).mockImplementation(() => {
				return [{}, setClientConfigMock, undefined];
			});

			const { wrapper } = renderWithProviderConfigProvider();
			renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(setClientConfigMock).toHaveBeenCalledWith(
					expect.objectContaining({
						name: '',
						order: 0,
						isEnabled: false,
					})
				);
			});
		});
	});

	describe('updateClient callback functionality', () => {
		it('updates client configuration when updateClient is called', async () => {
			const mockClientConfig: ProviderSettingType = {
				name: 'Test Client',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
				clientOptions: {} as unknown as ClientOptionsType,
				loginOptions: {} as unknown as LoginOptionsType,
			};

			const setClientConfigMock = vi.fn();

			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				setClientConfigMock,
				undefined,
			]);

			const { wrapper } = renderWithProviderConfigProvider();
			const { result } = renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(result.current.clientConfig).toBeDefined();
			});

			act(() => {
				result.current.updateClient('name', 'Updated Name');
			});

			expect(setClientConfigMock).toHaveBeenCalledWith(
				expect.objectContaining({
					name: 'Updated Name',
					order: 1,
					slug: 'oauth2',
					isEnabled: true,
				})
			);
		});

		it('updateClient preserves other fields', async () => {
			const mockClientConfig: ProviderSettingType = {
				name: 'Original Name',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
				clientOptions: {
					clientId: 'test-id',
				} as unknown as ClientOptionsType,
				loginOptions: {
					useAuthenticationCookie: true,
				} as unknown as LoginOptionsType,
				extraField: 'extra',
			};

			const setClientConfigMock = vi.fn();

			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				setClientConfigMock,
				undefined,
			]);

			const { wrapper } = renderWithProviderConfigProvider();
			const { result } = renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(result.current.clientConfig).toBeDefined();
			});

			act(() => {
				result.current.updateClient('name', 'Updated Name');
			});

			expect(setClientConfigMock).toHaveBeenCalledWith(
				expect.objectContaining({
					name: 'Updated Name',
					order: 1,
					slug: 'oauth2',
					isEnabled: true,
					clientOptions: {
						clientId: 'test-id',
					},
					loginOptions: {
						useAuthenticationCookie: true,
					},
					extraField: 'extra',
				})
			);
		});

		it('throws error when trying to set non-existent client', async () => {
			const mockClientConfig: ProviderSettingType = {
				name: 'Non-existent',
				order: 3,
				slug: 'nonexistent',
				isEnabled: true,
				clientOptions: {} as unknown as ClientOptionsType,
				loginOptions: {} as unknown as LoginOptionsType,
			};

			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				vi.fn(),
				undefined,
			]);

			const { wrapper } = renderWithProviderConfigProvider();
			const { result } = renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(result.current).toBeDefined();
			});

			expect(() => {
				act(() => {
					result.current.setActiveClient('non_existent');
				});
			}).toThrow('Client not found');
		});
	});

	describe('setClientOption callback functionality', () => {
		it('merges client options when setClientOption is called', async () => {
			const mockClientConfig: ProviderSettingType = {
				name: 'Test Client',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
				clientOptions: {
					clientId: 'old-client-id',
					extraField: 'keep-me',
				} as unknown as ClientOptionsType,
				loginOptions: {} as unknown as LoginOptionsType,
			};

			const setClientConfigMock = vi.fn();

			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				setClientConfigMock,
				undefined,
			]);

			const { wrapper } = renderWithProviderConfigProvider();
			const { result } = renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(result.current.clientConfig).toBeDefined();
			});

			act(() => {
				result.current.setClientOption({
					clientId: 'new-client-id',
					clientSecret: 'new-secret',
				} as unknown as ClientOptionsType);
			});

			expect(setClientConfigMock).toHaveBeenCalledWith(
				expect.objectContaining({
					clientOptions: {
						clientId: 'new-client-id',
						clientSecret: 'new-secret',
						extraField: 'keep-me',
					},
				})
			);
		});

		it('setClientOption merges with existing options', async () => {
			const mockClientConfig: ProviderSettingType = {
				name: 'Test Client',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
				clientOptions: {
					clientId: 'original-id',
					scope: 'original-scope',
				} as unknown as ClientOptionsType,
				loginOptions: {} as unknown as LoginOptionsType,
			};

			const setClientConfigMock = vi.fn();

			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				setClientConfigMock,
				undefined,
			]);

			const { wrapper } = renderWithProviderConfigProvider();
			const { result } = renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(result.current.clientConfig).toBeDefined();
			});

			act(() => {
				result.current.setClientOption({
					clientSecret: 'new-secret',
				} as unknown as ClientOptionsType);
			});

			expect(setClientConfigMock).toHaveBeenCalledWith(
				expect.objectContaining({
					clientOptions: {
						clientId: 'original-id',
						scope: 'original-scope',
						clientSecret: 'new-secret',
					},
				})
			);
		});
	});

	describe('setLoginOption callback functionality', () => {
		it('merges login options when setLoginOption is called', async () => {
			const mockClientConfig: ProviderSettingType = {
				name: 'Test Client',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
				clientOptions: {} as unknown as ClientOptionsType,
				loginOptions: {
					useAuthenticationCookie: false,
					extraField: 'keep-me',
				} as unknown as LoginOptionsType,
			};

			const setClientConfigMock = vi.fn();

			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				setClientConfigMock,
				undefined,
			]);

			const { wrapper } = renderWithProviderConfigProvider();
			const { result } = renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(result.current.clientConfig).toBeDefined();
			});

			act(() => {
				result.current.setLoginOption({
					useAuthenticationCookie: true,
					createUserIfNoneExists: true,
				} as unknown as LoginOptionsType);
			});

			expect(setClientConfigMock).toHaveBeenCalledWith(
				expect.objectContaining({
					loginOptions: {
						useAuthenticationCookie: true,
						createUserIfNoneExists: true,
						extraField: 'keep-me',
					},
				})
			);
		});

		it('setLoginOption merges with existing options', async () => {
			const mockClientConfig: ProviderSettingType = {
				name: 'Test Client',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
				clientOptions: {} as unknown as ClientOptionsType,
				loginOptions: {
					useAuthenticationCookie: true,
					createUserIfNoneExists: true,
				} as unknown as LoginOptionsType,
			};

			const setClientConfigMock = vi.fn();

			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				setClientConfigMock,
				undefined,
			]);

			const { wrapper } = renderWithProviderConfigProvider();
			const { result } = renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(result.current.clientConfig).toBeDefined();
			});

			act(() => {
				result.current.setLoginOption({
					linkExistingUsers: true,
				} as unknown as LoginOptionsType);
			});

			expect(setClientConfigMock).toHaveBeenCalledWith(
				expect.objectContaining({
					loginOptions: {
						useAuthenticationCookie: true,
						createUserIfNoneExists: true,
						linkExistingUsers: true,
					},
				})
			);
		});
	});

	describe('setActiveClient callback functionality', () => {
		it('changes active client correctly', async () => {
			const mockClientConfig: ProviderSettingType = {
				name: 'Updated Client',
				order: 2,
				slug: 'updated',
				isEnabled: true,
				clientOptions: {} as unknown as ClientOptionsType,
				loginOptions: {} as unknown as LoginOptionsType,
			};

			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				vi.fn(),
				undefined,
			]);

			const { wrapper } = renderWithProviderConfigProvider();
			const { result } = renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(result.current.activeClient).toBe(
					'wpgraphql_login_provider_oauth2'
				);
			});

			act(() => {
				result.current.setActiveClient(
					'wpgraphql_login_provider_oauth2'
				);
			});

			expect(result.current.activeClient).toBe(
				'wpgraphql_login_provider_oauth2'
			);
		});

		it('slug transformation in setActiveClientWrapper', async () => {
			const mockClientConfig: ProviderSettingType = {
				name: 'OAuth2',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
				clientOptions: {} as unknown as ClientOptionsType,
				loginOptions: {} as unknown as LoginOptionsType,
			};

			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				vi.fn(),
				undefined,
			]);

			const { wrapper } = renderWithProviderConfigProvider();
			const { result } = renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(result.current.activeClient).toBe(
					'wpgraphql_login_provider_oauth2'
				);
			});

			act(() => {
				result.current.setActiveClient('oauth2');
			});

			// After setting without prefix, it should be normalized to have prefix
			expect(result.current.activeClient).toBe(
				'wpgraphql_login_provider_oauth2'
			);
		});

		it('transforms slug correctly with prefix', async () => {
			const mockClientConfig: ProviderSettingType = {
				name: 'GitHub',
				order: 1,
				slug: 'github',
				isEnabled: true,
				clientOptions: {} as unknown as ClientOptionsType,
				loginOptions: {} as unknown as LoginOptionsType,
			};

			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				vi.fn(),
				undefined,
			]);

			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: { providers: Record<string, unknown> };
					};
				}
			).wpGraphQLLogin.settings.providers = {
				github: {
					name: 'GitHub',
					order: 1,
					slug: 'github',
					isEnabled: true,
				},
			};

			const { wrapper } = renderWithProviderConfigProvider();
			const { result } = renderHook(() => useClientContext(), {
				wrapper,
			});

			await waitFor(() => {
				expect(result.current.activeClient).toBe(
					'wpgraphql_login_provider_github'
				);
			});

			act(() => {
				result.current.setActiveClient('github');
			});

			// After setting without prefix, it should be normalized to have prefix
			expect(result.current.activeClient).toBe(
				'wpgraphql_login_provider_github'
			);
		});
	});
});
