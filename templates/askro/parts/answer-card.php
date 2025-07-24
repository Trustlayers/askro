<?php
/**
 * Answer Card Template Part
 *
 * Displays a single answer card with voting, content, and author information.
 * This template is used both for initial page load and AJAX insertions.
 *
 * @package Askro
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// This template expects $answer to be available in the current scope
if ( ! isset( $answer ) || ! is_object( $answer ) ) {
    return;
}

$answer_id = $answer->ID;
$answer_author = get_userdata( $answer->post_author );
$cached_score = get_post_meta( $answer_id, '_askro_cached_score', true );
$cached_score = $cached_score ? intval( $cached_score ) : 0;

// Get current user's votes for this answer
$user_votes = array();
if ( is_user_logged_in() ) {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $user_vote_results = $wpdb->get_results( $wpdb->prepare(
        "SELECT vote_type FROM {$wpdb->prefix}askro_user_votes WHERE user_id = %d AND answer_id = %d",
        $current_user_id,
        $answer_id
    ));
    foreach ( $user_vote_results as $vote ) {
        $user_votes[] = $vote->vote_type;
    }
}

// Fetch vote reason presets
global $wpdb;
$vote_reasons = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}askro_vote_reason_presets WHERE is_active = 1 ORDER BY display_order ASC"
);
?>

<article class="answer-item" id="answer-<?php echo esc_attr( $answer_id ); ?>">
    <div class="answer-voting">
        <?php if ( is_user_logged_in() && $vote_reasons ) : ?>
            <div class="vote-buttons">
                <?php foreach ( $vote_reasons as $reason ) : 
                    $is_voted = in_array( $reason->vote_type, $user_votes );
                    $button_class = 'vote-button vote-' . esc_attr( $reason->vote_type );
                    if ( $is_voted ) {
                        $button_class .= ' voted';
                    }
                ?>
                    <button 
                        class="<?php echo esc_attr( $button_class ); ?>"
                        data-action="askro-vote"
                        data-answer-id="<?php echo esc_attr( $answer_id ); ?>"
                        data-vote-type="<?php echo esc_attr( $reason->vote_type ); ?>"
                        title="<?php echo esc_attr( $reason->description ); ?>"
                    >
                        <?php echo wp_kses_post( $reason->icon ); ?>
                        <span class="vote-label"><?php echo esc_html( $reason->label ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="vote-score-container">
            <span class="vote-score" data-score-for="<?php echo esc_attr( $answer_id ); ?>">
                <?php echo esc_html( $cached_score ); ?>
            </span>
            <span class="score-label"><?php esc_html_e( 'points', 'askro' ); ?></span>
        </div>
    </div>
    
    <div class="answer-content">
        <div class="answer-body">
            <?php echo wp_kses_post( wpautop( $answer->post_content ) ); ?>
        </div>
        
        <div class="answer-footer">
            <div class="answer-actions">
                <button class="share-answer" data-answer-id="<?php echo esc_attr( $answer_id ); ?>">
                    <?php esc_html_e( 'Share', 'askro' ); ?>
                </button>
                <?php if ( current_user_can( 'edit_post', $answer_id ) ) : ?>
                    <a href="<?php echo esc_url( get_edit_post_link( $answer_id ) ); ?>" class="edit-answer">
                        <?php esc_html_e( 'Edit', 'askro' ); ?>
                    </a>
                <?php endif; ?>
                <button class="flag-answer" data-answer-id="<?php echo esc_attr( $answer_id ); ?>">
                    <?php esc_html_e( 'Flag', 'askro' ); ?>
                </button>
            </div>
            
            <div class="answer-author">
                <div class="author-info">
                    <?php echo get_avatar( $answer_author->ID, 40, '', '', array( 'class' => 'author-avatar' ) ); ?>
                    <div class="author-details">
                        <a href="<?php echo esc_url( get_author_posts_url( $answer_author->ID ) ); ?>" class="author-name">
                            <?php echo esc_html( $answer_author->display_name ); ?>
                        </a>
                        <div class="author-reputation">
                            <?php 
                            // TODO: Implement reputation system
                            esc_html_e( '0 reputation', 'askro' ); 
                            ?>
                        </div>
                        <time class="answer-date" datetime="<?php echo esc_attr( get_the_date( 'c', $answer ) ); ?>">
                            <?php 
                            printf( 
                                esc_html__( 'answered %s', 'askro' ),
                                esc_html( human_time_diff( get_the_time( 'U', $answer ), current_time( 'timestamp' ) ) . ' ago' )
                            );
                            ?>
                        </time>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Comments Section -->
        <div class="answer-comments">
            <?php askro_display_comments( $answer_id ); ?>
        </div>
    </div>
</article>
