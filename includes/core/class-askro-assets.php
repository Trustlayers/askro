<?php
/**
 * Askro Assets Manager
 *
 * Handles registration and conditional enqueuing of CSS and JavaScript assets
 * for optimal performance by loading assets only when needed.
 *
 * @package    Askro
 * @subpackage Core
 * @since      1.0.0
 * @author     Arashdi Team <info@arashdi.com>
 * @copyright  2025 Arashdi Team
 * @license    GPL-3.0-or-later
 * @link       https://arashdi.com/
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Asset Manager Class
 *
 * Manages the registration and conditional enqueuing of plugin assets
 * to ensure optimal performance and prevent unnecessary asset loading.
 *
 * @since 1.0.0
 */
final class Askro_Assets {

    /**
     * Private constructor to prevent direct instantiation
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Prevent direct instantiation
    }

    /**
     * Initialize the asset manager
     *
     * Sets up the necessary WordPress hooks for asset management.
     *
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        
        // Force load main stylesheet via wp_head hook (bypasses enqueue system)
        add_action( 'wp_head', array( __CLASS__, 'force_load_main_stylesheet' ), 999 );
        
        // Temporary debug line - REMOVE AFTER CONFIRMATION
        // error_log('DEBUG: Askro_Assets hooks registered successfully!');
    }

    /**
     * Register all plugin assets
     *
     * Registers CSS and JavaScript files with WordPress using the plugin's
     * root directory for pathing and file modification time for versioning
     * to prevent caching issues during development.
     *
     * @since 1.0.0
     * @return void
     */
    public static function register_assets() {
        // Correctly get base plugin URL and path from includes/core/ directory
        $plugin_url = plugin_dir_url( dirname( __FILE__, 2 ) );
        $plugin_path = plugin_dir_path( dirname( __FILE__, 2 ) );

        // Register Styles
        // Main stylesheet is now force-loaded via wp_head - see force_load_main_stylesheet() method
        /*
        wp_register_style(
            'askro-main-style',
            $plugin_url . 'assets/css/style.css',
            array(),
            filemtime( $plugin_path . 'assets/css/style.css' ),
            'all'
        );
        */

        // Register vendor CSS files (with existence check)
        if ( file_exists( $plugin_path . 'assets/vendor/cropperjs/cropper.min.css' ) ) {
            wp_register_style(
                'askro-cropperjs-style',
                $plugin_url . 'assets/vendor/cropperjs/cropper.min.css',
                array(),
                filemtime( $plugin_path . 'assets/vendor/cropperjs/cropper.min.css' ),
                'all'
            );
        }

        if ( file_exists( $plugin_path . 'assets/vendor/tagify/tagify.css' ) ) {
            wp_register_style(
                'askro-tagify-style',
                $plugin_url . 'assets/vendor/tagify/tagify.css',
                array(),
                filemtime( $plugin_path . 'assets/vendor/tagify/tagify.css' ),
                'all'
            );
        }

        if ( file_exists( $plugin_path . 'assets/vendor/toastr/toastr.min.css' ) ) {
            wp_register_style(
                'askro-toastr-style',
                $plugin_url . 'assets/vendor/toastr/toastr.min.css',
                array(),
                filemtime( $plugin_path . 'assets/vendor/toastr/toastr.min.css' ),
                'all'
            );
        }

        // Register Scripts (with existence check for vendor files)
        if ( file_exists( $plugin_path . 'assets/vendor/chartjs/chart.umd.js' ) ) {
            wp_register_script(
                'askro-chartjs',
                $plugin_url . 'assets/vendor/chartjs/chart.umd.js',
                array(),
                filemtime( $plugin_path . 'assets/vendor/chartjs/chart.umd.js' ),
                true
            );
        }

        if ( file_exists( $plugin_path . 'assets/vendor/swiper/swiper-bundle.min.js' ) ) {
            wp_register_script(
                'askro-swiper',
                $plugin_url . 'assets/vendor/swiper/swiper-bundle.min.js',
                array(),
                filemtime( $plugin_path . 'assets/vendor/swiper/swiper-bundle.min.js' ),
                true
            );
        }

        if ( file_exists( $plugin_path . 'assets/vendor/cropperjs/cropper.min.js' ) ) {
            wp_register_script(
                'askro-cropperjs',
                $plugin_url . 'assets/vendor/cropperjs/cropper.min.js',
                array( 'askro-cropperjs-style' ),
                filemtime( $plugin_path . 'assets/vendor/cropperjs/cropper.min.js' ),
                true
            );
        }

        if ( file_exists( $plugin_path . 'assets/vendor/tagify/tagify.js' ) ) {
            wp_register_script(
                'askro-tagify-script',
                $plugin_url . 'assets/vendor/tagify/tagify.js',
                array(),
                filemtime( $plugin_path . 'assets/vendor/tagify/tagify.js' ),
                true
            );
        }

        if ( file_exists( $plugin_path . 'assets/vendor/toastr/toastr.min.js' ) ) {
            wp_register_script(
                'askro-toastr-script',
                $plugin_url . 'assets/vendor/toastr/toastr.min.js',
                array( 'jquery' ),
                filemtime( $plugin_path . 'assets/vendor/toastr/toastr.min.js' ),
                true
            );
        }

        // Always register main script (core functionality)
        wp_register_script(
            'askro-main-script',
            $plugin_url . 'assets/js/main.js',
            array( 'jquery' ), // Simplified dependencies to avoid missing vendor files
            filemtime( $plugin_path . 'assets/js/main.js' ),
            true
        );
    }

    /**
     * Enqueue assets based on unified logic
     *
     * This method follows the strict unified flag pattern per project Rule #1.
     * It uses a single flag to determine if Askro assets should be loaded.
     *
     * @since 1.0.0
     * @return void
     */
    public static function enqueue_assets() {
        // STEP 1: UNIFIED CONDITION CHECK
        $load_core_assets = false;
        global $post;

        // Check if we're on singular question pages
        if (is_singular('question')) {
            $load_core_assets = true;
        }

        // Check for shortcodes in post content
        if (!$load_core_assets && is_a($post, 'WP_Post')) {
            if (has_shortcode($post->post_content, 'askro_questions_archive') ||
                has_shortcode($post->post_content, 'askro_submit_question_form') ||
                has_shortcode($post->post_content, 'askro_user_profile')) {
                $load_core_assets = true;
            }
        }

        // Check if we're on /questions/ URL directly (fallback for custom routing)
        if (!$load_core_assets && isset($_SERVER['REQUEST_URI'])) {
            $request_uri = $_SERVER['REQUEST_URI'];
            $request_path = parse_url($request_uri, PHP_URL_PATH);
            $request_path = trim($request_path, '/');
            
            if ($request_path === 'questions' || strpos($request_path, 'questions/') === 0) {
                $load_core_assets = true;
            }
        }

        // STEP 2: DECOUPLED ASSET LOADING
        if ($load_core_assets) {
            // Main stylesheet is force-loaded via wp_head - see force_load_main_stylesheet() method
            // wp_enqueue_style('askro-main-style');

            // Enqueue the main script
            wp_enqueue_script('askro-main-script');

            // Load specific assets if needed
            if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'askro_user_profile')) {
                if (wp_script_is('askro-chartjs', 'registered')) {
                    wp_enqueue_script('askro-chartjs');
                }
            }
            
            if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'askro_submit_question_form')) {
                if (wp_style_is('askro-tagify-style', 'registered')) {
                    wp_enqueue_style('askro-tagify-style');
                }
                if (wp_style_is('askro-toastr-style', 'registered')) {
                    wp_enqueue_style('askro-toastr-style');
                }
            }

            // Localize script ONCE
            if (!wp_script_is('askro-main-script', 'localized')) {
                $localized_data = [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('askro_nonce'),
                    'user_id'  => get_current_user_id()
                ];
                wp_localize_script('askro-main-script', 'askroAjax', $localized_data);
            }
        }
    }

    /**
     * Force load main stylesheet by directly printing to wp_head
     *
     * This method bypasses the WordPress enqueue system entirely and directly
     * outputs the main stylesheet link tag to ensure it always loads on Askro pages.
     * Uses the same unified flag logic as enqueue_assets().
     *
     * @since 1.0.0
     * @return void
     */
    public static function force_load_main_stylesheet() {
        // We only run this on the frontend.
        if (is_admin()) {
            return;
        }

        // STEP 1: UNIFIED CONDITION CHECK (matching enqueue_assets logic)
        $load_core_assets = false;
        global $post;

        // Check if we're on singular question pages
        if (is_singular('question')) {
            $load_core_assets = true;
        }

        // Check for shortcodes in post content
        if (!$load_core_assets && is_a($post, 'WP_Post')) {
            if (has_shortcode($post->post_content, 'askro_questions_archive') ||
                has_shortcode($post->post_content, 'askro_submit_question_form') ||
                has_shortcode($post->post_content, 'askro_user_profile')) {
                $load_core_assets = true;
            }
        }

        // Check if we're on /questions/ URL directly (fallback for custom routing)
        if (!$load_core_assets && isset($_SERVER['REQUEST_URI'])) {
            $request_uri = $_SERVER['REQUEST_URI'];
            $request_path = parse_url($request_uri, PHP_URL_PATH);
            $request_path = trim($request_path, '/');
            
            if ($request_path === 'questions' || strpos($request_path, 'questions/') === 0) {
                $load_core_assets = true;
            }
        }

        // If it's an Askro page, print the stylesheet link directly.
        if ($load_core_assets) {
            $css_url = plugin_dir_url(dirname(__FILE__, 2)) . 'assets/css/style.css';
            $css_path = plugin_dir_path(dirname(__FILE__, 2)) . 'assets/css/style.css';
            $version = file_exists($css_path) ? filemtime($css_path) : '1.0.0';
            
            // Handle protocol - use protocol-relative URLs or force HTTPS if site is HTTPS
            if (is_ssl()) {
                $css_url = set_url_scheme($css_url, 'https');
            } else {
                // Use protocol-relative URL to match the page protocol
                $css_url = set_url_scheme($css_url, 'relative');
            }
            
            echo '<link rel="stylesheet" id="askro-main-style-forced" href="' . esc_url($css_url) . '?v=' . $version . '" type="text/css" media="all" />' . "\n";
            
            // Also add a fallback using wp_enqueue_style
            wp_register_style(
                'askro-main-style',
                plugin_dir_url(dirname(__FILE__, 2)) . 'assets/css/style.css',
                array(),
                $version,
                'all'
            );
            wp_enqueue_style('askro-main-style');
        }
    }
}
