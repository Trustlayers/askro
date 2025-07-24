<?php
/**
 * Askro Profile AJAX Handler
 *
 * Handles AJAX requests for user profile data including tab content
 * and reputation history for chart visualization.
 *
 * @package    Askro
 * @subpackage Frontend\Ajax
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Profile AJAX Handler Class
 *
 * Manages all AJAX operations for user profile pages including
 * tab content loading and reputation chart data.
 *
 * @since 1.0.0
 */
class Askro_Profile_Handler {

    /**
     * Initialize WordPress hooks
     *
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        add_action( 'wp_ajax_askro_get_profile_tab_content', array( __CLASS__, 'handle_get_profile_tab_content' ) );
        add_action( 'wp_ajax_askro_get_reputation_history', array( __CLASS__, 'handle_get_reputation_history' ) );
    }

    /**
     * Handle AJAX request to get profile tab content
     *
     * @since 1.0.0
     * @return void
     */
    public static function handle_get_profile_tab_content() {
        // Verify nonce
        check_ajax_referer( 'askro_nonce', 'security' );

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'You must be logged in to view profile.' ) );
        }

        // Get parameters
        $tab = sanitize_text_field( $_POST['tab'] ?? 'activity' );
        $paged = absint( $_POST['paged'] ?? 1 );
        $user_id = get_current_user_id();

        // Capture output
        ob_start();

        switch ( $tab ) {
            case 'activity':
                self::render_activity_tab( $user_id, $paged );
                break;
            case 'questions':
                self::render_questions_tab( $user_id, $paged );
                break;
            case 'answers':
                self::render_answers_tab( $user_id, $paged );
                break;
            default:
                echo '<p>' . esc_html__( 'Invalid tab selected.', 'askro' ) . '</p>';
                break;
        }

        $html = ob_get_clean();

        wp_send_json_success( array( 'html' => $html ) );
    }

    /**
     * Handle AJAX request to get reputation history
     *
     * @since 1.0.0
     * @return void
     */
    public static function handle_get_reputation_history() {
        // Verify nonce
        check_ajax_referer( 'askro_nonce', 'security' );

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'You must be logged in to view reputation history.' ) );
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'askro_points_log';

        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
            wp_send_json_success( array( 
                'labels' => array(), 
                'data' => array() 
            ) );
        }

        // Get point changes for the last 30 days
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(created_at) as date, SUM(points_change) as daily_change 
             FROM {$table_name} 
             WHERE user_id = %d 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
             GROUP BY DATE(created_at) 
             ORDER BY date ASC",
            $user_id
        ) );

        $labels = array();
        $data = array();
        $cumulative_points = 0;

        // Process data into arrays for Chart.js
        foreach ( $results as $result ) {
            $labels[] = date( 'M j', strtotime( $result->date ) );
            $cumulative_points += $result->daily_change;
            $data[] = $cumulative_points;
        }

        wp_send_json_success( array( 
            'labels' => $labels, 
            'data' => $data 
        ) );
    }

    /**
     * Render activity tab content
     *
     * @since 1.0.0
     * @param int $user_id User ID
     * @param int $paged Page number
     * @return void
     */
    private static function render_activity_tab( $user_id, $paged ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'askro_points_log';

        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
            echo '<p>' . esc_html__( 'No activity data available.', 'askro' ) . '</p>';
            return;
        }

        $per_page = 10;
        $offset = ( $paged - 1 ) * $per_page;

        $activities = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ) );

        if ( $activities ) {
            echo '<div class="askro-activity-list">';
            foreach ( $activities as $activity ) {
                echo '<div class="activity-item">';
                echo '<span class="activity-type">' . esc_html( $activity->reason_key ) . '</span>';
                echo '<span class="activity-points">' . ($activity->points_change > 0 ? '+' : '') . esc_html( $activity->points_change ) . ' points</span>';
                echo '<span class="activity-date">' . esc_html( human_time_diff( strtotime( $activity->created_at ), current_time( 'timestamp' ) ) ) . ' ago</span>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p>' . esc_html__( 'No recent activity.', 'askro' ) . '</p>';
        }
    }

    /**
     * Render questions tab content
     *
     * @since 1.0.0
     * @param int $user_id User ID
     * @param int $paged Page number
     * @return void
     */
    private static function render_questions_tab( $user_id, $paged ) {
        $query_args = array(
            'post_type' => 'question',
            'post_status' => 'publish',
            'author' => $user_id,
            'paged' => $paged,
            'posts_per_page' => 10
        );

        $query = new WP_Query( $query_args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                // Load the question card template part from plugin
                include ASKRO_PLUGIN_DIR . 'templates/askro/parts/question-card.php';
            }
            wp_reset_postdata();

            // Add pagination if needed
            if ( $query->max_num_pages > 1 ) {
                echo '<div class="askro-pagination">';
                echo paginate_links( array(
                    'total' => $query->max_num_pages,
                    'current' => $paged,
                    'type' => 'list'
                ) );
                echo '</div>';
            }
        } else {
            echo '<p>' . esc_html__( 'No questions found.', 'askro' ) . '</p>';
        }
    }

    /**
     * Render answers tab content
     *
     * @since 1.0.0
     * @param int $user_id User ID
     * @param int $paged Page number
     * @return void
     */
    private static function render_answers_tab( $user_id, $paged ) {
        $query_args = array(
            'post_type' => 'answer',
            'post_status' => 'publish',
            'author' => $user_id,
            'paged' => $paged,
            'posts_per_page' => 10
        );

        $query = new WP_Query( $query_args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                // Check if answer card template exists, otherwise use a simple display
                $answer_card_path = ASKRO_PLUGIN_DIR . 'templates/askro/parts/answer-card.php';
                if ( file_exists( $answer_card_path ) ) {
                    include $answer_card_path;
                } else {
                    // Simple answer display if template doesn't exist
                    echo '<div class="answer-item">';
                    echo '<h4><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h4>';
                    echo '<p>' . esc_html( wp_trim_words( get_the_content(), 30 ) ) . '</p>';
                    echo '<span class="answer-date">' . esc_html( get_the_date() ) . '</span>';
                    echo '</div>';
                }
            }
            wp_reset_postdata();

            // Add pagination if needed
            if ( $query->max_num_pages > 1 ) {
                echo '<div class="askro-pagination">';
                echo paginate_links( array(
                    'total' => $query->max_num_pages,
                    'current' => $paged,
                    'type' => 'list'
                ) );
                echo '</div>';
            }
        } else {
            echo '<p>' . esc_html__( 'No answers found.', 'askro' ) . '</p>';
        }
    }
}
