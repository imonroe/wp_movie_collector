/**
 * Admin entry point for WP Movie Collector
 *
 * Webpack entry that imports admin JS and CSS for bundling/minification.
 */

import '../../../admin/css/wp-movie-collector-admin.css';
import { escHtml, isAllowedImageUrl } from '../../utils/dom-safety';
import '../../../admin/js/wp-movie-collector-admin';

// Expose the unit-tested helpers on the window. The inline
// wp_add_inline_script definitions remain as a fallback for the unbuilt
// source path; when this bundle is loaded it provides the canonical,
// tested implementations.
if ( typeof window !== 'undefined' ) {
	window.wpMovieCollectorEscHtml = escHtml;
	window.wpMovieCollectorIsAllowedImageUrl = isAllowedImageUrl;
}
