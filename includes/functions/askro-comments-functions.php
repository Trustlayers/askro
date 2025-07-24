<?php
/**
 * Askro Comments Helper Functions
 *
 * Collection of utility functions for the comment system including
 * retrieval, counting, and display functions.
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
 * Get comments for a specific post
 *
 * @since 1.0.0
 * @param int $post_id Post ID
 * @param array $args Optional arguments
 * @return array Array of comment objects
 */
function askro_get_comments( $post_id, $args = array() ) {
    global $wpdb;
    
    $defaults = array(
        'status' => 'approved',
        'order' => 'ASC',
        'limit' => 0
    );
    
    $args = wp_parse_args( $args, $defaults );
    $table_name = $wpdb->prefix . 'askro_comments';
    
    // Check if table exists, return empty array if not
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
        return array();
    }
    
    $query = "SELECT * FROM {$table_name} WHERE post_id = %d";
    $query_args = array( $post_id );
    
    if ( $args['status'] ) {
        $query .= " AND status = %s";
        $query_args[] = $args['status'];
    }
    
    $query .= " ORDER BY created_at " . esc_sql( $args['order'] );
    
    if ( $args['limit'] > 0 ) {
        $query .= " LIMIT %d";
        $query_args[] = $args['limit'];
    }
    
    return $wpdb->get_results( $wpdb->prepare( $query, $query_args ) );
}

/**
 * Get comment count for a post
 *
 * @since 1.0.0
 * @param int $post_id Post ID
 * @param string $status Comment status (default: 'approved')
 * @return int Number of comments
 */
function askro_get_comment_count( $post_id, $status = 'approved' ) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_comments';
    
    // Check if table exists, return 0 if not
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
        return 0;
    }
    
    return (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d AND status = %s",
        $post_id,
        $status
    ) );
}

/**
 * Check if user can comment on a post
 *
 * @since 1.0.0
 * @param int $post_id Post ID
 * @param int $user_id User ID (optional, defaults to current user)
 * @return bool True if user can comment
 */
function askro_user_can_comment( $post_id = 0, $user_id = 0 ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    
    // Must be logged in
    if ( ! $user_id ) {
        return false;
    }
    
    // Post must exist and be a question or answer
    if ( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || ! in_array( $post->post_type, array( 'question', 'answer' ) ) ) {
            return false;
        }
    }
    
    // Check user capability
    return user_can( $user_id, 'read' );
}

/**
 * Display comments for a post
 *
 * @since 1.0.0
 * @param int $post_id Post ID
 * @param array $args Optional display arguments
 * @return void
 */
function askro_display_comments( $post_id, $args = array() ) {
    $defaults = array(
        'show_form' => true,
        'show_count' => true,
        'max_depth' => 3
    );
    
    $args = wp_parse_args( $args, $defaults );
    $comments = askro_get_comments( $post_id );
    $comment_count = count( $comments );
    
    ?>
    <div class="askro-comments-section" data-post-id="<?php echo esc_attr( $post_id ); ?>">
        <?php if ( $args['show_count'] ) : ?>
            <div class="askro-comments-header">
                <h4><?php printf( _n( '%d Comment', '%d Comments', $comment_count, 'askro' ), $comment_count ); ?></h4>
            </div>
        <?php endif; ?>
        
        <div class="askro-comments-list" id="askro-comments-<?php echo esc_attr( $post_id ); ?>">
            <?php if ( $comments ) : ?>
                <?php foreach ( $comments as $comment ) : ?>
                    <?php askro_display_single_comment( $comment ); ?>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="askro-no-comments"><?php esc_html_e( 'No comments yet.', 'askro' ); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if ( $args['show_form'] && askro_user_can_comment( $post_id ) ) : ?>
            <?php askro_display_comment_form( $post_id ); ?>
        <?php elseif ( $args['show_form'] && ! is_user_logged_in() ) : ?>
            <p class="askro-comment-login-required">
                <?php 
                printf( 
                    __( 'You must be <a href="%s">logged in</a> to comment.', 'askro' ),
                    esc_url( wp_login_url( get_permalink() ) )
                );
                ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Display a single comment
 *
 * @since 1.0.0
 * @param object $comment Comment object
 * @return void
 */
function askro_display_single_comment( $comment ) {
    $user = get_userdata( $comment->user_id );
    if ( ! $user ) {
        return;
    }
    
    $avatar = get_avatar( $comment->user_id, 32 );
    $time_ago = human_time_diff( strtotime( $comment->created_at ), current_time( 'timestamp' ) );
    $current_user_id = get_current_user_id();
    $can_delete = ( $current_user_id == $comment->user_id ) || current_user_can( 'moderate_comments' );
    
    // Load the comment template part
    $template_args = array(
        'comment' => $comment,
        'user' => $user,
        'avatar' => $avatar,
        'time_ago' => $time_ago,
        'can_delete' => $can_delete
    );
    
    askro_get_template_part( 'comment-card', $template_args );
}

/**
 * Display comment form
 *
 * @since 1.0.0
 * @param int $post_id Post ID
 * @param int $parent_id Parent comment ID (optional)
 * @return void
 */
function askro_display_comment_form( $post_id, $parent_id = 0 ) {
    if ( ! askro_user_can_comment( $post_id ) ) {
        return;
    }
    
    $form_id = 'askro-comment-form-' . $post_id;
    if ( $parent_id ) {
        $form_id .= '-reply-' . $parent_id;
    }
    
    ?>
    <div class="askro-comment-form-wrapper">
        <form id="<?php echo esc_attr( $form_id ); ?>" class="askro-comment-form" data-post-id="<?php echo esc_attr( $post_id ); ?>" data-parent-id="<?php echo esc_attr( $parent_id ); ?>">
            <div class="askro-comment-form-field">
                <label for="askro-comment-content-<?php echo esc_attr( $post_id ); ?>" class="screen-reader-text">
                    <?php esc_html_e( 'Your comment', 'askro' ); ?>
                </label>
                <textarea 
                    id="askro-comment-content-<?php echo esc_attr( $post_id ); ?>"
                    name="content" 
                    class="askro-comment-textarea"
                    placeholder="<?php esc_attr_e( 'Write your comment...', 'askro' ); ?>"
                    rows="3"
                    maxlength="1000"
                    required
                ></textarea>
                <div class="askro-comment-char-count">
                    <span class="askro-char-current">0</span> / <span class="askro-char-max">1000</span>
                </div>
            </div>
            
            <div class="askro-comment-form-actions">
                <button type="submit" class="askro-btn askro-btn-primary">
                    <?php esc_html_e( 'Submit Comment', 'askro' ); ?>
                </button>
                <?php if ( $parent_id ) : ?>
                    <button type="button" class="askro-btn askro-btn-secondary askro-cancel-reply">
                        <?php esc_html_e( 'Cancel', 'askro' ); ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="askro-comment-form-loading" style="display: none;">
                <span><?php esc_html_e( 'Submitting...', 'askro' ); ?></span>
            </div>
            
            <?php wp_nonce_field( 'askro_comment_action', 'askro_comment_nonce' ); ?>
        </form>
    </div>
    <?php
}

/**
 * Get template part for comments
 *
 * @since 1.0.0
 * @param string $slug Template slug
 * @param array $args Template arguments
 * @return void
 */
function askro_get_template_part( $slug, $args = array() ) {
    // Extract args to variables
    if ( ! empty( $args ) && is_array( $args ) ) {
        extract( $args );
    }
    
    $template_path = ASKRO_PLUGIN_DIR . 'templates/parts/' . $slug . '.php';
    
    if ( file_exists( $template_path ) ) {
        include $template_path;
    }
}

/**
 * Sanitize comment content
 *
 * @since 1.0.0
 * @param string $content Comment content
 * @return string Sanitized content
 */
function askro_sanitize_comment_content( $content ) {
    // Remove HTML tags except basic formatting
    $allowed_tags = array(
        'br' => array(),
        'strong' => array(),
        'b' => array(),
        'em' => array(),
        'i' => array(),
    );
    
    $content = wp_kses( $content, $allowed_tags );
    $content = trim( $content );
    
    return $content;
}

/**
 * Check if user has reached comment limit
 *
 * @since 1.0.0
 * @param int $user_id User ID
 * @param int $time_period Time period in seconds (default: 3600 = 1 hour)
 * @param int $limit Maximum comments allowed (default: 10)
 * @return bool True if limit reached
 */
function askro_user_comment_limit_reached( $user_id, $time_period = 3600, $limit = 10 ) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_comments';
    $time_limit = gmdate( 'Y-m-d H:i:s', time() - $time_period );
    
    $comment_count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND created_at > %s",
        $user_id,
        $time_limit
    ) );
    
    return $comment_count >= $limit;
}

/**
 * Get user's recent comments
 *
 * @since 1.0.0
 * @param int $user_id User ID
 * @param int $limit Number of comments to retrieve (default: 5)
 * @return array Array of comment objects
 */
function askro_get_user_recent_comments( $user_id, $limit = 5 ) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_comments';
    
    return $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$table_name} 
         WHERE user_id = %d AND status = 'approved' 
         ORDER BY created_at DESC 
         LIMIT %d",
        $user_id,
        $limit
    ) );
}
