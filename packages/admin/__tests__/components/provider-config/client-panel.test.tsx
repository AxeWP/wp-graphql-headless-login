import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { vi, beforeEach, afterEach } from 'vitest';
import { ClientPanel } from '@/admin/components/provider-config/ClientPanel';
import { Fields } from '@/admin/components/fields';
import { ProviderConfigProvider } from '@/admin/contexts/provider-config-context';
import { SettingsProvider } from '@/admin/contexts/settings-context';
import {
	setupWpGraphQLLoginMock,
	resetWpGraphQLLoginMocks,
} from '../../mocks/wordpress-global.mock';
import apiFetch from '@wordpress/api-fetch';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

const mockSaveEditedEntityRecord = vi.fn().mockResolvedValue(true);
const mockCreateNotice = vi.fn();
const mockCreateErrorNotice = vi.fn();

vi.mock('@wordpress/core-data', () => ({
	store: {},
	useDispatch: vi.fn(() => ({
		saveEditedEntityRecord: mockSaveEditedEntityRecord,
	})),
	useEntityProp: vi.fn(),
}));

const mockSelectValues = {
	lastError: null as unknown,
	isSaving: false,
	hasEdits: false,
};

vi.mock('@wordpress/data', () => ({
	store: {},
	useDispatch: vi.fn(() => ({
		saveEditedEntityRecord: mockSaveEditedEntityRecord,
		createNotice: mockCreateNotice,
		createErrorNotice: mockCreateErrorNotice,
	})),
	useSelect: vi.fn(() => mockSelectValues),
}));

vi.mock('@wordpress/notices', () => ({
	store: { name: 'core/notices' },
}));

vi.mock('@wordpress/api-fetch');

vi.mock('@/admin/components/fields', () => ({
	Fields: vi.fn(() => null),
}));

vi.mock('@/admin/components/provider-config/ClientOptionList', () => ({
	ClientOptionList: vi.fn(() => <div data-testid="empty">Empty</div>),
}));

vi.mock('@/admin/assets/logo.svg', () => ({
	ReactComponent: () => <svg data-testid="logo-svg" />,
}));

const wrapper = ({ children }: { children: React.ReactNode }) => (
	<SettingsProvider>
		<ProviderConfigProvider>{children}</ProviderConfigProvider>
	</SettingsProvider>
);

describe('ClientPanel Component', () => {
	const wpGlobal = global as typeof globalThis & {
		wpGraphQLLogin?: {
			settings?: {
				providers?: Record<string, unknown>;
			};
			providers?: Record<string, unknown>;
		};
	};

	beforeEach(() => {
		setupWpGraphQLLoginMock();
		vi.clearAllMocks();
		vi.mocked(apiFetch).mockResolvedValue({});
		// Default mock for useEntityProp - returns undefined clientConfig (loading state)
		vi.mocked(useEntityProp).mockReturnValue([
			undefined,
			vi.fn(),
			undefined,
		]);
	});

	afterEach(() => {
		resetWpGraphQLLoginMocks();
		vi.clearAllMocks();
	});

	describe('Placeholder when loading', () => {
		it('renders Placeholder when loading (no clientConfig)', async () => {
			wpGlobal.wpGraphQLLogin!.settings!.providers = {};
			render(<ClientPanel />, { wrapper });
			await waitFor(() => {
				// Placeholder component renders "Loading..." in title attribute
				expect(screen.getByTitle('Loading…')).toBeInTheDocument();
			});
			// Check for the placeholder instructions (there may be multiple due to a11y regions)
			const instructions = screen.getAllByText(
				'Please wait while the settings are loaded.'
			);
			expect(instructions.length).toBeGreaterThan(0);
		});
	});

	describe('Panel rendering with clientConfig', () => {
		beforeEach(() => {
			const mockClientConfig = {
				name: 'OAuth2',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
			};
			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				vi.fn(),
				undefined,
			]);
		});

		it('renders panel when clientConfig exists', async () => {
			wpGlobal.wpGraphQLLogin!.settings!.providers = {
				oauth2: {
					name: { default: 'OAuth2' },
					order: 1,
					isEnabled: { default: false },
					clientOptions: { properties: {} },
				},
			};
			render(<ClientPanel />, { wrapper });
			await waitFor(() => {
				expect(screen.queryByText('Loading…')).not.toBeInTheDocument();
			});
			expect(screen.getByText('OAuth2 Settings')).toBeInTheDocument();
		});

		it('Panel displays provider name correctly', async () => {
			wpGlobal.wpGraphQLLogin!.settings!.providers = {
				github: { name: { default: 'GitHub' }, order: 1 },
			};
			const mockClientConfig = {
				name: 'GitHub',
				order: 1,
				slug: 'github',
				isEnabled: true,
			};
			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				vi.fn(),
				undefined,
			]);
			render(<ClientPanel />, { wrapper });
			await waitFor(() => {
				expect(screen.getByText('GitHub Settings')).toBeInTheDocument();
			});
		});

		// The localized schema is keyed by option name, which is how PHP sends it.
		it('resolves the schema from the prefixed option name', async () => {
			wpGlobal.wpGraphQLLogin!.settings!.providers = {
				wpgraphql_login_provider_github: {
					name: { default: 'GitHub' },
					isEnabled: { label: 'Enable Provider' },
				},
			};
			render(<ClientPanel />, { wrapper });
			await waitFor(() => {
				expect(screen.getByText('GitHub Settings')).toBeInTheDocument();
			});
			expect(vi.mocked(Fields).mock.calls[0]?.[0]?.fields).toEqual(
				expect.objectContaining({
					isEnabled: { label: 'Enable Provider' },
				})
			);
		});
	});

	describe('Fields rendering', () => {
		beforeEach(() => {
			const mockClientConfig = {
				name: 'OAuth2',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
			};
			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				vi.fn(),
				undefined,
			]);
		});

		it('renders Fields for main settings', async () => {
			wpGlobal.wpGraphQLLogin!.settings!.providers = {
				oauth2: {
					name: { default: 'OAuth2' },
					order: 1,
					isEnabled: {
						default: false,
						label: 'Enabled',
						type: 'boolean',
					},
					clientOptions: { properties: {} },
				},
			};
			render(<ClientPanel />, { wrapper });
			await waitFor(() => {
				expect(screen.getByText('OAuth2 Settings')).toBeInTheDocument();
			});
		});
	});

	describe('ClientOptionList rendering', () => {
		beforeEach(() => {
			const mockClientConfig = {
				name: 'OAuth2',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
			};
			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				vi.fn(),
				undefined,
			]);
		});

		it('renders ClientOptionList for clientOptions', async () => {
			wpGlobal.wpGraphQLLogin!.settings!.providers = {
				oauth2: {
					name: { default: 'OAuth2' },
					order: 1,
					clientOptions: { properties: {} },
				},
			};
			render(<ClientPanel />, { wrapper });
			await waitFor(() => {
				expect(screen.getByText('OAuth2 Settings')).toBeInTheDocument();
			});
		});

		it('renders ClientOptionList for loginOptions', async () => {
			wpGlobal.wpGraphQLLogin!.settings!.providers = {
				oauth2: {
					name: { default: 'OAuth2' },
					order: 1,
					loginOptions: { properties: {} },
				},
			};
			render(<ClientPanel />, { wrapper });
			await waitFor(() => {
				expect(screen.getByText('Login Settings')).toBeInTheDocument();
			});
		});
	});

	describe('Save button states', () => {
		beforeEach(() => {
			const mockClientConfig = {
				name: 'OAuth2',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
			};
			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				vi.fn(),
				undefined,
			]);
		});

		it('Save button disabled when no edits (hasEdits false)', async () => {
			vi.mocked(useSelect).mockReturnValue({
				lastError: null,
				isSaving: false,
				hasEdits: false,
			});
			wpGlobal.wpGraphQLLogin!.settings!.providers = {
				oauth2: { name: { default: 'OAuth2' }, order: 1 },
			};
			render(<ClientPanel />, { wrapper });
			await waitFor(() => {
				const saveButton = screen.getByRole('button', {
					name: 'Save Providers',
				});
				expect(saveButton).toBeDisabled();
			});
		});

		it('Save button enabled when hasEdits true', async () => {
			vi.mocked(useSelect).mockReturnValue({
				lastError: null,
				isSaving: false,
				hasEdits: true,
			});
			wpGlobal.wpGraphQLLogin!.settings!.providers = {
				oauth2: { name: { default: 'OAuth2' }, order: 1 },
			};
			render(<ClientPanel />, { wrapper });
			await waitFor(() => {
				const saveButton = screen.getByRole('button', {
					name: 'Save Providers',
				});
				expect(saveButton).not.toBeDisabled();
			});
		});

		it('Save button shows busy state when isSaving', async () => {
			vi.mocked(useSelect).mockReturnValue({
				lastError: null,
				isSaving: true,
				hasEdits: true,
			});
			wpGlobal.wpGraphQLLogin!.settings!.providers = {
				oauth2: { name: { default: 'OAuth2' }, order: 1 },
			};
			render(<ClientPanel />, { wrapper });
			await waitFor(() => {
				const saveButton = screen.getByRole('button', {
					name: 'Save Providers',
				});
				// WordPress Button with isBusy adds is-busy class
				expect(saveButton).toHaveClass('is-busy');
			});
		});

		it('Save button triggers saveEditedEntityRecord when clicked', async () => {
			mockSaveEditedEntityRecord.mockClear();
			mockSaveEditedEntityRecord.mockResolvedValue(true);
			vi.mocked(useSelect).mockReturnValue({
				lastError: null,
				isSaving: false,
				hasEdits: true,
			});
			wpGlobal.wpGraphQLLogin!.settings!.providers = {
				oauth2: { name: { default: 'OAuth2' }, order: 1 },
			};

			render(<ClientPanel />, { wrapper });

			const saveButton = await screen.findByRole('button', {
				name: 'Save Providers',
			});
			fireEvent.click(saveButton);

			await waitFor(() => {
				expect(mockSaveEditedEntityRecord).toHaveBeenCalledWith(
					'root',
					'site',
					undefined,
					expect.any(Object)
				);
			});
		});

		it('shows success notice when save completes', async () => {
			mockSaveEditedEntityRecord.mockClear();
			mockSaveEditedEntityRecord.mockResolvedValue(true);
			mockCreateNotice.mockClear();
			vi.mocked(useSelect).mockReturnValue({
				lastError: null,
				isSaving: false,
				hasEdits: true,
			});
			wpGlobal.wpGraphQLLogin!.settings!.providers = {
				oauth2: { name: { default: 'OAuth2' }, order: 1 },
			};

			render(<ClientPanel />, { wrapper });

			const saveButton = await screen.findByRole('button', {
				name: 'Save Providers',
			});
			fireEvent.click(saveButton);

			await waitFor(() => {
				expect(mockSaveEditedEntityRecord).toHaveBeenCalled();
			});
		});
	});

	describe('Error handling effects', () => {
		beforeEach(() => {
			const mockClientConfig = {
				name: 'OAuth2',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
			};
			vi.mocked(useEntityProp).mockReturnValue([
				mockClientConfig,
				vi.fn(),
				undefined,
			]);
		});

		it('shows error notice when lastError exists', async () => {
			mockCreateErrorNotice.mockClear();
			vi.mocked(useSelect).mockReturnValue({
				lastError: { message: 'Test error message' },
				isSaving: false,
				hasEdits: false,
			});
			wpGlobal.wpGraphQLLogin!.settings!.providers = {
				oauth2: { name: { default: 'OAuth2' }, order: 1 },
			};

			render(<ClientPanel />, { wrapper });

			await waitFor(() => {
				expect(mockCreateErrorNotice).toHaveBeenCalled();
			});
		});

		it('shows error notice with error params when available', async () => {
			mockCreateErrorNotice.mockClear();
			vi.mocked(useSelect).mockReturnValue({
				lastError: {
					message: 'Test error',
					data: {
						params: {
							wpgraphql_login_provider_oauth2: 'Specific error',
						},
					},
				},
				isSaving: false,
				hasEdits: false,
			});
			wpGlobal.wpGraphQLLogin!.settings!.providers = {
				oauth2: { name: { default: 'OAuth2' }, order: 1 },
			};

			render(<ClientPanel />, { wrapper });

			await waitFor(() => {
				expect(mockCreateErrorNotice).toHaveBeenCalled();
			});
		});
	});
});
