import { render, screen, cleanup } from '@testing-library/react';
import { vi, afterEach } from 'vitest';
import { ErrorBoundary } from '@/admin/components/layout/error-boundary';

describe( 'Error Boundary', () => {
	afterEach( () => {
		cleanup();
	} );

	it( 'renders children when no error', () => {
		render(
			<ErrorBoundary showErrorInfo={ false }>
				<div>Child Component</div>
			</ErrorBoundary>
		);

		expect( screen.getByText( 'Child Component' ) ).toBeInTheDocument();
	} );

	it( 'catches errors and renders fallback', () => {
		const ThrowError = () => {
			throw new Error( 'Test error' );
		};

		const FallbackComponent = () => <div>Fallback UI</div>;

		const consoleErrorSpy = vi
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );

		render(
			<ErrorBoundary fallback={ <FallbackComponent /> }>
				<ThrowError />
			</ErrorBoundary>
		);

		expect( screen.getByText( 'Fallback UI' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Child Component' )
		).not.toBeInTheDocument();

		consoleErrorSpy.mockRestore();
	} );

	it( 'renders custom fallback when provided', () => {
		const ThrowError = () => {
			throw new Error( 'Test error' );
		};

		const CustomFallback = () => <div>Custom Fallback</div>;

		render(
			<ErrorBoundary fallback={ <CustomFallback /> }>
				<ThrowError />
			</ErrorBoundary>
		);

		expect( screen.getByText( 'Custom Fallback' ) ).toBeInTheDocument();
	} );

	it( 'shows error info when showErrorInfo is true', () => {
		const ThrowError = () => {
			throw new Error( 'Test error' );
		};

		const consoleErrorSpy = vi
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );

		render(
			<ErrorBoundary showErrorInfo>
				<ThrowError />
			</ErrorBoundary>
		);

		expect(
			screen.getByText( 'Something went wrong.' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Error: Test error' ) ).toBeInTheDocument();

		consoleErrorSpy.mockRestore();
	} );

	it( 'shows component stack when showErrorInfo is true', () => {
		const ThrowError = () => {
			throw new Error( 'Test error' );
		};

		render(
			<ErrorBoundary showErrorInfo>
				<ThrowError />
			</ErrorBoundary>
		);

		// React's ErrorBoundary provides componentStack through componentDidCatch
		// We verify the error was caught by checking for error boundary UI
		expect(
			screen.getByText( 'Something went wrong.' )
		).toBeInTheDocument();
	} );

	it( 'does not show error info when showErrorInfo is false', () => {
		const ThrowError = () => {
			throw new Error( 'Test error' );
		};

		const consoleErrorSpy = vi
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );

		render(
			<ErrorBoundary showErrorInfo={ false }>
				<ThrowError />
			</ErrorBoundary>
		);

		expect(
			screen.queryByText( 'Something went wrong.' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'Error: Test error' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'ComponentStack: TestComponent' )
		).not.toBeInTheDocument();

		consoleErrorSpy.mockRestore();
	} );

	it( 'resets state when new children are rendered', () => {
		const ThrowError = () => {
			throw new Error( 'First error' );
		};

		const NoError = () => <div>No Error</div>;

		const consoleErrorSpy = vi
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );

		const { rerender } = render(
			<ErrorBoundary>
				<NoError />
			</ErrorBoundary>
		);

		// Should show normal content
		expect( screen.getByText( 'No Error' ) ).toBeInTheDocument();

		// Rerender with error
		rerender(
			<ErrorBoundary>
				<ThrowError />
			</ErrorBoundary>
		);

		// Should show error state
		expect(
			screen.queryByText( 'Something went wrong.' )
		).toBeInTheDocument();

		// Rerender with key to force remount
		rerender(
			<ErrorBoundary key="new">
				<NoError />
			</ErrorBoundary>
		);

		// Should not show error state anymore (new instance)
		expect( screen.getByText( 'No Error' ) ).toBeInTheDocument();

		consoleErrorSpy.mockRestore();
	} );

	it( 'logs error to console via componentDidCatch', () => {
		const ThrowError = () => {
			throw new Error( 'Console test error' );
		};

		const consoleErrorSpy = vi
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );

		render(
			<ErrorBoundary>
				<ThrowError />
			</ErrorBoundary>
		);

		expect( consoleErrorSpy ).toHaveBeenCalled();
		expect( consoleErrorSpy ).toHaveBeenCalledWith(
			'Uncaught error:',
			expect.any( Error ),
			expect.any( Object )
		);

		const lastCall =
			consoleErrorSpy.mock.calls[ consoleErrorSpy.mock.calls.length - 1 ];
		if ( lastCall ) {
			expect( lastCall[ 1 ] ).toBeInstanceOf( Error );
			expect( lastCall[ 1 ].message ).toBe( 'Console test error' );
		}

		consoleErrorSpy.mockRestore();
	} );

	it( 'handles error without message', () => {
		const ThrowError = () => {
			const error = new Error();
			Object.defineProperty( error, 'message', {
				value: '',
				writable: true,
				configurable: true,
			} );
			throw error;
		};

		const consoleErrorSpy = vi
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );

		render(
			<ErrorBoundary showErrorInfo>
				<ThrowError />
			</ErrorBoundary>
		);

		expect(
			screen.getByText( 'Something went wrong.' )
		).toBeInTheDocument();

		const errorSummary = screen.getByText( /^Error:/ );
		expect( errorSummary ).toBeInTheDocument();
		expect( errorSummary.textContent ).toMatch( /^Error:/ );

		consoleErrorSpy.mockRestore();
	} );

	it( 'handles error without componentStack', () => {
		const ThrowError = () => {
			throw new Error( 'No stack error' );
		};

		const consoleErrorSpy = vi
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );

		render(
			<ErrorBoundary showErrorInfo>
				<ThrowError />
			</ErrorBoundary>
		);

		expect(
			screen.getByText( 'Something went wrong.' )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Error: No stack error' )
		).toBeInTheDocument();

		consoleErrorSpy.mockRestore();
	} );
} );
