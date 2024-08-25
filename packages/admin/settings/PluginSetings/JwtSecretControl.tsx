import { useEffect } from '@wordpress/element';
import { BaseControl, Button } from '@wordpress/components';
import { store as coreStore, useEntityProp } from '@wordpress/core-data';
import { useDispatch, dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

export function JwtSecretControl() {
	const [ jwtSecret, setJwtSecret ] = useEntityProp(
		'root',
		'site',
		'wpgraphql_login_settings_jwt_secret_key'
	);
	const { saveEditedEntityRecord } = useDispatch( coreStore );

	const { lastError, isSaving } = useSelect(
		( select ) => ( {
			lastError: select( coreStore )?.getLastEntitySaveError(
				'root',
				'site',
				''
			),
			isSaving: select( coreStore )?.isSavingEntityRecord(
				'root',
				'site',
				''
			),
			hasEdits: select( coreStore )?.hasEditsForEntityRecord(
				'root',
				'site',
				''
			),
		} ),
		[]
	);

	useEffect( () => {
		if ( lastError ) {
			// @ts-expect-error this isnt typed.
			dispatch( 'core/notices' ).createErrorNotice(
				__(
					'The JWT secret could not be regenerated. Please try again later.',
					'wp-graphql-headless-login'
				),
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
		}
	}, [ lastError, jwtSecret ] );

	const regenerateJwtSecret = async () => {
		const saved = await saveEditedEntityRecord( 'root', 'site', undefined, {
			wpgraphql_login_settings_jwt_secret_key: jwtSecret,
		} );

		if ( saved ) {
			// @ts-expect-error this isnt typed.
			dispatch( 'core/notices' ).createNotice(
				'success',
				__(
					'The old JWT secret has been invalidated.',
					'wp-graphql-headless-login'
				),
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
		}
	};

	const secret = wpGraphQLLogin?.secret || {};

	return (
		<>
			<BaseControl
				className="wp-graphql-headless-login__secret"
				id="wp-graphql-headless-login__secret--control"
				help={ __(
					'The JWT Secret is used to sign the JWT tokens that are used to authenticate requests to the GraphQL API. Changing this secret will invalidate all previously-authenticated requests.',
					'wp-graphql-headless-login'
				) }
			>
				<Button
					isTertiary
					text={ __(
						'Regenerate JWT secret',
						'wp-graphql-headless-login'
					) }
					icon="admin-network"
					disabled={ !! secret?.isConstant }
					isDestructive
					isBusy={ isSaving }
					iconSize={ 16 }
					variant="tertiary"
					onClick={ () => {
						// By setting the secret to empty, the server will generate a new one.
						setJwtSecret( '' );
						regenerateJwtSecret();
					} }
				/>
				{ !! secret?.isConstant && (
					<p>
						<strong>
							{ __(
								'The JWT secret is set in wp-config.php and cannot be changed on the backend.',
								'wp-graphql-headless-login'
							) }
						</strong>
					</p>
				) }
			</BaseControl>
		</>
	);
}
