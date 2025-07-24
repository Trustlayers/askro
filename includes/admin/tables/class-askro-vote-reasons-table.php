<?php
/**
 * Vote Reasons List Table
 *
 * @package Askro
 * @subpackage Admin/Tables
 */

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Vote Reasons Table Class
 *
 * Displays and manages vote reason presets in a WP_List_Table format
 * with editable inline fields for title, description, icon, color, and active status.
 */
class Askro_Vote_Reasons_Table extends WP_List_Table {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct([
            'singular' => 'vote_reason',
            'plural'   => 'vote_reasons',
            'ajax'     => false
        ]);
    }

    /**
     * Define table columns
     *
     * @return array Column definitions
     */
    public function get_columns() {
        return [
            'title'       => __('Title', 'askro'),
            'description' => __('Description', 'askro'),
            'icon'        => __('Icon', 'askro'),
            'color'       => __('Color', 'askro'),
            'is_active'   => __('Active', 'askro')
        ];
    }

    /**
     * Prepare table items
     */
    public function prepare_items() {
        global $wpdb;

        $columns = $this->get_columns();
        $this->_column_headers = [$columns, [], []];

        // Fetch vote reason presets from database
        $table_name = $wpdb->prefix . 'askro_vote_reason_presets';
        $results = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY id ASC", ARRAY_A);

        $this->items = $results ?: [];
    }

    /**
     * Render title column with editable input
     *
     * @param array $item Row data
     * @return string HTML output
     */
    public function column_title($item) {
        $value = esc_attr($item['title']);
        $name = "reasons[{$item['id']}][title]";
        
        return sprintf(
            '<input type="text" name="%s" value="%s" class="regular-text" />',
            $name,
            $value
        );
    }

    /**
     * Render description column with editable textarea
     *
     * @param array $item Row data
     * @return string HTML output
     */
    public function column_description($item) {
        $value = esc_textarea($item['description']);
        $name = "reasons[{$item['id']}][description]";
        
        return sprintf(
            '<textarea name="%s" rows="2" class="large-text">%s</textarea>',
            $name,
            $value
        );
    }

    /**
     * Render icon column with editable input
     *
     * @param array $item Row data
     * @return string HTML output
     */
    public function column_icon($item) {
        $value = esc_attr($item['icon']);
        $name = "reasons[{$item['id']}][icon]";
        
        $preview = '';
        if (!empty($value)) {
            // Check if it's a dashicon class or SVG
            if (strpos($value, 'dashicons') === 0) {
                $preview = sprintf('<span class="dashicons %s" style="margin-right: 8px;"></span>', $value);
            } elseif (strpos($value, '<svg') === 0) {
                $preview = '<span style="margin-right: 8px;">' . $value . '</span>';
            }
        }
        
        return sprintf(
            '%s<input type="text" name="%s" value="%s" class="regular-text" placeholder="dashicons-thumbs-up or SVG code" />',
            $preview,
            $name,
            $value
        );
    }

    /**
     * Render color column with color picker
     *
     * @param array $item Row data
     * @return string HTML output
     */
    public function column_color($item) {
        $value = esc_attr($item['color']);
        $name = "reasons[{$item['id']}][color]";
        
        $color_preview = !empty($value) ? $value : '#000000';
        
        return sprintf(
            '<div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 20px; height: 20px; background-color: %s; border: 1px solid #ccc; border-radius: 3px;"></div>
                <input type="color" name="%s" value="%s" />
            </div>',
            $color_preview,
            $name,
            $color_preview
        );
    }

    /**
     * Render active status column with toggle switch
     *
     * @param array $item Row data
     * @return string HTML output
     */
    public function column_is_active($item) {
        $checked = $item['is_active'] ? 'checked' : '';
        $name = "reasons[{$item['id']}][is_active]";
        
        return sprintf(
            '<label class="askro-toggle-switch">
                <input type="hidden" name="%s" value="0" />
                <input type="checkbox" name="%s" value="1" %s />
                <span class="askro-toggle-slider"></span>
            </label>',
            $name,
            $name,
            $checked
        );
    }

    /**
     * Default column handler
     *
     * @param array  $item        Row data
     * @param string $column_name Column name
     * @return string HTML output
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'title':
                return $this->column_title($item);
            case 'description':
                return $this->column_description($item);
            case 'icon':
                return $this->column_icon($item);
            case 'color':
                return $this->column_color($item);
            case 'is_active':
                return $this->column_is_active($item);
            default:
                return isset($item[$column_name]) ? esc_html($item[$column_name]) : '';
        }
    }

    /**
     * Display when no items found
     */
    public function no_items() {
        _e('No vote reasons found.', 'askro');
    }

    /**
     * Add CSS for toggle switch styling
     */
    public function display() {
        echo '<style>
            .askro-toggle-switch {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 24px;
            }
            
            .askro-toggle-switch input[type="checkbox"] {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .askro-toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 24px;
            }
            
            .askro-toggle-slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }
            
            .askro-toggle-switch input:checked + .askro-toggle-slider {
                background-color: #2196F3;
            }
            
            .askro-toggle-switch input:checked + .askro-toggle-slider:before {
                transform: translateX(26px);
            }
        </style>';
        
        parent::display();
    }
}
