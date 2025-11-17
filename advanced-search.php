<?php
/**
 * Plugin Name: Advanced Search Module
 * Description: A module to enhance WordPress default search to include custom fields and taxonomies.
 * @version     1.0.0
 * @author      Hussain Ahmed Shrabon
 * @license     GPLv2 or later
 * @link        https://github.com/iamhussaina
 * @textdomain  hussainas
 */

// Prevent direct script access.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed' );
}

// Define a constant for the module path for easier inclusion.
define( 'HUSSAINAS_SEARCH_PATH', __DIR__ );

// Include core logic functions.
require_once HUSSAINAS_SEARCH_PATH . '/includes/functions.php';

// Include hooks to wire up the functionality.
require_once HUSSAINAS_SEARCH_PATH . '/includes/hooks.php';
