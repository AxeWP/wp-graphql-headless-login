import { _Hooks } from "@wordpress/hooks/build-types/createHooks";

declare global {
	const wpGraphQLLogin: {
		hooks: _Hooks;
	settings: {
		accessControl: Record<string, any>;
		providers: Record<string, any>;
		plugin: Record<string, any>;
	};
	nonce: string;
	secret: {
		hasKey: boolean;
		isConstant: boolean;
	};
	}
}
