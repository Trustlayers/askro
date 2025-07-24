<?php
/**
 * Answer Form Template Part
 *
 * Displays the form for submitting answers to the current question.
 * Only shown to logged-in users.
 *
 * @package Askro
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Only show form to logged-in users
if ( ! is_user_logged_in() ) {
    return;
}

$current_user = wp_get_current_user();
?>

<section class="askro-answer-form-section" id="answer-form">
    <div class="answer-form-header">
        <h3 class="answer-form-title">
            <?php esc_html_e( 'Your Answer', 'askro' ); ?>
        </h3>
        <p class="answer-form-description">
            <?php esc_html_e( 'Please provide a detailed answer to help the community.', 'askro' ); ?>
        </p>
    </div>

    <form class="askro-answer-form" id="askro-answer-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field( 'askro_submit_answer', 'askro_answer_nonce' ); ?>
        <input type="hidden" name="question_id" value="<?php echo esc_attr( get_the_ID() ); ?>">
        <input type="hidden" name="action" value="askro_submit_answer">
        
        <!-- Loading Spinner Container -->
        <div class="answer-form-loading" id="answer-form-loading" style="display: none;">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <span class="loading-text"><?php esc_html_e( 'Submitting your answer...', 'askro' ); ?></span>
            </div>
        </div>

        <div class="form-group answer-content-group">
            <label for="answer-content" class="form-label">
                <?php esc_html_e( 'Answer Content', 'askro' ); ?>
                <span class="required">*</span>
            </label>
            <?php
            // Use WordPress editor for answer content
            wp_editor( '', 'answer_content', array(
                'textarea_name' => 'answer_content',
                'textarea_rows' => 10,
                'media_buttons' => true,
                'teeny'         => false,
                'dfw'           => false,
                'tinymce'       => array(
                    'resize'             => false,
                    'wordpress_adv_hidden' => false,
                    'add_unload_trigger' => false,
                    'statusbar'          => false,
                    'wp_autoresize_on'   => false,
                ),
                'quicktags'     => array(
                    'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code'
                )
            ) );
            ?>
            <div class="field-error" id="answer-content-error"></div>
        </div>

        <div class="form-group answer-attachments-group">
            <label for="answer-attachments" class="form-label">
                <?php esc_html_e( 'Attachments (Optional)', 'askro' ); ?>
            </label>
            <input 
                type="file" 
                id="answer-attachments" 
                name="answer_attachments[]" 
                multiple 
                accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt"
                class="form-control file-input"
            >
            <small class="form-text">
                <?php esc_html_e( 'Accepted formats: JPG, PNG, GIF, PDF, DOC, DOCX, TXT. Max 5MB per file.', 'askro' ); ?>
            </small>
            <div class="field-error" id="answer-attachments-error"></div>
        </div>

        <div class="form-group answer-guidelines">
            <div class="guidelines-box">
                <h4><?php esc_html_e( 'Answer Guidelines', 'askro' ); ?></h4>
                <ul>
                    <li><?php esc_html_e( 'Provide a clear and detailed explanation', 'askro' ); ?></li>
                    <li><?php esc_html_e( 'Include examples or code snippets when relevant', 'askro' ); ?></li>
                    <li><?php esc_html_e( 'Back up your answer with reliable sources', 'askro' ); ?></li>
                    <li><?php esc_html_e( 'Be respectful and constructive', 'askro' ); ?></li>
                </ul>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary answer-submit-btn">
                <span class="btn-text"><?php esc_html_e( 'Post Your Answer', 'askro' ); ?></span>
                <span class="btn-loading" style="display: none;">
                    <span class="spinner"></span>
                    <?php esc_html_e( 'Posting...', 'askro' ); ?>
                </span>
            </button>
            
            <button type="button" class="btn btn-secondary answer-preview-btn">
                <?php esc_html_e( 'Preview', 'askro' ); ?>
            </button>
            
            <button type="button" class="btn btn-outline answer-cancel-btn">
                <?php esc_html_e( 'Cancel', 'askro' ); ?>
            </button>
        </div>

        <div class="form-messages">
            <div class="success-message" id="answer-success-message" style="display: none;"></div>
            <div class="error-message" id="answer-error-message" style="display: none;"></div>
        </div>
    </form>

    <!-- Answer Preview Modal -->
    <div class="answer-preview-modal" id="answer-preview-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h4><?php esc_html_e( 'Answer Preview', 'askro' ); ?></h4>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="preview-content" id="answer-preview-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-preview">
                    <?php esc_html_e( 'Close Preview', 'askro' ); ?>
                </button>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize answer form functionality
    if (typeof window.askroAnswerForm === 'undefined') {
        window.askroAnswerForm = {
            init: function() {
                this.bindEvents();
            },
            
            bindEvents: function() {
                const form = document.querySelector('.askro-answer-form');
                const previewBtn = document.querySelector('.answer-preview-btn');
                const cancelBtn = document.querySelector('.answer-cancel-btn');
                
                if (form) {
                    form.addEventListener('submit', this.handleSubmit.bind(this));
                }
                
                if (previewBtn) {
                    previewBtn.addEventListener('click', this.showPreview.bind(this));
                }
                
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', this.resetForm.bind(this));
                }
                
                // Modal close handlers
                document.addEventListener('click', function(e) {
                    if (e.target.matches('.modal-close, .close-preview, .modal-overlay')) {
                        document.getElementById('answer-preview-modal').style.display = 'none';
                    }
                });
            },
            
            handleSubmit: function(e) {
                e.preventDefault();
                
                const submitBtn = document.querySelector('.answer-submit-btn');
                const btnText = submitBtn.querySelector('.btn-text');
                const btnLoading = submitBtn.querySelector('.btn-loading');
                
                // Show loading state
                btnText.style.display = 'none';
                btnLoading.style.display = 'inline-flex';
                submitBtn.disabled = true;
                
                // Get editor content
                let content = '';
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('answer_content')) {
                    content = tinyMCE.get('answer_content').getContent();
                } else {
                    content = document.getElementById('answer_content').value;
                }
                
                const formData = new FormData(e.target);
                formData.set('answer_content', content);
                
                // TODO: Implement AJAX submission when answer system is ready
                // For now, use traditional form submission
                console.log('Answer form submitted - will be implemented with answer system');
                
                // Reset loading state (temporary)
                setTimeout(() => {
                    btnText.style.display = 'inline';
                    btnLoading.style.display = 'none';
                    submitBtn.disabled = false;
                    
                    // Show temporary success message
                    const successMsg = document.getElementById('answer-success-message');
                    successMsg.textContent = 'Answer submission will be implemented when answer system is ready.';
                    successMsg.style.display = 'block';
                    
                    setTimeout(() => {
                        successMsg.style.display = 'none';
                    }, 5000);
                }, 1000);
            },
            
            showPreview: function() {
                let content = '';
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('answer_content')) {
                    content = tinyMCE.get('answer_content').getContent();
                } else {
                    content = document.getElementById('answer_content').value;
                }
                
                if (!content.trim()) {
                    alert('<?php echo esc_js( __( 'Please enter some content to preview.', 'askro' ) ); ?>');
                    return;
                }
                
                document.getElementById('answer-preview-content').innerHTML = content;
                document.getElementById('answer-preview-modal').style.display = 'block';
            },
            
            resetForm: function() {
                if (confirm('<?php echo esc_js( __( 'Are you sure you want to clear the form?', 'askro' ) ); ?>')) {
                    const form = document.querySelector('.askro-answer-form');
                    form.reset();
                    
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('answer_content')) {
                        tinyMCE.get('answer_content').setContent('');
                    }
                    
                    // Clear error messages
                    document.querySelectorAll('.field-error').forEach(el => el.textContent = '');
                    document.getElementById('answer-success-message').style.display = 'none';
                    document.getElementById('answer-error-message').style.display = 'none';
                }
            }
        };
        
        window.askroAnswerForm.init();
    }
});
</script>
