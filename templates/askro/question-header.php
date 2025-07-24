<?php
/**
 * Question Header Template Part
 *
 * Displays the question title, meta information, voting controls, and other
 * header elements for single question pages.
 *
 * @package Askro
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<header class="askro-question-header">
    <div class="question-voting">
        <!-- TODO: Add voting controls (upvote/downvote buttons and score) -->
        <div class="vote-score">
            <span class="score-number">0</span>
            <span class="score-label"><?php esc_html_e( 'votes', 'askro' ); ?></span>
        </div>
    </div>

    <div class="question-main-header">
        <h1 class="question-title">
            <?php the_title(); ?>
        </h1>

        <div class="question-meta">
            <div class="question-stats">
                <span class="asked-date">
                    <?php
                    printf(
                        esc_html__( 'Asked %s', 'askro' ),
                        '<time datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( get_the_date() ) . '</time>'
                    );
                    ?>
                </span>
                
                <span class="view-count">
                    <!-- TODO: Add view count tracking -->
                    <?php esc_html_e( 'Viewed 0 times', 'askro' ); ?>
                </span>
                
                <span class="answer-count">
                    <!-- TODO: Add answer count -->
                    <?php esc_html_e( '0 answers', 'askro' ); ?>
                </span>
            </div>
        </div>

        <?php
        // Display question categories
        $categories = get_the_terms( get_the_ID(), 'question_category' );
        if ( $categories && ! is_wp_error( $categories ) ) {
            echo '<div class="question-categories">';
            echo '<span class="category-label">' . esc_html__( 'Category:', 'askro' ) . '</span>';
            $category_links = array();
            foreach ( $categories as $category ) {
                $category_links[] = '<a href="' . esc_url( get_term_link( $category ) ) . '" class="category-link">' . esc_html( $category->name ) . '</a>';
            }
            echo implode( ', ', $category_links );
            echo '</div>';
        }
        ?>
    </div>
</header>
