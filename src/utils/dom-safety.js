/**
 * Small DOM-safety helpers shared by the admin scripts.
 *
 * These mirror the inline helpers registered via wp_add_inline_script (which
 * remain as a fallback for the unbuilt source path); when the webpack bundle
 * is loaded these exported, unit-tested implementations are exposed on the
 * window instead.
 */

/**
 * Escape a value for safe insertion as HTML text.
 *
 * @param {*} value The value to escape.
 * @return {string} The HTML-escaped string.
 */
export function escHtml( value ) {
	const div = document.createElement( 'div' );
	div.appendChild( document.createTextNode( String( value ) ) );
	return div.innerHTML;
}

/**
 * Whether a value is a string http(s) URL safe to use as an image src.
 *
 * Rejects non-strings, relative URLs, and dangerous schemes such as
 * javascript: and data:.
 *
 * @param {*} url The candidate URL.
 * @return {boolean} True if the URL is an http(s) URL.
 */
export function isAllowedImageUrl( url ) {
	return typeof url === 'string' && /^https?:\/\//i.test( url );
}
