<?php
/**
 * Askro Question Category Taxonomy
 *
 * Handles the registration and management of the "Question Category" custom taxonomy
 * for organizing questions in the Askro plugin's Q&A functionality.
 *
 * @package    Askro
 * @subpackage Core/Taxonomies
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
 * Question Category Taxonomy Class
 *
 * Registers and manages the "Question Category" taxonomy for organizing questions
 * in a hierarchical structure similar to WordPress post categories.
 *
 * @since 1.0.0
 */
final class Askro_Category_Tax {

    /**
     * Taxonomy key
     *
     * @since 1.0.0
     * @var string
     */
    const TAXONOMY = 'question_category';

    /**
     * Private constructor to prevent direct instantiation
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Prevent direct instantiation
    }

    /**
     * Initialize the Question Category taxonomy
     *
     * Sets up the necessary WordPress hooks for taxonomy registration.
     *
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
    }

    /**
     * Register the Question Category taxonomy
     *
     * Registers the "Question Category" taxonomy with WordPress, including all
     * necessary labels, capabilities, and configuration options.
     *
     * @since 1.0.0
     * @return void
     */
    public static function register_taxonomy() {
        $labels = array(
            'name'                       => _x( 'Question Categories', 'Taxonomy General Name', 'askro' ),
            'singular_name'              => _x( 'Question Category', 'Taxonomy Singular Name', 'askro' ),
            'menu_name'                  => __( 'Categories', 'askro' ),
            'all_items'                  => __( 'All Categories', 'askro' ),
            'parent_item'                => __( 'Parent Category', 'askro' ),
            'parent_item_colon'          => __( 'Parent Category:', 'askro' ),
            'new_item_name'              => __( 'New Category Name', 'askro' ),
            'add_new_item'               => __( 'Add New Category', 'askro' ),
            'edit_item'                  => __( 'Edit Category', 'askro' ),
            'update_item'                => __( 'Update Category', 'askro' ),
            'view_item'                  => __( 'View Category', 'askro' ),
            'separate_items_with_commas' => __( 'Separate categories with commas', 'askro' ),
            'add_or_remove_items'        => __( 'Add or remove categories', 'askro' ),
            'choose_from_most_used'      => __( 'Choose from the most used', 'askro' ),
            'popular_items'              => __( 'Popular Categories', 'askro' ),
            'search_items'               => __( 'Search Categories', 'askro' ),
            'not_found'                  => __( 'Not Found', 'askro' ),
            'no_terms'                   => __( 'No categories', 'askro' ),
            'items_list'                 => __( 'Categories list', 'askro' ),
            'items_list_navigation'      => __( 'Categories list navigation', 'askro' ),
        );

        $args = array(
            'labels'                => $labels,
            'hierarchical'          => true,
            'public'                => true,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'show_in_nav_menus'     => true,
            'show_tagcloud'         => true,
            'show_in_rest'          => true,
            'query_var'             => true,
            'rewrite'               => array( 'slug' => 'questions/category' ),
            'capabilities'          => array(
                'manage_terms' => 'manage_categories',
                'edit_terms'   => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts',
            ),
        );

        register_taxonomy( self::TAXONOMY, array( 'question' ), $args );
    }

    /**
     * Get the taxonomy key
     *
     * @since 1.0.0
     * @return string The taxonomy key
     */
    public static function get_taxonomy() {
        return self::TAXONOMY;
    }
}
