<?php
/*
Plugin Name: ACF restricted search and access
Plugin URI:
Description:  This plugin make ACF fields accessible for WP searching with filters. Also, you can create rules for restricted access to ACF custom fields in Frontend and in the search results. Restricted access depends on users roles.
Author: Victor Demianenko
Version: 1.1.0
Author URI:
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Exit if accessed directly

if ( ! defined( 'ABSPATH' ) ) {
    header( 'Status: 403 Forbidden' );
    header( 'HTTP/1.1 403 Forbidden' );
    exit;
}


require_once plugin_dir_path( __FILE__ ) . 'includes/Handler.php';

function runACFAdvancedSearch() {

    $acf_advanced_search = new ACFAdvancedSearch\Handler();
    $acf_advanced_search->run();

}

runACFAdvancedSearch();

//function loadTextDomain() {
//
//    load_plugin_textdomain( 'acf-advanced-search', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
//
//}
//add_action( 'init', 'loadTexDomain' );
