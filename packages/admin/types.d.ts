/// <reference types="react" />

import type { createHooks } from '@wordpress/hooks';

export type AllowedConditionalLogicOperators =
	| '=='
	| '!='
	| '>'
	| '<'
	| '>='
	| '<=';

export type FieldSchema = {
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
	order?: number;
	required?: boolean;
	isAdvanced?: boolean;
	// Used by Provider configs. needs to be migrated to the uniform schema.
	advanced?: boolean;
};

export type SettingSchema = {
	[ key: string ]: {
		title: string;
		description: string;
		label: string;
		fields: Record< string, FieldSchema >;
	};
};

export type ProviderSettingType = {
	name: string;
	order: number;
	slug?: string;
	isEnabled: boolean;
	[ key: string ]: any;
	clientOptions: ClientOptionsType;
	loginOptions: LoginOptionsType;
};

export type ClientOptionsType =
	| OAuth2ClientOptionsType
	| SiteTokenClientOptionsType;

export type OAuth2ClientOptionsType = Record<
	string,
	{
		redirectUri: string;
		clientId: string;
		clientSecret: string;
		[ key: string ]: any;
	}
>;

export type SiteTokenClientOptionsType = Record<
	string,
	{
		headerKey: string;
		secretKey: string;
		[ key: string ]: any;
	}
>;

export type LoginOptionsType = {
	useAuthenticationCookie?: boolean;
	[ key: string ]: any;
} & ( OAuth2LoginOptionsType | SiteTokenLoginOptionsType );

export type SiteTokenLoginOptionsType = {
	metaKey?: string;
	[ key: string ]: any;
};

export type OAuth2LoginOptionsType = {
	createUserIfNoneExists?: boolean;
	linkExistingUsers?: boolean;
};

export type WpGraphQLLogin = {
	hooks: ReturnType< typeof createHooks >;
	settings: SettingSchema & {
		providers: Record<
			string,
			Record<
				string,
				FieldSchema & {
					properties: Record< string, FieldSchema >;
				}
			>
		>;
	};
	nonce: string;
	secret: {
		hasKey: boolean;
		isConstant: boolean;
	};
};

declare global {
	const wpGraphQLLogin: WpGraphQLLogin;
}
