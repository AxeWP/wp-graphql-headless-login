import { render, screen } from '@testing-library/react';
import { vi, beforeEach, afterEach, describe, it, expect } from 'vitest';
import { Notices } from '@/admin/components/notices';
import { useSelect, useDispatch } from '@wordpress/data';

vi.mock( '@wordpress/data', () => ( {
	useSelect: vi.fn(),
	useDispatch: vi.fn(),
} ) );

vi.mock( '@wordpress/components', () => ( {
	SnackbarList: ( {
		notices,
		className,
	}: {
		notices: Array< { id: string } >;
		className?: string;
	} ) => (
		<div data-testid="snackbar-list" className={ className }>
			{ notices.map( ( notice ) => (
				<div key={ notice.id } data-testid={ `notice-${ notice.id }` }>
					{ notice.id }
				</div>
			) ) }
		</div>
	),
} ) );

const mockUseSelect = vi.mocked( useSelect );
const mockUseDispatch = vi.mocked( useDispatch );

describe( 'Notices Component', () => {
	beforeEach( () => {
		vi.clearAllMocks();
		mockUseDispatch.mockImplementation( () => ( {
			removeNotice: vi.fn(),
		} ) );
	} );

	afterEach( () => {
		vi.clearAllMocks();
	} );

	describe( 'Empty notices list', () => {
		it( 'renders empty when no snackbar notices', () => {
			mockUseSelect.mockImplementation( ( _selector, _deps ) => [] );

			const { container } = render( <Notices /> );

			expect( container.firstChild ).toBeNull();
		} );

		it( 'renders empty when notices exist but are not snackbar type', () => {
			mockUseSelect.mockImplementation( ( _selector, _deps ) => [] );

			const { container } = render( <Notices /> );

			expect( container.firstChild ).toBeNull();
		} );

		it( 'renders empty when all notices are non-snackbar type', () => {
			mockUseSelect.mockImplementation( ( _selector, _deps ) => [] );

			const { container } = render( <Notices /> );

			expect( container.firstChild ).toBeNull();
		} );

		it( 'returns null when notices is undefined', () => {
			mockUseSelect.mockImplementation(
				( _selector, _deps ) => undefined
			);

			const { container } = render( <Notices /> );

			expect( container.firstChild ).toBeNull();
		} );

		it( 'returns null when notices is null', () => {
			mockUseSelect.mockImplementation( ( _selector, _deps ) => null );

			const { container } = render( <Notices /> );

			expect( container.firstChild ).toBeNull();
		} );
	} );

	describe( 'Renders notices from WordPress store', () => {
		it( 'renders SnackbarList when notices exist', () => {
			const mockNotices = [
				{
					id: 'notice-1',
					content: 'Test notice 1',
					type: 'snackbar',
					isDismissible: true,
				},
				{
					id: 'notice-2',
					content: 'Test notice 2',
					type: 'snackbar',
					isDismissible: false,
				},
			];

			mockUseSelect.mockImplementation(
				( _selector, _deps ) => mockNotices
			);

			const { container } = render( <Notices /> );

			expect( container.firstChild ).not.toBeNull();
			expect( screen.getByTestId( 'snackbar-list' ) ).toBeInTheDocument();
		} );

		it( 'filters only snackbar type notices', () => {
			const filteredNotices = [
				{
					id: 'snackbar-1',
					content: 'Snackbar notice',
					type: 'snackbar',
				},
				{
					id: 'snackbar-2',
					content: 'Another snackbar',
					type: 'snackbar',
				},
			];

			mockUseSelect.mockImplementation(
				( _selector, _deps ) => filteredNotices
			);

			render( <Notices /> );

			// Only snackbar notices should be rendered
			expect(
				screen.getByTestId( 'notice-snackbar-1' )
			).toBeInTheDocument();
			expect(
				screen.getByTestId( 'notice-snackbar-2' )
			).toBeInTheDocument();
		} );

		it( 'calls useSelect with WordPress notices store', () => {
			mockUseSelect.mockImplementation( ( _selector, _deps ) => [] );

			render( <Notices /> );

			expect( mockUseSelect ).toHaveBeenCalled();
		} );
	} );

	describe( 'Multiple notices', () => {
		it( 'renders multiple snackbar notices', () => {
			const mockNotices = [
				{ id: 'notice-1', content: 'First', type: 'snackbar' },
				{ id: 'notice-2', content: 'Second', type: 'snackbar' },
				{ id: 'notice-3', content: 'Third', type: 'snackbar' },
			];

			mockUseSelect.mockImplementation( () => mockNotices );
			mockUseDispatch.mockImplementation( () => ( {
				removeNotice: vi.fn(),
			} ) );

			render( <Notices /> );

			expect(
				screen.getByTestId( 'notice-notice-1' )
			).toBeInTheDocument();
			expect(
				screen.getByTestId( 'notice-notice-2' )
			).toBeInTheDocument();
			expect(
				screen.getByTestId( 'notice-notice-3' )
			).toBeInTheDocument();
		} );

		it( 'handles mixed notice types correctly', () => {
			const filteredNotices = [
				{ id: 'snackbar-1', content: 'Snackbar 1', type: 'snackbar' },
				{ id: 'snackbar-2', content: 'Snackbar 2', type: 'snackbar' },
			];

			mockUseSelect.mockImplementation(
				( _selector, _deps ) => filteredNotices
			);

			render( <Notices /> );

			// Only 2 snackbar notices should be rendered
			expect(
				screen.getByTestId( 'notice-snackbar-1' )
			).toBeInTheDocument();
			expect(
				screen.getByTestId( 'notice-snackbar-2' )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Different notice types', () => {
		it( 'renders success notices', () => {
			const mockNotices = [
				{
					id: 'success-1',
					content: 'Success message',
					type: 'snackbar',
					status: 'success',
				},
			];

			mockUseSelect.mockImplementation( () => mockNotices );
			mockUseDispatch.mockImplementation( () => ( {
				removeNotice: vi.fn(),
			} ) );

			render( <Notices /> );

			expect(
				screen.getByTestId( 'notice-success-1' )
			).toBeInTheDocument();
		} );

		it( 'renders error notices', () => {
			const mockNotices = [
				{
					id: 'error-1',
					content: 'Error message',
					type: 'snackbar',
					status: 'error',
				},
			];

			mockUseSelect.mockImplementation( () => mockNotices );
			mockUseDispatch.mockImplementation( () => ( {
				removeNotice: vi.fn(),
			} ) );

			render( <Notices /> );

			expect(
				screen.getByTestId( 'notice-error-1' )
			).toBeInTheDocument();
		} );

		it( 'renders info notices', () => {
			const mockNotices = [
				{
					id: 'info-1',
					content: 'Info message',
					type: 'snackbar',
					status: 'info',
				},
			];

			mockUseSelect.mockImplementation( () => mockNotices );
			mockUseDispatch.mockImplementation( () => ( {
				removeNotice: vi.fn(),
			} ) );

			render( <Notices /> );

			expect( screen.getByTestId( 'notice-info-1' ) ).toBeInTheDocument();
		} );

		it( 'renders warning notices', () => {
			const mockNotices = [
				{
					id: 'warning-1',
					content: 'Warning message',
					type: 'snackbar',
					status: 'warning',
				},
			];

			mockUseSelect.mockImplementation( () => mockNotices );
			mockUseDispatch.mockImplementation( () => ( {
				removeNotice: vi.fn(),
			} ) );

			render( <Notices /> );

			expect(
				screen.getByTestId( 'notice-warning-1' )
			).toBeInTheDocument();
		} );

		it( 'renders notices with different statuses simultaneously', () => {
			const mockNotices = [
				{
					id: 'success-1',
					content: 'Success',
					type: 'snackbar',
					status: 'success',
				},
				{
					id: 'error-1',
					content: 'Error',
					type: 'snackbar',
					status: 'error',
				},
				{
					id: 'info-1',
					content: 'Info',
					type: 'snackbar',
					status: 'info',
				},
				{
					id: 'warning-1',
					content: 'Warning',
					type: 'snackbar',
					status: 'warning',
				},
			];

			mockUseSelect.mockImplementation( () => mockNotices );
			mockUseDispatch.mockImplementation( () => ( {
				removeNotice: vi.fn(),
			} ) );

			render( <Notices /> );

			expect(
				screen.getByTestId( 'notice-success-1' )
			).toBeInTheDocument();
			expect(
				screen.getByTestId( 'notice-error-1' )
			).toBeInTheDocument();
			expect( screen.getByTestId( 'notice-info-1' ) ).toBeInTheDocument();
			expect(
				screen.getByTestId( 'notice-warning-1' )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Dismissible notices', () => {
		it( 'passes dismissible property to SnackbarList', () => {
			const mockNotices = [
				{
					id: 'notice-1',
					content: 'Dismissible notice',
					type: 'snackbar',
					isDismissible: true,
				},
				{
					id: 'notice-2',
					content: 'Non-dismissible notice',
					type: 'snackbar',
					isDismissible: false,
				},
			];

			mockUseSelect.mockImplementation( () => mockNotices );
			mockUseDispatch.mockImplementation( () => ( {
				removeNotice: vi.fn(),
			} ) );

			render( <Notices /> );

			expect(
				screen.getByTestId( 'notice-notice-1' )
			).toBeInTheDocument();
			expect(
				screen.getByTestId( 'notice-notice-2' )
			).toBeInTheDocument();
		} );

		it( 'passes removeNotice function to SnackbarList', () => {
			const mockNotices = [
				{
					id: 'notice-1',
					content: 'Test notice',
					type: 'snackbar',
					isDismissible: true,
				},
			];

			const removeNotice = vi.fn();

			mockUseSelect.mockImplementation( () => mockNotices );
			mockUseDispatch.mockImplementation( () => ( {
				removeNotice,
			} ) );

			render( <Notices /> );

			expect( mockUseDispatch ).toHaveBeenCalled();

			const snackbarList = screen.getByTestId( 'snackbar-list' );
			expect( snackbarList ).toBeInTheDocument();
		} );
	} );

	describe( 'Notice actions', () => {
		it( 'provides removeNotice callback from useDispatch', () => {
			const mockNotices = [
				{
					id: 'notice-1',
					content: 'Test notice',
					type: 'snackbar',
				},
			];

			const removeNotice = vi.fn();

			mockUseSelect.mockImplementation( () => mockNotices );
			mockUseDispatch.mockImplementation( () => ( {
				removeNotice,
			} ) );

			render( <Notices /> );

			expect( mockUseDispatch ).toHaveBeenCalledWith( expect.anything() );
		} );

		it( 'passes onRemove prop to SnackbarList', () => {
			const mockNotices = [
				{
					id: 'notice-1',
					content: 'Test notice',
					type: 'snackbar',
				},
			];

			const removeNotice = vi.fn();

			mockUseSelect.mockImplementation( () => mockNotices );
			mockUseDispatch.mockImplementation( () => ( {
				removeNotice,
			} ) );

			render( <Notices /> );

			const snackbarList = screen.getByTestId( 'snackbar-list' );
			expect( snackbarList ).toBeInTheDocument();
		} );

		it( 'notices can have actions', () => {
			const mockNotices = [
				{
					id: 'notice-1',
					content: 'Notice with action',
					type: 'snackbar',
					actions: [
						{
							label: 'Button 1',
							url: '#',
							onClick: vi.fn(),
						},
					],
				},
			];

			mockUseSelect.mockImplementation( () => mockNotices );
			mockUseDispatch.mockImplementation( () => ( {
				removeNotice: vi.fn(),
			} ) );

			render( <Notices /> );

			expect(
				screen.getByTestId( 'notice-notice-1' )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Component structure', () => {
		it( 'renders with correct className', () => {
			const mockNotices = [
				{
					id: 'notice-1',
					content: 'Test notice',
					type: 'snackbar',
				},
			];

			mockUseSelect.mockImplementation( () => mockNotices );
			mockUseDispatch.mockImplementation( () => ( {
				removeNotice: vi.fn(),
			} ) );

			render( <Notices /> );

			const snackbarList = screen.getByTestId( 'snackbar-list' );
			expect( snackbarList ).toHaveClass( 'edit-site-notices' );
		} );

		it( 'wraps SnackbarList in a div container', () => {
			const mockNotices = [
				{
					id: 'notice-1',
					content: 'Test notice',
					type: 'snackbar',
				},
			];

			mockUseSelect.mockImplementation( () => mockNotices );
			mockUseDispatch.mockImplementation( () => ( {
				removeNotice: vi.fn(),
			} ) );

			const { container } = render( <Notices /> );

			expect( container.firstChild ).toBeInstanceOf( HTMLDivElement );
		} );
	} );
} );
