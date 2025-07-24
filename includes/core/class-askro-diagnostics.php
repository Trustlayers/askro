<?php
/**
 * Askro Diagnostics
 *
 * Diagnostic tools for debugging post type and rewrite rules issues
 *
 * @package    Askro
 * @subpackage Core
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Askro_Diagnostics {
    
    public static function init() {
        // Add admin page for diagnostics
        add_action( 'admin_menu', array( __CLASS__, 'add_diagnostics_page' ), 99 );
        
        // Add debug info to admin bar
        add_action( 'admin_bar_menu', array( __CLASS__, 'add_debug_to_admin_bar' ), 999 );
        
        // Add query monitor for front-end
        add_action( 'template_redirect', array( __CLASS__, 'monitor_query' ), 1 );
    }
    
    public static function add_diagnostics_page() {
        add_submenu_page(
            'edit.php?post_type=question',
            'Askro Diagnostics',
            'Diagnostics',
            'manage_options',
            'askro-diagnostics',
            array( __CLASS__, 'render_diagnostics_page' )
        );
    }
    
    public static function render_diagnostics_page() {
        global $wp_rewrite, $wp_post_types;
        
        echo '<div class="wrap">';
        echo '<h1>Askro Diagnostics</h1>';
        
        // Post Type Information
        echo '<h2>Question Post Type Registration</h2>';
        echo '<pre style="background: #f0f0f0; padding: 10px; overflow: auto;">';
        
        if ( isset( $wp_post_types['question'] ) ) {
            $question_cpt = $wp_post_types['question'];
            echo "Post Type 'question' is registered:\n\n";
            echo "Public: " . ($question_cpt->public ? 'Yes' : 'No') . "\n";
            echo "Publicly Queryable: " . ($question_cpt->publicly_queryable ? 'Yes' : 'No') . "\n";
            echo "Has Archive: " . var_export($question_cpt->has_archive, true) . "\n";
            echo "Query Var: " . var_export($question_cpt->query_var, true) . "\n";
            echo "Rewrite Slug: " . (isset($question_cpt->rewrite['slug']) ? $question_cpt->rewrite['slug'] : 'Not set') . "\n";
            echo "Rewrite With Front: " . (isset($question_cpt->rewrite['with_front']) ? ($question_cpt->rewrite['with_front'] ? 'Yes' : 'No') : 'Not set') . "\n";
            echo "\nFull Rewrite Array:\n";
            print_r($question_cpt->rewrite);
        } else {
            echo "Post Type 'question' is NOT registered!";
        }
        
        echo '</pre>';
        
        // Rewrite Rules
        echo '<h2>Rewrite Rules for Questions</h2>';
        echo '<pre style="background: #f0f0f0; padding: 10px; overflow: auto; max-height: 400px;">';
        
        $rules = $wp_rewrite->rules;
        $question_rules = array();
        
        foreach ( $rules as $pattern => $redirect ) {
            if ( strpos( $pattern, 'question' ) !== false || strpos( $redirect, 'question' ) !== false ) {
                $question_rules[$pattern] = $redirect;
            }
        }
        
        if ( ! empty( $question_rules ) ) {
            echo "Question-related rewrite rules:\n\n";
            foreach ( $question_rules as $pattern => $redirect ) {
                echo "$pattern => $redirect\n";
            }
        } else {
            echo "No rewrite rules found containing 'question'";
        }
        
        echo '</pre>';
        
        // Permalink Structure
        echo '<h2>Permalink Structure</h2>';
        echo '<pre style="background: #f0f0f0; padding: 10px;">';
        echo "Permalink Structure: " . get_option('permalink_structure') . "\n";
        echo "Front Base: " . $wp_rewrite->front . "\n";
        echo '</pre>';
        
        // Test URLs
        echo '<h2>Test URLs</h2>';
        echo '<ul>';
        echo '<li><a href="' . home_url('/questions/') . '" target="_blank">/questions/ (Archive)</a></li>';
        echo '<li><a href="' . get_post_type_archive_link('question') . '" target="_blank">get_post_type_archive_link() result</a></li>';
        echo '</ul>';
        
        // Flush Rewrite Rules Button
        echo '<h2>Actions</h2>';
        echo '<form method="post">';
        wp_nonce_field( 'askro_flush_rules', 'askro_flush_nonce' );
        echo '<button type="submit" name="askro_flush_rules" class="button button-primary">Flush Rewrite Rules</button>';
        echo '</form>';
        
        if ( isset( $_POST['askro_flush_rules'] ) && wp_verify_nonce( $_POST['askro_flush_nonce'], 'askro_flush_rules' ) ) {
            flush_rewrite_rules();
            echo '<div class="notice notice-success"><p>Rewrite rules flushed!</p></div>';
        }
        
        echo '</div>';
    }
    
    public static function add_debug_to_admin_bar( $wp_admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        global $wp_query;
        
        $debug_info = array(
            'is_post_type_archive' => is_post_type_archive('question') ? 'Yes' : 'No',
            'post_type' => get_query_var('post_type'),
            'pagename' => get_query_var('pagename'),
            'name' => get_query_var('name'),
        );
        
        $wp_admin_bar->add_node( array(
            'id'    => 'askro-debug',
            'title' => 'Askro Debug',
            'href'  => admin_url('edit.php?post_type=question&page=askro-diagnostics'),
            'meta'  => array(
                'title' => 'Debug Info: ' . json_encode($debug_info),
            ),
        ) );
    }
    
    public static function monitor_query() {
        if ( ! current_user_can( 'manage_options' ) || ! defined('WP_DEBUG') || ! WP_DEBUG ) {
            return;
        }
        
        global $wp_query;
        
        // Only log on potential question pages
        $request_uri = $_SERVER['REQUEST_URI'];
        if ( strpos( $request_uri, 'question' ) !== false ) {
            error_log( '=== Askro Query Monitor ===' );
            error_log( 'Request URI: ' . $request_uri );
            error_log( 'Query Vars: ' . print_r( $wp_query->query_vars, true ) );
            error_log( 'Is 404: ' . ( is_404() ? 'Yes' : 'No' ) );
            error_log( 'Is Post Type Archive: ' . ( is_post_type_archive('question') ? 'Yes' : 'No' ) );
            error_log( 'Matched Rule: ' . ( isset($wp_query->matched_rule) ? $wp_query->matched_rule : 'None' ) );
            error_log( 'Matched Query: ' . ( isset($wp_query->matched_query) ? $wp_query->matched_query : 'None' ) );
            error_log( '=========================' );
        }
    }
}
