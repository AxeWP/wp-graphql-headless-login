import { useEffect } from 'react';
import { BaseControl, Button } from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { useSettings } from '@/admin/contexts/settings-context';
import type { FieldSchema } from '@/admin/types';

export function JwtSecretControl( { label, help }: FieldSchema ) {
	const { updateSettings, saveSettings, errorMessage, isSaving } =
		useSettings();

	useEffect( () => {
		if ( ! isSaving && errorMessage ) {
			// @ts-expect-error this isnt typed.
			dispatch( 'core/notices' ).createErrorNotice(
				sprintf(
					// translators: %s: error message
					__(
						'The JWT secret could not be regenerated. Please try again later. Error: %s',
						'wp-graphql-headless-login'
					),
					errorMessage
				),
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
		}
	}, [ isSaving, errorMessage ] );

	const regenerateJwtSecret = async () => {
		await updateSettings( {
			slug: 'wpgraphql_login_settings',
			values: {
				jwt_secret_key: '',
			},
		} );

		const success = await saveSettings( 'wpgraphql_login_settings' );

		if ( success ) {
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
				help={ help }
			>
				<Button
					text={ label }
					icon="admin-network"
					disabled={ !! secret?.isConstant }
					isDestructive
					isBusy={ isSaving }
					iconSize={ 16 }
					variant="secondary"
					onClick={ () => {
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
