<?php
/**
 * Askro Admin Menu
 *
 * Handles the creation of the admin menu for managing the Askro plugin settings
 * and features within the WordPress admin dashboard.
 *
 * @package    Askro
 * @subpackage Admin
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
 * Admin Menu Class
 *
 * Creates the main Askro admin menu and associated submenus for configuration
 * and management within the WordPress backend.
 *
 * @since 1.0.0
 */
final class Askro_Admin_Menu {

    /**
     * Private constructor to prevent direct instantiation
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Prevent direct instantiation
    }

    /**
     * Initialize the admin menu
     *
     * Sets up WordPress hooks for building the admin menu structure.
     *
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'setup_menu' ) );
    }

    /**
     * Set up the admin menu structure
     *
     * Adds the main menu and submenus under the Askro menu for accessing
     * plugin features and configuration settings.
     *
     * @since 1.0.0
     * @return void
     */
    public static function setup_menu() {
        add_menu_page(
            __( 'Askro Dashboard', 'askro' ),
            __( 'Askro', 'askro' ),
            'manage_options',
            'askro',
            array( __CLASS__, 'display_dashboard_page' ),
            'dashicons-groups'
        );

        add_submenu_page(
            'askro',
            __( 'Voting & Points Settings', 'askro' ),
            __( 'Voting & Points', 'askro' ),
            'manage_options',
            'askro-voting-settings',
            array( __CLASS__, 'display_settings_page' )
        );
    }

    /**
     * Display the Askro dashboard page
     *
     * Renders the contents of the Askro main dashboard page in the admin.
     *
     * @since 1.0.0
     * @return void
     */
    public static function display_dashboard_page() {
        echo '<h1>' . __( 'Askro Dashboard', 'askro' ) . '</h1>';
        echo '<p>' . __( 'Welcome to the Askro Plugin Dashboard!', 'askro' ) . '</p>';
    }

    /**
     * Display the Voting & Points settings page
     *
     * Renders the contents of the Voting & Points settings page in the admin.
     *
     * @since 1.0.0
     * @return void
     */
    public static function display_settings_page() {
        Askro_Voting_Settings_Page::render();
    }
}

