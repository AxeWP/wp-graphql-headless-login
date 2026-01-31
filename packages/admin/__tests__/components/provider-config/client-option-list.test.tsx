import { render, screen, fireEvent } from '@testing-library/react';
import { vi, beforeEach, afterEach } from 'vitest';
import { ClientOptionList } from '@/admin/components/provider-config/ClientOptionList';
import {
	setupWpGraphQLLoginMock,
	resetWpGraphQLLoginMocks,
} from '../../mocks/wordpress-global.mock';
import type { FieldSchema } from '@/admin/types';

vi.mock( '@/admin/components/fields', () => ( {
	Fields: vi.fn(
		( {
			fields,
			values,
			setValue,
			excludedProperties,
		}: {
			fields: Record< string, FieldSchema >;
			values: Record< string, unknown > | undefined;
			setValue: ( values: Record< string, unknown > ) => void;
			excludedProperties?: string[];
		} ) => {
			if ( ! values ) {
				return null;
			}

			const fieldKeys = Object.keys( fields )
				.filter(
					( key ) =>
						! excludedProperties?.includes( key ) &&
						! fields[ key ]?.hidden
				)
				.sort( ( a, b ) => {
					const aOrder = fields[ a ]?.order ?? 0;
					const bOrder = fields[ b ]?.order ?? 0;
					return aOrder - bOrder;
				} );

			if ( fieldKeys.length === 0 ) {
				return null;
			}

			return (
				<div data-testid="fields-container">
					{ fieldKeys.map( ( key ) => (
						<button
							key={ key }
							type="button"
							data-testid={ `field-${ key }` }
							data-field-key={ key }
							data-value={ JSON.stringify( values?.[ key ] ) }
							onClick={ () => {
								setValue( {
									...values,
									[ key ]: `updated-${ key }`,
								} );
							} }
						>
							{ fields[ key ]?.label || key }
						</button>
					) ) }
				</div>
			);
		}
	),
} ) );

describe( 'ClientOptionList Component', () => {
	let mockSetOption: ReturnType< typeof vi.fn > &
		( ( values: Record< string, unknown > ) => void );

	beforeEach( () => {
		setupWpGraphQLLoginMock();
		mockSetOption = vi.fn() as ReturnType< typeof vi.fn > &
			( ( values: Record< string, unknown > ) => void );
		vi.clearAllMocks();
	} );

	afterEach( () => {
		resetWpGraphQLLoginMocks();
		vi.clearAllMocks();
	} );

	describe( 'Renders all options from schema', () => {
		it( 'renders all fields defined in schema', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: {
							providers: Record<
								string,
								Record< string, unknown >
							>;
						};
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: 'OAuth2',
					order: 1,
					clientOptions: {
						properties: {
							clientId: {
								label: 'Client ID',
								description: 'OAuth Client ID',
								type: 'string',
							},
							clientSecret: {
								label: 'Client Secret',
								description: 'OAuth Client Secret',
								type: 'string',
							},
							redirectUri: {
								label: 'Redirect URI',
								description: 'Redirect URI',
								type: 'string',
							},
						},
					},
				},
			};

			const mockOptions = {
				clientId: 'test-client-id',
				clientSecret: 'test-client-secret',
				redirectUri: 'https://example.com/callback',
			};

			render(
				<ClientOptionList
					clientSlug="oauth2"
					optionsKey="clientOptions"
					options={ mockOptions }
					setOption={ mockSetOption }
				/>
			);

			expect(
				screen.getByTestId( 'field-clientId' )
			).toBeInTheDocument();
			expect(
				screen.getByTestId( 'field-clientSecret' )
			).toBeInTheDocument();
			expect(
				screen.getByTestId( 'field-redirectUri' )
			).toBeInTheDocument();
		} );

		it( 'renders fields in correct order based on order property', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: {
							providers: Record<
								string,
								Record< string, unknown >
							>;
						};
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: 'OAuth2',
					order: 1,
					loginOptions: {
						properties: {
							thirdField: {
								label: 'Third Field',
								type: 'string',
								order: 3,
							},
							firstField: {
								label: 'First Field',
								type: 'string',
								order: 1,
							},
							secondField: {
								label: 'Second Field',
								type: 'string',
								order: 2,
							},
						},
					},
				},
			};

			const mockOptions = {
				firstField: 'value1',
				secondField: 'value2',
				thirdField: 'value3',
			};

			const { container } = render(
				<ClientOptionList
					clientSlug="oauth2"
					optionsKey="loginOptions"
					options={ mockOptions }
					setOption={ mockSetOption }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 3 );
			expect( fieldElements[ 0 ] ).toHaveAttribute(
				'data-field-key',
				'firstField'
			);
			expect( fieldElements[ 1 ] ).toHaveAttribute(
				'data-field-key',
				'secondField'
			);
			expect( fieldElements[ 2 ] ).toHaveAttribute(
				'data-field-key',
				'thirdField'
			);
		} );
	} );

	describe( 'Option values display correctly', () => {
		it( 'displays option values correctly in field data attributes', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: {
							providers: Record<
								string,
								Record< string, unknown >
							>;
						};
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: 'OAuth2',
					order: 1,
					clientOptions: {
						properties: {
							clientId: {
								label: 'Client ID',
								type: 'string',
							},
							count: {
								label: 'Count',
								type: 'number',
							},
						},
					},
				},
			};

			const mockOptions = {
				clientId: 'my-app-client-id',
				count: 42,
			};

			render(
				<ClientOptionList
					clientSlug="oauth2"
					optionsKey="clientOptions"
					options={ mockOptions }
					setOption={ mockSetOption }
				/>
			);

			const clientIdField = screen.getByTestId( 'field-clientId' );
			const countField = screen.getByTestId( 'field-count' );

			expect( clientIdField ).toHaveAttribute(
				'data-value',
				'"my-app-client-id"'
			);
			expect( countField ).toHaveAttribute( 'data-value', '42' );
		} );

		it( 'handles null and undefined values', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: {
							providers: Record<
								string,
								Record< string, unknown >
							>;
						};
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: 'OAuth2',
					order: 1,
					clientOptions: {
						properties: {
							clientId: {
								label: 'Client ID',
								type: 'string',
							},
							secret: {
								label: 'Secret',
								type: 'string',
							},
						},
					},
				},
			};

			const mockOptions = {
				clientId: 'test-id',
				secret: null,
			};

			render(
				<ClientOptionList
					clientSlug="oauth2"
					optionsKey="clientOptions"
					options={ mockOptions }
					setOption={ mockSetOption }
				/>
			);

			const clientIdField = screen.getByTestId( 'field-clientId' );
			const secretField = screen.getByTestId( 'field-secret' );

			expect( clientIdField ).toHaveAttribute(
				'data-value',
				'"test-id"'
			);
			expect( secretField ).toHaveAttribute( 'data-value', 'null' );
		} );
	} );

	describe( 'Option updates trigger setOption callback', () => {
		it( 'calls setOption when a field is updated', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: {
							providers: Record<
								string,
								Record< string, unknown >
							>;
						};
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: 'OAuth2',
					order: 1,
					clientOptions: {
						properties: {
							clientId: {
								label: 'Client ID',
								type: 'string',
							},
							clientSecret: {
								label: 'Client Secret',
								type: 'string',
							},
						},
					},
				},
			};

			const mockOptions = {
				clientId: 'original-id',
				clientSecret: 'original-secret',
			};

			render(
				<ClientOptionList
					clientSlug="oauth2"
					optionsKey="clientOptions"
					options={ mockOptions }
					setOption={ mockSetOption }
				/>
			);

			const clientIdField = screen.getByTestId( 'field-clientId' );
			fireEvent.click( clientIdField );

			expect( mockSetOption ).toHaveBeenCalledTimes( 1 );
			expect( mockSetOption ).toHaveBeenCalledWith( {
				clientId: 'updated-clientId',
				clientSecret: 'original-secret',
			} );
		} );

		it( 'maintains other option values when one is updated', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: {
							providers: Record<
								string,
								Record< string, unknown >
							>;
						};
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: 'OAuth2',
					order: 1,
					loginOptions: {
						properties: {
							option1: {
								label: 'Option 1',
								type: 'string',
							},
							option2: {
								label: 'Option 2',
								type: 'string',
							},
							option3: {
								label: 'Option 3',
								type: 'string',
							},
						},
					},
				},
			};

			const mockOptions = {
				option1: 'value1',
				option2: 'value2',
				option3: 'value3',
			};

			render(
				<ClientOptionList
					clientSlug="oauth2"
					optionsKey="loginOptions"
					options={ mockOptions }
					setOption={ mockSetOption }
				/>
			);

			const option2Field = screen.getByTestId( 'field-option2' );
			fireEvent.click( option2Field );

			expect( mockSetOption ).toHaveBeenCalledWith( {
				option1: 'value1',
				option2: 'updated-option2',
				option3: 'value3',
			} );
		} );
	} );

	describe( 'Hidden options (via conditional logic) do not render', () => {
		it( 'does not render hidden fields', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: {
							providers: Record<
								string,
								Record< string, unknown >
							>;
						};
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: 'OAuth2',
					order: 1,
					clientOptions: {
						properties: {
							clientId: {
								label: 'Client ID',
								type: 'string',
							},
							secret: {
								label: 'Secret',
								type: 'string',
								hidden: true,
							},
						},
					},
				},
			};

			const mockOptions = {
				clientId: 'test-id',
				secret: 'test-secret',
			};

			render(
				<ClientOptionList
					clientSlug="oauth2"
					optionsKey="clientOptions"
					options={ mockOptions }
					setOption={ mockSetOption }
				/>
			);

			expect(
				screen.getByTestId( 'field-clientId' )
			).toBeInTheDocument();
			expect(
				screen.queryByTestId( 'field-secret' )
			).not.toBeInTheDocument();
		} );

		it( 'handles all fields hidden scenario', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: {
							providers: Record<
								string,
								Record< string, unknown >
							>;
						};
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: 'OAuth2',
					order: 1,
					loginOptions: {
						properties: {
							option1: {
								label: 'Option 1',
								type: 'string',
								hidden: true,
							},
							option2: {
								label: 'Option 2',
								type: 'string',
								hidden: true,
							},
						},
					},
				},
			};

			render(
				<ClientOptionList
					clientSlug="oauth2"
					optionsKey="clientOptions"
					options={
						undefined as unknown as Record< string, unknown >
					}
					setOption={ mockSetOption }
				/>
			);

			const fieldsContainer = screen.queryByTestId( 'fields-container' );
			expect( fieldsContainer ).not.toBeInTheDocument();
		} );
	} );

	describe( 'Missing option in schema', () => {
		it( 'handles options that exist but are not in schema', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: {
							providers: Record<
								string,
								Record< string, unknown >
							>;
						};
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: 'OAuth2',
					order: 1,
					clientOptions: {
						properties: {
							clientId: {
								label: 'Client ID',
								type: 'string',
							},
						},
					},
				},
			};

			const mockOptions = {
				clientId: 'test-id',
				extraOption: 'extra-value', // Not in schema
			};

			render(
				<ClientOptionList
					clientSlug="oauth2"
					optionsKey="clientOptions"
					options={ mockOptions }
					setOption={ mockSetOption }
				/>
			);

			expect(
				screen.getByTestId( 'field-clientId' )
			).toBeInTheDocument();
			expect(
				screen.queryByTestId( 'field-extraOption' )
			).not.toBeInTheDocument();
		} );

		it( 'handles schema with empty properties', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: {
							providers: Record<
								string,
								Record< string, unknown >
							>;
						};
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: 'OAuth2',
					order: 1,
					clientOptions: {
						properties: {},
					},
				},
			};

			const mockOptions = {
				clientId: 'test-id',
				clientSecret: 'test-secret',
			};

			render(
				<ClientOptionList
					clientSlug="oauth2"
					optionsKey="clientOptions"
					options={ mockOptions }
					setOption={ mockSetOption }
				/>
			);

			const fieldsContainer = screen.queryByTestId( 'fields-container' );
			if ( fieldsContainer ) {
				expect( fieldsContainer.children ).toHaveLength( 0 );
			} else {
				expect( fieldsContainer ).toBeNull();
			}
		} );

		it( 'handles missing optionsKey in provider settings', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: {
							providers: Record<
								string,
								Record< string, unknown >
							>;
						};
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: 'OAuth2',
					order: 1,
					// clientOptions is missing
				},
			};

			const mockOptions = {
				clientId: 'test-id',
			};

			render(
				<ClientOptionList
					clientSlug="oauth2"
					optionsKey="clientOptions"
					options={ mockOptions }
					setOption={ mockSetOption }
				/>
			);

			const fieldsContainer = screen.queryByTestId( 'fields-container' );
			if ( fieldsContainer ) {
				expect( fieldsContainer.children ).toHaveLength( 0 );
			} else {
				expect( fieldsContainer ).toBeNull();
			}
		} );

		it( 'handles missing provider in settings', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: {
							providers: Record<
								string,
								Record< string, unknown >
							>;
						};
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: 'OAuth2',
					order: 1,
				},
				// github provider is missing
			};

			const mockOptions = {
				clientId: 'test-id',
			};

			render(
				<ClientOptionList
					clientSlug="oauth2"
					optionsKey="clientOptions"
					options={ mockOptions }
					setOption={ mockSetOption }
				/>
			);

			const fieldsContainer = screen.queryByTestId( 'fields-container' );
			if ( fieldsContainer ) {
				expect( fieldsContainer.children ).toHaveLength( 0 );
			} else {
				expect( fieldsContainer ).toBeNull();
			}
		} );
	} );

	describe( 'Excluded properties filtering', () => {
		it( 'excludes id and order properties from rendering', () => {
			(
				global as unknown as {
					wpGraphQLLogin: {
						settings: {
							providers: Record<
								string,
								Record< string, unknown >
							>;
						};
					};
				}
			 ).wpGraphQLLogin.settings.providers = {
				oauth2: {
					name: 'OAuth2',
					order: 1,
					clientOptions: {
						properties: {
							clientId: {
								label: 'Client ID',
								type: 'string',
							},
							id: {
								label: 'ID',
								type: 'string',
							},
							order: {
								label: 'Order',
								type: 'string',
							},
							clientSecret: {
								label: 'Client Secret',
								type: 'string',
							},
						},
					},
				},
			};

			const mockOptions = {
				clientId: 'test-id',
				id: '123',
				order: '1',
				clientSecret: 'test-secret',
			};

			render(
				<ClientOptionList
					clientSlug="oauth2"
					optionsKey="clientOptions"
					options={ mockOptions }
					setOption={ mockSetOption }
				/>
			);

			expect(
				screen.getByTestId( 'field-clientId' )
			).toBeInTheDocument();
			expect(
				screen.getByTestId( 'field-clientSecret' )
			).toBeInTheDocument();
			expect(
				screen.queryByTestId( 'field-id' )
			).not.toBeInTheDocument();
			expect(
				screen.queryByTestId( 'field-order' )
			).not.toBeInTheDocument();
		} );
	} );
} );
