<?php
/**
 * Askro Question Tag Taxonomy
 *
 * Handles the registration and management of the "Question Tag" custom taxonomy
 * for tagging questions in the Askro plugin's Q&A functionality.
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
 * Question Tag Taxonomy Class
 *
 * Registers and manages the "Question Tag" taxonomy for tagging questions
 * in a non-hierarchical structure similar to WordPress post tags.
 *
 * @since 1.0.0
 */
final class Askro_Tag_Tax {

    /**
     * Taxonomy key
     *
     * @since 1.0.0
     * @var string
     */
    const TAXONOMY = 'question_tag';

    /**
     * Private constructor to prevent direct instantiation
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Prevent direct instantiation
    }

    /**
     * Initialize the Question Tag taxonomy
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
     * Register the Question Tag taxonomy
     *
     * Registers the "Question Tag" taxonomy with WordPress, including all
     * necessary labels, capabilities, and configuration options.
     *
     * @since 1.0.0
     * @return void
     */
    public static function register_taxonomy() {
        $labels = array(
            'name'                       => _x( 'Question Tags', 'Taxonomy General Name', 'askro' ),
            'singular_name'              => _x( 'Question Tag', 'Taxonomy Singular Name', 'askro' ),
            'menu_name'                  => __( 'Tags', 'askro' ),
            'all_items'                  => __( 'All Tags', 'askro' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'new_item_name'              => __( 'New Tag Name', 'askro' ),
            'add_new_item'               => __( 'Add New Tag', 'askro' ),
            'edit_item'                  => __( 'Edit Tag', 'askro' ),
            'update_item'                => __( 'Update Tag', 'askro' ),
            'view_item'                  => __( 'View Tag', 'askro' ),
            'separate_items_with_commas' => __( 'Separate tags with commas', 'askro' ),
            'add_or_remove_items'        => __( 'Add or remove tags', 'askro' ),
            'choose_from_most_used'      => __( 'Choose from the most used', 'askro' ),
            'popular_items'              => __( 'Popular Tags', 'askro' ),
            'search_items'               => __( 'Search Tags', 'askro' ),
            'not_found'                  => __( 'Not Found', 'askro' ),
            'no_terms'                   => __( 'No tags', 'askro' ),
            'items_list'                 => __( 'Tags list', 'askro' ),
            'items_list_navigation'      => __( 'Tags list navigation', 'askro' ),
        );

        $args = array(
            'labels'                => $labels,
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'show_in_nav_menus'     => true,
            'show_tagcloud'         => true,
            'show_in_rest'          => true,
            'query_var'             => true,
            'rewrite'               => array( 'slug' => 'questions/tag' ),
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
