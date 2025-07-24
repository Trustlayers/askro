<?php
/**
 * Comment Card Template Part
 *
 * Template for displaying individual comments with user info,
 * timestamp, content, and action buttons.
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

// Ensure we have the required variables
if ( ! isset( $comment ) || ! isset( $user ) ) {
    return;
}
?>

<div class="askro-comment" data-comment-id="<?php echo esc_attr( $comment->id ); ?>">
    <div class="askro-comment-avatar">
        <?php echo $avatar; ?>
    </div>
    
    <div class="askro-comment-content">
        <div class="askro-comment-header">
            <div class="askro-comment-meta">
                <span class="askro-comment-author"><?php echo esc_html( $user->display_name ); ?></span>
                <span class="askro-comment-separator">•</span>
                <span class="askro-comment-time" title="<?php echo esc_attr( $comment->created_at ); ?>">
                    <?php echo esc_html( $time_ago ); ?> ago
                </span>
            </div>
            
            <?php if ( $can_delete ) : ?>
                <div class="askro-comment-actions">
                    <button 
                        class="askro-comment-delete" 
                        data-comment-id="<?php echo esc_attr( $comment->id ); ?>"
                        title="<?php esc_attr_e( 'Delete comment', 'askro' ); ?>"
                        aria-label="<?php esc_attr_e( 'Delete comment', 'askro' ); ?>"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3,6 5,6 21,6"></polyline>
                            <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                        <span class="askro-sr-only"><?php esc_html_e( 'Delete', 'askro' ); ?></span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="askro-comment-text">
            <?php echo nl2br( esc_html( $comment->content ) ); ?>
        </div>
        
        <?php if ( $comment->parent_id == 0 && is_user_logged_in() ) : ?>
            <div class="askro-comment-reply-btn">
                <button 
                    class="askro-reply-link" 
                    data-comment-id="<?php echo esc_attr( $comment->id ); ?>"
                    data-post-id="<?php echo esc_attr( $comment->post_id ); ?>"
                >
                    <?php esc_html_e( 'Reply', 'askro' ); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ( $comment->parent_id == 0 ) : ?>
    <!-- Container for replies (will be populated by JavaScript) -->
    <div class="askro-comment-replies" id="askro-replies-<?php echo esc_attr( $comment->id ); ?>">
        <!-- Replies will be inserted here dynamically -->
    </div>
    
    <!-- Container for reply form (will be shown when replying) -->
    <div class="askro-reply-form-container" id="askro-reply-form-<?php echo esc_attr( $comment->id ); ?>" style="display: none;">
        <!-- Reply form will be inserted here dynamically -->
    </div>
<?php endif; ?>
