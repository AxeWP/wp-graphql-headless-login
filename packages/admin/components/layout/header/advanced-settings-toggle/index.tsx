import { useSettings } from '@/admin/contexts/settings-context';
import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * The advanced settings toggle.
 */
export const AdvancedSettingsToggle = () => {
	const { showAdvancedSettings, updateSettings, saveSettings } =
		useSettings();

	const classNames = 'wp-graphql-headless-login__advanced-settings-toggle';
	const label = __( 'Show advanced settings', 'wp-graphql-headless-login' );

	const setShowAdvancedSettings = async ( value: boolean ) => {
		await updateSettings( {
			slug: 'wpgraphql_login_settings',
			values: {
				show_advanced_settings: value,
			},
		} );

		// Save the setting immediately
		await saveSettings( 'wpgraphql_login_settings' );
	};

	return (
		<ToggleControl
			className={ classNames }
			checked={ showAdvancedSettings }
			label={ label }
			onChange={ ( value ) => setShowAdvancedSettings( value ) }
		/>
	);
};
