<?php
/**
 * Askro Database Manager
 *
 * Handles the creation and management of custom database tables for the
 * multi-dimensional voting and points system with support for AI-driven analysis.
 *
 * @package    Askro
 * @subpackage Database
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
 * Database Manager Class
 *
 * Manages the creation and maintenance of custom database tables for the
 * Askro plugin's voting system, points tracking, and related functionality.
 *
 * @since 1.0.0
 */
final class Askro_Database_Manager {

    /**
     * Private constructor to prevent direct instantiation
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Prevent direct instantiation
    }

    /**
     * Create all custom database tables for the plugin
     *
     * This method creates the core database tables needed for the voting system,
     * points tracking, and vote configuration. Uses WordPress dbDelta function
     * for safe table creation and updates.
     *
     * @since 1.0.0
     * @return void
     */
    public static function create_tables() {
        global $wpdb;

        // Require the upgrade file for dbDelta function
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // Get the WordPress database charset and collation
        $charset_collate = $wpdb->get_charset_collate();

        // Create user votes table
        self::create_user_votes_table( $wpdb, $charset_collate );

        // Create vote weights table
        self::create_vote_weights_table( $wpdb, $charset_collate );

        // Create points log table
        self::create_points_log_table( $wpdb, $charset_collate );

        // Create vote reason presets table
        self::create_vote_reason_presets_table( $wpdb, $charset_collate );

        // Create comments table
        self::create_comments_table( $wpdb, $charset_collate );

        // Insert default data
        self::insert_default_data();
    }

    /**
     * Create the user votes table
     *
     * Stores individual vote records with support for multiple vote types,
     * strength levels, and contextual scoring for AI analysis.
     *
     * @since 1.0.0
     * @param wpdb   $wpdb WordPress database object
     * @param string $charset_collate Database charset and collation
     * @return void
     */
    private static function create_user_votes_table( $wpdb, $charset_collate ) {
        $table_name = $wpdb->prefix . 'askro_user_votes';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            target_user_id BIGINT UNSIGNED NOT NULL,
            vote_type ENUM('useful', 'creative', 'emotional', 'toxic', 'offtopic', 'funny', 'deep') NOT NULL,
            vote_strength TINYINT NOT NULL,
            context_score FLOAT(5,2) DEFAULT 0.00,
            meta JSON,
            voted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_user_idx (post_id, user_id),
            KEY target_user_idx (target_user_id)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Create the vote weights table
     *
     * Defines the point changes for different vote types and strengths,
     * supporting dynamic weight adjustments for both voters and targets.
     *
     * @since 1.0.0
     * @param wpdb   $wpdb WordPress database object
     * @param string $charset_collate Database charset and collation
     * @return void
     */
    private static function create_vote_weights_table( $wpdb, $charset_collate ) {
        $table_name = $wpdb->prefix . 'askro_vote_weights';

        $sql = "CREATE TABLE {$table_name} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            vote_type ENUM('useful', 'creative', 'emotional', 'toxic', 'offtopic', 'funny', 'deep') NOT NULL,
            vote_strength TINYINT NOT NULL,
            point_change_for_voter INT NOT NULL DEFAULT 0,
            point_change_for_target INT NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY type_strength_idx (vote_type, vote_strength)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Create the points log table
     *
     * Tracks all point changes with detailed context for auditing and analysis.
     * Supports multiple sources and reasons for point changes.
     *
     * @since 1.0.0
     * @param wpdb   $wpdb WordPress database object
     * @param string $charset_collate Database charset and collation
     * @return void
     */
    private static function create_points_log_table( $wpdb, $charset_collate ) {
        $table_name = $wpdb->prefix . 'askro_points_log';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            source_user_id BIGINT UNSIGNED,
            points_change INT NOT NULL,
            reason_key VARCHAR(50) NOT NULL,
            related_type ENUM('answer', 'question', 'vote', 'comment', 'achievement', 'system') NOT NULL,
            related_id BIGINT UNSIGNED,
            context JSON,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_reason_idx (user_id, reason_key)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Create the vote reason presets table
     *
     * Stores predefined vote reasons with UI configuration for different
     * vote types, including icons, colors, and descriptions.
     *
     * @since 1.0.0
     * @param wpdb   $wpdb WordPress database object
     * @param string $charset_collate Database charset and collation
     * @return void
     */
    private static function create_vote_reason_presets_table( $wpdb, $charset_collate ) {
        $table_name = $wpdb->prefix . 'askro_vote_reason_presets';

        $sql = "CREATE TABLE {$table_name} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            vote_type ENUM('useful', 'creative', 'emotional', 'toxic', 'offtopic', 'funny', 'deep') NOT NULL,
            title VARCHAR(100) NOT NULL,
            description VARCHAR(255),
            icon VARCHAR(50),
            color VARCHAR(20),
            is_active BOOLEAN NOT NULL DEFAULT true,
            PRIMARY KEY (id),
            KEY type_active_idx (vote_type, is_active)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Create the comments table
     *
     * Stores custom comments for questions and answers with support for
     * nested commenting and moderation status tracking.
     *
     * @since 1.0.0
     * @param wpdb   $wpdb WordPress database object
     * @param string $charset_collate Database charset and collation
     * @return void
     */
    private static function create_comments_table( $wpdb, $charset_collate ) {
        $table_name = $wpdb->prefix . 'askro_comments';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            parent_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            content TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'approved',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id_idx (post_id, status),
            KEY user_id_idx (user_id),
            KEY parent_id_idx (parent_id)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Insert default data into the database tables
     *
     * Populates the vote weights and reason presets tables with initial
     * configuration data for the voting system.
     *
     * @since 1.0.0
     * @return void
     */
    private static function insert_default_data() {
        global $wpdb;

        // Insert default vote weights
        self::insert_default_vote_weights( $wpdb );

        // Insert default vote reason presets
        self::insert_default_vote_reason_presets( $wpdb );
    }

    /**
     * Insert default vote weights configuration
     *
     * Sets up the initial point values for different vote types and strengths.
     * Positive votes generally benefit both voter and target, while negative
     * votes may penalize the target.
     *
     * @since 1.0.0
     * @param wpdb $wpdb WordPress database object
     * @return void
     */
    private static function insert_default_vote_weights( $wpdb ) {
        $table_name = $wpdb->prefix . 'askro_vote_weights';

        // Check if data already exists to prevent duplicates
        $existing_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
        if ( $existing_count > 0 ) {
            return;
        }

        $default_weights = array(
            // Useful votes - high positive impact
            array( 'useful', 1, 1, 5 ),
            array( 'useful', 2, 2, 10 ),
            array( 'useful', 3, 3, 15 ),
            
            // Creative votes - moderate positive impact
            array( 'creative', 1, 1, 3 ),
            array( 'creative', 2, 2, 6 ),
            array( 'creative', 3, 3, 10 ),
            
            // Emotional votes - low positive impact
            array( 'emotional', 1, 1, 2 ),
            array( 'emotional', 2, 2, 4 ),
            array( 'emotional', 3, 3, 6 ),
            
            // Funny votes - low positive impact
            array( 'funny', 1, 1, 2 ),
            array( 'funny', 2, 2, 4 ),
            array( 'funny', 3, 3, 6 ),
            
            // Deep votes - high positive impact
            array( 'deep', 1, 1, 8 ),
            array( 'deep', 2, 2, 12 ),
            array( 'deep', 3, 3, 20 ),
            
            // Toxic votes - negative impact on target
            array( 'toxic', 1, 0, -2 ),
            array( 'toxic', 2, 0, -5 ),
            array( 'toxic', 3, 0, -10 ),
            
            // Off-topic votes - negative impact on target
            array( 'offtopic', 1, 0, -1 ),
            array( 'offtopic', 2, 0, -3 ),
            array( 'offtopic', 3, 0, -6 ),
        );

        foreach ( $default_weights as $weight ) {
            $wpdb->insert(
                $table_name,
                array(
                    'vote_type' => $weight[0],
                    'vote_strength' => $weight[1],
                    'point_change_for_voter' => $weight[2],
                    'point_change_for_target' => $weight[3],
                ),
                array( '%s', '%d', '%d', '%d' )
            );
        }
    }

    /**
     * Insert default vote reason presets
     *
     * Creates the initial UI configuration for vote types including
     * titles, descriptions, icons, and color schemes.
     *
     * @since 1.0.0
     * @param wpdb $wpdb WordPress database object
     * @return void
     */
    private static function insert_default_vote_reason_presets( $wpdb ) {
        $table_name = $wpdb->prefix . 'askro_vote_reason_presets';

        // Check if data already exists to prevent duplicates
        $existing_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
        if ( $existing_count > 0 ) {
            return;
        }

        $default_presets = array(
            array(
                'vote_type' => 'useful',
                'title' => 'Useful',
                'description' => 'This content is helpful and valuable',
                'icon' => 'thumbs-up',
                'color' => '#22c55e',
            ),
            array(
                'vote_type' => 'creative',
                'title' => 'Creative',
                'description' => 'This content shows creativity and innovation',
                'icon' => 'lightbulb',
                'color' => '#f59e0b',
            ),
            array(
                'vote_type' => 'emotional',
                'title' => 'Emotional',
                'description' => 'This content evokes strong emotions',
                'icon' => 'heart',
                'color' => '#ec4899',
            ),
            array(
                'vote_type' => 'funny',
                'title' => 'Funny',
                'description' => 'This content is humorous and entertaining',
                'icon' => 'smile',
                'color' => '#facc15',
            ),
            array(
                'vote_type' => 'deep',
                'title' => 'Deep',
                'description' => 'This content provides profound insights',
                'icon' => 'brain',
                'color' => '#6366f1',
            ),
            array(
                'vote_type' => 'toxic',
                'title' => 'Toxic',
                'description' => 'This content is harmful or inappropriate',
                'icon' => 'warning',
                'color' => '#ef4444',
            ),
            array(
                'vote_type' => 'offtopic',
                'title' => 'Off Topic',
                'description' => 'This content is not relevant to the discussion',
                'icon' => 'arrow-right',
                'color' => '#6b7280',
            ),
        );

        foreach ( $default_presets as $preset ) {
            $wpdb->insert(
                $table_name,
                $preset,
                array( '%s', '%s', '%s', '%s', '%s' )
            );
        }
    }

    /**
     * Drop all plugin-related database tables
     *
     * Used during plugin uninstallation to clean up the database.
     * This method should be called with caution as it permanently removes data.
     *
     * @since 1.0.0
     * @return void
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'askro_user_votes',
            $wpdb->prefix . 'askro_vote_weights',
            $wpdb->prefix . 'askro_points_log',
            $wpdb->prefix . 'askro_vote_reason_presets',
            $wpdb->prefix . 'askro_comments',
        );

        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
        }
    }

    /**
     * Get the current database version
     *
     * Retrieves the stored database version for migration purposes.
     *
     * @since 1.0.0
     * @return string The current database version
     */
    public static function get_db_version() {
        return get_option( 'askro_db_version', '1.0.0' );
    }

    /**
     * Update the database version
     *
     * Updates the stored database version after successful migrations.
     *
     * @since 1.0.0
     * @param string $version The new database version
     * @return void
     */
    public static function update_db_version( $version ) {
        update_option( 'askro_db_version', $version );
    }
}
