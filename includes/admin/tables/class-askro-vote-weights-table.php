<?php
/**
 * Askro Vote Weights Table
 *
 * Custom WP_List_Table implementation for managing vote weights in the
 * Askro plugin admin interface with inline editing capabilities.
 *
 * @package    Askro
 * @subpackage Admin/Tables
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

// Include WP_List_Table if not already loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Vote Weights Table Class
 *
 * Extends WP_List_Table to provide a structured interface for managing
 * vote weight configurations with inline editing capabilities.
 *
 * @since 1.0.0
 */
class Askro_Vote_Weights_Table extends WP_List_Table {

    /**
     * Constructor
     *
     * Sets up the table with appropriate singular and plural names
     * for proper WordPress admin interface integration.
     *
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct( array(
            'singular' => 'weight',
            'plural'   => 'weights',
            'ajax'     => false
        ) );
    }

    /**
     * Get table columns
     *
     * Defines the columns that will be displayed in the admin table.
     *
     * @since 1.0.0
     * @return array Associative array of column slugs and labels
     */
    public function get_columns() {
        return array(
            'vote_type'               => __( 'Vote Type', 'askro' ),
            'vote_strength'           => __( 'Strength', 'askro' ),
            'point_change_for_voter'  => __( 'Points for Voter', 'askro' ),
            'point_change_for_target' => __( 'Points for Target', 'askro' ),
            'updated_at'              => __( 'Last Updated', 'askro' )
        );
    }

    /**
     * Prepare table items
     *
     * Fetches vote weight data from the database and prepares it for display.
     *
     * @since 1.0.0
     * @return void
     */
    public function prepare_items() {
        global $wpdb;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $table_name = $wpdb->prefix . 'askro_vote_weights';
        $results = $wpdb->get_results( 
            "SELECT * FROM {$table_name} ORDER BY vote_type, vote_strength", 
            ARRAY_A 
        );

        $this->items = $results;
    }

    /**
     * Default column renderer
     *
     * Handles rendering of columns that don't have a specific column method.
     *
     * @since 1.0.0
     * @param array  $item        The current item data
     * @param string $column_name The column name
     * @return string The column content
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'vote_type':
                return ucfirst( str_replace( '_', ' ', $item[ $column_name ] ) );
            case 'vote_strength':
                return $item[ $column_name ];
            case 'updated_at':
                return mysql2date( 'Y/m/d g:i:s A', $item[ $column_name ] );
            default:
                return $item[ $column_name ];
        }
    }

    /**
     * Render editable points for voter column
     *
     * Creates an editable number input field for the points awarded to voters.
     *
     * @since 1.0.0
     * @param array $item The current item data
     * @return string HTML input field
     */
    public function column_point_change_for_voter( $item ) {
        return sprintf(
            '<input type="number" name="weights[%d][voter]" value="%d" min="-100" max="100" step="1" class="small-text" />',
            $item['id'],
            $item['point_change_for_voter']
        );
    }

    /**
     * Render editable points for target column
     *
     * Creates an editable number input field for the points awarded to vote targets.
     *
     * @since 1.0.0
     * @param array $item The current item data
     * @return string HTML input field
     */
    public function column_point_change_for_target( $item ) {
        return sprintf(
            '<input type="number" name="weights[%d][target]" value="%d" min="-100" max="100" step="1" class="small-text" />',
            $item['id'],
            $item['point_change_for_target']
        );
    }

    /**
     * Render vote type column with badge styling
     *
     * Displays vote types with visual styling for better user experience.
     *
     * @since 1.0.0
     * @param array $item The current item data
     * @return string HTML with styled vote type
     */
    public function column_vote_type( $item ) {
        $vote_type = $item['vote_type'];
        $display_name = ucfirst( str_replace( '_', ' ', $vote_type ) );
        
        // Define color classes for different vote types
        $color_classes = array(
            'useful'    => 'background: #22c55e; color: white;',
            'creative'  => 'background: #f59e0b; color: white;',
            'emotional' => 'background: #ec4899; color: white;',
            'funny'     => 'background: #facc15; color: black;',
            'deep'      => 'background: #6366f1; color: white;',
            'toxic'     => 'background: #ef4444; color: white;',
            'offtopic'  => 'background: #6b7280; color: white;'
        );

        $style = isset( $color_classes[ $vote_type ] ) ? $color_classes[ $vote_type ] : 'background: #9ca3af; color: white;';

        return sprintf(
            '<span style="%s padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">%s</span>',
            $style,
            $display_name
        );
    }

    /**
     * Render vote strength column with visual indicators
     *
     * Displays vote strength with visual indicators for better readability.
     *
     * @since 1.0.0
     * @param array $item The current item data
     * @return string HTML with styled strength indicator
     */
    public function column_vote_strength( $item ) {
        $strength = $item['vote_strength'];
        $stars = str_repeat( '★', $strength ) . str_repeat( '☆', 3 - $strength );
        
        return sprintf(
            '<span title="%s">%s <small>(%d)</small></span>',
            sprintf( __( 'Strength Level %d', 'askro' ), $strength ),
            $stars,
            $strength
        );
    }

    /**
     * Display message when no items are found
     *
     * Shows a user-friendly message when the table is empty.
     *
     * @since 1.0.0
     * @return void
     */
    public function no_items() {
        _e( 'No vote weights found. Please ensure the database tables are properly created.', 'askro' );
    }
}
