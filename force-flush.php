<?php
/**
 * Force Flush Rewrite Rules
 * 
 * This file can be accessed directly to force a flush of rewrite rules
 * Access: /wp-content/plugins/Askro/force-flush.php?flush=yes
 */

// Load WordPress
require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );

// Check if user is admin and flush parameter is set
if ( current_user_can( 'manage_options' ) && isset( $_GET['flush'] ) && $_GET['flush'] === 'yes' ) {
    // Force the askro_flush_rewrite_rules option
    update_option( 'askro_flush_rewrite_rules', 'yes' );
    
    // Also flush immediately
    flush_rewrite_rules();
    
    echo '<h1>Rewrite Rules Flushed!</h1>';
    echo '<p>The rewrite rules have been flushed successfully.</p>';
    echo '<p><a href="' . home_url('/questions/') . '">Test Questions Archive</a></p>';
    echo '<p><a href="' . admin_url('edit.php?post_type=question&page=askro-diagnostics') . '">View Diagnostics</a></p>';
} else {
    echo '<h1>Access Denied</h1>';
    echo '<p>You must be logged in as an administrator to flush rewrite rules.</p>';
    echo '<p><a href="' . wp_login_url( $_SERVER['REQUEST_URI'] ) . '">Log In</a></p>';
}
