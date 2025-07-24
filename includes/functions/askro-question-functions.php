<?php
/**
 * Askro Question Helper Functions
 *
 * Collection of utility functions for managing question metadata,
 * including answer counts, view counts, and other statistics.
 *
 * @package    Askro
 * @subpackage Functions
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
 * Update answer count for a question
 *
 * @since 1.0.0
 * @param int $question_id Question post ID
 * @return void
 */
function askro_update_answer_count( $question_id ) {
    // Count published answers for this question
    $answer_count = get_posts( array(
        'post_type' => 'askro_answer',
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_askro_question_id',
                'value' => $question_id,
                'compare' => '='
            )
        ),
        'posts_per_page' => -1,
        'fields' => 'ids'
    ) );
    
    $count = is_array( $answer_count ) ? count( $answer_count ) : 0;
    
    // Update the meta field
    update_post_meta( $question_id, '_askro_answer_count', $count );
}

/**
 * Increment answer count for a question
 *
 * @since 1.0.0
 * @param int $question_id Question post ID
 * @return void
 */
function askro_increment_answer_count( $question_id ) {
    $current_count = get_post_meta( $question_id, '_askro_answer_count', true );
    $current_count = $current_count ? intval( $current_count ) : 0;
    update_post_meta( $question_id, '_askro_answer_count', $current_count + 1 );
}

/**
 * Decrement answer count for a question
 *
 * @since 1.0.0
 * @param int $question_id Question post ID
 * @return void
 */
function askro_decrement_answer_count( $question_id ) {
    $current_count = get_post_meta( $question_id, '_askro_answer_count', true );
    $current_count = $current_count ? intval( $current_count ) : 0;
    $new_count = max( 0, $current_count - 1 ); // Ensure it doesn't go below 0
    update_post_meta( $question_id, '_askro_answer_count', $new_count );
}

/**
 * Update view count for a question
 *
 * @since 1.0.0
 * @param int $question_id Question post ID
 * @return void
 */
function askro_increment_view_count( $question_id ) {
    $current_count = get_post_meta( $question_id, '_askro_view_count', true );
    $current_count = $current_count ? intval( $current_count ) : 0;
    update_post_meta( $question_id, '_askro_view_count', $current_count + 1 );
}

/**
 * Get answer count for a question
 *
 * @since 1.0.0
 * @param int $question_id Question post ID
 * @return int Number of answers
 */
function askro_get_answer_count( $question_id ) {
    $count = get_post_meta( $question_id, '_askro_answer_count', true );
    return $count ? intval( $count ) : 0;
}

/**
 * Get view count for a question
 *
 * @since 1.0.0
 * @param int $question_id Question post ID
 * @return int Number of views
 */
function askro_get_view_count( $question_id ) {
    $count = get_post_meta( $question_id, '_askro_view_count', true );
    return $count ? intval( $count ) : 0;
}

/**
 * Update all question answer counts (bulk operation)
 *
 * @since 1.0.0
 * @return void
 */
function askro_update_all_answer_counts() {
    $questions = get_posts( array(
        'post_type' => 'askro_question',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ) );
    
    foreach ( $questions as $question_id ) {
        askro_update_answer_count( $question_id );
    }
}

// Hook into answer status changes
add_action( 'transition_post_status', 'askro_handle_answer_status_change', 10, 3 );

/**
 * Handle answer status changes to update question answer counts
 *
 * @since 1.0.0
 * @param string $new_status New post status
 * @param string $old_status Old post status
 * @param WP_Post $post Post object
 * @return void
 */
function askro_handle_answer_status_change( $new_status, $old_status, $post ) {
    // Only handle answer posts
    if ( $post->post_type !== 'askro_answer' ) {
        return;
    }
    
    $question_id = get_post_meta( $post->ID, '_askro_question_id', true );
    if ( ! $question_id ) {
        return;
    }
    
    // Handle publish/unpublish transitions
    if ( $old_status !== 'publish' && $new_status === 'publish' ) {
        // Answer was published
        askro_increment_answer_count( $question_id );
    } elseif ( $old_status === 'publish' && $new_status !== 'publish' ) {
        // Answer was unpublished
        askro_decrement_answer_count( $question_id );
    }
}

// Hook into answer deletion
add_action( 'before_delete_post', 'askro_handle_answer_deletion' );

/**
 * Handle answer deletion to update question answer counts
 *
 * @since 1.0.0
 * @param int $post_id Post ID being deleted
 * @return void
 */
function askro_handle_answer_deletion( $post_id ) {
    $post = get_post( $post_id );
    
    // Only handle published answer posts
    if ( ! $post || $post->post_type !== 'askro_answer' || $post->post_status !== 'publish' ) {
        return;
    }
    
    $question_id = get_post_meta( $post_id, '_askro_question_id', true );
    if ( $question_id ) {
        askro_decrement_answer_count( $question_id );
    }
}

// Hook into single question view to increment view count
add_action( 'wp', 'askro_maybe_increment_view_count' );

/**
 * Maybe increment view count when viewing a single question
 *
 * @since 1.0.0
 * @return void
 */
function askro_maybe_increment_view_count() {
    if ( is_singular( 'askro_question' ) && ! is_user_logged_in() ) {
        // Only count views from non-logged-in users to avoid inflating counts
        $question_id = get_the_ID();
        if ( $question_id ) {
            askro_increment_view_count( $question_id );
        }
    }
}
