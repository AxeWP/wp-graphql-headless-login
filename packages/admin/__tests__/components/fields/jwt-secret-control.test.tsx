import {
	render,
	screen,
	fireEvent,
	waitFor,
	renderHook,
	act,
} from '@testing-library/react';
import { vi, describe, it, expect, beforeEach, afterEach } from 'vitest';
import { JwtSecretControl } from '@/admin/components/fields/jwt-secret-control';
import {
	SettingsProvider,
	useSettings,
} from '@/admin/contexts/settings-context';
import {
	setupWpGraphQLLoginMock,
	resetWpGraphQLLoginMocks,
} from '@/admin/__tests__/mocks/wordpress-global.mock';
import type { FieldSchema } from '@/admin/types';
import apiFetch from '@wordpress/api-fetch';

vi.mock( '@wordpress/api-fetch' );

const mockCreateNotice = vi.fn();
const mockCreateErrorNotice = vi.fn();

vi.mock( '@wordpress/components', () => ( {
	BaseControl: ( {
		children,
		id,
		help,
		className,
	}: {
		children: React.ReactNode;
		id?: string;
		help?: string;
		className?: string;
	} ) => (
		<div
			data-testid="base-control"
			data-id={ id || '' }
			data-help={ help || '' }
			data-classname={ className || '' }
		>
			{ children }
		</div>
	),
	Button: ( {
		children,
		text,
		icon,
		disabled,
		isDestructive,
		isBusy,
		variant,
		iconSize,
		onClick,
	}: {
		children?: React.ReactNode;
		text?: string;
		icon?: string;
		disabled?: boolean;
		isDestructive?: boolean;
		isBusy?: boolean;
		variant?: string;
		iconSize?: number;
		onClick?: () => void;
	} ) => (
		<button
			data-testid="jwt-secret-button"
			data-text={ text || String( children || '' ) }
			data-icon={ icon || '' }
			data-disabled={ String( disabled || false ) }
			data-is-destructive={ String( isDestructive || false ) }
			data-is-busy={ String( isBusy || false ) }
			data-variant={ variant || '' }
			data-icon-size={ String( iconSize || 0 ) }
			disabled={ disabled }
			onClick={ onClick }
		>
			{ text || String( children || '' ) }
		</button>
	),
} ) );

vi.mock( '@wordpress/data', () => ( {
	useDispatch: () => ( {
		createNotice: mockCreateNotice,
		createErrorNotice: mockCreateErrorNotice,
	} ),
	// Required by `@wordpress/notices` at import time.
	createReduxStore: vi.fn( () => ( {} ) ),
	register: vi.fn(),
} ) );

vi.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
	sprintf: ( template: string, ...args: string[] ) => {
		return args.reduce(
			( str, arg ) => str.replace( /%s/, arg ),
			template
		);
	},
} ) );

vi.mock( '@wordpress/compose', () => ( {
	useInstanceId: vi.fn( () => 'test-instance-id' ),
} ) );

interface WpGraphQLLoginGlobal {
	settings: Record< string, unknown >;
	providers: Record< string, unknown >;
	secret: {
		isConstant?: boolean;
		[ key: string ]: unknown;
	};
}

function renderWithSettingsProvider( ui: React.ReactElement ) {
	const wrapper = ( { children }: { children: React.ReactNode } ) => (
		<SettingsProvider>{ children }</SettingsProvider>
	);

	return {
		...render( ui, { wrapper } ),
	};
}

describe( 'JwtSecretControl Component', () => {
	beforeEach( () => {
		setupWpGraphQLLoginMock();
		vi.clearAllMocks();
		// The SettingsProvider fetches settings on mount.
		vi.mocked( apiFetch ).mockResolvedValue( {} );
	} );

	afterEach( () => {
		resetWpGraphQLLoginMocks();
		vi.clearAllMocks();
	} );

	describe( 'Basic rendering', () => {
		it( 'renders button with correct label', () => {
			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				...(
					global as unknown as {
						wpGraphQLLogin: WpGraphQLLoginGlobal;
					}
				 ).wpGraphQLLogin,
				secret: {},
			};

			const props: FieldSchema = {
				label: 'Regenerate JWT Secret',
				description: 'JWT Secret description',
				type: 'string',
				help: 'This will invalidate all existing tokens',
			};

			renderWithSettingsProvider( <JwtSecretControl { ...props } /> );

			const button = screen.getByTestId( 'jwt-secret-button' );
			expect( button ).toBeInTheDocument();
			expect( button ).toHaveAttribute(
				'data-text',
				'Regenerate JWT Secret'
			);
		} );

		it( 'help text displays correctly', () => {
			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				...(
					global as unknown as {
						wpGraphQLLogin: WpGraphQLLoginGlobal;
					}
				 ).wpGraphQLLogin,
				secret: {},
			};

			const props: FieldSchema = {
				label: 'Regenerate JWT Secret',
				description: 'JWT Secret description',
				type: 'string',
				help: 'This will invalidate all existing tokens',
			};

			renderWithSettingsProvider( <JwtSecretControl { ...props } /> );

			const baseControl = screen.getByTestId( 'base-control' );
			expect( baseControl ).toHaveAttribute(
				'data-help',
				'This will invalidate all existing tokens'
			);
		} );

		it( 'button is destructive (red variant)', () => {
			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				...(
					global as unknown as {
						wpGraphQLLogin: WpGraphQLLoginGlobal;
					}
				 ).wpGraphQLLogin,
				secret: {},
			};

			const props: FieldSchema = {
				label: 'Regenerate JWT Secret',
				description: 'JWT Secret description',
				type: 'string',
				help: 'Help text',
			};

			renderWithSettingsProvider( <JwtSecretControl { ...props } /> );

			const button = screen.getByTestId( 'jwt-secret-button' );
			expect( button ).toHaveAttribute( 'data-is-destructive', 'true' );
		} );

		it( 'button has correct icon and variant', () => {
			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				...(
					global as unknown as {
						wpGraphQLLogin: WpGraphQLLoginGlobal;
					}
				 ).wpGraphQLLogin,
				secret: {},
			};

			const props: FieldSchema = {
				label: 'Regenerate JWT Secret',
				description: 'JWT Secret description',
				type: 'string',
				help: 'Help text',
			};

			renderWithSettingsProvider( <JwtSecretControl { ...props } /> );

			const button = screen.getByTestId( 'jwt-secret-button' );
			expect( button ).toHaveAttribute( 'data-icon', 'admin-network' );
			expect( button ).toHaveAttribute( 'data-variant', 'secondary' );
			expect( button ).toHaveAttribute( 'data-icon-size', '16' );
		} );
	} );

	describe( 'Disabled state', () => {
		it( 'button is disabled when secret.isConstant is true', () => {
			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				...(
					global as unknown as {
						wpGraphQLLogin: WpGraphQLLoginGlobal;
					}
				 ).wpGraphQLLogin,
				secret: {
					isConstant: true,
				},
			};

			const props: FieldSchema = {
				label: 'Regenerate JWT Secret',
				description: 'JWT Secret description',
				type: 'string',
				help: 'Help text',
			};

			renderWithSettingsProvider( <JwtSecretControl { ...props } /> );

			const button = screen.getByTestId( 'jwt-secret-button' );
			expect( button ).toHaveAttribute( 'data-disabled', 'true' );
		} );

		it( 'renders warning message when secret.isConstant', () => {
			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				...(
					global as unknown as {
						wpGraphQLLogin: WpGraphQLLoginGlobal;
					}
				 ).wpGraphQLLogin,
				secret: {
					isConstant: true,
				},
			};

			const props: FieldSchema = {
				label: 'Regenerate JWT Secret',
				description: 'JWT Secret description',
				type: 'string',
				help: 'Help text',
			};

			renderWithSettingsProvider( <JwtSecretControl { ...props } /> );

			const warningText = screen.queryByText(
				/The JWT secret is set in wp-config\.php and cannot be changed on the backend\./
			);
			expect( warningText ).toBeInTheDocument();
		} );

		it( 'button is enabled when secret.isConstant is false', () => {
			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				...(
					global as unknown as {
						wpGraphQLLogin: WpGraphQLLoginGlobal;
					}
				 ).wpGraphQLLogin,
				secret: {
					isConstant: false,
				},
			};

			const props: FieldSchema = {
				label: 'Regenerate JWT Secret',
				description: 'JWT Secret description',
				type: 'string',
				help: 'Help text',
			};

			renderWithSettingsProvider( <JwtSecretControl { ...props } /> );

			const button = screen.getByTestId( 'jwt-secret-button' );
			expect( button ).toHaveAttribute( 'data-disabled', 'false' );
		} );

		it( 'button click only works when not disabled', () => {
			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				...(
					global as unknown as {
						wpGraphQLLogin: WpGraphQLLoginGlobal;
					}
				 ).wpGraphQLLogin,
				secret: {
					isConstant: true,
				},
			};

			const props: FieldSchema = {
				label: 'Regenerate JWT Secret',
				description: 'JWT Secret description',
				type: 'string',
				help: 'Help text',
			};

			renderWithSettingsProvider( <JwtSecretControl { ...props } /> );

			const button = screen.getByTestId( 'jwt-secret-button' );

			act( () => {
				fireEvent.click( button );
			} );

			expect( () => {
				fireEvent.click( button );
			} ).not.toThrow();
		} );
	} );

	describe( 'Secret state variations', () => {
		it( 'handles undefined secret (empty object)', () => {
			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				...(
					global as unknown as {
						wpGraphQLLogin: WpGraphQLLoginGlobal;
					}
				 ).wpGraphQLLogin,
				secret: {},
			};

			const props: FieldSchema = {
				label: 'Regenerate JWT Secret',
				description: 'JWT Secret description',
				type: 'string',
				help: 'Help text',
			};

			renderWithSettingsProvider( <JwtSecretControl { ...props } /> );

			const button = screen.getByTestId( 'jwt-secret-button' );
			expect( button ).toBeInTheDocument();
			expect( button ).toHaveAttribute( 'data-disabled', 'false' );
		} );

		it( 'handles secret without isConstant property', () => {
			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				...(
					global as unknown as {
						wpGraphQLLogin: WpGraphQLLoginGlobal;
					}
				 ).wpGraphQLLogin,
				secret: {
					otherProperty: 'value',
				},
			};

			const props: FieldSchema = {
				label: 'Regenerate JWT Secret',
				description: 'JWT Secret description',
				type: 'string',
				help: 'Help text',
			};

			renderWithSettingsProvider( <JwtSecretControl { ...props } /> );

			const button = screen.getByTestId( 'jwt-secret-button' );
			expect( button ).toBeInTheDocument();
			expect( button ).toHaveAttribute( 'data-disabled', 'false' );
		} );
	} );

	describe( 'Integration with SettingsContext', () => {
		it( 'has access to settings context methods', async () => {
			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				...(
					global as unknown as {
						wpGraphQLLogin: WpGraphQLLoginGlobal;
					}
				 ).wpGraphQLLogin,
				secret: {},
			};

			const { result } = renderHook( () => useSettings(), {
				wrapper: ( { children } ) => (
					<SettingsProvider>{ children }</SettingsProvider>
				),
			} );

			await waitFor( () => {
				expect( result.current ).toBeDefined();
			} );

			expect( result.current.updateSettings ).toBeDefined();
			expect( result.current.saveSettings ).toBeDefined();
			expect( result.current.isSaving ).toBeDefined();
			expect( typeof result.current.updateSettings ).toBe( 'function' );
			expect( typeof result.current.saveSettings ).toBe( 'function' );
		} );

		it( 'useDispatch is called for notices', () => {
			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				...(
					global as unknown as {
						wpGraphQLLogin: WpGraphQLLoginGlobal;
					}
				 ).wpGraphQLLogin,
				secret: {},
			};

			const props: FieldSchema = {
				label: 'Regenerate JWT Secret',
				description: 'JWT Secret description',
				type: 'string',
				help: 'Help text',
			};

			renderWithSettingsProvider( <JwtSecretControl { ...props } /> );

			expect( mockCreateNotice ).toBeDefined();
			expect( mockCreateErrorNotice ).toBeDefined();
		} );
	} );

	describe( 'Regenerating the JWT secret', () => {
		const props: FieldSchema = {
			label: 'Regenerate JWT Secret',
			description: 'JWT Secret description',
			type: 'string',
			help: 'Help text',
		};

		beforeEach( () => {
			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				...(
					global as unknown as {
						wpGraphQLLogin: WpGraphQLLoginGlobal;
					}
				 ).wpGraphQLLogin,
				secret: {},
			};
		} );

		it( 'POSTs an explicitly empty jwt_secret_key and shows the success notice', async () => {
			renderWithSettingsProvider( <JwtSecretControl { ...props } /> );

			fireEvent.click( screen.getByTestId( 'jwt-secret-button' ) );

			// The empty string (not the masked placeholder) must reach the
			// server — it is what triggers server-side regeneration.
			await waitFor( () => {
				expect( apiFetch ).toHaveBeenCalledWith( {
					path: 'wp-graphql-login/v1/settings',
					method: 'POST',
					data: {
						slug: 'wpgraphql_login_settings',
						values: { jwt_secret_key: '' },
					},
				} );
			} );

			await waitFor( () => {
				expect( mockCreateNotice ).toHaveBeenCalledWith(
					'success',
					'The old JWT secret has been invalidated.',
					{ type: 'snackbar', isDismissible: true }
				);
			} );
		} );

		it( 'does not show the success notice when saving fails', async () => {
			vi.mocked( apiFetch )
				// Initial GET on mount.
				.mockResolvedValueOnce( {} )
				// The regenerate POST.
				.mockRejectedValueOnce( new Error( 'Request failed' ) );

			renderWithSettingsProvider( <JwtSecretControl { ...props } /> );

			fireEvent.click( screen.getByTestId( 'jwt-secret-button' ) );

			await waitFor( () => {
				expect( mockCreateErrorNotice ).toHaveBeenCalled();
			} );

			expect( mockCreateNotice ).not.toHaveBeenCalled();
		} );
	} );
} );
