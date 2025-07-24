<?php
/**
 * Plugin Name: ASKRO - Ask and Answer Plugin
 * Plugin URI: https://github.com/williamalowe/askro-plugin
 * Description: A comprehensive WordPress plugin for managing Q&A functionality with advanced features for community engagement and knowledge sharing.
 * Version: 1.0.0
 * Author: William Lowe
 * Author URI: https://github.com/williamalowe
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: askro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package ASKRO
 * @author William Lowe
 * @since 1.0.0
 * @license GPL-3.0-or-later
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Askro Plugin Class
 *
 * This class serves as the main entry point for the Askro plugin.
 * It handles plugin activation, deactivation, and initialization of core functionality.
 *
 * @package Askro
 * @since   1.0.0
 */
class Askro_Main
{
    /**
     * Plugin version
     *
     * @var string
     * @since 1.0.0
     */
    public const VERSION = '1.0.0';

    /**
     * Minimum PHP version required
     *
     * @var string
     * @since 1.0.0
     */
    public const MIN_PHP_VERSION = '7.4';

    /**
     * Minimum WordPress version required
     *
     * @var string
     * @since 1.0.0
     */
    public const MIN_WP_VERSION = '5.0';

    /**
     * Plugin text domain
     *
     * @var string
     * @since 1.0.0
     */
    public const TEXT_DOMAIN = 'askro';

    /**
     * Plugin file path
     *
     * @var string
     * @since 1.0.0
     */
    private $plugin_file;

    /**
     * Plugin directory path
     *
     * @var string
     * @since 1.0.0
     */
    private $plugin_dir;

    /**
     * Plugin URL
     *
     * @var string
     * @since 1.0.0
     */
    private $plugin_url;

    /**
     * Class constructor
     *
     * Initializes the plugin by setting up activation and deactivation hooks,
     * and performing initial setup tasks.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Set plugin paths
        $this->plugin_file = __FILE__;
        $this->plugin_dir = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

        // Register activation hook
        register_activation_hook($this->plugin_file, [self::class, 'activate']);

        // Register deactivation hook
        register_deactivation_hook($this->plugin_file, [self::class, 'deactivate']);

        // Initialize plugin
        add_action('plugins_loaded', [$this, 'init']);
    }

    /**
     * Plugin activation hook
     *
     * This method is called when the plugin is activated.
     * It performs necessary setup tasks such as creating database tables,
     * setting default options, and flushing rewrite rules.
     *
     * @since 1.0.0
     * @return void
     */
    public static function activate(): void
    {
        // Check minimum PHP version
        if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                sprintf(
                    /* translators: 1: Plugin name, 2: Required PHP version, 3: Current PHP version */
                    __('%1$s requires PHP version %2$s or higher. You are running version %3$s.', self::TEXT_DOMAIN),
                    'Askro',
                    self::MIN_PHP_VERSION,
                    PHP_VERSION
                ),
                __('Plugin Activation Error', self::TEXT_DOMAIN),
                ['back_link' => true]
            );
        }

        // Check minimum WordPress version
        if (version_compare(get_bloginfo('version'), self::MIN_WP_VERSION, '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                sprintf(
                    /* translators: 1: Plugin name, 2: Required WordPress version, 3: Current WordPress version */
                    __('%1$s requires WordPress version %2$s or higher. You are running version %3$s.', self::TEXT_DOMAIN),
                    'Askro',
                    self::MIN_WP_VERSION,
                    get_bloginfo('version')
                ),
                __('Plugin Activation Error', self::TEXT_DOMAIN),
                ['back_link' => true]
            );
        }

        // Set activation flag for first-time setup
        add_option('askro_activation_time', time());
        add_option('askro_version', self::VERSION);

        // Flush rewrite rules to ensure custom post types and taxonomies work
        flush_rewrite_rules();

        // TODO: Create database tables if needed
        // TODO: Set default plugin options
        // TODO: Schedule cron jobs if needed
        // TODO: Create default pages or posts if needed
    }

    /**
     * Plugin deactivation hook
     *
     * This method is called when the plugin is deactivated.
     * It performs cleanup tasks such as clearing scheduled events
     * and flushing rewrite rules.
     *
     * @since 1.0.0
     * @return void
     */
    public static function deactivate(): void
    {
        // Clear scheduled events
        wp_clear_scheduled_hook('askro_daily_cleanup');
        wp_clear_scheduled_hook('askro_weekly_maintenance');

        // Flush rewrite rules to clean up custom post types and taxonomies
        flush_rewrite_rules();

        // TODO: Perform additional cleanup tasks
        // TODO: Clear transients
        // TODO: Remove temporary files
        // Note: Do not remove user data or settings on deactivation
        // Only remove data on uninstall if explicitly requested
    }

    /**
     * Initialize the plugin
     *
     * This method is called on the 'plugins_loaded' hook to ensure
     * WordPress is fully loaded before initializing plugin functionality.
     *
     * @since 1.0.0
     * @return void
     */
    public function init(): void
    {
        // Load text domain for internationalization
        load_plugin_textdomain(
            self::TEXT_DOMAIN,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );

        // TODO: Initialize core plugin components
        // TODO: Load required files
        // TODO: Initialize admin interface
        // TODO: Initialize frontend functionality
        // TODO: Register custom post types and taxonomies
        // TODO: Enqueue scripts and styles
        // TODO: Set up AJAX handlers
        // TODO: Initialize REST API endpoints
    }

    /**
     * Get plugin version
     *
     * @since 1.0.0
     * @return string Plugin version
     */
    public function get_version(): string
    {
        return self::VERSION;
    }

    /**
     * Get plugin file path
     *
     * @since 1.0.0
     * @return string Plugin file path
     */
    public function get_plugin_file(): string
    {
        return $this->plugin_file;
    }

    /**
     * Get plugin directory path
     *
     * @since 1.0.0
     * @return string Plugin directory path
     */
    public function get_plugin_dir(): string
    {
        return $this->plugin_dir;
    }

    /**
     * Get plugin URL
     *
     * @since 1.0.0
     * @return string Plugin URL
     */
    public function get_plugin_url(): string
    {
        return $this->plugin_url;
    }
}

// Instantiate the main plugin class to initialize the plugin
new Askro_Main();
