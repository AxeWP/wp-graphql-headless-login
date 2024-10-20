<?php //phpcs:ignoreFile

// Fixes compatibility with WP6.0
if ( ! isset( $GLOBALS['wp_filters'] ) ) {
	$GLOBALS['wp_filters'] = [];
}
