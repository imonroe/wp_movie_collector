/**
 * Admin entry point for WP Movie Collector
 *
 * Webpack entry that imports admin JS and CSS for bundling/minification.
 */

import '../../../admin/css/wp-movie-collector-admin.css';
import { escHtml } from '../../utils/dom-safety';
import '../../../admin/js/wp-movie-collector-admin';

// Expose the unit-tested escHtml helper on the window. The inline
// wp_add_inline_script definition remains as a fallback for the unbuilt
// source path; when this bundle is loaded it provides the canonical,
// tested implementation.
if ( typeof window !== 'undefined' ) {
	window.wpMovieCollectorEscHtml = escHtml;
}
