import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { __, sprintf } from '@wordpress/i18n';
import { useEffect } from 'react';
import { Button, Notice, PanelBody, Spinner } from '@wordpress/components';
import { Fields } from '@/admin/components/fields';
import { useSettings } from '@/admin/contexts/settings-context';

export const SettingsScreen = ({ settingKey }: { settingKey: string }) => {
	const {
		settings,
		updateSettings,
		saveSettings,
		isComplete,
		isSaving,
		isDirty,
		errorMessage,
		isConditionMet,
		getUnmetCondition,
	} = useSettings();

	const { createNotice, createErrorNotice } = useDispatch(noticesStore);

	const optionsSchema =
		wpGraphQLLogin?.settings?.[settingKey]?.fields || undefined;

	const localValues = settings?.[settingKey] || {};

	useEffect(() => {
		if (errorMessage && !isSaving) {
			createErrorNotice(
				sprintf(
					// translators: %s: Error message.
					__(
						'Error saving settings: %s',
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
	}, [errorMessage, isSaving, createErrorNotice]);

	const save = async () => {
		// Prevent multiple save requests
		if (isSaving) {
			return;
		}

		await saveSettings(settingKey);

		if (isComplete && !errorMessage) {
			createNotice(
				'success',
				__('Settings saved', 'wp-graphql-headless-login'),
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
		}
	};

	const setValue = (value: Record<string, unknown>) => {
		updateSettings({
			slug: settingKey,
			values: value,
		});
	};

	const validateConditionalLogic = (field: string) => {
		return isConditionMet({
			settingKey,
			field,
		});
	};

	if (!settings || !optionsSchema) {
		return null;
	}

	// When conditional logic hides every field, tell the user what unlocks them.
	const shownFields = Object.keys(optionsSchema).filter(
		(field) => !optionsSchema[field]?.hidden
	);
	const blocker =
		shownFields.length > 0 && !shownFields.some(validateConditionalLogic)
			? getUnmetCondition({ settingKey, field: shownFields[0]! })
			: undefined;
	if (blocker) {
		const blockerSetting = wpGraphQLLogin?.settings?.[blocker.settingKey];
		const blockerLabel = blockerSetting?.fields?.[blocker.field]?.label;

		if (!blockerLabel) {
			return null;
		}

		return (
			<PanelBody>
				<Notice status="info" isDismissible={false}>
					{blocker.settingKey === settingKey || !blockerSetting?.label
						? sprintf(
								// translators: %s: Label of the setting that unlocks this screen.
								__(
									'Nothing to configure yet — these settings unlock once “%s” is enabled.',
									'wp-graphql-headless-login'
								),
								blockerLabel
							)
						: sprintf(
								// translators: %1$s: Label of the setting that unlocks this screen. %2$s: Label of the screen it lives on.
								__(
									'Nothing to configure yet — these settings unlock once “%1$s” is enabled under %2$s.',
									'wp-graphql-headless-login'
								),
								blockerLabel,
								blockerSetting.label
							)}
				</Notice>
			</PanelBody>
		);
	}

	return (
		<>
			<PanelBody>
				<Fields
					fields={optionsSchema}
					values={localValues}
					setValue={setValue}
					validateConditionalLogic={validateConditionalLogic}
				/>
			</PanelBody>
			<Button
				isBusy={isSaving}
				onClick={save}
				disabled={!isDirty || isSaving}
				variant="primary"
			>
				{__('Save', 'wp-graphql-headless-login')}
				{isSaving && <Spinner />}
			</Button>
		</>
	);
};
