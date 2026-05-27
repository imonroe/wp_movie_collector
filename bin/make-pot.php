<?php
/**
 * Minimal POT generator for the wp-movie-collector text domain.
 *
 * Scans the plugin's PHP files for WordPress translation calls and writes
 * languages/wp-movie-collector.pot. This is a dependency-free fallback for
 * environments without WP-CLI; if WP-CLI is available, prefer:
 *
 *   wp i18n make-pot . languages/wp-movie-collector.pot
 *
 * Usage: php bin/make-pot.php
 */

$root        = dirname( __DIR__ );
$text_domain = 'wp-movie-collector';
$out_file    = $root . '/languages/' . $text_domain . '.pot';

// Directories to scan for source strings.
$scan_dirs = array( 'includes', 'admin', 'public' );
$scan_dirs[] = '.'; // root php files (wp-movie-collector.php, uninstall.php)

// Functions and the argument index of their msgid (1-based), and whether the
// next argument is a plural form (for _n / _nx).
$functions = array(
	'__'             => array( 'msgid' => 1 ),
	'_e'             => array( 'msgid' => 1 ),
	'esc_html__'     => array( 'msgid' => 1 ),
	'esc_html_e'     => array( 'msgid' => 1 ),
	'esc_attr__'     => array( 'msgid' => 1 ),
	'esc_attr_e'     => array( 'msgid' => 1 ),
	'_x'             => array( 'msgid' => 1, 'context' => 2 ),
	'_ex'            => array( 'msgid' => 1, 'context' => 2 ),
	'esc_html_x'     => array( 'msgid' => 1, 'context' => 2 ),
	'esc_attr_x'     => array( 'msgid' => 1, 'context' => 2 ),
	'_n'             => array( 'msgid' => 1, 'plural' => 2 ),
	'_nx'            => array( 'msgid' => 1, 'plural' => 2, 'context' => 3 ),
);

/**
 * Recursively collect PHP files, skipping vendor/node_modules/tests.
 */
function collect_php_files( $dir ) {
	$skip = array( 'vendor', 'node_modules', 'tests', '.git', 'dist', 'build' );
	$files = array();
	if ( ! is_dir( $dir ) ) {
		return $files;
	}
	$it = new RecursiveIteratorIterator(
		new RecursiveCallbackFilterIterator(
			new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
			function ( $current ) use ( $skip ) {
				if ( $current->isDir() && in_array( $current->getFilename(), $skip, true ) ) {
					return false;
				}
				return true;
			}
		)
	);
	foreach ( $it as $file ) {
		if ( $file->isFile() && 'php' === strtolower( $file->getExtension() ) ) {
			$files[] = $file->getPathname();
		}
	}
	return $files;
}

/**
 * Extract translation entries from a token stream.
 * Returns array keyed by "context\x04msgid" => entry.
 */
function extract_from_file( $path, $functions, $text_domain, &$entries ) {
	$code   = file_get_contents( $path );
	$tokens = token_get_all( $code );
	$count  = count( $tokens );

	for ( $i = 0; $i < $count; $i++ ) {
		$tok = $tokens[ $i ];
		if ( ! is_array( $tok ) || T_STRING !== $tok[0] ) {
			continue;
		}
		$fn = $tok[1];
		if ( ! isset( $functions[ $fn ] ) ) {
			continue;
		}
		// Find the opening parenthesis.
		$j = $i + 1;
		while ( $j < $count && is_array( $tokens[ $j ] ) && in_array( $tokens[ $j ][0], array( T_WHITESPACE ), true ) ) {
			$j++;
		}
		if ( $j >= $count || '(' !== $tokens[ $j ] ) {
			continue;
		}
		// Collect string-literal arguments in order (only top-level, simple strings).
		$args  = array();
		$depth = 0;
		$line  = is_array( $tok ) ? $tok[2] : 0;
		for ( $k = $j; $k < $count; $k++ ) {
			$t = $tokens[ $k ];
			if ( '(' === $t ) {
				$depth++;
				continue;
			}
			if ( ')' === $t ) {
				$depth--;
				if ( 0 === $depth ) {
					break;
				}
				continue;
			}
			if ( 1 === $depth && is_array( $t ) && T_CONSTANT_ENCAPSED_STRING === $t[0] ) {
				$args[] = unquote_php_string( $t[1] );
			} elseif ( 1 === $depth && ',' === $t ) {
				// Mark argument boundaries; we rely on positional capture below.
				$args[] = "\x00SEP\x00";
			}
		}

		// Rebuild ordered argument list (string or null) split on separators.
		$ordered = array();
		$current = null;
		foreach ( $args as $a ) {
			if ( "\x00SEP\x00" === $a ) {
				$ordered[] = $current;
				$current   = null;
			} elseif ( null === $current ) {
				$current = $a;
			}
		}
		$ordered[] = $current;

		$spec  = $functions[ $fn ];
		$msgid = isset( $spec['msgid'] ) && isset( $ordered[ $spec['msgid'] - 1 ] ) ? $ordered[ $spec['msgid'] - 1 ] : null;
		if ( null === $msgid || '' === $msgid ) {
			continue;
		}
		$context = ( isset( $spec['context'] ) && isset( $ordered[ $spec['context'] - 1 ] ) ) ? $ordered[ $spec['context'] - 1 ] : null;
		$plural  = ( isset( $spec['plural'] ) && isset( $ordered[ $spec['plural'] - 1 ] ) ) ? $ordered[ $spec['plural'] - 1 ] : null;

		$key = ( null !== $context ? $context : '' ) . "\x04" . $msgid;
		if ( ! isset( $entries[ $key ] ) ) {
			$entries[ $key ] = array(
				'msgid'   => $msgid,
				'plural'  => $plural,
				'context' => $context,
				'refs'    => array(),
			);
		}
		if ( null !== $plural && null === $entries[ $key ]['plural'] ) {
			$entries[ $key ]['plural'] = $plural;
		}
		$entries[ $key ]['refs'][] = $path . ':' . $line;
	}
}

/**
 * Convert a PHP single/double quoted literal (with surrounding quotes) to its value.
 */
function unquote_php_string( $raw ) {
	$quote = $raw[0];
	$inner = substr( $raw, 1, -1 );
	if ( "'" === $quote ) {
		return str_replace( array( "\\'", '\\\\' ), array( "'", '\\' ), $inner );
	}
	// Double quotes: handle common escapes.
	return strtr(
		$inner,
		array(
			'\\"'  => '"',
			'\\\\' => '\\',
			'\\n'  => "\n",
			'\\t'  => "\t",
			'\\r'  => "\r",
		)
	);
}

/**
 * Escape a string for the POT format.
 */
function pot_escape( $str ) {
	return str_replace(
		array( '\\', '"', "\n", "\t" ),
		array( '\\\\', '\\"', '\\n', '\\t' ),
		$str
	);
}

$entries = array();
foreach ( $scan_dirs as $dir ) {
	$target = ( '.' === $dir ) ? $root : $root . '/' . $dir;
	if ( '.' === $dir ) {
		// Only top-level php files for root, not recursive (handled by subdirs).
		foreach ( glob( $root . '/*.php' ) as $f ) {
			extract_from_file( $f, $functions, $text_domain, $entries );
		}
		continue;
	}
	foreach ( collect_php_files( $target ) as $f ) {
		extract_from_file( $f, $functions, $text_domain, $entries );
	}
}

ksort( $entries );

$root_len = strlen( $root ) + 1;
$now      = gmdate( 'Y-m-d H:iO' );

$pot  = "# Copyright (C) " . gmdate( 'Y' ) . "\n";
$pot .= "# This file is distributed under the GPL-2.0+ license.\n";
$pot .= "msgid \"\"\n";
$pot .= "msgstr \"\"\n";
$pot .= "\"Project-Id-Version: WP Movie Collector\\n\"\n";
$pot .= "\"Report-Msgid-Bugs-To: https://github.com/imonroe/wp_movie_collector/issues\\n\"\n";
$pot .= "\"POT-Creation-Date: {$now}\\n\"\n";
$pot .= "\"MIME-Version: 1.0\\n\"\n";
$pot .= "\"Content-Type: text/plain; charset=UTF-8\\n\"\n";
$pot .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
$pot .= "\"Language-Team: \\n\"\n";
$pot .= "\"X-Domain: {$text_domain}\\n\"\n";
$pot .= "\n";

foreach ( $entries as $entry ) {
	$refs = array_map(
		function ( $r ) use ( $root_len ) {
			return substr( $r, $root_len );
		},
		array_unique( $entry['refs'] )
	);
	$pot .= '#: ' . implode( ' ', $refs ) . "\n";
	if ( null !== $entry['context'] ) {
		$pot .= 'msgctxt "' . pot_escape( $entry['context'] ) . "\"\n";
	}
	$pot .= 'msgid "' . pot_escape( $entry['msgid'] ) . "\"\n";
	if ( null !== $entry['plural'] ) {
		$pot .= 'msgid_plural "' . pot_escape( $entry['plural'] ) . "\"\n";
		$pot .= "msgstr[0] \"\"\n";
		$pot .= "msgstr[1] \"\"\n";
	} else {
		$pot .= "msgstr \"\"\n";
	}
	$pot .= "\n";
}

if ( ! is_dir( dirname( $out_file ) ) ) {
	mkdir( dirname( $out_file ), 0755, true );
}
file_put_contents( $out_file, $pot );

printf( "Wrote %d strings to %s\n", count( $entries ), $out_file );
