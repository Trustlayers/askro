<?php
/**
 * Plugin Name:       Askro
 * Plugin URI:        https://github.com/Trustlayers/askro.git
 * Description:       A powerful Q&A and problem-solving community plugin with advanced gamification and voting systems.
 * Version:           1.1.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Arashdi Team
 * Author URI:        https://arashdi.com/
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       askro
 * Domain Path:       /languages
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define core plugin constants.
define( 'ASKRO_VERSION', '1.1.0' );
define( 'ASKRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASKRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load Composer autoloader if available.
if ( file_exists( ASKRO_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once ASKRO_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * The main plugin class.
 */
final class Askro_Main {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        // Core Functionality
        require_once ASKRO_PLUGIN_DIR . 'includes/core/class-askro-assets.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/core/class-askro-sidebars.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/core/debug-tools.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/core/class-askro-archive-fix.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/core/class-askro-css-loader.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/core/class-askro-diagnostics.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/functions/askro-question-functions.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/functions/askro-comments-functions.php';
        
        // Database
        require_once ASKRO_PLUGIN_DIR . 'includes/database/class-askro-db-manager.php';

        // Post Types & Taxonomies
        require_once ASKRO_PLUGIN_DIR . 'includes/core/post-types/class-askro-question-cpt.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/core/taxonomies/class-askro-category-tax.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/core/taxonomies/class-askro-tag-tax.php';

        // Admin Interface
        require_once ASKRO_PLUGIN_DIR . 'includes/admin/class-askro-admin-menu.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/admin/tables/class-askro-vote-weights-table.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/admin/tables/class-askro-vote-reasons-table.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/admin/pages/class-askro-voting-settings-page.php';

        // Frontend Features
        require_once ASKRO_PLUGIN_DIR . 'includes/frontend/shortcodes/class-askro-submit-form-shortcode.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/frontend/shortcodes/class-askro-user-profile-shortcode.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/frontend/shortcodes/class-askro-archive-shortcode.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/frontend/ajax/class-askro-archive-handler.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/frontend/ajax/class-askro-voting-handler.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/frontend/ajax/class-askro-answer-handler.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/frontend/ajax/class-askro-comment-handler.php';
        require_once ASKRO_PLUGIN_DIR . 'includes/frontend/ajax/class-askro-profile-handler.php';
    }

    private function init_hooks() {
        // Register activation and deactivation hooks
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
        
        // Initialize post types and taxonomies here, as they hook into 'init'.
        Askro_Question_CPT::init();
        Askro_Category_Tax::init();
        Askro_Tag_Tax::init();
        
        // Check if we need to flush rewrite rules based on plugin version changes
        add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 99 );
    }
    
    /**
     * Check if we need to flush rewrite rules
     * 
     * This is an additional safeguard to ensure rewrite rules are up to date.
     * It runs on a version change or new installation.
     */
    public function maybe_flush_rewrite_rules() {
        $version = get_option( 'askro_version', '0' );
        
        // If version has changed or new installation
        if ( $version !== ASKRO_VERSION ) {
            // Store current version
            update_option( 'askro_version', ASKRO_VERSION );
            
            // Flag that we need to flush
            update_option( 'askro_flush_rewrite_rules', 'yes' );
        }
        
        // Check if we need to flush
        if ( 'yes' === get_option( 'askro_flush_rewrite_rules', 'no' ) ) {
            // Clear the flag first to prevent infinite loop
            update_option( 'askro_flush_rewrite_rules', 'no' );
            
            // Flush the rules
            flush_rewrite_rules();
        }
    }
    
    /**
     * Plugin deactivation hook
     */
    public function deactivate() {
        // Flush rewrite rules on deactivation
        flush_rewrite_rules();
    }

    public function activate() {
        // During activation, we need to register post types directly
        // since the 'init' action won't fire during activation
        Askro_Question_CPT::register_post_type();
        Askro_Category_Tax::register_taxonomy();
        Askro_Tag_Tax::register_taxonomy();

        // Create custom database tables.
        Askro_Database_Manager::create_tables();

        // Flush rewrite rules to recognize the new CPTs.
        flush_rewrite_rules();
    }

    public function on_plugins_loaded() {
        // Initialize all components.
        Askro_Assets::init();
        Askro_CSS_Loader::init();
        Askro_Sidebars::init();
        Askro_Archive_Fix::init();
        Askro_Diagnostics::init();
        Askro_Admin_Menu::init();
        Askro_Submit_Form_Shortcode::init();
        Askro_User_Profile_Shortcode::init();
        Askro_Archive_Shortcode::init();
        Askro_Archive_Handler::init();
        Askro_Voting_Handler::init();
        Askro_Answer_Handler::init();
        Askro_Comment_Handler::init();
        Askro_Profile_Handler::init();
    }
}

/**
 * Begins execution of the plugin.
 */
function askro_run() {
    return Askro_Main::instance();
}
askro_run();
