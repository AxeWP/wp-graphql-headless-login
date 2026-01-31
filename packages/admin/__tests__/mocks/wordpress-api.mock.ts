type ApiResponseHandler = (
	request: RequestInfo
) => Response | Promise< Response >;

const apiHandlers = new Map< string, ApiResponseHandler >();
const defaultSettings: Record< string, unknown > = {
	wpgraphql_login_settings: {},
};

export const setupApiFetchMock = (): void => {
	global.fetch = vi.fn( async ( input: RequestInfo | URL ) => {
		let url: URL;
		if ( typeof input === 'string' ) {
			url = new URL( input );
		} else if ( input instanceof URL ) {
			url = input;
		} else {
			url = new URL( input.url );
		}

		const handler = apiHandlers.get( url.toString() );

		if ( handler ) {
			return handler( input as unknown as Request );
		}

		return new Response( JSON.stringify( {} ), { status: 404 } );
	} ) as unknown as (
		request: RequestInfo | URL,
		init?: RequestInit
	) => Promise< Response >;
};

export const resetApiResponseHandlers = (): void => {
	apiHandlers.clear();
};

export const addApiResponseHandler = (
	url: string,
	handler: ApiResponseHandler
): void => {
	apiHandlers.set( url, handler );
};

export const setDefaultSettingsResponse = (
	settings: Record< string, unknown >
): void => {
	( defaultSettings as Record< string, unknown > )[
		'wpgraphql_login_settings'
	] = settings;
};
