<?php
/**
 * Askro Debugging Tools
 *
 * Provides debugging functions to troubleshoot plugin issues.
 *
 * @package    Askro
 * @subpackage Core
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Debug post type registration
 *
 * Outputs information about registered post types to help troubleshoot issues.
 *
 * @since 1.0.0
 * @return void
 */
function askro_debug_post_types() {
    // Only run on admin pages
    if ( ! is_admin() ) {
        return;
    }

    // Only run for admin users
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Check if debug parameter is set
    if ( ! isset( $_GET['askro_debug'] ) || $_GET['askro_debug'] !== 'post_types' ) {
        return;
    }

    // Get all registered post types
    $post_types = get_post_types( array(), 'objects' );

    echo '<div class="wrap">';
    echo '<h1>Askro Debug: Registered Post Types</h1>';
    
    // Check if our post type exists
    if ( isset( $post_types['question'] ) ) {
        $question = $post_types['question'];
        
        echo '<h2>Question Post Type Details:</h2>';
        echo '<pre>';
        print_r( $question );
        echo '</pre>';
        
        echo '<h3>Rewrite Rules:</h3>';
        echo '<pre>';
        print_r( $question->rewrite );
        echo '</pre>';
        
        echo '<h3>Has Archive:</h3>';
        echo '<pre>';
        var_dump( $question->has_archive );
        echo '</pre>';
        
        echo '<h3>Current Rewrite Rules:</h3>';
        echo '<pre>';
        global $wp_rewrite;
        print_r( $wp_rewrite->rules );
        echo '</pre>';
    } else {
        echo '<p>Error: Question post type is not registered!</p>';
    }
    
    echo '</div>';
    exit;
}

// Hook the debugging function
add_action( 'admin_init', 'askro_debug_post_types' );
