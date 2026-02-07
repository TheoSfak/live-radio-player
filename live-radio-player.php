<?php
/**
 * Plugin Name: Live Radio Player
 * Plugin URI: https://github.com/TheoSfak/live-radio-player
 * Description: Production-ready live radio streaming plugin supporting Icecast and Shoutcast with spectacular UI, lyrics display, and 10-tab admin panel
 * Version: 1.4.3
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Theodore Sfakianakis
 * Author URI: https://github.com/TheoSfak
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: live-radio-player
 * Domain Path: /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'LRP_VERSION', '1.4.3' );
define( 'LRP_PLUGIN_FILE', __FILE__ );
define( 'LRP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LRP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LRP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 */
final class Live_Radio_Player {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Core interfaces
        require_once LRP_PLUGIN_DIR . 'includes/interfaces/interface-stream-provider.php';
        
        // Providers
        require_once LRP_PLUGIN_DIR . 'includes/providers/class-icecast-provider.php';
        require_once LRP_PLUGIN_DIR . 'includes/providers/class-shoutcast-provider.php';
        
        // Core classes
        require_once LRP_PLUGIN_DIR . 'includes/class-stream-manager.php';
        require_once LRP_PLUGIN_DIR . 'includes/class-lyrics-service.php';
        require_once LRP_PLUGIN_DIR . 'includes/class-artwork-service.php';
        require_once LRP_PLUGIN_DIR . 'includes/class-rest-api.php';
        
        // Admin
        if ( is_admin() ) {
            require_once LRP_PLUGIN_DIR . 'includes/admin/class-admin.php';
        }
        
        // Public
        require_once LRP_PLUGIN_DIR . 'includes/public/class-public.php';
        
        // Gutenberg block
        require_once LRP_PLUGIN_DIR . 'includes/class-gutenberg-block.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook( LRP_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( LRP_PLUGIN_FILE, array( $this, 'deactivate' ) );
        
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain( 'live-radio-player', false, dirname( LRP_PLUGIN_BASENAME ) . '/languages' );
        
        // Initialize components
        if ( is_admin() ) {
            LRP_Admin::get_instance();
        }
        
        LRP_Public::get_instance();
        LRP_REST_API::get_instance();
        LRP_Gutenberg_Block::get_instance();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $defaults = array(
            'stream_type' => 'icecast',
            'stream_url' => '',
            'mount_point' => '',
            'sid' => '1',
            'stream_format' => 'mp3',
            'refresh_interval' => 10,
            'connection_timeout' => 5,
            'enable_metadata_fetch' => true,
            'debug_mode' => false,
            
            // Content control
            'show_artist' => true,
            'show_title' => true,
            'show_album' => true,
            'show_artwork' => true,
            'show_listeners' => true,
            'show_status' => true,
            'show_nowplaying_label' => true,
            'show_station_name' => false,
            'show_lyrics' => false,
            'fallback_text' => 'No track information available',
            'fallback_image' => '',
            
            // Layout
            'layout_type' => 'card',
            'orientation' => 'horizontal',
            'alignment' => 'center',
            'sticky_player' => false,
            'player_width' => 'auto',
            'custom_width' => '100%',
            
            // Visual style
            'bg_color' => '#ffffff',
            'accent_color' => '#007cba',
            'text_color' => '#333333',
            'button_color' => '#007cba',
            'button_hover_color' => '#005a87',
            'font_size' => 16,
            'font_weight' => '400',
            'line_height' => 1.5,
            'border_radius' => 8,
            'shadow' => 'soft',
            'padding' => 20,
            'spacing' => 15,
            
            // Theme presets
            'theme_preset' => 'classic',
            'override_preset' => false,
            
            // Lyrics & Artwork
            'enable_lyrics' => false,
            'lyrics_source' => 'free_api',
            'lyrics_language' => 'auto',
            'lyrics_cache_duration' => 1440,
            'enable_external_artwork' => false,
            'artwork_size' => 'medium',
            'artwork_cache_duration' => 1440,
            'custom_lyrics_message' => 'Lyrics not available',
            
            // Performance
            'api_mode' => 'rest',
            'lazy_load' => false,
            'allow_multiple_players' => true,
            'force_reload_on_change' => false,
            
            // Custom code
            'custom_css' => '',
            'custom_player_class' => '',
            'disable_plugin_css' => false,
            'disable_plugin_js' => false
        );
        
        add_option( 'lrp_settings', $defaults );
        
        // Flush rewrite rules for REST API
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear all transients
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lrp_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lrp_%'" );
        
        flush_rewrite_rules();
    }
}

/**
 * Initialize the plugin
 */
function lrp_init() {
    return Live_Radio_Player::get_instance();
}

// Start the plugin
lrp_init();
