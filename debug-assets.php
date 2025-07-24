<?php
/**
 * Debug Asset Loading
 * 
 * Access: /wp-content/plugins/Askro/debug-assets.php
 */

// Load WordPress
require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );

// Check if user is admin
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Access denied' );
}

// Get various URLs
$plugin_url = plugin_dir_url( __FILE__ );
$plugin_path = plugin_dir_path( __FILE__ );
$css_url = plugin_dir_url( __FILE__ ) . 'assets/css/style.css';
$css_path = plugin_dir_path( __FILE__ ) . 'assets/css/style.css';

// Check if we're on HTTPS
$is_ssl = is_ssl();
$site_url = get_site_url();
$home_url = home_url();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Askro Asset Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        table { border-collapse: collapse; margin: 20px 0; }
        td, th { border: 1px solid #ccc; padding: 8px; }
        th { background: #f0f0f0; }
        .exists { color: green; }
        .missing { color: red; }
    </style>
</head>
<body>
    <h1>Askro Asset Debug</h1>
    
    <h2>Site Configuration</h2>
    <table>
        <tr><th>Setting</th><th>Value</th></tr>
        <tr><td>Site URL</td><td><?php echo esc_html( $site_url ); ?></td></tr>
        <tr><td>Home URL</td><td><?php echo esc_html( $home_url ); ?></td></tr>
        <tr><td>Is SSL?</td><td><?php echo $is_ssl ? 'Yes' : 'No'; ?></td></tr>
        <tr><td>Protocol</td><td><?php echo isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'HTTPS' : 'HTTP'; ?></td></tr>
        <tr><td>REQUEST_URI</td><td><?php echo esc_html( $_SERVER['REQUEST_URI'] ); ?></td></tr>
    </table>
    
    <h2>Plugin URLs</h2>
    <table>
        <tr><th>Type</th><th>URL</th></tr>
        <tr><td>Plugin URL</td><td><?php echo esc_html( $plugin_url ); ?></td></tr>
        <tr><td>CSS URL</td><td><?php echo esc_html( $css_url ); ?></td></tr>
        <tr><td>CSS URL (forced HTTPS)</td><td><?php echo esc_html( set_url_scheme( $css_url, 'https' ) ); ?></td></tr>
        <tr><td>CSS URL (protocol relative)</td><td><?php echo esc_html( set_url_scheme( $css_url, 'relative' ) ); ?></td></tr>
    </table>
    
    <h2>File Existence</h2>
    <table>
        <tr><th>File</th><th>Path</th><th>Exists?</th></tr>
        <tr>
            <td>style.css</td>
            <td><?php echo esc_html( $css_path ); ?></td>
            <td class="<?php echo file_exists( $css_path ) ? 'exists' : 'missing'; ?>">
                <?php echo file_exists( $css_path ) ? 'Yes' : 'No'; ?>
            </td>
        </tr>
    </table>
    
    <h2>Test Loading CSS</h2>
    <p>Try these different methods to load the CSS:</p>
    
    <h3>Method 1: Direct Link</h3>
    <p><a href="<?php echo esc_url( $css_url ); ?>" target="_blank">Open CSS file directly</a></p>
    
    <h3>Method 2: Force HTTPS</h3>
    <p><a href="<?php echo esc_url( set_url_scheme( $css_url, 'https' ) ); ?>" target="_blank">Open CSS with HTTPS</a></p>
    
    <h3>Method 3: Protocol Relative</h3>
    <p><a href="<?php echo esc_url( set_url_scheme( $css_url, 'relative' ) ); ?>" target="_blank">Open CSS with protocol relative URL</a></p>
    
    <h2>Test Inline CSS</h2>
    <?php
    $css_url_to_use = set_url_scheme( $css_url, is_ssl() ? 'https' : 'http' );
    ?>
    <link rel="stylesheet" href="<?php echo esc_url( $css_url_to_use ); ?>" type="text/css" media="all" />
    <p>If the plugin CSS is loading correctly, this text should have the Askro styles applied.</p>
    
    <h2>Actions</h2>
    <p><a href="<?php echo home_url('/questions/'); ?>">Go to Questions Archive</a></p>
    <p><a href="<?php echo admin_url('edit.php?post_type=question&page=askro-diagnostics'); ?>">Go to Diagnostics</a></p>
</body>
</html>
