<?php
/**
 * Core functions for the Advanced Search Module.
 *
 * This file contains the helper functions and the main SQL modification logic.
 *
 * @package AdvancedSearchModule
 * @author hussainas
 */

// Prevent direct script access.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed' );
}

/**
 * Retrieves the configurable list of post types to search.
 *
 * @return array A list of post type slugs.
 */
function hussainas_get_searchable_post_types() {
	$post_types = [ 'post', 'page' ]; // Default: search posts and pages.

	/**
	 * Filters the list of post types to include in the advanced search.
	 *
	 * @param array $post_types Array of post type slugs.
	 */
	return apply_filters( 'hussainas_searchable_post_types', $post_types );
}

/**
 * Retrieves the configurable list of meta keys to search.
 *
 * IMPORTANT: For performance, only add meta keys that are necessary
 * and are indexed or have a reasonable number of entries.
 *
 * @return array A list of meta keys.
 */
function hussainas_get_searchable_meta_keys() {
	$meta_keys = [
		// 'example_meta_key_1',
		// 'example_meta_key_2',
		// 'sku',
		// '_another_meta_field',
	];

	/**
	 * Filters the list of custom field meta keys to include in the advanced search.
	 *
	 * @param array $meta_keys Array of meta key strings.
	 */
	return apply_filters( 'hussainas_searchable_meta_keys', $meta_keys );
}

/**
 * Retrieves the list of searchable taxonomies based on post types.
 *
 * @return array A list of taxonomy slugs.
 */
function hussainas_get_searchable_taxonomies() {
	$post_types      = hussainas_get_searchable_post_types();
	$taxonomies      = get_object_taxonomies( $post_types, 'names' );
	$excluded_taxs   = [ 'post_format', 'nav_menu', 'link_category' ];
	$searchable_taxs = [];

	foreach ( $taxonomies as $tax ) {
		if ( ! is_string( $tax ) || in_array( $tax, $excluded_taxs, true ) ) {
			continue;
		}
		$searchable_taxs[] = $tax;
	}

	/**
	 * Filters the list of taxonomies to include in the advanced search.
	 *
	 * @param array $searchable_taxs Array of taxonomy slugs.
	 */
	return apply_filters( 'hussainas_searchable_taxonomies', $searchable_taxs );
}

/**
 * Modifies the JOIN clause of the search query.
 *
 * Joins postmeta and taxonomy tables to make them searchable.
 *
 * @param string $join The original JOIN clause.
 * @return string The modified JOIN clause.
 */
function hussainas_modify_search_join( $join ) {
	global $wpdb;

	$meta_keys  = hussainas_get_searchable_meta_keys();
	$taxonomies = hussainas_get_searchable_taxonomies();

	// 1. Join postmeta table if we have keys to search.
	if ( ! empty( $meta_keys ) ) {
		// Use a unique alias to avoid conflicts.
		$join .= " LEFT JOIN {$wpdb->postmeta} AS hmeta ON {$wpdb->posts}.ID = hmeta.post_id";
	}

	// 2. Join taxonomy tables if we have taxonomies to search.
	if ( ! empty( $taxonomies ) ) {
		// Use unique aliases.
		$join .= " LEFT JOIN {$wpdb->term_relationships} AS htr ON {$wpdb->posts}.ID = htr.object_id";
		$join .= " LEFT JOIN {$wpdb->term_taxonomy} AS htt ON htr.term_taxonomy_id = htt.term_taxonomy_id";
		$join .= " LEFT JOIN {$wpdb->terms} AS ht ON htt.term_id = ht.term_id";
	}

	return $join;
}

/**
 * Modifies the WHERE clause of the search query.
 *
 * This is the core logic. It rebuilds the search condition to be:
 * (title OR content) OR (meta value) OR (taxonomy term name)
 *
 * @param string $where The original WHERE clause.
 * @return string The modified WHERE clause.
 */
function hussainas_modify_search_where( $where ) {
	global $wpdb;

	$s = get_search_query();
	if ( empty( $s ) ) {
		return $where; // Don't modify if search is empty.
	}

	$meta_keys  = hussainas_get_searchable_meta_keys();
	$taxonomies = hussainas_get_searchable_taxonomies();

	// Prepare the search term for SQL LIKE.
	$s_like = '%' . $wpdb->esc_like( $s ) . '%';
	$s_sql  = $wpdb->prepare( '%s', $s_like ); // Prepared search term.

	// 1. Default search: (post_title LIKE %s%) OR (post_content LIKE %s%)
	$search_sql = "({$wpdb->posts}.post_title LIKE {$s_sql} OR {$wpdb->posts}.post_content LIKE {$s_sql})";

	// 2. Meta field search
	if ( ! empty( $meta_keys ) ) {
		// Create placeholders for the 'IN' clause: (%s, %s, %s)
		$meta_key_placeholders = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );

		// Prepare the meta query part
		// (hmeta.meta_value LIKE %s% AND hmeta.meta_key IN (%s, %s, ...))
		$meta_sql = $wpdb->prepare(
			"(hmeta.meta_value LIKE {$s_sql} AND hmeta.meta_key IN ($meta_key_placeholders))",
			$meta_keys // Pass the array of keys for the IN clause.
		);
		$search_sql .= " OR " . $meta_sql;
	}

	// 3. Taxonomy term search
	if ( ! empty( $taxonomies ) ) {
		// Create placeholders for the 'IN' clause: (%s, %s, %s)
		$tax_placeholders = implode( ', ', array_fill( 0, count( $taxonomies ), '%s' ) );

		// Prepare the taxonomy query part
		// (ht.name LIKE %s% AND htt.taxonomy IN (%s, %s, ...))
		$tax_sql = $wpdb->prepare(
			"(ht.name LIKE {$s_sql} AND htt.taxonomy IN ($tax_placeholders))",
			$taxonomies // Pass the array of taxonomy names.
		);
		$search_sql .= " OR " . $tax_sql;
	}

	// 4. Rebuild the main WHERE clause.
	// We replace the default WP search clause with our new, expanded search clause.
	// This regex finds the default WP search clause.
	$where = preg_replace(
		"/\(\s*{$wpdb->posts}.post_title\s+LIKE\s*(\'[^\']+\')\s*\)\s+OR\s+\(\s*{$wpdb->posts}.post_content\s+LIKE\s*(\'[^\']+\')\s*\)/",
		"($search_sql)",
		$where
	);

	// 5. Unhook these filters immediately after use to prevent
	// them from affecting other queries on the same page.
	remove_filter( 'posts_join', 'hussainas_modify_search_join' );
	remove_filter( 'posts_where', 'hussainas_modify_search_where' );
	remove_filter( 'posts_distinct', 'hussainas_modify_search_distinct' );

	return $where;
}

/**
 * Modifies the DISTINCT clause of the search query.
 *
 * Required because the JOINs (especially with postmeta) can
 * create duplicate post entries.
 *
 * @param string $distinct The original DISTINCT clause.
 * @return string The modified DISTINCT clause.
 */
function hussainas_modify_search_distinct( $distinct ) {
	return 'DISTINCT';
}

/**
 * Main query modifier (hooked to 'pre_get_posts').
 *
 * This function checks if it's the correct query (main, search, frontend)
 * and then adds the SQL modification filters.
 *
 * @param WP_Query $query The WP_Query object (passed by reference).
 */
function hussainas_search_query_modifier( $query ) {
	// We only want to modify the main search query on the frontend.
	if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {

		// Set the post types to search.
		$query->set( 'post_type', hussainas_get_searchable_post_types() );

		// Add the filters that will build the custom SQL.
		// These will be removed by hussainas_modify_search_where() after execution.
		add_filter( 'posts_join', 'hussainas_modify_search_join' );
		add_filter( 'posts_where', 'hussainas_modify_search_where' );
		add_filter( 'posts_distinct', 'hussainas_modify_search_distinct' );
	}
}
