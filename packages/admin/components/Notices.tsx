import { useDispatch, useSelect } from '@wordpress/data';
import { store } from '@wordpress/notices';
import { SnackbarList } from '@wordpress/components';

function Notices(): JSX.Element {
	const notices = useSelect(
		(select) =>
			select(store)
				.getNotices()
				.filter((notice) => notice.type === 'snackbar'),
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
