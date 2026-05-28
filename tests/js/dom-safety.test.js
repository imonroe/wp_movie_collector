/**
 * Unit tests for the shared DOM-safety helper.
 */

import { escHtml } from '../../src/utils/dom-safety';

describe( 'escHtml', () => {
	it( 'escapes angle brackets so markup cannot be injected', () => {
		expect( escHtml( '<img src=x onerror=alert(1)>' ) ).toBe(
			'&lt;img src=x onerror=alert(1)&gt;'
		);
	} );

	it( 'escapes ampersands', () => {
		expect( escHtml( 'Tom & Jerry' ) ).toBe( 'Tom &amp; Jerry' );
	} );

	it( 'escapes double and single quotes for attribute-context safety', () => {
		expect( escHtml( '" onmouseover="alert(1)' ) ).toBe(
			'&quot; onmouseover=&quot;alert(1)'
		);
		expect( escHtml( "it's" ) ).toBe( 'it&#039;s' );
	} );

	it( 'coerces non-string input to a string', () => {
		expect( escHtml( 42 ) ).toBe( '42' );
	} );

	it( 'returns an empty string for an empty input', () => {
		expect( escHtml( '' ) ).toBe( '' );
	} );
} );
