# Askro - WordPress Q&A Plugin

A powerful Q&A and problem-solving community plugin with advanced gamification and voting systems.

## Overview

Askro is a comprehensive WordPress plugin for managing Q&A functionality with advanced features for community engagement and knowledge sharing.

## Features

- Question & Answer system
- Voting mechanism
- User profiles and reputation
- Categories and tags for questions
- Community moderation tools
- Responsive design
- Shortcode-based archive renderer for flexible theme integration

## Installation

1. Upload the `askro` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings via the Askro menu in your WordPress admin

## Troubleshooting

### 404 Errors on Archive Pages

If you encounter 404 errors when visiting the `/questions/` archive page, try the following steps:

1. **Flush Permalinks**: Go to Settings > Permalinks and click "Save Changes" to flush the rewrite rules.

2. **Manual Plugin Reset**: Deactivate and reactivate the Askro plugin.

3. **Check Permalinks Structure**: Ensure your site is using a permalink structure other than "Plain" (Settings > Permalinks).

4. **Debugging**: Add `?askro_debug=post_types` to any admin page URL (when logged in as an administrator) to view CPT registration details.

### Common Issues and Solutions

#### Archive Page Not Working

If the question archive page (`/questions/`) returns a 404 error:

- This is typically caused by rewrite rules not being properly flushed or a conflict with permalink settings
- The plugin now includes a built-in safeguard that automatically flushes rewrite rules when needed
- Try visiting the WordPress Permalinks settings page and clicking "Save Changes"

#### Template Not Loading

If the archive template is not loading correctly:

- Check if your theme includes an `archive-question.php` file that might be overriding the plugin template
- Verify that the plugin's template loader is working by inspecting the HTML source of the page
- The plugin includes a fallback template in the `templates/` directory

## Support

For support inquiries, please open an issue on our [GitHub repository](https://github.com/Trustlayers/askro.git) or contact the Arashdi Team at info@arashdi.com.

## Developers

### Custom Templates

You can override the plugin's templates by creating the following files in your theme:

- `archive-question.php` - For the questions archive page
- `single-question.php` - For individual question pages
- `taxonomy-question_category.php` - For question category pages
- `taxonomy-question_tag.php` - For question tag pages

Alternatively, you can place these files in an `askro/` subdirectory in your theme for better organization.

### Shortcodes

The plugin includes a flexible shortcode system for displaying questions:

#### Archive Shortcode

Use `[askro_archive]` to display a questions archive anywhere on your site. This shortcode replaced the previous template-based system and provides better theme compatibility.

**Basic Usage:**
```
[askro_archive]
```

**With Parameters:**
```
[askro_archive posts_per_page="10" category="general" order="DESC"]
```

### Hooks and Filters

The plugin provides several hooks and filters for extending functionality:

- `askro_question_args` - Filter the arguments used to register the question post type
- `askro_template_include` - Filter the template path before it's included
- `askro_before_question_content` - Action hook that fires before question content
- `askro_after_question_content` - Action hook that fires after question content
- `askro_archive_shortcode_output` - Filter the archive shortcode output

## License

This plugin is licensed under the GPL v3 or later.
