/**
 * Askro Plugin - Main JavaScript File
 * 
 * This file initializes and manages the main JavaScript functionality
 * for the Askro WordPress plugin, including Chart.js, Swiper, and CropperJS.
 * 
 * @package Askro
 * @version 1.0.0
 */

// Import dependencies (when using a bundler)
// import Chart from 'chart.js/auto';
// import { Swiper, Navigation, Pagination } from 'swiper';
// import Cropper from 'cropperjs';

(function($) {
    'use strict';

    /**
     * Main Askro object
     */
    const Askro = {
        
        /**
         * Initialize all components
         */
        init: function() {
            this.initCharts();
            this.initSwipers();
            this.initCroppers();
            this.initTagify();
            this.initToastr();
            this.initSubmitForm();
            this.initVoting();
            this.initAnswerForm();
            this.initComments();
            this.initArchivePage();
            this.initUserProfile();
            this.bindEvents();
        },

        /**
         * Initialize Chart.js charts
         */
        initCharts: function() {
            const chartElements = document.querySelectorAll('.askro-chart');
            
            chartElements.forEach(function(element) {
                if (typeof Chart !== 'undefined') {
                    const chartType = element.dataset.chartType || 'line';
                    const chartData = JSON.parse(element.dataset.chartData || '{}');
                    
                    new Chart(element, {
                        type: chartType,
                        data: chartData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                title: {
                                    display: true,
                                    text: element.dataset.chartTitle || 'Chart'
                                }
                            }
                        }
                    });
                }
            });
        },

        /**
         * Initialize Swiper sliders
         */
        initSwipers: function() {
            const swiperElements = document.querySelectorAll('.askro-swiper');
            
            swiperElements.forEach(function(element) {
                if (typeof Swiper !== 'undefined') {
                    new Swiper(element, {
                        slidesPerView: 1,
                        spaceBetween: 10,
                        navigation: {
                            nextEl: '.swiper-button-next',
                            prevEl: '.swiper-button-prev',
                        },
                        pagination: {
                            el: '.swiper-pagination',
                            clickable: true,
                        },
                        breakpoints: {
                            640: {
                                slidesPerView: 2,
                                spaceBetween: 20,
                            },
                            768: {
                                slidesPerView: 3,
                                spaceBetween: 30,
                            },
                            1024: {
                                slidesPerView: 4,
                                spaceBetween: 40,
                            },
                        },
                    });
                }
            });
        },

        /**
         * Initialize Cropper.js image croppers
         */
        initCroppers: function() {
            const cropperElements = document.querySelectorAll('.askro-cropper');
            
            cropperElements.forEach(function(element) {
                if (typeof Cropper !== 'undefined') {
                    const aspectRatio = parseFloat(element.dataset.aspectRatio) || 16/9;
                    
                    new Cropper(element, {
                        aspectRatio: aspectRatio,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 0.8,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                    });
                }
            });
        },

        /**
         * Initialize Tagify for tag inputs
         */
        initTagify: function() {
            const tagifyElements = document.querySelectorAll('.askro-tagify');
            
            tagifyElements.forEach(function(element) {
                if (typeof Tagify !== 'undefined') {
                    const whitelist = element.dataset.whitelist ? JSON.parse(element.dataset.whitelist) : [];
                    const maxTags = element.dataset.maxTags ? parseInt(element.dataset.maxTags) : 10;
                    
                    new Tagify(element, {
                        whitelist: whitelist,
                        maxTags: maxTags,
                        dropdown: {
                            maxItems: 20,
                            classname: 'tagify__dropdown',
                            enabled: 0,
                            closeOnSelect: false
                        },
                        editTags: 1,
                        duplicates: false
                    });
                }
            });
        },

        /**
         * Initialize Toastr for notifications
         */
        initToastr: function() {
            if (typeof toastr !== 'undefined') {
                // Configure toastr options
                toastr.options = {
                    closeButton: true,
                    debug: false,
                    newestOnTop: true,
                    progressBar: true,
                    positionClass: 'toast-top-right',
                    preventDuplicates: false,
                    onclick: null,
                    showDuration: '300',
                    hideDuration: '1000',
                    timeOut: '5000',
                    extendedTimeOut: '1000',
                    showEasing: 'swing',
                    hideEasing: 'linear',
                    showMethod: 'fadeIn',
                    hideMethod: 'fadeOut'
                };
            }
        },

        /**
         * Show success toast notification
         */
        showSuccess: function(message, title) {
            if (typeof toastr !== 'undefined') {
                toastr.success(message, title || 'Success');
            }
        },

        /**
         * Show error toast notification
         */
        showError: function(message, title) {
            if (typeof toastr !== 'undefined') {
                toastr.error(message, title || 'Error');
            }
        },

        /**
         * Initialize Comments functionality
         */
        initComments: function() {
            document.querySelectorAll('.askro-comment-form').forEach(form => {
                form.addEventListener('submit', this.handleCommentSubmit.bind(this));
            });

            document.addEventListener('click', event => {
                const btn = event.target.closest('.askro-comment-delete');
                if (btn) {
                    this.handleCommentDelete(btn);
                }
            });

            document.addEventListener('click', event => {
                const btn = event.target.closest('.askro-reply-link');
                if (btn) {
                    this.toggleReplyForm(btn);
                }
            });
        },

        /**
         * Handle Comment Submission
         */
        handleCommentSubmit: function(event) {
            event.preventDefault();

            const form = event.target;
            const loadingIndicator = form.querySelector('.askro-comment-form-loading');

            const formData = new FormData(form);
            formData.append('action', 'askro_submit_comment');

            fetch(askroAjax.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const commentList = document.getElementById('askro-comments-' + form.dataset.postId);
                    if (commentList) {
                        commentList.insertAdjacentHTML('beforeend', data.comment_html);
                    }
                    form.reset();
                    this.showSuccess(data.message);
                } else {
                    this.showError(data.message);
                }
            })
            .catch(() => {
                this.showError('An unexpected error occurred. Please try again.');
            })
            .finally(() => {
                if (loadingIndicator) loadingIndicator.style.display = 'none';
            });
        },

        /**
        * Toggle the reply form
        */
        toggleReplyForm: function(button) {
            const commentId = button.getAttribute('data-comment-id');
            const replyForm = document.getElementById('askro-reply-form-' + commentId);
            if (replyForm) {
                replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
            }
        },

        /**
         * Handle Comment Deletion
         */
        handleCommentDelete: function(button) {
            if (!confirm('Are you sure you want to delete this comment?')) return;

            const commentId = button.getAttribute('data-comment-id');
            const formData = new FormData();
            formData.append('action', 'askro_delete_comment');
            formData.append('comment_id', commentId);

            fetch(askroAjax.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const comment = document.querySelector('.askro-comment[data-comment-id="' + commentId + '"]');
                    if (comment) {
                        comment.remove();
                    }
                    this.showSuccess(data.message);
                } else {
                    this.showError(data.message);
                }
            })
            .catch(() => {
                this.showError('An unexpected error occurred. Please try again.');
            });
        },

        /**
         * Initialize Archive Page functionality
         */
        initArchivePage: function() {
            // Only run on archive pages
            if (!document.getElementById('askro-question-list')) return;

            // Load saved filters from localStorage
            this.loadSavedFilters();

            // Initialize event listeners
            this.bindArchiveEvents();
            
            // Initialize debounced search
            this.initDebouncedSearch();
        },

        /**
         * Load saved filters from localStorage
         */
        loadSavedFilters: function() {
            const savedFilters = localStorage.getItem('askro_filters');
            if (savedFilters) {
                try {
                    const filters = JSON.parse(savedFilters);
                    
                    // Apply sort button state
                    if (filters.sort) {
                        const sortButton = document.querySelector(`[data-sort="${filters.sort}"]`);
                        if (sortButton) {
                            // Remove active class from all sort buttons
                            document.querySelectorAll('[data-sort]').forEach(btn => {
                                btn.classList.remove('btn-active');
                            });
                            // Add active class to current sort
                            sortButton.classList.add('btn-active');
                        }
                    }
                    
                    // Apply filter button state
                    if (filters.filter) {
                        const filterButton = document.querySelector(`[data-filter="${filters.filter}"]`);
                        if (filterButton) {
                            filterButton.classList.add('btn-active');
                        }
                    }
                    
                    // Apply search query
                    if (filters.search_query) {
                        const searchInput = document.getElementById('askro-archive-search');
                        if (searchInput) {
                            searchInput.value = filters.search_query;
                        }
                    }
                } catch (e) {
                    console.warn('Failed to parse saved filters:', e);
                }
            }
        },

        /**
         * Bind archive page events
         */
        bindArchiveEvents: function() {
            // Sort button clicks
            document.querySelectorAll('[data-sort]').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Remove active class from all sort buttons
                    document.querySelectorAll('[data-sort]').forEach(btn => {
                        btn.classList.remove('btn-active');
                    });
                    
                    // Add active class to clicked button
                    button.classList.add('btn-active');
                    
                    // Fetch questions with new sort
                    this.fetchQuestions();
                });
            });
            
            // Filter button clicks
            document.querySelectorAll('[data-filter]').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Toggle filter button state
                    button.classList.toggle('btn-active');
                    
                    // Fetch questions with new filter
                    this.fetchQuestions();
                });
            });
            
            // Pagination clicks (using event delegation)
            document.addEventListener('click', (e) => {
                if (e.target.matches('.page-numbers')) {
                    e.preventDefault();
                    const pageUrl = new URL(e.target.href);
                    const page = pageUrl.searchParams.get('paged') || 1;
                    this.fetchQuestions(parseInt(page));
                }
            });
        },

        /**
         * Initialize debounced search
         */
        initDebouncedSearch: function() {
            const searchInput = document.getElementById('askro-archive-search');
            if (!searchInput) return;
            
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.fetchQuestions(1); // Reset to page 1 on search
                }, 500); // 500ms debounce
            });
        },

        /**
         * Fetch questions based on current filters
         */
        fetchQuestions: function(page = 1) {
            const questionList = document.getElementById('askro-question-list');
            if (!questionList) return;
            
            // Get current filter state
            const currentSort = document.querySelector('[data-sort].btn-active')?.dataset.sort || 'latest';
            const currentFilter = document.querySelector('[data-filter].btn-active')?.dataset.filter || '';
            const searchQuery = document.getElementById('askro-archive-search')?.value || '';
            
            // Save current state to localStorage
            const filters = {
                sort: currentSort,
                filter: currentFilter,
                search_query: searchQuery,
                page: page
            };
            localStorage.setItem('askro_filters', JSON.stringify(filters));
            
            // Show loading state
            questionList.innerHTML = '<div class="text-center py-8"><span class="loading loading-spinner loading-lg"></span></div>';
            
            // Prepare AJAX data
            const formData = new FormData();
            formData.append('action', 'askro_filter_questions');
            formData.append('sort', currentSort);
            formData.append('filter', currentFilter);
            formData.append('search_query', searchQuery);
            formData.append('paged', page);
            formData.append('security', askroAjax.nonce);
            
            // Make AJAX request
            fetch(askroAjax.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update question list
                    questionList.innerHTML = data.data.html;
                    
                    // Update pagination
                    const paginationContainer = document.getElementById('askro-pagination');
                    if (paginationContainer) {
                        paginationContainer.innerHTML = data.data.pagination;
                    }
                    
                    // Scroll to top of question list
                    questionList.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    this.showError('Failed to load questions. Please try again.');
                    questionList.innerHTML = '<p class="text-center py-8">Failed to load questions. Please refresh the page.</p>';
                }
            })
            .catch(error => {
                console.error('Archive fetch error:', error);
                this.showError('An unexpected error occurred. Please try again.');
                questionList.innerHTML = '<p class="text-center py-8">Failed to load questions. Please refresh the page.</p>';
            });
        },

        /**
         * Show info toast notification
         */
        showInfo: function(message, title) {
            if (typeof toastr !== 'undefined') {
                toastr.info(message, title || 'Info');
            }
        },

        /**
         * Show warning toast notification
         */
        showWarning: function(message, title) {
            if (typeof toastr !== 'undefined') {
                toastr.warning(message, title || 'Warning');
            }
        },

        /**
         * Initialize the submit question form
         */
        initSubmitForm: function() {
            const form = document.getElementById('askro-submit-form');
            if (!form) return;

            // Initialize Tagify specifically for the submit form
            const tagInput = form.querySelector('#question_tags');
            if (tagInput && typeof Tagify !== 'undefined') {
                new Tagify(tagInput, {
                    maxTags: 10,
                    dropdown: {
                        maxItems: 20,
                        enabled: 0,
                        closeOnSelect: false
                    },
                    editTags: 1,
                    duplicates: false,
                    trim: true
                });
            }

            // Add submit event listener
            form.addEventListener('submit', this.handleFormSubmit.bind(this));
        },

        /**
         * Handle form submission
         */
        handleFormSubmit: function(event) {
            event.preventDefault();
            
            const form = event.target;
            const submitButtons = form.querySelectorAll('button[type="submit"]');
            const messagesContainer = document.getElementById('askro-form-messages');
            
            // Clear previous messages
            this.clearFormMessages();
            
            // Show loading state
            this.setFormLoading(true, submitButtons);
            
            // Get the clicked button's action
            const clickedButton = document.activeElement;
            const action = clickedButton.value || 'draft';
            
            // Prepare form data
            const formData = new FormData(form);
            formData.append('action', 'askro_submit_question');
            formData.append('askro_action', action);
            
            // Handle WordPress editor content
            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('question_content')) {
                formData.set('question_content', tinyMCE.get('question_content').getContent());
            }
            
            // Make AJAX request
            fetch(askroAjax.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                this.handleFormResponse(data, form);
            })
            .catch(error => {
                console.error('Form submission error:', error);
                this.showError('An unexpected error occurred. Please try again.');
            })
            .finally(() => {
                this.setFormLoading(false, submitButtons);
            });
        },

        /**
         * Handle form response
         */
        handleFormResponse: function(response, form) {
            if (response.success) {
                // Success response
                this.showSuccess(response.data.message);
                
                // Reset form
                form.reset();
                
                // Reset WordPress editor if present
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('question_content')) {
                    tinyMCE.get('question_content').setContent('');
                }
                
                // Redirect after a short delay
                setTimeout(() => {
                    window.location.href = response.data.redirect_url;
                }, 1500);
                
            } else {
                // Error response
                this.showError('Please correct the errors below.');
                this.displayFormErrors(response.data.errors);
            }
        },

        /**
         * Display form errors
         */
        displayFormErrors: function(errors) {
            const messagesContainer = document.getElementById('askro-form-messages');
            
            if (errors.general) {
                messagesContainer.innerHTML = `<div class="alert alert-error mb-4">${errors.general}</div>`;
                messagesContainer.classList.remove('hidden');
            }
            
            // Display field-specific errors
            Object.keys(errors).forEach(fieldName => {
                if (fieldName === 'general') return;
                
                const field = document.getElementById(fieldName) || document.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    // Add error class to field
                    field.classList.add('input-error', 'border-error');
                    
                    // Find or create error message element
                    const fieldContainer = field.closest('.form-control');
                    if (fieldContainer) {
                        // Remove existing error message
                        const existingError = fieldContainer.querySelector('.field-error-message');
                        if (existingError) {
                            existingError.remove();
                        }
                        
                        // Add new error message
                        const errorElement = document.createElement('label');
                        errorElement.className = 'label field-error-message';
                        errorElement.innerHTML = `<span class="label-text-alt text-error">${errors[fieldName]}</span>`;
                        fieldContainer.appendChild(errorElement);
                    }
                }
            });
        },

        /**
         * Clear form messages and errors
         */
        clearFormMessages: function() {
            const messagesContainer = document.getElementById('askro-form-messages');
            if (messagesContainer) {
                messagesContainer.innerHTML = '';
                messagesContainer.classList.add('hidden');
            }
            
            // Clear field errors
            const form = document.getElementById('askro-submit-form');
            if (form) {
                // Remove error classes from fields
                form.querySelectorAll('.input-error, .border-error').forEach(field => {
                    field.classList.remove('input-error', 'border-error');
                });
                
                // Remove error messages
                form.querySelectorAll('.field-error-message').forEach(error => {
                    error.remove();
                });
            }
        },

        /**
         * Set form loading state
         */
        setFormLoading: function(loading, buttons) {
            buttons.forEach(button => {
                if (loading) {
                    button.disabled = true;
                    button.classList.add('loading');
                    button.setAttribute('data-original-text', button.textContent);
                    button.textContent = 'Submitting...';
                } else {
                    button.disabled = false;
                    button.classList.remove('loading');
                    const originalText = button.getAttribute('data-original-text');
                    if (originalText) {
                        button.textContent = originalText;
                        button.removeAttribute('data-original-text');
                    }
                }
            });
        },

        /**
         * Initialize voting functionality
         */
        initVoting: function() {
            // Use event delegation to handle vote button clicks
            document.addEventListener('click', this.handleVoteClick.bind(this));
        },

        /**
         * Handle vote button clicks
         */
        handleVoteClick: function(event) {
            // Check if the clicked element is a vote button
            const button = event.target.closest('[data-action="askro-vote"]');
            if (!button) return;

            event.preventDefault();

            // Get vote data from button attributes
            const answerId = button.getAttribute('data-answer-id');
            const voteType = button.getAttribute('data-vote-type');

            if (!answerId || !voteType) {
                this.showError('Invalid vote data. Please refresh the page and try again.');
                return;
            }

            // Show loading state
            this.setVoteButtonLoading(button, true);

            // Prepare vote data
            const voteData = new FormData();
            voteData.append('action', 'askro_handle_vote');
            voteData.append('answer_id', answerId);
            voteData.append('vote_type', voteType);
            voteData.append('nonce', askroAjax.voting_nonce);

            // Make AJAX request
            fetch(askroAjax.ajaxurl, {
                method: 'POST',
                body: voteData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                this.handleVoteResponse(data, button, answerId);
            })
            .catch(error => {
                console.error('Vote submission error:', error);
                this.showError('An unexpected error occurred. Please try again.');
            })
            .finally(() => {
                this.setVoteButtonLoading(button, false);
            });
        },

        /**
         * Handle vote response
         */
        handleVoteResponse: function(response, button, answerId) {
            if (response.success) {
                // Update score display
                const scoreElement = document.querySelector(`[data-score-for="${answerId}"]`);
                if (scoreElement) {
                    scoreElement.textContent = response.data.new_score;
                }

                // Update button state
                const answerVoting = button.closest('.answer-voting');
                const voteType = button.getAttribute('data-vote-type');

                if (response.data.action === 'voted') {
                    // Remove 'voted' class from all buttons in this answer
                    if (answerVoting) {
                        answerVoting.querySelectorAll('.vote-button.voted').forEach(btn => {
                            btn.classList.remove('voted');
                        });
                    }
                    // Add 'voted' class to the clicked button
                    button.classList.add('voted');
                } else if (response.data.action === 'retracted') {
                    // Remove 'voted' class from the clicked button
                    button.classList.remove('voted');
                }

                // Show success message
                this.showSuccess(response.data.message);

            } else {
                // Show error message
                this.showError(response.data.message || 'Failed to process vote.');
            }
        },

        /**
         * Set vote button loading state
         */
        setVoteButtonLoading: function(button, loading) {
            if (loading) {
                button.disabled = true;
                button.classList.add('loading');
                // Store original content
                if (!button.hasAttribute('data-original-html')) {
                    button.setAttribute('data-original-html', button.innerHTML);
                }
                // Show loading indicator
                const label = button.querySelector('.vote-label');
                if (label) {
                    label.textContent = 'Loading...';
                }
            } else {
                button.disabled = false;
                button.classList.remove('loading');
                // Restore original content
                const originalHtml = button.getAttribute('data-original-html');
                if (originalHtml) {
                    button.innerHTML = originalHtml;
                    button.removeAttribute('data-original-html');
                }
            }
        },

        /**
         * Initialize answer form functionality
         */
        initAnswerForm: function() {
            const form = document.getElementById('askro-answer-form');
            if (!form) return;

            // Add beforeunload warning if editor has content
            this.initAnswerFormUnloadWarning();

            // Add Ctrl+Enter to submit functionality
            this.initAnswerFormKeyboardShortcuts();

            // Add form submit handler
            form.addEventListener('submit', this.handleAnswerFormSubmit.bind(this));
        },

        /**
         * Initialize unload warning for answer form
         */
        initAnswerFormUnloadWarning: function() {
            window.addEventListener('beforeunload', function(event) {
                const editor = document.getElementById('answer_content');
                let hasContent = false;

                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('answer_content')) {
                    hasContent = tinyMCE.get('answer_content').getContent().trim().length > 0;
                } else if (editor) {
                    hasContent = editor.value.trim().length > 0;
                }

                if (hasContent) {
                    const message = 'You have unsaved changes to your answer. Are you sure you want to leave?';
                    event.returnValue = message;
                    return message;
                }
            });
        },

        /**
         * Initialize keyboard shortcuts for answer form
         */
        initAnswerFormKeyboardShortcuts: function() {
            document.addEventListener('keydown', function(event) {
                // Check for Ctrl+Enter (or Cmd+Enter on Mac)
                if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                    const answerForm = document.getElementById('askro-answer-form');
                    const activeElement = document.activeElement;
                    
                    // Check if we're in the answer editor
                    if (answerForm && (activeElement.id === 'answer_content' || 
                        activeElement.closest('#wp-answer_content-wrap'))) {
                        event.preventDefault();
                        answerForm.dispatchEvent(new Event('submit'));
                    }
                }
            });
        },

        /**
         * Handle answer form submission
         */
        handleAnswerFormSubmit: function(event) {
            event.preventDefault();

            const form = event.target;
            const submitButton = form.querySelector('.answer-submit-btn');
            const loadingContainer = document.getElementById('answer-form-loading');

            // Get editor content
            let content = '';
            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('answer_content')) {
                content = tinyMCE.get('answer_content').getContent();
            } else {
                const editor = document.getElementById('answer_content');
                if (editor) {
                    content = editor.value;
                }
            }

            // Validate content
            if (!content.trim()) {
                this.showError('Please enter your answer before submitting.');
                return;
            }

            // Show loading state
            this.setAnswerFormLoading(true, submitButton, loadingContainer);

            // Prepare form data
            const formData = new FormData(form);
            formData.set('answer_content', content);

            // Make AJAX request
            fetch(askroAjax.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                this.handleAnswerFormResponse(data, form);
            })
            .catch(error => {
                console.error('Answer submission error:', error);
                this.showError('An unexpected error occurred. Please try again.');
            })
            .finally(() => {
                this.setAnswerFormLoading(false, submitButton, loadingContainer);
            });
        },

        /**
         * Handle answer form response
         */
        handleAnswerFormResponse: function(response, form) {
            if (response.success) {
                // Insert new answer HTML
                const answerContainer = document.getElementById('answer-list-container');
                if (answerContainer && response.data.new_answer_html) {
                    // Remove "no answers" message if it exists
                    const noAnswers = answerContainer.querySelector('.no-answers');
                    if (noAnswers) {
                        noAnswers.remove();
                    }

                    // Prepend new answer (most recent first)
                    answerContainer.insertAdjacentHTML('afterbegin', response.data.new_answer_html);

                    // Update answer count in header
                    this.updateAnswerCount();
                }

                // Reset form
                this.resetAnswerForm(form);

                // Show success message
                this.showSuccess('Your answer has been submitted successfully!');

                // Scroll to the new answer
                setTimeout(() => {
                    const newAnswer = answerContainer.querySelector('.answer-item:first-child');
                    if (newAnswer) {
                        newAnswer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 100);

            } else {
                // Show error message
                this.showError(response.data.message || 'Failed to submit your answer.');
            }
        },

        /**
         * Set answer form loading state
         */
        setAnswerFormLoading: function(loading, button, loadingContainer) {
            if (loading) {
                // Disable submit button
                button.disabled = true;
                button.classList.add('loading');
                
                // Show loading spinner
                if (loadingContainer) {
                    loadingContainer.style.display = 'block';
                }
                
                // Hide form content
                const formGroups = document.querySelectorAll('#askro-answer-form .form-group, #askro-answer-form .form-actions');
                formGroups.forEach(group => {
                    group.style.opacity = '0.6';
                    group.style.pointerEvents = 'none';
                });
            } else {
                // Re-enable submit button
                button.disabled = false;
                button.classList.remove('loading');
                
                // Hide loading spinner
                if (loadingContainer) {
                    loadingContainer.style.display = 'none';
                }
                
                // Show form content
                const formGroups = document.querySelectorAll('#askro-answer-form .form-group, #askro-answer-form .form-actions');
                formGroups.forEach(group => {
                    group.style.opacity = '1';
                    group.style.pointerEvents = 'auto';
                });
            }
        },

        /**
         * Reset answer form
         */
        resetAnswerForm: function(form) {
            // Reset form fields
            form.reset();

            // Clear editor content
            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('answer_content')) {
                tinyMCE.get('answer_content').setContent('');
            }

            // Clear any error messages
            const successMessage = document.getElementById('answer-success-message');
            const errorMessage = document.getElementById('answer-error-message');
            if (successMessage) successMessage.style.display = 'none';
            if (errorMessage) errorMessage.style.display = 'none';
        },

        /**
         * Update answer count display
         */
        updateAnswerCount: function() {
            const answersTitle = document.querySelector('.answers-title');
            const answerItems = document.querySelectorAll('.answer-item');
            
            if (answersTitle && answerItems.length > 0) {
                const count = answerItems.length;
                const newText = count === 1 ? `${count} Answer` : `${count} Answers`;
                answersTitle.textContent = newText;

                // Show sorting controls if we now have answers
                const sortingControls = document.querySelector('.answers-sorting');
                if (sortingControls && count > 0) {
                    sortingControls.style.display = 'block';
                }
            }
        },

        /**
         * Initialize User Profile functionality
         */
        initUserProfile: function() {
            // Only run on user profile pages
            if (!document.getElementById('askro-user-profile')) return;

            // Fetch chart data on page load
            this.fetchReputationHistory();

            // Initialize tab handling
            this.bindProfileTabEvents();

            // Load default tab (activity)
            this.loadProfileTab('activity');
        },

        /**
         * Fetch reputation history for chart
         */
        fetchReputationHistory: function() {
            const formData = new FormData();
            formData.append('action', 'askro_get_reputation_history');
            formData.append('security', askroAjax.nonce);

            fetch(askroAjax.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.initReputationChart(data.data.labels, data.data.data);
                } else {
                    console.warn('Failed to fetch reputation history:', data.data?.message);
                }
            })
            .catch(error => {
                console.error('Error fetching reputation history:', error);
            });
        },

        /**
         * Initialize reputation chart using Chart.js
         */
        initReputationChart: function(labels, data) {
            const canvas = document.getElementById('askro-reputation-chart');
            if (!canvas || typeof Chart === 'undefined') return;

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Reputation Over Time',
                        data: data,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Reputation History (Last 30 Days)'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        /**
         * Bind profile tab events
         */
        bindProfileTabEvents: function() {
            const tabLinks = document.querySelectorAll('.askro-tabs a[data-tab]');
            
            tabLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Update active tab
                    tabLinks.forEach(tab => tab.closest('li').classList.remove('active'));
                    link.closest('li').classList.add('active');
                    
                    // Load tab content
                    this.loadProfileTab(link.dataset.tab);
                });
            });
        },

        /**
         * Load profile tab content
         */
        loadProfileTab: function(tabName) {
            const contentContainer = document.getElementById('askro-profile-content');
            if (!contentContainer) return;

            // Show loading state
            contentContainer.innerHTML = '<div class="text-center py-8"><span class="loading loading-spinner loading-lg"></span></div>';

            // Prepare AJAX data
            const formData = new FormData();
            formData.append('action', 'askro_get_profile_tab_content');
            formData.append('tab', tabName);
            formData.append('paged', 1);
            formData.append('security', askroAjax.nonce);

            // Make AJAX request
            fetch(askroAjax.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    contentContainer.innerHTML = data.data.html;
                } else {
                    contentContainer.innerHTML = '<p class="text-red-500">Error loading content: ' + (data.data?.message || 'Unknown error') + '</p>';
                }
            })
            .catch(error => {
                console.error('Error loading tab content:', error);
                contentContainer.innerHTML = '<p class="text-red-500">An unexpected error occurred while loading the content.</p>';
            });
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Custom event handlers can be added here
            $(document).on('click', '.askro-btn', function(e) {
                // Add any global button click handlers
            });

            // Handle dynamic content loading
            $(document).on('askro:contentLoaded', function() {
                Askro.init();
            });

            // Handle form submissions with toast notifications
            $(document).on('submit', '.askro-form', function(e) {
                // Add loading state to submit buttons
                $(this).find('button[type="submit"]').prop('disabled', true).addClass('loading');
            });
        }
    };

    /**
     * Initialize when DOM is ready
     */
    $(document).ready(function() {
        Askro.init();
    });

    // Make Askro globally available
    window.Askro = Askro;

})(jQuery);
