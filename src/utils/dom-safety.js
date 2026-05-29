/**
 * Small DOM-safety helper — the canonical, unit-tested escHtml implementation.
 *
 * The runtime scripts (`admin/js/wp-movie-collector-admin.js` and
 * `public/js/wp-movie-collector-public.js`) are enqueued directly and do not
 * import this module. The inline `wpMovieCollectorEscHtml` registered via
 * `wp_add_inline_script` in `WP_Movie_Collector_Admin::enqueue_scripts()`
 * (admin-only — the public script does not currently call it) mirrors this
 * implementation 1:1, and is what callers actually invoke. This module
 * exists so the algorithm can be exercised by the Jest suite
 * (`tests/js/dom-safety.test.js`); keep the two in sync when changes are
 * needed.
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
