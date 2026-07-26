import { useEffect, useMemo } from 'react';
import {
	Button,
	Icon,
	PanelBody,
	PanelRow,
	Placeholder,
	Spinner,
} from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { ClientOptionList } from './ClientOptionList';
import { useClientContext } from '@/admin/contexts/provider-config-context';
import { ReactComponent as Logo } from '@/admin/assets/logo.svg';
import { Fields } from '@/admin/components/fields';
import { useSettings } from '@/admin/contexts/settings-context';

const PROVIDER_PREFIX = 'wpgraphql_login_provider_';

export function ClientPanel() {
	const { settings } = useSettings();
	const {
		activeClient,
		clientConfig,
		setClientConfig,
		updateClient,
		setClientOption,
		setLoginOption,
	} = useClientContext();
	const { saveEditedEntityRecord } = useDispatch( coreStore );
	const { createNotice, createErrorNotice } = useDispatch( noticesStore );

	// Strip the prefix from activeClient for looking up provider settings
	const providerSlug = useMemo(
		() => activeClient?.replace( PROVIDER_PREFIX, '' ) || '',
		[ activeClient ]
	);

	const { lastError, isSaving, hasEdits } = useSelect(
		( select ) => ( {
			lastError: select( coreStore )?.getLastEntitySaveError(
				'root',
				'site'
			),
			isSaving: select( coreStore )?.isSavingEntityRecord(
				'root',
				'site'
			),
			hasEdits: select( coreStore )?.hasEditsForEntityRecord(
				'root',
				'site'
			),
		} ),
		[]
	);

	useEffect( () => {
		if ( lastError ) {
			createErrorNotice(
				sprintf(
					// translators: %s: Error message.
					__(
						'Error saving settings: %s',
						'wp-graphql-headless-login'
					),
					lastError?.data?.params?.[ activeClient ] ||
						lastError?.message
				),
				{
					type: 'snackbar',
					isDismissible: true,
					explicitDismiss: true,
				}
			);
		}
	}, [ lastError, activeClient, createErrorNotice ] );

	// Disable siteToken if shouldBlockUnauthorizedDomains is false
	useEffect( () => {
		const accessControlSettings =
			settings?.[ 'wpgraphql_login_access_control' ] || {};

		if (
			! accessControlSettings?.[ 'shouldBlockUnauthorizedDomains' ] &&
			activeClient === 'wpgraphql_login_provider_siteToken' &&
			clientConfig?.isEnabled
		) {
			updateClient( 'isEnabled', false );

			createErrorNotice(
				__(
					'The Site Token provider can only be enabled if `Access Control Settings: Block unauthorized domains` is enabled.',
					'wp-graphql-headless-login'
				),
				{
					type: 'snackbar',
					isDismissible: true,
					explicitDismiss: true,
				}
			);
		}
	}, [
		settings,
		activeClient,
		clientConfig,
		updateClient,
		createErrorNotice,
	] );

	const saveRecord = async () => {
		const saved = await saveEditedEntityRecord( 'root', 'site', undefined, {
			[ activeClient ]: clientConfig,
		} );

		if ( saved ) {
			createNotice( 'success', 'Settings saved', {
				type: 'snackbar',
				isDismissible: true,
			} );
		}
	};

	const CustomPanel = (): JSX.Element => {
		return wpGraphQLLogin.hooks.applyFilters(
			'graphql_login_custom_client_settings',
			<></>,
			activeClient,
			clientConfig
		) as JSX.Element;
	};

	if ( ! activeClient || ! clientConfig ) {
		return (
			<Placeholder
				icon={ <Icon icon={ <Logo /> } /> }
				title={ __( 'Loading…', 'wp-graphql-headless-login' ) }
				instructions={ __(
					'Please wait while the settings are loaded.',
					'wp-graphql-headless-login'
				) }
			/>
		);
	}

	return (
		<>
			<PanelBody>
				<PanelRow>
					<h2 className="components-panel__body-title">
						{ sprintf(
							// translators: %s: Client slug.
							__( '%s Settings', 'wp-graphql-headless-login' ),
							( wpGraphQLLogin?.settings?.providers?.[
								providerSlug
							]?.[ 'name' ]?.default as string ) || 'Provider'
						) }
					</h2>
				</PanelRow>
				<Fields
					excludedProperties={ [
						'loginOptions',
						'clientOptions',
						'order',
					] }
					values={ clientConfig }
					fields={
						wpGraphQLLogin?.settings?.providers?.[ providerSlug ] ??
						{}
					}
					setValue={ ( value ) => {
						setClientConfig( {
							...clientConfig,
							...value,
						} );
					} }
				/>
				<ClientOptionList
					clientSlug={ activeClient }
					optionsKey="clientOptions"
					options={ clientConfig?.clientOptions }
					setOption={ setClientOption }
				/>
			</PanelBody>
			<PanelBody>
				<PanelRow>
					<h2 className="components-panel__body-title">
						{ __( 'Login Settings', 'wp-graphql-headless-login' ) }
						<Icon
							icon="admin-users"
							className="components-panel__icon"
							size={ 20 }
						/>
					</h2>
				</PanelRow>

				<ClientOptionList
					clientSlug={ activeClient }
					optionsKey="loginOptions"
					options={ clientConfig?.loginOptions }
					setOption={ setLoginOption }
				/>
			</PanelBody>

			<CustomPanel />

			<Button
				variant="primary"
				onClick={ () => {
					saveRecord();
				} }
				disabled={ ! hasEdits }
				isBusy={ isSaving }
			>
				{ __( 'Save Providers', 'wp-graphql-headless-login' ) }
				{ isSaving && <Spinner /> }
			</Button>
		</>
	);
}
