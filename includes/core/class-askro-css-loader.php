<?php
/**
 * Askro CSS Loader
 *
 * Alternative CSS loading mechanism that ensures styles are loaded
 *
 * @package    Askro
 * @subpackage Core
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Askro_CSS_Loader {
    
    /**
     * Initialize the CSS loader
     */
    public static function init() {
        // Hook into template_redirect to ensure we're after the main query is set
        add_action( 'template_redirect', array( __CLASS__, 'maybe_load_css' ), 1 );
        
        // Also hook into wp_footer as a fallback
        add_action( 'wp_footer', array( __CLASS__, 'fallback_load_css' ), 999 );
    }
    
    /**
     * Check if we should load CSS and do it
     */
    public static function maybe_load_css() {
        if ( self::is_askro_page() ) {
            add_action( 'wp_head', array( __CLASS__, 'output_css_link' ), 1 );
        }
    }
    
    /**
     * Fallback CSS loading in footer if not loaded in head
     */
    public static function fallback_load_css() {
        if ( self::is_askro_page() && ! self::css_already_loaded() ) {
            self::output_css_link();
            ?>
            <script>
            // Move CSS from footer to head
            (function() {
                var css = document.getElementById('askro-main-style-inline');
                if (css && document.head) {
                    document.head.appendChild(css);
                }
            })();
            </script>
            <?php
        }
    }
    
    /**
     * Check if CSS is already loaded
     */
    private static function css_already_loaded() {
        return wp_style_is( 'askro-main-style', 'done' ) || 
               wp_style_is( 'askro-main-style-forced', 'done' );
    }
    
    /**
     * Output the CSS link
     */
    public static function output_css_link() {
        $css_path = plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'assets/css/style.css';
        
        if ( ! file_exists( $css_path ) ) {
            return;
        }
        
        // Get the CSS URL with proper protocol
        $css_url = plugins_url( 'assets/css/style.css', dirname( dirname( __FILE__ ) ) );
        
        // Ensure proper protocol
        if ( is_ssl() ) {
            $css_url = str_replace( 'http://', 'https://', $css_url );
        }
        
        $version = filemtime( $css_path );
        
        ?>
        <link rel="stylesheet" 
              id="askro-main-style-inline" 
              href="<?php echo esc_url( $css_url ); ?>?ver=<?php echo esc_attr( $version ); ?>" 
              type="text/css" 
              media="all" />
        <?php
    }
    
    /**
     * Determine if current page is an Askro page
     */
    private static function is_askro_page() {
        global $wp_query, $post;
        
        // Direct URL check
        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $request_uri = $_SERVER['REQUEST_URI'];
            $request_path = parse_url( $request_uri, PHP_URL_PATH );
            $request_path = trim( $request_path, '/' );
            
            if ( $request_path === 'questions' || strpos( $request_path, 'questions/' ) === 0 ) {
                return true;
            }
        }
        
        // WordPress conditionals
        if ( is_post_type_archive( 'question' ) || is_singular( 'question' ) ) {
            return true;
        }
        
        // Query vars check
        if ( isset( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] === 'question' ) {
            return true;
        }
        
        // Post type check
        if ( is_a( $post, 'WP_Post' ) && $post->post_type === 'question' ) {
            return true;
        }
        
        // Taxonomy check
        if ( is_tax( 'question_category' ) || is_tax( 'question_tag' ) ) {
            return true;
        }
        
        // Shortcode check
        if ( is_a( $post, 'WP_Post' ) && (
            has_shortcode( $post->post_content, 'askro_submit_question_form' ) ||
            has_shortcode( $post->post_content, 'askro_user_profile' )
        ) ) {
            return true;
        }
        
        return false;
    }
}
