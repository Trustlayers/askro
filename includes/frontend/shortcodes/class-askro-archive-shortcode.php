<?php
/**
 * @package    Askro
 * @subpackage Frontend/Shortcodes
 * @since      1.0.0
 * @author     Arashdi <arashdi@wratcliff.dev>
 * @copyright  2025 William Ratcliff
 * @license    GPL-3.0-or-later
 * @link       https://arashdi.com
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Askro_Archive_Shortcode
 *
 * Handles the rendering of the questions archive page via shortcode.
 * Provides the same functionality as the archive-question.php template
 * but accessible through a shortcode for flexible placement.
 *
 * @since 1.0.0
 */
final class Askro_Archive_Shortcode {

    /**
     * Initialize the shortcode
     *
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        add_action( 'init', array( self::class, 'register' ) );
    }

    /**
     * Register the shortcode
     *
     * @since 1.0.0
     * @return void
     */
    public static function register() {
        add_shortcode( 'askro_questions_archive', array( self::class, 'render' ) );
    }

    /**
     * Render the questions archive
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function render( $atts = array() ) {
        // Parse shortcode attributes
        $atts = shortcode_atts( array(
            'posts_per_page' => get_option( 'posts_per_page', 10 ),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'show_sidebar'   => 'true',
        ), $atts, 'askro_questions_archive' );

        // Setup the query
        $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
        
        $query_args = array(
            'post_type'      => 'question',
            'post_status'    => 'publish',
            'posts_per_page' => intval( $atts['posts_per_page'] ),
            'paged'          => $paged,
            'orderby'        => sanitize_text_field( $atts['orderby'] ),
            'order'          => sanitize_text_field( $atts['order'] ),
        );

        $questions_query = new WP_Query( $query_args );

        // Start output buffering to capture template output
        ob_start();
        ?>
        <main class="max-w-screen-xl mx-auto p-6">

            <!-- Archive Grid Layout -->
            <div class="grid grid-cols-1 <?php echo ( $atts['show_sidebar'] === 'true' ) ? 'lg:grid-cols-3' : ''; ?> gap-6">

                <!-- Main Content Area (70%) -->
                <div class="<?php echo ( $atts['show_sidebar'] === 'true' ) ? 'col-span-2' : 'col-span-1'; ?>">

                    <!-- Archive Header -->
                    <div class="mb-6">
                        <h1 class="text-3xl font-bold mb-2"><?php esc_html_e( 'Questions', 'askro' ); ?></h1>
                        <p class="text-base-content/70"><?php esc_html_e( 'Browse and discover questions from the community', 'askro' ); ?></p>
                    </div>

                    <!-- Filter and Sorting Bar -->
                    <div class="bg-base-100 p-4 rounded-lg shadow-sm mb-6">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <!-- Left Side: Sorting & Filters -->
                            <div class="flex flex-wrap items-center gap-4">
                                <!-- Sorting Buttons -->
                                <div class="btn-group">
                                    <button data-sort="latest" class="btn btn-sm btn-active"><?php esc_html_e( 'Latest', 'askro' ); ?></button>
                                    <button data-sort="top_voted" class="btn btn-sm"><?php esc_html_e( 'Top Voted', 'askro' ); ?></button>
                                    <button data-sort="most_answered" class="btn btn-sm"><?php esc_html_e( 'Most Answered', 'askro' ); ?></button>
                                </div>

                                <!-- Filter Toggles -->
                                <button data-filter="unanswered" class="btn btn-sm btn-outline"><?php esc_html_e( 'Unanswered', 'askro' ); ?></button>
                            </div>

                            <!-- Right Side: Search -->
                            <div class="flex-1 max-w-md">
                                <input type="search" 
                                       id="askro-archive-search" 
                                       class="input input-sm input-bordered w-full" 
                                       placeholder="<?php esc_attr_e( 'Search questions...', 'askro' ); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Question List (AJAX-Updated) -->
                    <div id="askro-question-list">
                        <?php
                        if ( $questions_query->have_posts() ) :
                            while ( $questions_query->have_posts() ) :
                                $questions_query->the_post();
                                
                                // Display question card inline
                                ?>
                                <div class="bg-white p-6 rounded-lg shadow-sm mb-4 border border-gray-200">
                                    <div class="flex justify-between items-start mb-3">
                                        <h2 class="text-xl font-semibold">
                                            <a href="<?php the_permalink(); ?>" class="text-blue-600 hover:text-blue-800 hover:underline">
                                                <?php the_title(); ?>
                                            </a>
                                        </h2>
                                        <div class="text-sm text-gray-500">
                                            <?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ); ?> ago
                                        </div>
                                    </div>
                                    
                                    <div class="text-gray-700 mb-4">
                                        <?php echo wp_trim_words( get_the_excerpt(), 25, '...' ); ?>
                                    </div>
                                    
                                    <div class="flex items-center justify-between text-sm text-gray-600">
                                        <div class="flex items-center gap-4">
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <?php echo esc_html( get_the_author() ); ?>
                                            </span>
                                            
                                            <?php
                                            $view_count = get_post_meta( get_the_ID(), '_askro_view_count', true );
                                            if ( $view_count > 0 ) :
                                            ?>
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    <?php echo esc_html( number_format( $view_count ) ); ?> views
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php
                                        $tags = get_the_terms( get_the_ID(), 'question_tag' );
                                        if ( $tags && ! is_wp_error( $tags ) && count( $tags ) > 0 ) :
                                            $limited_tags = array_slice( $tags, 0, 3 ); // Show max 3 tags
                                        ?>
                                            <div class="flex flex-wrap gap-1">
                                                <?php foreach ( $limited_tags as $tag ) : ?>
                                                    <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                                        #<?php echo esc_html( $tag->name ); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                                <?php if ( count( $tags ) > 3 ) : ?>
                                                    <span class="text-xs text-gray-500">+<?php echo count( $tags ) - 3; ?> more</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php

                            endwhile;
                            wp_reset_postdata();
                        else :
                            echo '<p>' . esc_html__( 'No questions found.', 'askro' ) . '</p>';
                        endif;
                        ?>
                    </div>
                    
                    <!-- Pagination -->
                    <div id="askro-pagination" class="mt-8">
                        <?php
                        echo paginate_links( array(
                            'prev_text' => esc_html__( '« Previous', 'askro' ),
                            'next_text' => esc_html__( 'Next »', 'askro' ),
                            'type'      => 'list',
                            'total'     => $questions_query->max_num_pages,
                            'current'   => $paged,
                        ) );
                        ?>
                    </div>
                
                </div>

                <?php if ( $atts['show_sidebar'] === 'true' ) : ?>
                <!-- Sidebar Area (30%) -->
                <aside class="col-span-1">
                    <?php 
                    if ( is_active_sidebar( 'askro_archive_sidebar' ) ) :
                        dynamic_sidebar( 'askro_archive_sidebar' ); 
                    else : 
                        // Default content if no widgets are active
                        echo '<p>' . esc_html__( 'Add widgets to the sidebar to enhance the archive experience.', 'askro' ) . '</p>';
                    endif;
                    ?>
                </aside>
                <?php endif; ?>

            </div>

        </main>
        <?php
        
        return ob_get_clean();
    }
}
