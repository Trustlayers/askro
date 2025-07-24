<?php
/**
 * AJAX Voting Handler Class
 *
 * Handles AJAX requests for answer voting functionality.
 * Processes vote submissions, retractions, and score calculations.
 *
 * @package Askro
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Askro Voting Handler Class
 */
class Askro_Voting_Handler {

    /**
     * Initialize the voting handler
     *
     * @since 1.0.0
     */
    public static function init() {
        // Register AJAX actions for logged-in users
        add_action( 'wp_ajax_askro_handle_vote', array( __CLASS__, 'handle_vote' ) );
        
        // Optionally allow non-logged-in users (they'll get an error response)
        add_action( 'wp_ajax_nopriv_askro_handle_vote', array( __CLASS__, 'handle_vote' ) );
    }

    /**
     * Handle vote AJAX request
     *
     * Processes vote submissions, retractions, and score updates.
     *
     * @since 1.0.0
     */
    public static function handle_vote() {
        global $wpdb;

        // Security: Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'askro_voting_nonce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed. Please refresh the page and try again.', 'askro' )
            ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'You must be logged in to vote.', 'askro' )
            ) );
        }

        // Get and sanitize input
        $answer_id = isset( $_POST['answer_id'] ) ? intval( $_POST['answer_id'] ) : 0;
        $vote_type = isset( $_POST['vote_type'] ) ? sanitize_text_field( $_POST['vote_type'] ) : '';

        if ( ! $answer_id || ! $vote_type ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid vote data provided.', 'askro' )
            ) );
        }

        // Verify answer exists and is published
        $answer = get_post( $answer_id );
        if ( ! $answer || $answer->post_type !== 'answer' || $answer->post_status !== 'publish' ) {
            wp_send_json_error( array(
                'message' => __( 'Answer not found or not available for voting.', 'askro' )
            ) );
        }

        // Verify vote type exists and is active
        $vote_reason = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}askro_vote_reason_presets WHERE vote_type = %s AND is_active = 1",
            $vote_type
        ) );

        if ( ! $vote_reason ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid vote type.', 'askro' )
            ) );
        }

        $current_user_id = get_current_user_id();
        $answer_author_id = $answer->post_author;

        // Prevent self-voting
        if ( $current_user_id == $answer_author_id ) {
            wp_send_json_error( array(
                'message' => __( 'You cannot vote on your own answers.', 'askro' )
            ) );
        }

        // Check for existing vote
        $existing_vote = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}askro_user_votes WHERE user_id = %d AND answer_id = %d AND vote_type = %s",
            $current_user_id,
            $answer_id,
            $vote_type
        ) );

        $action_taken = '';
        $vote_weight = 0;

        if ( $existing_vote ) {
            // Handle vote retraction (undo)
            $deleted = $wpdb->delete(
                $wpdb->prefix . 'askro_user_votes',
                array(
                    'user_id' => $current_user_id,
                    'answer_id' => $answer_id,
                    'vote_type' => $vote_type
                ),
                array( '%d', '%d', '%s' )
            );

            if ( $deleted === false ) {
                wp_send_json_error( array(
                    'message' => __( 'Failed to retract vote. Please try again.', 'askro' )
                ) );
            }

            $action_taken = 'retracted';
            $vote_weight = -intval( $existing_vote->vote_strength ); // Negative to subtract

            // Log point changes for retraction
            self::log_point_change( $current_user_id, 'vote_retracted', -intval( $vote_reason->voter_points ), $answer_id );
            self::log_point_change( $answer_author_id, 'vote_received_retracted', -intval( $vote_reason->author_points ), $answer_id );

        } else {
            // Handle new vote
            // First, remove any other vote types from the same user on the same answer
            $wpdb->delete(
                $wpdb->prefix . 'askro_user_votes',
                array(
                    'user_id' => $current_user_id,
                    'answer_id' => $answer_id
                ),
                array( '%d', '%d' )
            );

            // Get vote weight from settings
            $vote_weight_setting = $wpdb->get_row( $wpdb->prepare(
                "SELECT vote_strength FROM {$wpdb->prefix}askro_vote_weights WHERE vote_type = %s",
                $vote_type
            ) );

            $vote_strength = $vote_weight_setting ? intval( $vote_weight_setting->vote_strength ) : 1;

            // Insert new vote
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'askro_user_votes',
                array(
                    'user_id' => $current_user_id,
                    'answer_id' => $answer_id,
                    'vote_type' => $vote_type,
                    'vote_strength' => $vote_strength,
                    'created_at' => current_time( 'mysql' )
                ),
                array( '%d', '%d', '%s', '%d', '%s' )
            );

            if ( $inserted === false ) {
                wp_send_json_error( array(
                    'message' => __( 'Failed to submit vote. Please try again.', 'askro' )
                ) );
            }

            $action_taken = 'voted';
            $vote_weight = $vote_strength;

            // Log point changes for new vote
            self::log_point_change( $current_user_id, 'vote_cast', intval( $vote_reason->voter_points ), $answer_id );
            self::log_point_change( $answer_author_id, 'vote_received', intval( $vote_reason->author_points ), $answer_id );
        }

        // Recalculate and cache the total score for the answer
        $new_score = self::recalculate_answer_score( $answer_id );

        // Send success response
        wp_send_json_success( array(
            'new_score' => $new_score,
            'action' => $action_taken,
            'message' => $action_taken === 'voted' 
                ? __( 'Vote submitted successfully!', 'askro' )
                : __( 'Vote retracted successfully!', 'askro' )
        ) );
    }

    /**
     * Recalculate the total score for an answer
     *
     * @since 1.0.0
     * @param int $answer_id Answer ID
     * @return int New total score
     */
    private static function recalculate_answer_score( $answer_id ) {
        global $wpdb;

        // Sum all vote strengths for this answer
        $total_score = $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(vote_strength), 0) FROM {$wpdb->prefix}askro_user_votes WHERE answer_id = %d",
            $answer_id
        ) );

        $total_score = intval( $total_score );

        // Update the cached score meta field
        update_post_meta( $answer_id, '_askro_cached_score', $total_score );

        return $total_score;
    }

    /**
     * Log point changes for users
     *
     * @since 1.0.0
     * @param int    $user_id User ID
     * @param string $action Action type
     * @param int    $points Points to add/subtract
     * @param int    $answer_id Answer ID for reference
     */
    private static function log_point_change( $user_id, $action, $points, $answer_id ) {
        global $wpdb;

        if ( $points == 0 ) {
            return; // No point change to log
        }

        // Insert into points log
        $wpdb->insert(
            $wpdb->prefix . 'askro_points_log',
            array(
                'user_id' => $user_id,
                'action' => $action,
                'points' => $points,
                'reference_id' => $answer_id,
                'reference_type' => 'answer',
                'created_at' => current_time( 'mysql' )
            ),
            array( '%d', '%s', '%d', '%d', '%s', '%s' )
        );

        // Update user's total points (assuming we have a user points system)
        $current_points = get_user_meta( $user_id, 'askro_total_points', true );
        $current_points = $current_points ? intval( $current_points ) : 0;
        $new_total = $current_points + $points;
        
        update_user_meta( $user_id, 'askro_total_points', $new_total );
    }

    /**
     * Get user's current votes for an answer
     *
     * @since 1.0.0
     * @param int $user_id User ID
     * @param int $answer_id Answer ID
     * @return array Array of vote types the user has cast
     */
    public static function get_user_votes( $user_id, $answer_id ) {
        global $wpdb;

        $votes = $wpdb->get_col( $wpdb->prepare(
            "SELECT vote_type FROM {$wpdb->prefix}askro_user_votes WHERE user_id = %d AND answer_id = %d",
            $user_id,
            $answer_id
        ) );

        return $votes ? $votes : array();
    }

    /**
     * Get answer score
     *
     * @since 1.0.0
     * @param int $answer_id Answer ID
     * @return int Answer score
     */
    public static function get_answer_score( $answer_id ) {
        $cached_score = get_post_meta( $answer_id, '_askro_cached_score', true );
        
        if ( $cached_score === '' ) {
            // No cached score, calculate and cache it
            return self::recalculate_answer_score( $answer_id );
        }

        return intval( $cached_score );
    }
}
