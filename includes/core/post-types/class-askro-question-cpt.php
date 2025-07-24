<?php
/**
 * Askro Question Custom Post Type
 *
 * Handles the registration and management of the "Question" custom post type
 * for the Askro plugin's Q&A functionality.
 *
 * @package    Askro
 * @subpackage Core/Post_Types
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
 * Question Custom Post Type Class
 *
 * Registers and manages the "Question" custom post type for the Q&A system.
 * This post type serves as the foundation for all user-submitted questions.
 *
 * @since 1.0.0
 */
final class Askro_Question_CPT {

    /**
     * Post type key
     *
     * @since 1.0.0
     * @var string
     */
    const POST_TYPE = 'question';

    /**
     * Private constructor to prevent direct instantiation
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Prevent direct instantiation
    }

    /**
     * Initialize the Question custom post type
     *
     * Sets up the necessary WordPress hooks for post type registration.
     *
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
    }

    /**
     * Register the Question custom post type
     *
     * Registers the "Question" post type with WordPress, including all
     * necessary labels, capabilities, and configuration options.
     *
     * @since 1.0.0
     * @return void
     */
    public static function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Questions', 'Post type general name', 'askro' ),
            'singular_name'         => _x( 'Question', 'Post type singular name', 'askro' ),
            'menu_name'            => _x( 'Questions', 'Admin Menu text', 'askro' ),
            'name_admin_bar'       => _x( 'Question', 'Add New on Toolbar', 'askro' ),
            'add_new'              => __( 'Add New', 'askro' ),
            'add_new_item'         => __( 'Add New Question', 'askro' ),
            'new_item'             => __( 'New Question', 'askro' ),
            'edit_item'            => __( 'Edit Question', 'askro' ),
            'view_item'            => __( 'View Question', 'askro' ),
            'all_items'            => __( 'All Questions', 'askro' ),
            'search_items'         => __( 'Search Questions', 'askro' ),
            'parent_item_colon'    => __( 'Parent Questions:', 'askro' ),
            'not_found'            => __( 'No questions found.', 'askro' ),
            'not_found_in_trash'   => __( 'No questions found in Trash.', 'askro' ),
            'featured_image'       => _x( 'Question Featured Image', 'Overrides the "Featured Image" phrase', 'askro' ),
            'set_featured_image'   => _x( 'Set featured image', 'Overrides the "Set featured image" phrase', 'askro' ),
            'remove_featured_image' => _x( 'Remove featured image', 'Overrides the "Remove featured image" phrase', 'askro' ),
            'use_featured_image'   => _x( 'Use as featured image', 'Overrides the "Use as featured image" phrase', 'askro' ),
            'archives'             => _x( 'Question archives', 'The post type archive label', 'askro' ),
            'insert_into_item'     => _x( 'Insert into question', 'Overrides the "Insert into post" phrase', 'askro' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this question', 'Overrides the "Uploaded to this post" phrase', 'askro' ),
            'filter_items_list'    => _x( 'Filter questions list', 'Screen reader text for the filter links', 'askro' ),
            'items_list_navigation' => _x( 'Questions list navigation', 'Screen reader text for the pagination', 'askro' ),
            'items_list'           => _x( 'Questions list', 'Screen reader text for the items list', 'askro' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'show_in_rest'       => true,
            'query_var'          => 'question',
            'capability_type'    => 'post',
            'has_archive'        => 'questions', // Explicitly set archive slug to match rewrite slug
            'rewrite'            => array(
                'slug'       => 'questions',
                'with_front' => false,
                'feeds'      => true,
                'pages'      => true
            ),
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-editor-help',
            'supports'           => array(
                'title',
                'editor',
                'author',
                'thumbnail',
                'comments',
                'custom-fields'
            ),
            'taxonomies'         => array( 'question_category', 'question_tag' ),
            'can_export'         => true,
            'delete_with_user'   => false,
            'exclude_from_search' => false,
        );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Get the post type key
     *
     * @since 1.0.0
     * @return string The post type key
     */
    public static function get_post_type() {
        return self::POST_TYPE;
    }
}
