<?php
/**
 * Hooks for the Advanced Search Module.
 *
 * This file registers all the actions and filters required
 * for the module to function.
 *
 * @package AdvancedSearchModule
 * @author hussainas
 */

// Prevent direct script access.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed' );
}

/**
 * Hooks the main query modifier function into 'pre_get_posts'.
 *
 * This is the entry point for modifying the search query.
 * Priority 10 is standard.
 */
add_action( 'pre_get_posts', 'hussainas_search_query_modifier', 10 );
