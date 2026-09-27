import { act, useEffect } from 'react';
import { vi, describe, it, expect } from 'vitest';

const lifecycle = vi.hoisted( () => ( { active: 0 } ) );

vi.mock( '@wordpress/dom-ready', () => ( {
	default: ( callback: () => void ) => callback(),
} ) );

vi.mock( '@/admin/app', () => {
	const MockApp = () => {
		useEffect( () => {
			lifecycle.active++;

			return () => {
				lifecycle.active--;
			};
		}, [] );

		return <p data-testid="settings-app">App</p>;
	};

	return { default: MockApp };
} );

const addContainer = async () => {
	const container = document.createElement( 'div' );
	container.id = 'wp-graphql-headless-login-settings';

	await act( async () => {
		document.body.appendChild( container );
	} );

	return container;
};

describe( 'Admin entrypoint', () => {
	it( 'mounts into containers added after load and unmounts once they are removed', async () => {
		await import( '@/admin/index' );

		expect( lifecycle.active ).toBe( 0 );

		const first = await addContainer();

		expect(
			first.querySelector( '[data-testid="settings-app"]' )
		).not.toBeNull();
		expect( lifecycle.active ).toBe( 1 );

		await act( async () => {
			first.remove();
		} );

		expect( lifecycle.active ).toBe( 0 );

		const second = await addContainer();

		expect(
			second.querySelector( '[data-testid="settings-app"]' )
		).not.toBeNull();
		expect( lifecycle.active ).toBe( 1 );
	} );
} );
