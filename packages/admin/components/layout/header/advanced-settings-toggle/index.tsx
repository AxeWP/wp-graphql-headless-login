import { useSettings } from '@/admin/contexts/settings-context';
import { ToggleControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * The advanced settings toggle.
 */
export const AdvancedSettingsToggle = () => {
	const {
		showAdvancedSettings,
		updateSettings,
		saveSettings,
		isSaving,
		settings,
	} = useSettings();
	const [ needsSave, setNeedsSave ] = useState( false );

	useEffect( () => {
		const save = async () => {
			if ( needsSave && ! isSaving ) {
				await saveSettings( 'wpgraphql_login_settings' );
				setNeedsSave( false );
			}
		};

		save();
	}, [ needsSave, isSaving, saveSettings ] );

	const classNames = 'wp-graphql-headless-login__advanced-settings-toggle';
	const label = __( 'Show advanced settings', 'wp-graphql-headless-login' );

	const setShowAdvancedSettings = async ( value: boolean ) => {
		await updateSettings( {
			slug: 'wpgraphql_login_settings',
			values: {
				...settings?.wpgraphql_login_settings,
				show_advanced_settings: value,
			},
		} );

		setNeedsSave( true );
	};

	return (
		<ToggleControl
			className={ classNames }
			checked={ showAdvancedSettings }
			label={ label }
			disabled={ isSaving }
			onChange={ setShowAdvancedSettings }
		/>
	);
};
