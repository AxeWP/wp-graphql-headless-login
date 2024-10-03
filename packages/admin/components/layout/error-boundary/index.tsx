import { Component, ErrorInfo } from 'react';

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

	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	static getDerivedStateFromError( _error: Error ) {
		return { hasError: true };
	}

	componentDidCatch( error: Error, errorInfo: ErrorInfo ) {
		// eslint-disable-next-line no-console
		console.error( 'Uncaught error:', error, errorInfo );
		this.setState( { error, errorInfo } );
	}

	render() {
		if ( ! this.state.hasError ) {
			return this.props.children;
		}

		return (
			this.props.fallback || (
				<div>
					<h1>Something went wrong.</h1>
					{ this.props.showErrorInfo && this.state.errorInfo && (
						<details style={ { whiteSpace: 'pre-wrap' } }>
							{ this.state.error && this.state.error.toString() }
							<br />
							{ this.state.errorInfo.componentStack }
						</details>
					) }
				</div>
			)
		);
	}
}
