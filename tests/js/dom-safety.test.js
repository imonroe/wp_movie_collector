/**
 * Unit tests for the shared DOM-safety helpers.
 */

import { escHtml, isAllowedImageUrl } from '../../src/utils/dom-safety';

describe( 'escHtml', () => {
	it( 'escapes angle brackets so markup cannot be injected', () => {
		expect( escHtml( '<img src=x onerror=alert(1)>' ) ).toBe(
			'&lt;img src=x onerror=alert(1)&gt;'
		);
	} );

	it( 'escapes ampersands', () => {
		expect( escHtml( 'Tom & Jerry' ) ).toBe( 'Tom &amp; Jerry' );
	} );

	it( 'coerces non-string input to a string', () => {
		expect( escHtml( 42 ) ).toBe( '42' );
	} );

	it( 'returns an empty string for an empty input', () => {
		expect( escHtml( '' ) ).toBe( '' );
	} );
} );

describe( 'isAllowedImageUrl', () => {
	it( 'accepts http and https URLs', () => {
		expect( isAllowedImageUrl( 'http://example.com/a.jpg' ) ).toBe( true );
		expect( isAllowedImageUrl( 'https://example.com/a.jpg' ) ).toBe( true );
		expect( isAllowedImageUrl( 'HTTPS://EXAMPLE.COM/A.JPG' ) ).toBe( true );
	} );

	it( 'rejects javascript: and data: schemes', () => {
		expect( isAllowedImageUrl( 'javascript:alert(1)' ) ).toBe( false );
		expect( isAllowedImageUrl( 'data:text/html,<script>1</script>' ) ).toBe(
			false
		);
	} );

	it( 'rejects relative URLs and protocol-relative URLs', () => {
		expect( isAllowedImageUrl( '/relative/a.jpg' ) ).toBe( false );
		expect( isAllowedImageUrl( '//example.com/a.jpg' ) ).toBe( false );
	} );

	it( 'rejects non-string input', () => {
		expect( isAllowedImageUrl( null ) ).toBe( false );
		expect( isAllowedImageUrl( undefined ) ).toBe( false );
		expect( isAllowedImageUrl( 123 ) ).toBe( false );
	} );
} );
