import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { vi, beforeEach, afterEach } from 'vitest';
import {
	ClientMenu,
	StatusBadge,
} from '@/admin/components/provider-config/ClientMenu';
import {
	ProviderConfigProvider,
	useClientContext,
} from '@/admin/contexts/provider-config-context';
import { useEntityProp } from '@wordpress/core-data';
import {
	setupWpGraphQLLoginMock,
	resetWpGraphQLLoginMocks,
} from '../../mocks/wordpress-global.mock';

vi.mock( '@wordpress/core-data', () => ( {
	useEntityProp: vi.fn(),
} ) );

vi.mock( '@wordpress/components', () => ( {
	__experimentalNavigation: ( {
		children,
		activeItem,
	}: {
		children: React.ReactNode;
		activeItem?: string;
	} ) => (
		<div data-testid="navigation" data-active-item={ activeItem }>
			{ children }
		</div>
	),
	__experimentalNavigationMenu: ( {
		title,
		children,
	}: {
		title: string;
		children: React.ReactNode;
	} ) => (
		<div data-testid="navigation-menu" data-title={ title }>
			{ children }
		</div>
	),
	__experimentalNavigationItem: ( {
		item,
		title,
		onClick,
		children,
		className,
	}: {
		item: string;
		title?: string;
		onClick?: () => void;
		children?: React.ReactNode;
		className?: string;
	} ) => (
		<button
			type="button"
			data-testid={ `navigation-item-${ item }` }
			data-item={ item }
			data-title={ title }
			className={ className }
			onClick={ onClick }
		>
			{ children }
			{ title }
		</button>
	),
} ) );

describe( 'ClientMenu Component', () => {
	let mockSetClientConfig: ReturnType< typeof vi.fn >;

	beforeEach( () => {
		setupWpGraphQLLoginMock();
		mockSetClientConfig = vi.fn();
		vi.mocked( useEntityProp ).mockReturnValue( [
			{
				name: 'OAuth2',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
			},
			mockSetClientConfig,
			undefined,
		] );
	} );

	afterEach( () => {
		resetWpGraphQLLoginMocks();
		vi.clearAllMocks();
	} );

	describe( 'Rendering provider buttons', () => {
		it( 'renders all provider buttons from settings', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: { providers: Record< string, unknown > };
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: { default: 'OAuth2' },
					order: 1,
					slug: 'oauth2',
					isEnabled: true,
				},
				github: {
					name: { default: 'GitHub' },
					order: 2,
					slug: 'github',
					isEnabled: true,
				},
				google: {
					name: { default: 'Google' },
					order: 3,
					slug: 'google',
					isEnabled: true,
				},
			};

			render(
				<ProviderConfigProvider>
					<ClientMenu />
				</ProviderConfigProvider>
			);

			expect(
				screen.getByTestId( 'navigation-item-oauth2' )
			).toBeInTheDocument();
			expect(
				screen.getByTestId( 'navigation-item-github' )
			).toBeInTheDocument();
			expect(
				screen.getByTestId( 'navigation-item-google' )
			).toBeInTheDocument();
		} );

		it( 'renders provider names correctly', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: { providers: Record< string, unknown > };
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: { default: 'Custom OAuth2 Provider' },
					order: 1,
					slug: 'oauth2',
					isEnabled: true,
				},
			};

			render(
				<ProviderConfigProvider>
					<ClientMenu />
				</ProviderConfigProvider>
			);

			const oauth2Button = screen.getByTestId( 'navigation-item-oauth2' );
			expect( oauth2Button ).toHaveAttribute(
				'data-title',
				'Custom OAuth2 Provider'
			);
		} );
	} );

	describe( 'Active state styling', () => {
		it( 'highlights active provider with activeItem prop', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: { providers: Record< string, unknown > };
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: { default: 'OAuth2' },
					order: 1,
					slug: 'oauth2',
					isEnabled: true,
				},
				github: {
					name: { default: 'GitHub' },
					order: 2,
					slug: 'github',
					isEnabled: true,
				},
			};

			render(
				<ProviderConfigProvider>
					<ClientMenu />
				</ProviderConfigProvider>
			);

			const navigation = screen.getByTestId( 'navigation' );
			expect( navigation ).toHaveAttribute(
				'data-active-item',
				'wpgraphql_login_provider_oauth2'
			);
		} );
	} );

	describe( 'Provider selection', () => {
		it( 'changes active client when provider is clicked', async () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: { providers: Record< string, unknown > };
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: { default: 'OAuth2' },
					order: 1,
					slug: 'oauth2',
					isEnabled: true,
				},
				github: {
					name: { default: 'GitHub' },
					order: 2,
					slug: 'github',
					isEnabled: true,
				},
			};

			render(
				<ProviderConfigProvider>
					<ClientMenu />
				</ProviderConfigProvider>
			);

			const githubButton = screen.getByTestId( 'navigation-item-github' );
			fireEvent.click( githubButton );

			const navigation = screen.getByTestId( 'navigation' );
			await waitFor( () => {
				// After clicking, activeClient should have the prefix
				expect( navigation ).toHaveAttribute(
					'data-active-item',
					'wpgraphql_login_provider_github'
				);
			} );
		} );
	} );

	describe( 'Empty providers list', () => {
		it( 'renders empty navigation when no providers exist', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: { providers: Record< string, unknown > };
					};
				}
			 ).wpGraphQLLogin.settings.providers = {};

			render(
				<ProviderConfigProvider>
					<ClientMenu />
				</ProviderConfigProvider>
			);

			const navigationItems =
				screen.queryAllByTestId( /navigation-item-/ );
			expect( navigationItems ).toHaveLength( 0 );
		} );

		it( 'handles missing providers gracefully', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: { providers: Record< string, unknown > };
					};
				}
			 ).wpGraphQLLogin.settings.providers = {};

			render(
				<ProviderConfigProvider>
					<ClientMenu />
				</ProviderConfigProvider>
			);

			const navigationItems =
				screen.queryAllByTestId( /navigation-item-/ );
			expect( navigationItems ).toHaveLength( 0 );
		} );
	} );

	describe( 'Single provider', () => {
		it( 'renders correctly with a single provider', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: { providers: Record< string, unknown > };
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: { default: 'OAuth2' },
					order: 1,
					slug: 'oauth2',
					isEnabled: true,
				},
			};

			render(
				<ProviderConfigProvider>
					<ClientMenu />
				</ProviderConfigProvider>
			);

			expect(
				screen.getByTestId( 'navigation-item-oauth2' )
			).toBeInTheDocument();
			expect(
				screen.queryByTestId( 'navigation-item-github' )
			).not.toBeInTheDocument();
		} );

		it( 'activates single provider by default', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: { providers: Record< string, unknown > };
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: { default: 'OAuth2' },
					order: 1,
					slug: 'oauth2',
					isEnabled: true,
				},
			};

			render(
				<ProviderConfigProvider>
					<ClientMenu />
				</ProviderConfigProvider>
			);

			const navigation = screen.getByTestId( 'navigation' );
			expect( navigation ).toHaveAttribute(
				'data-active-item',
				'wpgraphql_login_provider_oauth2'
			);
		} );
	} );

	describe( 'Invalid provider slug', () => {
		it( 'handles providers with missing name property', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: { providers: Record< string, unknown > };
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					order: 1,
					slug: 'oauth2',
					isEnabled: true,
				},
			};

			render(
				<ProviderConfigProvider>
					<ClientMenu />
				</ProviderConfigProvider>
			);

			const oauth2Button = screen.getByTestId( 'navigation-item-oauth2' );
			expect( oauth2Button.getAttribute( 'data-title' ) ).toBeNull();
			expect( oauth2Button ).toBeInTheDocument();
		} );

		it( 'handles providers with malformed name property', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: { providers: Record< string, unknown > };
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: 'Not an object',
					order: 1,
					slug: 'oauth2',
					isEnabled: true,
				},
			};

			render(
				<ProviderConfigProvider>
					<ClientMenu />
				</ProviderConfigProvider>
			);

			const oauth2Button = screen.getByTestId( 'navigation-item-oauth2' );
			expect( oauth2Button ).toBeInTheDocument();
		} );
	} );

	describe( 'StatusBadge Component', () => {
		it( 'renders enabled status badge when provider is enabled', () => {
			vi.mocked( useEntityProp ).mockReturnValue( [
				{
					name: 'OAuth2',
					order: 1,
					slug: 'oauth2',
					isEnabled: true,
				},
				mockSetClientConfig,
				undefined,
			] );

			const { container } = render(
				<ProviderConfigProvider>
					<StatusBadge provider="oauth2" />
				</ProviderConfigProvider>
			);

			const div = container.querySelector( 'div' );
			const statusIndicator = div?.querySelector( 'span' );

			expect( div ).toBeInTheDocument();
			expect( statusIndicator ).toBeInTheDocument();
			expect( statusIndicator?.className ).toContain( 'enabled' );
			expect( statusIndicator ).toHaveAttribute(
				'aria-label',
				'Enabled'
			);
			expect( statusIndicator ).toHaveAttribute( 'title', 'Enabled' );
		} );

		it( 'renders disabled status badge when provider is disabled', () => {
			vi.mocked( useEntityProp ).mockReturnValue( [
				{
					name: 'OAuth2',
					order: 1,
					slug: 'oauth2',
					isEnabled: false,
				},
				mockSetClientConfig,
				undefined,
			] );

			const { container } = render(
				<ProviderConfigProvider>
					<StatusBadge provider="oauth2" />
				</ProviderConfigProvider>
			);

			const div = container.querySelector( 'div' );
			const statusIndicator = div?.querySelector( 'span' );

			expect( div ).toBeInTheDocument();
			expect( statusIndicator ).toBeInTheDocument();
			expect( statusIndicator?.className ).not.toContain( 'enabled' );
			expect( statusIndicator ).toHaveAttribute(
				'aria-label',
				'Disabled'
			);
			expect( statusIndicator ).toHaveAttribute( 'title', 'Disabled' );
		} );

		it( 'handles undefined provider config as disabled', () => {
			vi.mocked( useEntityProp ).mockReturnValue( [
				undefined,
				mockSetClientConfig,
				undefined,
			] );

			const { container } = render(
				<ProviderConfigProvider>
					<StatusBadge provider="oauth2" />
				</ProviderConfigProvider>
			);

			const div = container.querySelector( 'div' );
			const statusIndicator = div?.querySelector( 'span' );

			expect( div ).toBeInTheDocument();
			expect( statusIndicator ).toBeInTheDocument();
			expect( statusIndicator?.className ).not.toContain( 'enabled' );
			expect( statusIndicator ).toHaveAttribute(
				'aria-label',
				'Disabled'
			);
		} );
	} );

	describe( 'Integration with Context', () => {
		it( 'updates activeClient in context when provider is clicked', async () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: { providers: Record< string, unknown > };
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: { default: 'OAuth2' },
					order: 1,
					slug: 'oauth2',
					isEnabled: true,
				},
				github: {
					name: { default: 'GitHub' },
					order: 2,
					slug: 'github',
					isEnabled: true,
				},
			};

			const TestComponent = () => {
				const { activeClient } = useClientContext();
				return (
					<>
						<div data-testid="current-active-client">
							{ activeClient }
						</div>
						<ClientMenu />
					</>
				);
			};

			render(
				<ProviderConfigProvider>
					<TestComponent />
				</ProviderConfigProvider>
			);

			const initialActiveClient = screen.getByTestId(
				'current-active-client'
			);
			expect( initialActiveClient ).toHaveTextContent(
				'wpgraphql_login_provider_oauth2'
			);

			const githubButton = screen.getByTestId( 'navigation-item-github' );
			fireEvent.click( githubButton );

			await waitFor( () => {
				const updatedActiveClient = screen.getByTestId(
					'current-active-client'
				);
				// After clicking, should have prefix
				expect( updatedActiveClient ).toHaveTextContent(
					'wpgraphql_login_provider_github'
				);
			} );
		} );
	} );
} );
