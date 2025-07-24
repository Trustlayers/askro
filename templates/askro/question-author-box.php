<?php
/**
 * Question Author Box Template Part
 *
 * Displays information about the question author including name, reputation,
 * avatar, badges, and other relevant author details.
 *
 * @package Askro
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get author information
$author_id = get_the_author_meta( 'ID' );
$author_name = get_the_author();
$author_url = get_author_posts_url( $author_id );
$author_bio = get_the_author_meta( 'description' );
?>

<div class="askro-question-author-box">
    <div class="author-label">
        <?php esc_html_e( 'Asked by', 'askro' ); ?>
    </div>
    
    <div class="author-info">
        <div class="author-avatar">
            <?php echo get_avatar( $author_id, 64, '', esc_attr( $author_name ) ); ?>
        </div>
        
        <div class="author-details">
            <div class="author-name">
                <a href="<?php echo esc_url( $author_url ); ?>" class="author-link">
                    <?php echo esc_html( $author_name ); ?>
                </a>
            </div>
            
            <div class="author-meta">
                <div class="author-reputation">
                    <!-- TODO: Add reputation system -->
                    <span class="reputation-score">0</span>
                    <span class="reputation-label"><?php esc_html_e( 'reputation', 'askro' ); ?></span>
                </div>
                
                <div class="author-badges">
                    <!-- TODO: Add badge system -->
                    <span class="badge-count">
                        <?php esc_html_e( 'No badges yet', 'askro' ); ?>
                    </span>
                </div>
                
                <div class="author-join-date">
                    <?php
                    $user_registered = get_userdata( $author_id )->user_registered;
                    printf(
                        esc_html__( 'Member since %s', 'askro' ),
                        date_i18n( get_option( 'date_format' ), strtotime( $user_registered ) )
                    );
                    ?>
                </div>
            </div>
            
            <?php if ( ! empty( $author_bio ) ) : ?>
                <div class="author-bio">
                    <?php echo wp_kses_post( wpautop( $author_bio ) ); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="author-stats">
        <div class="stat-item">
            <span class="stat-number">
                <?php echo count_user_posts( $author_id, 'question' ); ?>
            </span>
            <span class="stat-label">
                <?php esc_html_e( 'questions', 'askro' ); ?>
            </span>
        </div>
        
        <div class="stat-item">
            <!-- TODO: Add answer count when answer system is implemented -->
            <span class="stat-number">0</span>
            <span class="stat-label">
                <?php esc_html_e( 'answers', 'askro' ); ?>
            </span>
        </div>
    </div>
</div>
