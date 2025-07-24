<?php
/**
 * Answer Section Template Part
 *
 * Displays all answers to the current question with sorting options,
 * voting controls, and answer management features.
 *
 * @package Askro
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Fetch answers for this question
$answers = get_posts(array(
    'post_type' => 'answer',
    'post_parent' => get_the_ID(),
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'meta_value_num',
    'meta_key' => '_askro_cached_score',
    'order' => 'DESC',
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => '_askro_cached_score',
            'compare' => 'EXISTS'
        ),
        array(
            'key' => '_askro_cached_score',
            'compare' => 'NOT EXISTS'
        )
    )
));

$answer_count = count($answers);

// Fetch vote reason presets
global $wpdb;
$vote_reasons = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}askro_vote_reason_presets WHERE is_active = 1 ORDER BY display_order ASC"
);
?>

<section class="askro-answer-section" id="answers">
    <div class="answers-header">
        <h2 class="answers-title">
            <?php
            if ( $answer_count > 0 ) {
                printf(
                    _n( '%d Answer', '%d Answers', $answer_count, 'askro' ),
                    $answer_count
                );
            } else {
                esc_html_e( 'Answers', 'askro' );
            }
            ?>
        </h2>
        
        <?php if ( $answer_count > 0 ) : ?>
            <div class="answers-sorting">
                <label for="answer-sort" class="sort-label">
                    <?php esc_html_e( 'Sort by:', 'askro' ); ?>
                </label>
                <select id="answer-sort" class="sort-dropdown">
                    <option value="votes"><?php esc_html_e( 'Highest voted', 'askro' ); ?></option>
                    <option value="newest"><?php esc_html_e( 'Newest first', 'askro' ); ?></option>
                    <option value="oldest"><?php esc_html_e( 'Oldest first', 'askro' ); ?></option>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <div class="answers-list" id="answer-list-container">
        <?php if ( $answer_count > 0 ) : ?>
            <?php foreach ( $answers as $answer ) : 
                // Load the answer card template part
                $template_name = 'askro/parts/answer-card.php';
                $template_path = locate_template( array( 'askro/' . $template_name, $template_name ) );
                
                if ( ! $template_path ) {
                    // Fall back to plugin template
                    $plugin_template = ASKRO_PLUGIN_DIR . 'templates/' . $template_name;
                    if ( file_exists( $plugin_template ) ) {
                        $template_path = $plugin_template;
                    }
                }
                
                if ( $template_path ) {
                    include $template_path;
                }
            endforeach;
            wp_reset_postdata();
            ?>
        <?php else : ?>
            <div class="no-answers">
                <div class="no-answers-icon">❓</div>
                <div class="no-answers-content">
                    <h3 class="no-answers-title">
                        <?php esc_html_e( 'No answers yet', 'askro' ); ?>
                    </h3>
                    <p class="no-answers-description">
                        <?php esc_html_e( 'Be the first to answer this question!', 'askro' ); ?>
                    </p>
                    <?php if ( is_user_logged_in() ) : ?>
                        <a href="#answer-form" class="answer-cta-button">
                            <?php esc_html_e( 'Write an Answer', 'askro' ); ?>
                        </a>
                    <?php else : ?>
                        <p class="login-required">
                            <?php
                            printf(
                                __( 'Please <a href="%s">log in</a> to answer this question.', 'askro' ),
                                esc_url( wp_login_url( get_permalink() ) )
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</section>
