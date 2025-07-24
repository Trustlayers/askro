<?php
/**
 * User Profile Shortcode
 *
 * Handles the rendering of the user profile page via the shortcode.
 *
 * @package Askro
 * @subpackage Frontend\Shortcodes
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Askro_User_Profile_Shortcode {

    /**
     * Initialize the shortcode
     *
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        add_shortcode( 'askro_user_profile', array( __CLASS__, 'render' ) );
    }

    /**
     * Render the user profile page
     *
     * Outputs the main structure of the profile page including the header,
     * chart, and tab navigation for loading user activity.
     *
     * @since 1.0.0
     * @return string HTML output
     */
    public static function render() {
        ob_start();
        ?>
        <div id="askro-user-profile" class="askro-profile-container">
            <!-- User Header Section -->
            <div class="askro-user-header">
                <?php 
                $current_user = wp_get_current_user();
                $user_points = get_user_meta( $current_user->ID, 'total_points', true );
                $user_points = $user_points ? intval( $user_points ) : 0;
                ?>
                <div class="user-avatar">
                    <?php echo get_avatar( $current_user->ID, 96 ); ?>
                </div>
                <div class="user-info">
                    <h2><?php echo esc_html( $current_user->display_name ); ?></h2>
                    <p><?php printf( __( 'Total Points: %d', 'askro' ), $user_points ); ?></p>
                </div>
            </div>

            <!-- Reputation Chart -->
            <div>
                <canvas id="askro-reputation-chart" width="400" height="200"></canvas>
            </div>

            <!-- Tab Navigation -->
            <ul class="askro-tabs">
                <li><a href="#" data-tab="activity">Activity</a></li>
                <li><a href="#" data-tab="questions">Questions</a></li>
                <li><a href="#" data-tab="answers">Answers</a></li>
            </ul>

            <!-- Content Container -->
            <div id="askro-profile-content"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}

