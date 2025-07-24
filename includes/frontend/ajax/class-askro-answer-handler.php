<?php
/**
 * AJAX Answer Handler Class
 *
 * Handles AJAX requests for submitting answers.
 *
 * @package Askro
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Askro Answer Handler Class
 */
class Askro_Answer_Handler {

    /**
     * Initialize the answer handler
     *
     * @since 1.0.0
     */
    public static function init() {
        // Register AJAX action for logged-in users
        add_action( 'wp_ajax_askro_submit_answer', array( __CLASS__, 'handle_submit_answer' ) );
    }

    /**
     * Handle submit answer AJAX request
     *
     * Processes answer submissions and returns the rendered answer HTML.
     *
     * @since 1.0.0
     */
    public static function handle_submit_answer() {
        // Security: Verify nonce
        if ( ! isset( $_POST['askro_answer_nonce'] ) || ! wp_verify_nonce( $_POST['askro_answer_nonce'], 'askro_submit_answer' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed. Please refresh the page and try again.', 'askro' )
            ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'You must be logged in to submit an answer.', 'askro' )
            ) );
        }

        // Get and sanitize input
        $question_id = isset( $_POST['question_id'] ) ? intval( $_POST['question_id'] ) : 0;
        $answer_content = isset( $_POST['answer_content'] ) ? wp_kses_post( trim( $_POST['answer_content'] ) ) : '';

        if ( ! $question_id || empty( $answer_content ) ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid data provided. Please ensure all fields are filled out.', 'askro' )
            ) );
        }

        // Insert the answer post
        $answer_id = wp_insert_post( array(
            'post_type' => 'answer',
            'post_status' => 'publish',
            'post_parent' => $question_id,
            'post_content' => $answer_content,
            'post_author' => get_current_user_id()
        ) );

        if ( is_wp_error( $answer_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Failed to submit your answer. Please try again.', 'askro' )
            ) );
        }

        // Fetch points for answering
        global $wpdb;
        $points_row = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}askro_vote_weights WHERE action = 'submit_answer'" );
        $points = $points_row ? intval( $points_row->points ) : 0;

        // Log points for the user
        self::log_point_change( get_current_user_id(), $points, $answer_id );

        // Generate the answer HTML using the template loader
        $new_answer_html = self::render_answer_card( $answer_id );

        // Send success response with the new answer HTML
        wp_send_json_success( array(
            'new_answer_html' => $new_answer_html
        ) );
    }

    /**
     * Log point changes for users
     *
     * @since 1.0.0
     * @param int $user_id User ID
     * @param int $points Points to add
     * @param int $answer_id Answer ID for reference
     */
    private static function log_point_change( $user_id, $points, $answer_id ) {
        global $wpdb;

        // Insert into points log
        $wpdb->insert(
            $wpdb->prefix . 'askro_points_log',
            array(
                'user_id' => $user_id,
                'action' => 'submit_answer',
                'points' => $points,
                'reference_id' => $answer_id,
                'reference_type' => 'answer',
                'created_at' => current_time( 'mysql' )
            ),
            array( '%d', '%s', '%d', '%d', '%s', '%s' )
        );

        // Update user's total points
        $current_points = get_user_meta( $user_id, 'askro_total_points', true );
        $current_points = $current_points ? intval( $current_points ) : 0;
        $new_total = $current_points + $points;
        
        update_user_meta( $user_id, 'askro_total_points', $new_total );
    }

    /**
     * Render answer card HTML
     *
     * @since 1.0.0
     * @param int $answer_id Answer ID
     * @return string Rendered HTML
     */
    private static function render_answer_card( $answer_id ) {
        $answer = get_post( $answer_id );
        if ( ! $answer ) {
            return '';
        }

        // Use WordPress template hierarchy to find the template
        $template_name = 'askro/parts/answer-card.php';
        
        // Check theme first, then plugin
        $template_path = locate_template( array( 'askro/' . $template_name, $template_name ) );
        
        if ( ! $template_path ) {
            // Fall back to plugin template
            $plugin_template = ASKRO_PLUGIN_DIR . 'templates/' . $template_name;
            if ( file_exists( $plugin_template ) ) {
                $template_path = $plugin_template;
            }
        }
        
        if ( ! $template_path ) {
            return '';
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }
}
