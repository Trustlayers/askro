<?php
/**
 * Question Content Template Part
 *
 * Displays the main question content, attachments, tags, and other
 * content-related elements.
 *
 * @package Askro
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="askro-question-content">
    <div class="question-body">
        <?php the_content(); ?>
    </div>

    <?php
    // Display question attachments if any
    $attachments = get_attached_media( '', get_the_ID() );
    if ( ! empty( $attachments ) ) :
    ?>
        <div class="question-attachments">
            <h4 class="attachments-title">
                <?php esc_html_e( 'Attachments', 'askro' ); ?>
            </h4>
            <div class="attachments-list">
                <?php foreach ( $attachments as $attachment ) : ?>
                    <div class="attachment-item">
                        <?php if ( wp_attachment_is_image( $attachment->ID ) ) : ?>
                            <div class="attachment-image">
                                <?php echo wp_get_attachment_image( $attachment->ID, 'medium' ); ?>
                            </div>
                        <?php else : ?>
                            <div class="attachment-file">
                                <a href="<?php echo esc_url( wp_get_attachment_url( $attachment->ID ) ); ?>" 
                                   class="attachment-link" 
                                   target="_blank" 
                                   rel="noopener noreferrer">
                                    <span class="attachment-icon">📄</span>
                                    <span class="attachment-name">
                                        <?php echo esc_html( get_the_title( $attachment->ID ) ); ?>
                                    </span>
                                    <span class="attachment-size">
                                        (<?php echo esc_html( size_format( filesize( get_attached_file( $attachment->ID ) ) ) ); ?>)
                                    </span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php
    // Display question tags
    $tags = get_the_terms( get_the_ID(), 'question_tag' );
    if ( $tags && ! is_wp_error( $tags ) ) :
    ?>
        <div class="question-tags">
            <div class="tags-label">
                <?php esc_html_e( 'Tags:', 'askro' ); ?>
            </div>
            <div class="tags-list">
                <?php foreach ( $tags as $tag ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $tag ) ); ?>" 
                       class="question-tag" 
                       title="<?php echo esc_attr( sprintf( __( 'View all questions tagged with %s', 'askro' ), $tag->name ) ); ?>">
                        <?php echo esc_html( $tag->name ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="question-actions">
        <div class="question-share">
            <!-- TODO: Add sharing functionality -->
            <button type="button" class="share-button" disabled>
                <?php esc_html_e( 'Share', 'askro' ); ?>
            </button>
        </div>
        
        <div class="question-bookmark">
            <!-- TODO: Add bookmark functionality -->
            <button type="button" class="bookmark-button" disabled>
                <?php esc_html_e( 'Bookmark', 'askro' ); ?>
            </button>
        </div>
        
        <?php if ( current_user_can( 'edit_post', get_the_ID() ) ) : ?>
            <div class="question-edit">
                <a href="<?php echo esc_url( get_edit_post_link() ); ?>" class="edit-question-link">
                    <?php esc_html_e( 'Edit Question', 'askro' ); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Comments Section -->
    <div class="question-comments">
        <?php askro_display_comments( get_the_ID() ); ?>
    </div>
</div>
