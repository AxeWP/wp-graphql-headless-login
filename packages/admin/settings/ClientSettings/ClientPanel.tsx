import { useEffect } from '@wordpress/element';
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
import { useDispatch, dispatch, useSelect } from '@wordpress/data';
import { ClientOptionList } from './ClientOptionList';
import { OptionList } from '../../components';
import { useClientContext } from '../../contexts/ClientProvider';
import { useAppContext } from '../../contexts/AppProvider';
import { ReactComponent as Logo } from '../../assets/logo.svg';

export function ClientPanel() {
	const { accessControlSettings } = useAppContext();
	const {
		activeClient,
		clientConfig,
		setClientConfig,
		updateClient,
		setClientOption,
		setLoginOption,
	} = useClientContext();
	const { saveEditedEntityRecord } = useDispatch( coreStore );

	const { lastError, isSaving, hasEdits } = useSelect(
		( select ) => ( {
			// @ts-expect-error this isnt typed.
			lastError: select( coreStore )?.getLastEntitySaveError(
				'root',
				'site'
			),
			// @ts-expect-error this isnt typed.
			isSaving: select( coreStore )?.isSavingEntityRecord(
				'root',
				'site'
			),
			// @ts-expect-error this isnt typed.
			hasEdits: select( coreStore )?.hasEditsForEntityRecord(
				'root',
				'site'
			),
		} ),
		[]
	);

	useEffect( () => {
		if ( lastError ) {
			// @ts-expect-error this isnt typed.
			dispatch( 'core/notices' ).createErrorNotice(
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
	}, [ lastError, activeClient ] );

	// Disable siteToken if shouldBlockUnauthorizedDomains is false
	useEffect( () => {
		if (
			! accessControlSettings?.shouldBlockUnauthorizedDomains &&
			activeClient === 'wpgraphql_login_provider_siteToken' &&
			clientConfig?.isEnabled
		) {
			updateClient( 'isEnabled', false );

			// @ts-expect-error this isnt typed.
			dispatch( 'core/notices' ).createErrorNotice(
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
	}, [ accessControlSettings, activeClient, clientConfig, updateClient ] );

	const saveRecord = async () => {
		const saved = await saveEditedEntityRecord( 'root', 'site', undefined, {
			[ activeClient ]: clientConfig,
		} );

		if ( saved ) {
			// @ts-expect-error this isnt typed.
			dispatch( 'core/notices' ).createNotice(
				'success',
				'Settings saved',
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
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
							wpGraphQLLogin?.settings?.providers?.[
								activeClient
							]?.name?.default || 'Provider'
						) }
					</h2>
				</PanelRow>
				<OptionList
					excludedProperties={ [
						'loginOptions',
						'clientOptions',
						'order',
					] }
					options={ clientConfig }
					optionsSchema={
						wpGraphQLLogin?.settings?.providers?.[ activeClient ]
					}
					setOption={ ( value ) => {
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
				isPrimary
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
