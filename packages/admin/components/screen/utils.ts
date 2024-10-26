/**
 * Maps a plugin setting key to to its corresponding screen.
 *
 * @param {string} setting The setting to map to a screen. E.g. `wpgraphql_login_access_control`.
 */
export const getScreenForSetting = ( setting: string ): string => {
	const settingPrefix = 'wpgraphql_login_';

	// First, strip the prefix.
	const settingWithoutPrefix = setting.replace( settingPrefix, '' );

	// Then, convert to `kebab-case`.
	return settingWithoutPrefix.replace( /_/g, '-' );
};

/**
 * Maps a screen to it's corresponding plugin setting.
 *
 * @param {string} screen The screen to map to a setting. E.g. `access-control`.
 */
export const getSettingForScreen = ( screen: string ): string => {
	const settingPrefix = 'wpgraphql_login_';

	// First, convert to `snake_case`.
	const snakeCaseScreen = screen.replace( /-/g, '_' );

	// Then, add the prefix.
	const setting = settingPrefix + snakeCaseScreen;

	// Ensure lowercase
	return setting.toLowerCase();
};

/**
 * Checks whether a screen is allowed.
 *
 * Allowed screens are the keys defined in the `WPGraphQLLogin.settings` global.
 *
 * @param {string} screen The screen to check.
 */
export const isAllowedScreen = ( screen: string ): boolean => {
	const allowedSettings = Object.keys( wpGraphQLLogin.settings );

	const settingToCheck = getSettingForScreen( screen );

	return allowedSettings.includes( settingToCheck );
};
