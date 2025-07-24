<?php
/**
 * Askro Sidebars Class
 *
 * Handles registration and management of widget areas (sidebars) for the
 * Askro plugin, including the archive page sidebar.
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
 * Sidebars Management Class
 *
 * Registers and manages widget areas for the Askro plugin.
 *
 * @since 1.0.0
 */
class Askro_Sidebars {

    /**
     * Initialize the sidebars
     *
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        add_action( 'widgets_init', array( __CLASS__, 'register_sidebars' ) );
    }

    /**
     * Register all plugin sidebars
     *
     * @since 1.0.0
     * @return void
     */
    public static function register_sidebars() {
        // Archive sidebar
        register_sidebar( array(
            'name'          => __( 'Askro Archive Sidebar', 'askro' ),
            'id'            => 'askro_archive_sidebar',
            'description'   => __( 'Sidebar for the questions archive page. Add widgets to enhance the user experience.', 'askro' ),
            'before_widget' => '<div id="%1$s" class="widget %2$s mb-6 p-4 bg-base-100 rounded-lg shadow-sm">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title text-lg font-semibold mb-4 pb-2 border-b border-base-300">',
            'after_title'   => '</h3>',
        ) );

        // Single question sidebar
        register_sidebar( array(
            'name'          => __( 'Askro Single Question Sidebar', 'askro' ),
            'id'            => 'askro_single_sidebar',
            'description'   => __( 'Sidebar for individual question pages. Perfect for related questions, tags, or user information.', 'askro' ),
            'before_widget' => '<div id="%1$s" class="widget %2$s mb-6 p-4 bg-base-100 rounded-lg shadow-sm">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title text-lg font-semibold mb-4 pb-2 border-b border-base-300">',
            'after_title'   => '</h3>',
        ) );

        // Submit form sidebar
        register_sidebar( array(
            'name'          => __( 'Askro Submit Form Sidebar', 'askro' ),
            'id'            => 'askro_submit_sidebar',
            'description'   => __( 'Sidebar for the question submission form. Add helpful widgets or guidelines.', 'askro' ),
            'before_widget' => '<div id="%1$s" class="widget %2$s mb-6 p-4 bg-base-100 rounded-lg shadow-sm">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title text-lg font-semibold mb-4 pb-2 border-b border-base-300">',
            'after_title'   => '</h3>',
        ) );
    }

    /**
     * Check if a sidebar has widgets
     *
     * @since 1.0.0
     * @param string $sidebar_id Sidebar ID to check
     * @return bool True if sidebar has widgets
     */
    public static function has_widgets( $sidebar_id ) {
        return is_active_sidebar( $sidebar_id );
    }

    /**
     * Display sidebar with fallback content
     *
     * @since 1.0.0
     * @param string $sidebar_id Sidebar ID to display
     * @param string $fallback_content Optional fallback HTML content
     * @return void
     */
    public static function display_sidebar( $sidebar_id, $fallback_content = '' ) {
        if ( self::has_widgets( $sidebar_id ) ) {
            dynamic_sidebar( $sidebar_id );
        } elseif ( ! empty( $fallback_content ) ) {
            echo wp_kses_post( $fallback_content );
        }
    }
}
