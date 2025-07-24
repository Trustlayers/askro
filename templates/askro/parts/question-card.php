<?php
/**
 * Question Card Template Part
 *
 * Displays a single question card with metadata, voting info, 
 * and action buttons for use in archives and listings.
 *
 * @package    Askro
 * @subpackage Templates\Parts
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

// Use the global $post object
global $post;
if ( ! $post ) {
    return;
}

$question_id = $post->ID;
$question_author = get_userdata( $post->post_author );
$cached_score = get_post_meta( $question_id, '_askro_cached_score', true );
$cached_score = $cached_score ? intval( $cached_score ) : 0;
$answer_count = get_post_meta( $question_id, '_askro_answer_count', true );
$answer_count = $answer_count ? intval( $answer_count ) : 0;
$comment_count = askro_get_comment_count( $question_id );
$view_count = get_post_meta( $question_id, '_askro_view_count', true );
$view_count = $view_count ? intval( $view_count ) : 0;

// Get question tags
$tags = get_the_terms( $question_id, 'question_tag' );
$categories = get_the_terms( $question_id, 'question_category' );
?>

<article class="askro-question-card bg-base-100 border border-base-300 rounded-lg p-6 mb-4 hover:shadow-md transition-shadow duration-200" data-question-id="<?php echo esc_attr( $question_id ); ?>">
    
    <!-- Question Header -->
    <div class="flex items-start justify-between mb-4">
        <div class="flex-1">
            <h2 class="text-lg font-semibold mb-2">
                <a href="<?php echo esc_url( get_permalink( $question_id ) ); ?>" 
                   class="text-base-content hover:text-primary transition-colors duration-200">
                    <?php echo esc_html( get_the_title( $post ) ); ?>
                </a>
            </h2>
            
            <!-- Question Meta -->
            <div class="flex flex-wrap items-center gap-4 text-sm text-base-content/70 mb-3">
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span><?php echo esc_html( $question_author->display_name ); ?></span>
                </div>
                
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <time datetime="<?php echo esc_attr( get_the_date( 'c', $post ) ); ?>">
                        <?php echo esc_html( human_time_diff( get_the_time( 'U', $post ), current_time( 'timestamp' ) ) ); ?> ago
                    </time>
                </div>
                
                <?php if ( $view_count > 0 ) : ?>
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <span><?php echo esc_html( number_format( $view_count ) ); ?> views</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Question Stats -->
        <div class="flex items-center gap-4 ml-4">
            <!-- Vote Score -->
            <div class="text-center">
                <div class="text-lg font-semibold <?php echo $cached_score > 0 ? 'text-success' : ( $cached_score < 0 ? 'text-error' : 'text-base-content' ); ?>">
                    <?php echo esc_html( $cached_score ); ?>
                </div>
                <div class="text-xs text-base-content/60">
                    <?php esc_html_e( 'votes', 'askro' ); ?>
                </div>
            </div>
            
            <!-- Answer Count -->
            <div class="text-center">
                <div class="text-lg font-semibold <?php echo $answer_count > 0 ? 'text-success' : 'text-base-content'; ?>">
                    <?php echo esc_html( $answer_count ); ?>
                </div>
                <div class="text-xs text-base-content/60">
                    <?php echo _n( 'answer', 'answers', $answer_count, 'askro' ); ?>
                </div>
            </div>
            
            <!-- Comment Count -->
            <?php if ( $comment_count > 0 ) : ?>
                <div class="text-center">
                    <div class="text-lg font-semibold text-base-content">
                        <?php echo esc_html( $comment_count ); ?>
                    </div>
                    <div class="text-xs text-base-content/60">
                        <?php echo _n( 'comment', 'comments', $comment_count, 'askro' ); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Question Excerpt -->
    <div class="mb-4 text-base-content/80">
        <?php
        $excerpt = get_the_excerpt( $post );
        if ( empty( $excerpt ) ) {
            $excerpt = wp_trim_words( strip_shortcodes( $post->post_content ), 30, '...' );
        }
        echo esc_html( $excerpt );
        ?>
    </div>
    
    <!-- Tags and Categories -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            <!-- Categories -->
            <?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
                <?php foreach ( array_slice( $categories, 0, 2 ) as $category ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $category ) ); ?>" 
                       class="inline-flex items-center px-2 py-1 bg-primary/10 text-primary text-xs font-medium rounded-full hover:bg-primary/20 transition-colors duration-200">
                        <?php echo esc_html( $category->name ); ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Tags -->
            <?php if ( $tags && ! is_wp_error( $tags ) ) : ?>
                <?php foreach ( array_slice( $tags, 0, 3 ) as $tag ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $tag ) ); ?>" 
                       class="inline-flex items-center px-2 py-1 bg-base-200 text-base-content text-xs rounded-full hover:bg-base-300 transition-colors duration-200">
                        #<?php echo esc_html( $tag->name ); ?>
                    </a>
                <?php endforeach; ?>
                
                <?php if ( count( $tags ) > 3 ) : ?>
                    <span class="text-xs text-base-content/60">
                        +<?php echo esc_html( count( $tags ) - 3 ); ?> more
                    </span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Status Indicators -->
        <div class="flex items-center gap-2">
            <?php if ( $answer_count === 0 ) : ?>
                <span class="inline-flex items-center px-2 py-1 bg-warning/20 text-warning text-xs font-medium rounded-full">
                    <?php esc_html_e( 'Unanswered', 'askro' ); ?>
                </span>
            <?php endif; ?>
            
            <?php if ( get_post_status( $post ) === 'draft' ) : ?>
                <span class="inline-flex items-center px-2 py-1 bg-info/20 text-info text-xs font-medium rounded-full">
                    <?php esc_html_e( 'Draft', 'askro' ); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Question Actions (if current user can edit) -->
    <?php if ( current_user_can( 'edit_post', $question_id ) ) : ?>
        <div class="mt-4 pt-4 border-t border-base-300">
            <div class="flex items-center gap-3">
                <a href="<?php echo esc_url( get_edit_post_link( $question_id ) ); ?>" 
                   class="text-sm text-base-content/70 hover:text-primary transition-colors duration-200">
                    <?php esc_html_e( 'Edit', 'askro' ); ?>
                </a>
                
                <?php if ( get_post_status( $post ) === 'draft' ) : ?>
                    <button type="button" 
                            class="text-sm text-base-content/70 hover:text-success transition-colors duration-200 askro-publish-question"
                            data-question-id="<?php echo esc_attr( $question_id ); ?>">
                        <?php esc_html_e( 'Publish', 'askro' ); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</article>
