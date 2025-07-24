<?php
/**
 * Askro Voting & Points Settings Page
 *
 * Handles rendering and submission for the voting & points settings in the
 * Askro plugin admin interface. Integrates with WP_List_Table for vote weight
 * management.
 *
 * @package    Askro
 * @subpackage Admin/Pages
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
 * Voting & Points Settings Page Class
 *
 * Creates and manages the settings page for adjusting vote weight configurations
 * within the Askro admin menu. Integrates with Askro_Vote_Weights_Table for
 * inline editing and updates.
 *
 * @since 1.0.0
 */
class Askro_Voting_Settings_Page {

    /**
     * Initialize the settings page
     *
     * Sets up hooks for form submission handling.
     *
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'handle_submission' ) );
    }

    /**
     * Get the current active tab
     *
     * @since 1.0.0
     * @return string Current active tab
     */
    private static function get_current_tab() {
        return isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'weights';
    }

    /**
     * Render the settings page
     *
     * Displays the voting & points configuration interface with tabbed navigation
     * for both Vote Weights and Vote Reasons management.
     *
     * @since 1.0.0
     * @return void
     */
    public static function render() {
        $current_tab = self::get_current_tab();
        $base_url = admin_url( 'admin.php?page=askro-voting-settings' );

        // Display page title and tabs
        echo '<div class="wrap">';
        echo '<h1>' . __( 'Voting & Points Settings', 'askro' ) . '</h1>';
        
        // Tab navigation
        echo '<nav class="nav-tab-wrapper">';
        
        $tabs = array(
            'weights' => __( 'Vote Weights', 'askro' ),
            'reasons' => __( 'Vote Reasons', 'askro' )
        );
        
        foreach( $tabs as $tab_key => $tab_label ) {
            $tab_url = add_query_arg( 'tab', $tab_key, $base_url );
            $active_class = ( $current_tab === $tab_key ) ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url( $tab_url ) . '" class="nav-tab' . $active_class . '">' . esc_html( $tab_label ) . '</a>';
        }
        
        echo '</nav>';
        
        // Tab content
        echo '<div class="tab-content">';
        
        if ( $current_tab === 'weights' ) {
            self::render_vote_weights_tab();
        } elseif ( $current_tab === 'reasons' ) {
            self::render_vote_reasons_tab();
        }
        
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render the Vote Weights tab content
     *
     * @since 1.0.0
     * @return void
     */
    private static function render_vote_weights_tab() {
        // Instantiate table class and prepare items
        $vote_weights_table = new Askro_Vote_Weights_Table();
        $vote_weights_table->prepare_items();

        // Display form
        echo '<form method="post">';
        echo '<input type="hidden" name="action" value="save_vote_weights" />';
        wp_nonce_field( 'askro_save_vote_weights' );
        $vote_weights_table->display();
        submit_button();
        echo '</form>';
    }

    /**
     * Render the Vote Reasons tab content
     *
     * @since 1.0.0
     * @return void
     */
    private static function render_vote_reasons_tab() {
        // Instantiate table class and prepare items
        $vote_reasons_table = new Askro_Vote_Reasons_Table();
        $vote_reasons_table->prepare_items();

        // Display form
        echo '<form method="post">';
        echo '<input type="hidden" name="action" value="save_vote_reasons" />';
        wp_nonce_field( 'askro_save_vote_reasons' );
        $vote_reasons_table->display();
        submit_button();
        echo '</form>';
    }

    /**
     * Handle the form submission
     *
     * Processes form data for updating both vote weight and vote reason configurations
     * based on the submitted action. Ensures data integrity and security checks.
     *
     * @since 1.0.0
     * @return void
     */
    public static function handle_submission() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! isset( $_POST['action'] ) ) {
            return;
        }

        $action = sanitize_text_field( $_POST['action'] );

        switch( $action ) {
            case 'save_vote_weights':
                self::handle_vote_weights_submission();
                break;
                
            case 'save_vote_reasons':
                self::handle_vote_reasons_submission();
                break;
        }
    }

    /**
     * Handle vote weights form submission
     *
     * @since 1.0.0
     * @return void
     */
    private static function handle_vote_weights_submission() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'askro_save_vote_weights' ) ) {
            return;
        }

        if ( isset( $_POST['weights'] ) && is_array( $_POST['weights'] ) ) {
            global $wpdb;

            foreach ( $_POST['weights'] as $id => $data ) {
                $id = intval( $id );
                $voter_points = isset( $data['voter'] ) ? intval( $data['voter'] ) : 0;
                $target_points = isset( $data['target'] ) ? intval( $data['target'] ) : 0;

                $wpdb->update(
                    $wpdb->prefix . 'askro_vote_weights',
                    array(
                        'point_change_for_voter' => $voter_points,
                        'point_change_for_target' => $target_points
                    ),
                    array( 'id' => $id ),
                    array( '%d', '%d' ),
                    array( '%d' )
                );
            }

            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . __( 'Vote weights settings saved.', 'askro' ) . '</p>';
                echo '</div>';
            } );
        }
    }

    /**
     * Handle vote reasons form submission
     *
     * @since 1.0.0
     * @return void
     */
    private static function handle_vote_reasons_submission() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'askro_save_vote_reasons' ) ) {
            return;
        }

        if ( isset( $_POST['reasons'] ) && is_array( $_POST['reasons'] ) ) {
            global $wpdb;

            foreach ( $_POST['reasons'] as $id => $data ) {
                $id = intval( $id );
                $title = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
                $description = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
                $icon = isset( $data['icon'] ) ? sanitize_text_field( $data['icon'] ) : '';
                $color = isset( $data['color'] ) ? sanitize_hex_color( $data['color'] ) : '#000000';
                $is_active = isset( $data['is_active'] ) ? absint( $data['is_active'] ) : 0;

                $wpdb->update(
                    $wpdb->prefix . 'askro_vote_reason_presets',
                    array(
                        'title' => $title,
                        'description' => $description,
                        'icon' => $icon,
                        'color' => $color,
                        'is_active' => $is_active
                    ),
                    array( 'id' => $id ),
                    array( '%s', '%s', '%s', '%s', '%d' ),
                    array( '%d' )
                );
            }

            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . __( 'Vote reasons settings saved.', 'askro' ) . '</p>';
                echo '</div>';
            } );
        }
    }
}

