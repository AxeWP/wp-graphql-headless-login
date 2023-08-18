import { useEntityProp, store } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import { createContext, useContext } from '@wordpress/element';
import type { PropsWithChildren } from 'react';

export const AppProviderContext = createContext<{
	showAdvancedSettings: boolean;
	setShowAdvancedSettings: (value: boolean) => void;
}>({
	showAdvancedSettings: false,
	setShowAdvancedSettings: () => {}, // eslint-disable-line @typescript-eslint/no-empty-function
});

export const AppProvider = ({ children }: PropsWithChildren) => {
	const { saveEditedEntityRecord } = useDispatch(store);

	const [shouldShow, setShouldShow] = useEntityProp(
		'root',
		'site',
		'wpgraphql_login_settings_show_advanced_settings'
	);

	const setShowAdvancedSettings = (value: boolean) => {
		setShouldShow(!!value);
		saveEditedEntityRecord('root', 'site', undefined, {
			wpgraphql_login_settings_show_advanced_settings: !!value,
		});
	};

	return (
		<AppProviderContext.Provider
			value={{
				showAdvancedSettings: !!shouldShow,
				setShowAdvancedSettings,
			}}
		>
			{children}
		</AppProviderContext.Provider>
	);
};

export const useAppContext = () => useContext(AppProviderContext);
