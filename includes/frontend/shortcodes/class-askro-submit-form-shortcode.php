<?php
/**
 * Submit Question Form Shortcode
 *
 * @package Askro
 * @subpackage Frontend/Shortcodes
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Askro_Submit_Form_Shortcode
 *
 * Handles the rendering and processing of the "Submit Question" form via shortcode.
 * Provides secure form handling with comprehensive validation and error management.
 *
 * @since 1.0.0
 */
final class Askro_Submit_Form_Shortcode {

    /**
     * Form submission errors
     *
     * @var array
     */
    private static $errors = [];

    /**
     * Form submission success message
     *
     * @var string
     */
    private static $success_message = '';

    /**
     * Initialize the shortcode
     *
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        add_shortcode('askro_submit_question_form', [self::class, 'render_form']);
        add_action('init', [self::class, 'handle_submission']);
        
        // Add AJAX endpoints
        add_action('wp_ajax_askro_submit_question', [self::class, 'handle_ajax_submission']);
        add_action('wp_ajax_nopriv_askro_submit_question', [self::class, 'handle_ajax_submission']);
    }

    /**
     * Render the question submission form
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string HTML form
     */
    public static function render_form( $atts = [] ) {
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            return '<div class="alert alert-warning">' . __( 'You must be logged in to submit a question.', 'askro' ) . '</div>';
        }

        // Check user capabilities
        if ( ! current_user_can( 'publish_posts' ) ) {
            return '<div class="alert alert-error">' . __( 'You do not have permission to submit questions.', 'askro' ) . '</div>';
        }

        // Get previously submitted values for form persistence
        $form_data = self::get_form_data();

        ob_start();
        ?>
        <div class="askro-submit-form-container">
            <?php self::display_messages(); ?>
            
            <!-- AJAX Messages Container -->
            <div id="askro-form-messages" class="hidden"></div>
            
            <form id="askro-submit-form" method="post" enctype="multipart/form-data" class="askro-question-form space-y-4">
                <?php wp_nonce_field( 'askro_submit_question', 'askro_nonce' ); ?>
                
                <!-- Question Title -->
                <div class="form-control w-full">
                    <label class="label" for="question_title">
                        <span class="label-text"><?php esc_html_e( 'Question Title', 'askro' ); ?> <span class="text-error">*</span></span>
                    </label>
                    <input 
                        type="text" 
                        name="question_title" 
                        id="question_title" 
                        required 
                        class="input input-bordered w-full <?php echo self::has_error('question_title') ? 'input-error' : ''; ?>" 
                        value="<?php echo esc_attr( $form_data['question_title'] ); ?>"
                        placeholder="<?php esc_attr_e( 'Enter your question title...', 'askro' ); ?>"
                    />
                    <?php if ( self::has_error('question_title') ): ?>
                        <label class="label">
                            <span class="label-text-alt text-error"><?php echo esc_html( self::get_error('question_title') ); ?></span>
                        </label>
                    <?php endif; ?>
                </div>

                <!-- Question Content -->
                <div class="form-control w-full">
                    <label class="label" for="question_content">
                        <span class="label-text"><?php esc_html_e( 'Question Content', 'askro' ); ?> <span class="text-error">*</span></span>
                    </label>
                    <?php 
                    wp_editor( $form_data['question_content'], 'question_content', [
                        'textarea_name' => 'question_content',
                        'media_buttons' => true,
                        'textarea_rows' => 8,
                        'teeny' => false,
                        'quicktags' => true
                    ]); 
                    ?>
                    <?php if ( self::has_error('question_content') ): ?>
                        <label class="label">
                            <span class="label-text-alt text-error"><?php echo esc_html( self::get_error('question_content') ); ?></span>
                        </label>
                    <?php endif; ?>
                </div>

                <!-- Question Category -->
                <div class="form-control w-full">
                    <label class="label" for="question_category">
                        <span class="label-text"><?php esc_html_e( 'Category', 'askro' ); ?></span>
                    </label>
                    <select name="question_category" id="question_category" class="select select-bordered w-full">
                        <option value=""><?php esc_html_e( 'Select a category...', 'askro' ); ?></option>
                        <?php
                        $terms = get_terms([
                            'taxonomy' => 'question_category', 
                            'hide_empty' => false,
                            'orderby' => 'name',
                            'order' => 'ASC'
                        ]);
                        
                        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                            foreach ( $terms as $term ) {
                                $selected = selected( $form_data['question_category'], $term->term_id, false );
                                echo '<option value="' . esc_attr( $term->term_id ) . '"' . $selected . '>' . esc_html( $term->name ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Question Tags -->
                <div class="form-control w-full">
                    <label class="label" for="question_tags">
                        <span class="label-text"><?php esc_html_e( 'Tags', 'askro' ); ?></span>
                        <span class="label-text-alt"><?php esc_html_e( 'Separate tags with commas', 'askro' ); ?></span>
                    </label>
                    <input 
                        type="text" 
                        name="question_tags" 
                        id="question_tags" 
                        class="input input-bordered w-full askro-tagify" 
                        value="<?php echo esc_attr( $form_data['question_tags'] ); ?>"
                        placeholder="<?php esc_attr_e( 'e.g., wordpress, php, javascript', 'askro' ); ?>"
                    />
                </div>

                <!-- File Upload -->
                <div class="form-control w-full">
                    <label class="label" for="question_attachment">
                        <span class="label-text"><?php esc_html_e( 'Attachment (Optional)', 'askro' ); ?></span>
                        <span class="label-text-alt"><?php esc_html_e( 'Max file size: 2MB', 'askro' ); ?></span>
                    </label>
                    <input 
                        type="file" 
                        name="question_attachment" 
                        id="question_attachment" 
                        class="file-input file-input-bordered w-full" 
                        accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt"
                    />
                    <?php if ( self::has_error('question_attachment') ): ?>
                        <label class="label">
                            <span class="label-text-alt text-error"><?php echo esc_html( self::get_error('question_attachment') ); ?></span>
                        </label>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="form-control mt-6">
                    <div class="flex gap-2 justify-end">
                        <button type="submit" name="askro_action" value="draft" class="btn btn-outline">
                            <?php esc_html_e( 'Save as Draft', 'askro' ); ?>
                        </button>
                        <button type="submit" name="askro_action" value="publish" class="btn btn-primary">
                            <?php esc_html_e( 'Publish Question', 'askro' ); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle AJAX form submission
     *
     * @since 1.0.0
     * @return void
     */
    public static function handle_ajax_submission() {
        // Process the submission and get result
        $result = self::process_submission();
        
        // Send JSON response based on result
        if ( $result['success'] ) {
            wp_send_json_success( $result['data'] );
        } else {
            wp_send_json_error( $result['data'] );
        }
    }

    /**
     * Handle the form submission (legacy for non-AJAX)
     *
     * @since 1.0.0
     * @return void
     */
    public static function handle_submission() {
        // Check if form was submitted
        if ( ! isset( $_POST['askro_nonce'] ) ) {
            return;
        }

        // Process the submission
        $result = self::process_submission();
        
        if ( $result['success'] ) {
            // Redirect to the new post for non-AJAX submissions
            wp_redirect( $result['data']['redirect_url'] );
            exit();
        }
        // For errors, let the form display them
    }

    /**
     * Process form submission (used by both AJAX and non-AJAX)
     *
     * @since 1.0.0
     * @return array Result array with success status and data
     */
    private static function process_submission() {
        // Clear previous errors
        self::$errors = [];
        
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['askro_nonce'], 'askro_submit_question' ) ) {
            self::add_error( 'general', __( 'Security check failed. Please try again.', 'askro' ) );
            return [
                'success' => false,
                'data' => ['errors' => self::$errors]
            ];
        }

        // Check user capabilities
        if ( ! current_user_can( 'publish_posts' ) ) {
            self::add_error( 'general', __( 'You do not have permission to submit questions.', 'askro' ) );
            return [
                'success' => false,
                'data' => ['errors' => self::$errors]
            ];
        }

        // Sanitize and validate form data
        $form_data = self::sanitize_form_data( $_POST );
        
        if ( ! self::validate_form_data( $form_data ) ) {
            return [
                'success' => false,
                'data' => ['errors' => self::$errors]
            ];
        }

        // Create the post
        $post_data = [
            'post_title'   => $form_data['question_title'],
            'post_content' => $form_data['question_content'],
            'post_status'  => $form_data['askro_action'] === 'publish' ? 'publish' : 'draft',
            'post_author'  => get_current_user_id(),
            'post_type'    => 'question',
        ];

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) {
            self::add_error( 'general', __( 'Failed to create question. Please try again.', 'askro' ) );
            return [
                'success' => false,
                'data' => ['errors' => self::$errors]
            ];
        }

        if ( $post_id ) {
            // Set category
            if ( ! empty( $form_data['question_category'] ) ) {
                wp_set_post_terms( $post_id, [ $form_data['question_category'] ], 'question_category' );
            }

            // Set tags
            if ( ! empty( $form_data['question_tags'] ) ) {
                $tags = array_map( 'trim', explode( ',', $form_data['question_tags'] ) );
                $tags = array_filter( $tags ); // Remove empty tags
                wp_set_post_terms( $post_id, $tags, 'question_tag' );
            }

            // Handle file upload
            if ( ! empty( $_FILES['question_attachment']['name'] ) ) {
                $upload_result = self::handle_file_upload( $post_id );
                if ( ! $upload_result ) {
                    // File upload failed, but we still have a post created
                    return [
                        'success' => false,
                        'data' => ['errors' => self::$errors]
                    ];
                }
            }

            // Prepare success response
            $action_text = $form_data['askro_action'] === 'publish' ? __( 'published', 'askro' ) : __( 'saved as draft', 'askro' );
            $success_message = sprintf( __( 'Your question has been %s successfully!', 'askro' ), $action_text );
            
            return [
                'success' => true,
                'data' => [
                    'message' => $success_message,
                    'redirect_url' => get_permalink( $post_id ),
                    'post_id' => $post_id
                ]
            ];
        }
        
        // Fallback error
        self::add_error( 'general', __( 'An unexpected error occurred. Please try again.', 'askro' ) );
        return [
            'success' => false,
            'data' => ['errors' => self::$errors]
        ];
    }

    /**
     * Sanitize form data
     *
     * @since 1.0.0
     * @param array $raw_data Raw POST data
     * @return array Sanitized data
     */
    private static function sanitize_form_data( $raw_data ) {
        return [
            'question_title'    => sanitize_text_field( $raw_data['question_title'] ?? '' ),
            'question_content'  => wp_kses_post( $raw_data['question_content'] ?? '' ),
            'question_category' => absint( $raw_data['question_category'] ?? 0 ),
            'question_tags'     => sanitize_text_field( $raw_data['question_tags'] ?? '' ),
            'askro_action'      => sanitize_text_field( $raw_data['askro_action'] ?? 'draft' ),
        ];
    }

    /**
     * Validate form data
     *
     * @since 1.0.0
     * @param array $data Sanitized form data
     * @return bool True if valid, false otherwise
     */
    private static function validate_form_data( $data ) {
        $is_valid = true;

        // Validate title
        if ( empty( $data['question_title'] ) ) {
            self::add_error( 'question_title', __( 'Question title is required.', 'askro' ) );
            $is_valid = false;
        } elseif ( strlen( $data['question_title'] ) > 200 ) {
            self::add_error( 'question_title', __( 'Question title must be less than 200 characters.', 'askro' ) );
            $is_valid = false;
        }

        // Validate content
        if ( empty( $data['question_content'] ) ) {
            self::add_error( 'question_content', __( 'Question content is required.', 'askro' ) );
            $is_valid = false;
        }

        // Validate action
        if ( ! in_array( $data['askro_action'], [ 'publish', 'draft' ] ) ) {
            self::add_error( 'general', __( 'Invalid form action.', 'askro' ) );
            $is_valid = false;
        }

        return $is_valid;
    }

    /**
     * Handle file upload
     *
     * @since 1.0.0
     * @param int $post_id Post ID to attach file to
     * @return bool True on success, false on failure
     */
    private static function handle_file_upload( $post_id ) {
        // Check file size (2MB limit)
        if ( $_FILES['question_attachment']['size'] > 2 * 1024 * 1024 ) {
            self::add_error( 'question_attachment', __( 'File size must be less than 2MB.', 'askro' ) );
            return false;
        }

        // Define allowed file types
        $allowed_types = [ 'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt' ];
        $file_extension = strtolower( pathinfo( $_FILES['question_attachment']['name'], PATHINFO_EXTENSION ) );
        
        if ( ! in_array( $file_extension, $allowed_types ) ) {
            self::add_error( 'question_attachment', __( 'File type not allowed.', 'askro' ) );
            return false;
        }

        // Handle the upload
        $upload_overrides = [ 'test_form' => false ];
        $movefile = wp_handle_upload( $_FILES['question_attachment'], $upload_overrides );

        if ( $movefile && ! isset( $movefile['error'] ) ) {
            // Create attachment post
            $attachment = [
                'post_mime_type' => $movefile['type'],
                'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $movefile['file'] ) ),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ];

            $attach_id = wp_insert_attachment( $attachment, $movefile['file'], $post_id );
            
            if ( ! is_wp_error( $attach_id ) ) {
                // Generate attachment metadata
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                $attach_data = wp_generate_attachment_metadata( $attach_id, $movefile['file'] );
                wp_update_attachment_metadata( $attach_id, $attach_data );
                return true;
            } else {
                self::add_error( 'question_attachment', __( 'Failed to attach file to question.', 'askro' ) );
                return false;
            }
        } else {
            $error_message = isset( $movefile['error'] ) ? $movefile['error'] : __( 'Unknown upload error.', 'askro' );
            self::add_error( 'question_attachment', __( 'File upload failed: ', 'askro' ) . $error_message );
            return false;
        }
    }

    /**
     * Add an error message
     *
     * @since 1.0.0
     * @param string $field Field name
     * @param string $message Error message
     * @return void
     */
    private static function add_error( $field, $message ) {
        self::$errors[ $field ] = $message;
    }

    /**
     * Check if field has error
     *
     * @since 1.0.0
     * @param string $field Field name
     * @return bool
     */
    private static function has_error( $field ) {
        return isset( self::$errors[ $field ] );
    }

    /**
     * Get error message for field
     *
     * @since 1.0.0
     * @param string $field Field name
     * @return string
     */
    private static function get_error( $field ) {
        return self::$errors[ $field ] ?? '';
    }

    /**
     * Get form data for persistence
     *
     * @since 1.0.0
     * @return array
     */
    private static function get_form_data() {
        return [
            'question_title'    => $_POST['question_title'] ?? '',
            'question_content'  => $_POST['question_content'] ?? '',
            'question_category' => $_POST['question_category'] ?? '',
            'question_tags'     => $_POST['question_tags'] ?? '',
        ];
    }

    /**
     * Display error and success messages
     *
     * @since 1.0.0
     * @return void
     */
    private static function display_messages() {
        // Display general errors
        if ( self::has_error( 'general' ) ) {
            echo '<div class="alert alert-error mb-4">' . esc_html( self::get_error( 'general' ) ) . '</div>';
        }

        // Display success message
        if ( ! empty( self::$success_message ) ) {
            echo '<div class="alert alert-success mb-4">' . esc_html( self::$success_message ) . '</div>';
        }
    }
}

