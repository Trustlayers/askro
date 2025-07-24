<?php
/**
 * Askro Archive Fix
 *
 * Fixes archive page handling for the question custom post type
 * Works with both classic and block themes
 *
 * @package    Askro
 * @subpackage Core
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Askro_Archive_Fix {
    
    /**
     * Initialize the archive fix
     */
    public static function init() {
        // Hook into parse_request to handle our custom archive
        add_action( 'parse_request', array( __CLASS__, 'handle_question_archive' ), 5 );
        
        // Add rewrite rules with high priority
        add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ), 5 );
        
        // Filter the request to set proper query vars
        add_filter( 'request', array( __CLASS__, 'filter_request' ), 5 );
        
        // Force template include for block themes
        add_filter( 'template_include', array( __CLASS__, 'force_template' ), 5 );
        
        // Add support for block themes
        add_action( 'after_setup_theme', array( __CLASS__, 'add_block_theme_support' ), 20 );
    }
    
    /**
     * Add explicit rewrite rules for the questions archive
     */
    public static function add_rewrite_rules() {
        // Add explicit rule for /questions/ archive
        add_rewrite_rule(
            '^questions/?$',
            'index.php?post_type=question',
            'top'
        );
        
        // Add rule for paginated archives
        add_rewrite_rule(
            '^questions/page/([0-9]+)/?$',
            'index.php?post_type=question&paged=$matches[1]',
            'top'
        );
    }
    
    /**
     * Handle question archive requests
     */
    public static function handle_question_archive( $wp ) {
        // Check if this is a questions archive request
        if ( isset( $wp->request ) && ( $wp->request === 'questions' || strpos( $wp->request, 'questions/' ) === 0 ) ) {
            // Set the query vars for post type archive
            $wp->query_vars['post_type'] = 'question';
            $wp->query_vars['name'] = '';
            $wp->query_vars['pagename'] = '';
            
            // Handle pagination
            if ( preg_match( '/questions\/page\/(\d+)/', $wp->request, $matches ) ) {
                $wp->query_vars['paged'] = $matches[1];
            }
        }
    }
    
    /**
     * Filter the request to ensure proper query vars
     */
    public static function filter_request( $query_vars ) {
        // If accessing /questions/, ensure it's treated as post type archive
        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $request_uri = $_SERVER['REQUEST_URI'];
            $request_path = parse_url( $request_uri, PHP_URL_PATH );
            $request_path = trim( $request_path, '/' );
            
            if ( $request_path === 'questions' || strpos( $request_path, 'questions/' ) === 0 ) {
                $query_vars['post_type'] = 'question';
                unset( $query_vars['name'] );
                unset( $query_vars['pagename'] );
                unset( $query_vars['error'] );
            }
        }
        
        return $query_vars;
    }
    
    /**
     * Force template loading for block themes
     */
    public static function force_template( $template ) {
        global $wp_query;
        
        // Check if we're on the question archive
        if ( is_post_type_archive( 'question' ) || 
             ( isset( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] === 'question' && ! is_single() ) ) {
            
            // Check if current theme is a block theme
            if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
                // For block themes, we need to ensure the template loader runs
                remove_filter( 'template_include', 'get_query_template' );
                
                // Try to get our custom template using WordPress hierarchy
                $template_name = 'archive-question.php';
                $custom_template = locate_template( array( 'askro/' . $template_name, $template_name ) );
                
                if ( ! $custom_template ) {
                    // Fall back to plugin template
                    $plugin_template = ASKRO_PLUGIN_DIR . 'templates/' . $template_name;
                    if ( file_exists( $plugin_template ) ) {
                        $custom_template = $plugin_template;
                    }
                }
                
                if ( $custom_template ) {
                    return $custom_template;
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Add support for block themes
     */
    public static function add_block_theme_support() {
        // Register block template parts for our post type
        if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
            // Add theme support for custom templates
            add_theme_support( 'custom-template-post-types', array( 'question' ) );
        }
    }
    
    /**
     * Debug function to check if archive is working
     */
    public static function debug_archive() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        global $wp_query, $wp_rewrite;
        
        echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
        echo '<h3>Askro Archive Debug</h3>';
        echo '<p><strong>Request URI:</strong> ' . $_SERVER['REQUEST_URI'] . '</p>';
        echo '<p><strong>Is Post Type Archive:</strong> ' . ( is_post_type_archive( 'question' ) ? 'Yes' : 'No' ) . '</p>';
        echo '<p><strong>Query Vars:</strong></p>';
        echo '<pre>' . print_r( $wp_query->query_vars, true ) . '</pre>';
        echo '<p><strong>Rewrite Rules (question-related):</strong></p>';
        echo '<pre>';
        foreach ( $wp_rewrite->rules as $pattern => $redirect ) {
            if ( strpos( $pattern, 'question' ) !== false || strpos( $redirect, 'question' ) !== false ) {
                echo "$pattern => $redirect\n";
            }
        }
        echo '</pre>';
        echo '</div>';
    }
}
