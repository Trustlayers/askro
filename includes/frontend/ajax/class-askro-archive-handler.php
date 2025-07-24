<?php
/**
 * Askro Archive AJAX Handler
 *
 * Handles AJAX requests for filtering, sorting, and searching questions
 * in the Askro archive.
 *
 * @package    Askro
 * @subpackage Frontend\Ajax
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Archive AJAX Handler Class
 *
 * Manages all AJAX operations for questions archive, including
 * filtering, sorting, and real-time search.
 *
 * @since 1.0.0
 */
class Askro_Archive_Handler {

    /**
     * Initialize WordPress hooks
     *
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        add_action( 'wp_ajax_nopriv_askro_filter_questions', array( __CLASS__, 'handle_filter_questions' ) );
        add_action( 'wp_ajax_askro_filter_questions', array( __CLASS__, 'handle_filter_questions' ) );
    }

    /**
     * Handle AJAX request to filter and sort questions
     *
     * @since 1.0.0
     * @return void
     */
    public static function handle_filter_questions() {
        // Verify nonce
        check_ajax_referer( 'askro_nonce', 'security' );

        // Get query parameters
        $sort = sanitize_text_field( $_POST['sort'] ?? 'latest' );
        $filter = sanitize_text_field( $_POST['filter'] ?? '' );
        $search_query = sanitize_text_field( $_POST['search_query'] ?? '' );
        $paged = absint( $_POST['paged'] ?? 1 );

        // Build query arguments
        $query_args = [
            'post_type' => 'question',
            'post_status' => 'publish',
            'paged' => $paged,
            's' => $search_query,
        ];

        // Sorting options
        switch ( $sort ) {
            case 'top_voted':
                $query_args['meta_key'] = '_askro_cached_score';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'DESC';
                break;
            case 'most_answered':
                $query_args['meta_key'] = '_askro_answer_count';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'DESC';
                break;
            case 'latest':
            default:
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;
        }

        // Filters
        if ( 'unanswered' === $filter ) {
            $query_args['meta_query'] = [
                [
                    'key' => '_askro_answer_count',
                    'value' => '0',
                    'compare' => '='
                ]
            ];
        }

        // Execute query
        $query = new WP_Query( $query_args );

        // Capture output
        ob_start();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                // Load the question card template part from plugin
                include ASKRO_PLUGIN_DIR . 'templates/askro/parts/question-card.php';
            }
            wp_reset_postdata();
        } else {
            echo '<p>' . esc_html__( 'No questions found.', 'askro' ) . '</p>';
        }
        $html = ob_get_clean();

        // Generate pagination
        ob_start();
        echo paginate_links( [
            'total' => $query->max_num_pages,
            'current' => $paged,
            'mid_size' => 2,
            'prev_text' => __( '« Prev', 'askro' ),
            'next_text' => __( 'Next »', 'askro' ),
            'type' => 'list'
        ] );
        $pagination_html = ob_get_clean();

        // Send response
        wp_send_json_success( [
            'html' => $html,
            'pagination' => $pagination_html,
        ] );
    }
}

// Initialize the handler
Askro_Archive_Handler::init();
