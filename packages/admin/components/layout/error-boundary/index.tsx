import { Component, type ErrorInfo } from 'react';

interface ErrorBoundaryProps {
	children: React.ReactNode;
	fallback?: React.ReactNode;
	showErrorInfo?: boolean;
}

interface ErrorBoundaryState {
	hasError: boolean;
	error?: Error;
	errorInfo?: ErrorInfo;
}

export class ErrorBoundary extends Component<
	ErrorBoundaryProps,
	ErrorBoundaryState
> {
	constructor( props: ErrorBoundaryProps ) {
		super( props );
		this.state = { hasError: false };
	}

	static getDerivedStateFromError( error: Error ) {
		return { hasError: true, error };
	}

	override componentDidCatch( error: Error, errorInfo: ErrorInfo ) {
		// eslint-disable-next-line no-console
		console.error( 'Uncaught error:', error, errorInfo );
		this.setState( { error, errorInfo } );
	}

	override render() {
		if ( ! this.state.hasError ) {
			return this.props.children;
		}

		if ( this.props.fallback ) {
			return this.props.fallback;
		}

		// If showErrorInfo is explicitly false, render nothing
		// This allows complete suppression of error UI
		if ( this.props.showErrorInfo === false ) {
			return null;
		}

		return (
			<div>
				<h1>Something went wrong.</h1>
				{ this.state.error && (
					<details open style={ { whiteSpace: 'pre-wrap' } }>
						<summary>Error: { this.state.error.message }</summary>
						{ this.state.errorInfo?.componentStack }
					</details>
				) }
			</div>
		);
	}
}
