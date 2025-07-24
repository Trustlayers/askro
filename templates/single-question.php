<?php
/**
 * Single Question Template
 *
 * Template for displaying single question posts. This file uses a modular
 * approach with template parts to make customization easier.
 *
 * @package Askro
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); ?>

<main class="max-w-screen-xl mx-auto p-6">
    
    <!-- Single Question Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Main Content Area (70%) -->
        <div class="col-span-2">
            
            <?php while ( have_posts() ) : the_post(); ?>
                
                <!-- Question Header -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold mb-4"><?php the_title(); ?></h1>
                    
                    <!-- Question Meta -->
                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 mb-4">
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span><?php echo esc_html( get_the_author() ); ?></span>
                        </div>
                        
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                <?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ); ?> ago
                            </time>
                        </div>
                        
                        <?php
                        $view_count = get_post_meta( get_the_ID(), '_askro_view_count', true );
                        if ( $view_count > 0 ) :
                        ?>
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
                
                <!-- Question Content -->
                <div class="bg-white p-6 rounded-lg shadow-sm mb-8">
                    <div class="prose max-w-none">
                        <?php the_content(); ?>
                    </div>
                    
                    <!-- Question Tags -->
                    <?php
                    $tags = get_the_terms( get_the_ID(), 'question_tag' );
                    if ( $tags && ! is_wp_error( $tags ) ) :
                    ?>
                        <div class="mt-6 pt-4 border-t">
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ( $tags as $tag ) : ?>
                                    <a href="<?php echo esc_url( get_term_link( $tag ) ); ?>" 
                                       class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full hover:bg-blue-200 transition-colors">
                                        #<?php echo esc_html( $tag->name ); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Simple Answer Section -->
                <div class="bg-white p-6 rounded-lg shadow-sm mb-8">
                    <h3 class="text-xl font-semibold mb-4">Answers</h3>
                    <p class="text-gray-500">Answer functionality coming soon...</p>
                </div>
                
            <?php endwhile; ?>
            
        </div>
        
        <!-- Sidebar Area (30%) -->
        <aside class="col-span-1">
            <?php 
            if ( is_active_sidebar( 'askro_single_sidebar' ) ) :
                dynamic_sidebar( 'askro_single_sidebar' ); 
            else : 
                echo '<div class="bg-white p-4 rounded-lg shadow-sm">';
                echo '<h3 class="font-semibold mb-3">Question Details</h3>';
                echo '<p class="text-sm text-gray-500">Add widgets to enhance the question experience.</p>';
                echo '</div>';
            endif;
            ?>
        </aside>
        
    </div>
    
</main>

<?php get_footer();

