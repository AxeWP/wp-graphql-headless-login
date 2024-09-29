import { _Hooks } from '@wordpress/hooks/build-types/createHooks';

declare global {
	const wpGraphQLLogin: {
		hooks: _Hooks;
		settings: {
			accessControl: Record<
				keyof AccessControlSettingsType,
				FieldSchema
			>;
			providers: Record<
				string,
				Record<
					keyof ProviderSettingType,
					FieldSchema & {
						properties: Record<
							keyof ProviderSettingType,
							FieldSchema
						>;
					}
				>
			>;
			plugin: Record< keyof PluginSettingsType, FieldSchema >;
		};
		nonce: string;
		secret: {
			hasKey: boolean;
			isConstant: boolean;
		};
	};
}

type AllowedConditionalLogicOperators = '==' | '!=' | '>' | '<' | '>=' | '<=';

type FieldSchema = {
	conditionalLogic?: [
		{
			slug: string;
			operator: AllowedConditionalLogicOperators;
			value: string | number | boolean;
		},
	];
	advanced?: boolean;
	controlOverrides?: Record< string, unknown >;
	controlType?: string;
	default?: unknown;
	description: string;
	enum?: string[];
	help?: string;
	hidden?: boolean;
	label: string;
	order?: number;
	required?: boolean;
	type: string;
};

type AccessControlSettingsType = {
	hasAccessControlAllowCredentials?: boolean;
	hasSiteAddressInOrigin?: boolean;
	additionalAuthorizedDomains?: string[];
	shouldBlockUnauthorizedDomains?: boolean;
	customHeaders?: string[];
	[ key: string ]: any;
};

type PluginSettingsType = {
	wpgraphql_login_settings_show_avanced_settings?: boolean;
	wpgraphql_login_settings_jwt_secret_key?: string;
	wpgraphql_login_settings_delete_data_on_deactivate?: boolean;
	[ key: string ]: any;
};

type ProviderSettingType = {
	name: string;
	order: number;
	slug?: string;
	isEnabled: boolean;
	[ key: string ]: any;
	clientOptions: ClientOptionsType;
	loginOptions: LoginOptionsType;
};

type ClientOptionsType = OAuth2ClientOptionsType | SiteTokenClientOptionsType;

type OAuth2ClientOptionsType = Record<
	string,
	{
		redirectUri: string;
		clientId: string;
		clientSecret: string;
		[ key: string ]: any;
	}
>;

type SiteTokenClientOptionsType = Record<
	string,
	{
		headerKey: string;
		secretKey: string;
		[ key: string ]: any;
	}
>;

type LoginOptionsType = {
	useAuthenticationCookie?: boolean;
	[ key: string ]: any;
} & ( OAuth2LoginOptionsType | SiteTokenLoginOptionsType );

type SiteTokenLoginOptionsType = {
	metaKey?: string;
	[ key: string ]: any;
};

type OAuth2LoginOptionsType = {
	createUserIfNoneExists?: boolean;
	linkExistingUsers?: boolean;
};
