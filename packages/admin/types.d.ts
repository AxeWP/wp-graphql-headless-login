import { _Hooks } from '@wordpress/hooks/build-types/createHooks';

declare global {
	const wpGraphQLLogin: {
		hooks: _Hooks;
		settings: SettingSchema & {
			providers: Record< string, Record< string, FieldSchema & {
				properties: Record< string, FieldSchema >;
			} > >;
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
	description: string;
	label: string;
	type: string;
	conditionalLogic?: [
		{
			slug: string;
			operator: AllowedConditionalLogicOperators;
			value: string | number | boolean;
		},
	];
	controlOverrides?: Record< string, unknown >;
	controlType?: string;
	default?: unknown;
	enum?: string[]; // @todo Not sure if this is the correct type
	help?: string;
	hidden?: boolean;
	isAdvanced?: boolean;
	order?: number;
	required?: boolean;
};

type SettingSchema = {
	[ key: string ]: {
		title: string;
		description: string;
		label: string;
		fields: Record< string, FieldSchema >;
	}
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
