<?php
/**
 * Askro Comment AJAX Handler
 *
 * Handles AJAX requests for comment submission, editing, and deletion
 * with proper security checks and validation.
 *
 * @package    Askro
 * @subpackage Frontend\Ajax
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
 * Comment AJAX Handler Class
 *
 * Manages all AJAX operations related to comments including
 * submission, validation, and response formatting.
 *
 * @since 1.0.0
 */
class Askro_Comment_Handler {

    /**
     * Initialize the handler and register AJAX hooks.
     */
    public static function init() {
        add_action('wp_ajax_askro_submit_comment', array(__CLASS__, 'handle_comment_submission'));
        add_action('wp_ajax_nopriv_askro_submit_comment', array(__CLASS__, 'handle_comment_submission'));
        add_action('wp_ajax_askro_delete_comment', array(__CLASS__, 'handle_comment_deletion'));
        add_action('wp_ajax_askro_load_comments', array(__CLASS__, 'handle_load_comments'));
    }

    /**
     * Handle AJAX comment submission
     *
     * @since 1.0.0
     * @return void
     */
    public static function handle_comment_submission() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['askro_comment_nonce'] ?? '', 'askro_comment_action' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'You must be logged in to comment.' ) );
        }

        // Sanitize and validate input
        $post_id = absint( $_POST['post_id'] ?? 0 );
        $parent_id = absint( $_POST['parent_id'] ?? 0 );
        $content = sanitize_textarea_field( $_POST['content'] ?? '' );

        if ( empty( $post_id ) || empty( $content ) ) {
            wp_send_json_error( array( 'message' => 'Post ID and content are required.' ) );
        }

        // Verify post exists and is a question or answer
        $post = get_post( $post_id );
        if ( ! $post || ! in_array( $post->post_type, array( 'question', 'answer' ) ) ) {
            wp_send_json_error( array( 'message' => 'Invalid post.' ) );
        }

        // Verify parent comment exists if provided
        if ( $parent_id && ! self::comment_exists( $parent_id ) ) {
            wp_send_json_error( array( 'message' => 'Parent comment does not exist.' ) );
        }

        // Check content length
        if ( strlen( $content ) < 10 ) {
            wp_send_json_error( array( 'message' => 'Comment must be at least 10 characters long.' ) );
        }

        if ( strlen( $content ) > 1000 ) {
            wp_send_json_error( array( 'message' => 'Comment cannot exceed 1000 characters.' ) );
        }

        // Rate limiting check
        if ( self::is_rate_limited( get_current_user_id() ) ) {
            wp_send_json_error( array( 'message' => 'You are commenting too frequently. Please wait a moment.' ) );
        }

        // Insert comment
        $comment_id = self::insert_comment( array(
            'post_id' => $post_id,
            'user_id' => get_current_user_id(),
            'parent_id' => $parent_id,
            'content' => $content,
            'status' => 'approved'
        ) );

        if ( ! $comment_id ) {
            wp_send_json_error( array( 'message' => 'Failed to save comment.' ) );
        }

        // Get the newly created comment for response
        $comment = self::get_comment( $comment_id );
        if ( ! $comment ) {
            wp_send_json_error( array( 'message' => 'Comment created but could not be retrieved.' ) );
        }

        // Generate comment HTML
        $comment_html = self::render_comment( $comment );

        wp_send_json_success( array(
            'message' => 'Comment submitted successfully!',
            'comment_html' => $comment_html,
            'comment_id' => $comment_id
        ) );
    }

    /**
     * Handle AJAX comment deletion
     *
     * @since 1.0.0
     * @return void
     */
    public static function handle_comment_deletion() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['askro_comment_nonce'] ?? '', 'askro_comment_action' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'You must be logged in to delete comments.' ) );
        }

        $comment_id = absint( $_POST['comment_id'] ?? 0 );
        if ( empty( $comment_id ) ) {
            wp_send_json_error( array( 'message' => 'Comment ID is required.' ) );
        }

        $comment = self::get_comment( $comment_id );
        if ( ! $comment ) {
            wp_send_json_error( array( 'message' => 'Comment not found.' ) );
        }

        // Check permissions
        $current_user_id = get_current_user_id();
        if ( $comment->user_id != $current_user_id && ! current_user_can( 'moderate_comments' ) ) {
            wp_send_json_error( array( 'message' => 'You do not have permission to delete this comment.' ) );
        }

        // Delete comment
        if ( self::delete_comment( $comment_id ) ) {
            wp_send_json_success( array( 'message' => 'Comment deleted successfully.' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to delete comment.' ) );
        }
    }

    /**
     * Handle AJAX comment loading
     *
     * @since 1.0.0
     * @return void
     */
    public static function handle_load_comments() {
        $post_id = absint( $_GET['post_id'] ?? 0 );
        if ( empty( $post_id ) ) {
            wp_send_json_error( array( 'message' => 'Post ID is required.' ) );
        }

        $comments = self::get_comments_for_post( $post_id );
        $comments_html = '';

        foreach ( $comments as $comment ) {
            $comments_html .= self::render_comment( $comment );
        }

        wp_send_json_success( array(
            'comments_html' => $comments_html,
            'count' => count( $comments )
        ) );
    }

    /**
     * Check if user is rate limited
     *
     * @since 1.0.0
     * @param int $user_id User ID
     * @return bool True if rate limited
     */
    private static function is_rate_limited( $user_id ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'askro_comments';
        $time_limit = gmdate( 'Y-m-d H:i:s', time() - 30 ); // 30 seconds ago
        
        $recent_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND created_at > %s",
            $user_id,
            $time_limit
        ) );
        
        return $recent_count >= 3; // Max 3 comments per 30 seconds
    }

    /**
     * Insert a new comment
     *
     * @since 1.0.0
     * @param array $data Comment data
     * @return int|false Comment ID on success, false on failure
     */
    private static function insert_comment( $data ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'askro_comments';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'post_id' => $data['post_id'],
                'user_id' => $data['user_id'],
                'parent_id' => $data['parent_id'],
                'content' => $data['content'],
                'status' => $data['status'],
                'created_at' => current_time( 'mysql' )
            ),
            array( '%d', '%d', '%d', '%s', '%s', '%s' )
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get a comment by ID
     *
     * @since 1.0.0
     * @param int $comment_id Comment ID
     * @return object|null Comment object or null if not found
     */
    private static function get_comment( $comment_id ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'askro_comments';
        
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $comment_id
        ) );
    }

    /**
     * Check if comment exists
     *
     * @since 1.0.0
     * @param int $comment_id Comment ID
     * @return bool True if comment exists
     */
    private static function comment_exists( $comment_id ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'askro_comments';
        
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE id = %d",
            $comment_id
        ) );
        
        return $count > 0;
    }

    /**
     * Get comments for a post
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return array Array of comment objects
     */
    private static function get_comments_for_post( $post_id ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'askro_comments';
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE post_id = %d AND status = 'approved' 
             ORDER BY created_at ASC",
            $post_id
        ) );
    }

    /**
     * Delete a comment
     *
     * @since 1.0.0
     * @param int $comment_id Comment ID
     * @return bool True on success, false on failure
     */
    private static function delete_comment( $comment_id ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'askro_comments';
        
        $result = $wpdb->delete(
            $table_name,
            array( 'id' => $comment_id ),
            array( '%d' )
        );
        
        return $result !== false;
    }

    /**
     * Render a comment as HTML
     *
     * @since 1.0.0
     * @param object $comment Comment object
     * @return string Comment HTML
     */
    private static function render_comment( $comment ) {
        $user = get_userdata( $comment->user_id );
        $avatar = get_avatar( $comment->user_id, 32 );
        $time_ago = human_time_diff( strtotime( $comment->created_at ), current_time( 'timestamp' ) );
        $current_user_id = get_current_user_id();
        $can_delete = ( $current_user_id == $comment->user_id ) || current_user_can( 'moderate_comments' );
        
        ob_start();
        ?>
        <div class="askro-comment" data-comment-id="<?php echo esc_attr( $comment->id ); ?>">
            <div class="askro-comment-avatar">
                <?php echo $avatar; ?>
            </div>
            <div class="askro-comment-content">
                <div class="askro-comment-header">
                    <span class="askro-comment-author"><?php echo esc_html( $user->display_name ); ?></span>
                    <span class="askro-comment-time"><?php echo esc_html( $time_ago ); ?> ago</span>
                    <?php if ( $can_delete ) : ?>
                        <button class="askro-comment-delete" data-comment-id="<?php echo esc_attr( $comment->id ); ?>">
                            Delete
                        </button>
                    <?php endif; ?>
                </div>
                <div class="askro-comment-text">
                    <?php echo nl2br( esc_html( $comment->content ) ); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
