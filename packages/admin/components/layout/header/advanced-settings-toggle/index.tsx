import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useAppContext } from '@/admin/contexts/AppProvider';

/**
 * The advanced settings toggle.
 */
export const AdvancedSettingsToggle = () => {
	const { showAdvancedSettings, setShowAdvancedSettings } = useAppContext();

	const classNames = 'wp-graphql-headless-login__advanced-settings-toggle';
	const label = __( 'Show advanced settings', 'wp-graphql-headless-login' );

	return (
		<ToggleControl
			className={ classNames }
			checked={ showAdvancedSettings }
			label={ label }
			onChange={ ( value ) => setShowAdvancedSettings( value ) }
		/>
	);
};
