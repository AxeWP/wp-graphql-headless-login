import { useDispatch, useSelect } from '@wordpress/data';
import { store } from '@wordpress/notices';
import { SnackbarList } from '@wordpress/components';
import type { WPNotice } from '@wordpress/notices/build-types/store/selectors';

function Notices(): JSX.Element {
	const notices = useSelect(
		(select) =>
			// @ts-expect-error this isnt typed.
			select(store)
				.getNotices()
				.filter((notice: WPNotice) => notice.type === 'snackbar'),
		[]
	);
	const { removeNotice } = useDispatch(store);
	return (
		<SnackbarList
			className="edit-site-notices"
			notices={notices}
			onRemove={removeNotice}
		/>
	);
}
export default Notices;
