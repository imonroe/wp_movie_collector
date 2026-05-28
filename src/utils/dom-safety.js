/**
 * Small DOM-safety helper shared by the admin scripts.
 *
 * Mirrors the inline helper registered via wp_add_inline_script (which remains
 * as a fallback for the unbuilt source path); when the webpack bundle is loaded
 * this exported, unit-tested implementation is exposed on the window instead.
 */

/**
 * Escape a value for safe insertion as HTML — text or attribute context.
 *
 * Encodes &, <, >, " and ' so the result is safe both as element text and
 * when concatenated into a double/single-quoted attribute value (preventing
 * attribute breakout).
 *
 * @param {*} value The value to escape.
 * @return {string} The HTML-escaped string.
 */
export function escHtml( value ) {
	return String( value )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' )
		.replace( /'/g, '&#039;' );
}
