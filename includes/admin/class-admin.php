<?php
/**
 * Admin Panel Handler
 *
 * @package Live_Radio_Player
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin panel class
 */
class LRP_Admin {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Settings option name
     */
    private $option_name = 'lrp_settings';
    
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
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_ajax_lrp_clear_cache', array( $this, 'ajax_clear_cache' ) );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Live Radio Player', 'live-radio-player' ),
            __( 'Live Radio', 'live-radio-player' ),
            'manage_options',
            'live-radio-player',
            array( $this, 'render_admin_page' ),
            'dashicons-controls-volumeon',
            30
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'lrp_settings_group', $this->option_name, array(
            'sanitize_callback' => array( $this, 'sanitize_settings' )
        ) );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_live-radio-player' !== $hook ) {
            return;
        }
        
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        
        wp_enqueue_media();
        
        wp_enqueue_style(
            'lrp-admin-css',
            LRP_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            LRP_VERSION
        );
        
        wp_enqueue_script(
            'lrp-admin-js',
            LRP_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'wp-color-picker' ),
            LRP_VERSION,
            true
        );
        
        wp_localize_script( 'lrp-admin-js', 'lrpAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'lrp_admin_nonce' )
        ) );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        $settings = get_option( $this->option_name, array() );
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'stream_settings';
        
        ?>
        <div class="wrap lrp-admin-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <?php settings_errors( $this->option_name ); ?>
            
            <h2 class="nav-tab-wrapper lrp-nav-tabs">
                <?php $this->render_tabs( $active_tab ); ?>
            </h2>
            
            <form method="post" action="options.php" class="lrp-admin-form">
                <?php
                settings_fields( 'lrp_settings_group' );
                ?>
                <input type="hidden" name="<?php echo esc_attr( $this->option_name ); ?>[_current_tab]" value="<?php echo esc_attr( $active_tab ); ?>" />
                <?php
                
                switch ( $active_tab ) {
                    case 'stream_settings':
                        $this->render_tab_stream_settings( $settings );
                        break;
                    case 'content_control':
                        $this->render_tab_content_control( $settings );
                        break;
                    case 'player_layout':
                        $this->render_tab_player_layout( $settings );
                        break;
                    case 'visual_style':
                        $this->render_tab_visual_style( $settings );
                        break;
                    case 'theme_presets':
                        $this->render_tab_theme_presets( $settings );
                        break;
                    case 'lyrics_artwork':
                        $this->render_tab_lyrics_artwork( $settings );
                        break;
                    case 'performance':
                        $this->render_tab_performance( $settings );
                        break;
                    case 'custom_code':
                        $this->render_tab_custom_code( $settings );
                        break;
                    case 'integration':
                        $this->render_tab_integration( $settings );
                        break;
                    case 'diagnostics':
                        $this->render_tab_diagnostics( $settings );
                        break;
                }
                ?>
                
                <?php if ( $active_tab !== 'integration' && $active_tab !== 'diagnostics' ) : ?>
                    <?php submit_button(); ?>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render navigation tabs
     */
    private function render_tabs( $active_tab ) {
        $tabs = array(
            'stream_settings' => __( 'Stream Settings', 'live-radio-player' ),
            'content_control' => __( 'Content Control', 'live-radio-player' ),
            'player_layout' => __( 'Player Layout', 'live-radio-player' ),
            'visual_style' => __( 'Visual Style', 'live-radio-player' ),
            'theme_presets' => __( 'Theme Presets', 'live-radio-player' ),
            'lyrics_artwork' => __( 'Lyrics & Artwork', 'live-radio-player' ),
            'performance' => __( 'Performance', 'live-radio-player' ),
            'custom_code' => __( 'Custom Code', 'live-radio-player' ),
            'integration' => __( 'Integration', 'live-radio-player' ),
            'diagnostics' => __( 'Diagnostics', 'live-radio-player' )
        );
        
        foreach ( $tabs as $tab => $name ) {
            $class = ( $active_tab === $tab ) ? 'nav-tab nav-tab-active' : 'nav-tab';
            $url = add_query_arg( array(
                'page' => 'live-radio-player',
                'tab' => $tab
            ), admin_url( 'admin.php' ) );
            
            printf(
                '<a href="%s" class="%s">%s</a>',
                esc_url( $url ),
                esc_attr( $class ),
                esc_html( $name )
            );
        }
    }
    
    /**
     * TAB 1: Stream Settings
     */
    private function render_tab_stream_settings( $settings ) {
        ?>
        <div class="lrp-tab-content">
            <h2><?php esc_html_e( 'General / Stream Settings', 'live-radio-player' ); ?></h2>
            <p><?php esc_html_e( 'Configure your streaming server connection and basic functionality.', 'live-radio-player' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="stream_type"><?php esc_html_e( 'Stream Type', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr( $this->option_name ); ?>[stream_type]" id="stream_type">
                            <option value="icecast" <?php selected( $settings['stream_type'] ?? 'icecast', 'icecast' ); ?>>Icecast</option>
                            <option value="shoutcast" <?php selected( $settings['stream_type'] ?? '', 'shoutcast' ); ?>>Shoutcast v1</option>
                            <option value="shoutcast_v2" <?php selected( $settings['stream_type'] ?? '', 'shoutcast_v2' ); ?>>Shoutcast v2</option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="stream_url"><?php esc_html_e( 'Stream Base URL', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="url" name="<?php echo esc_attr( $this->option_name ); ?>[stream_url]" id="stream_url" 
                               value="<?php echo esc_attr( $settings['stream_url'] ?? '' ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Example: http://stream.example.com:8000/', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr class="lrp-icecast-only">
                    <th scope="row">
                        <label for="mount_point"><?php esc_html_e( 'Mount Point', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[mount_point]" id="mount_point" 
                               value="<?php echo esc_attr( $settings['mount_point'] ?? '' ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Icecast only. Example: /radio.mp3', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr class="lrp-shoutcast-only">
                    <th scope="row">
                        <label for="sid"><?php esc_html_e( 'SID (Stream ID)', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[sid]" id="sid" 
                               value="<?php echo esc_attr( $settings['sid'] ?? '1' ); ?>" min="1" />
                        <p class="description"><?php esc_html_e( 'Shoutcast v2 only. Default: 1', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="stream_format"><?php esc_html_e( 'Stream Format', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr( $this->option_name ); ?>[stream_format]" id="stream_format">
                            <option value="mp3" <?php selected( $settings['stream_format'] ?? 'mp3', 'mp3' ); ?>>MP3</option>
                            <option value="aac" <?php selected( $settings['stream_format'] ?? '', 'aac' ); ?>>AAC</option>
                            <option value="ogg" <?php selected( $settings['stream_format'] ?? '', 'ogg' ); ?>>OGG</option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="refresh_interval"><?php esc_html_e( 'Metadata Refresh Interval', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[refresh_interval]" id="refresh_interval" 
                               value="<?php echo esc_attr( $settings['refresh_interval'] ?? 30 ); ?>" min="5" max="300" /> 
                        <?php esc_html_e( 'seconds', 'live-radio-player' ); ?>
                        <p class="description"><?php esc_html_e( 'How often to check for new track information.', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="connection_timeout"><?php esc_html_e( 'Connection Timeout', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[connection_timeout]" id="connection_timeout" 
                               value="<?php echo esc_attr( $settings['connection_timeout'] ?? 5 ); ?>" min="1" max="30" /> 
                        <?php esc_html_e( 'seconds', 'live-radio-player' ); ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="enable_metadata_fetch"><?php esc_html_e( 'Enable Server-side Metadata Fetching', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enable_metadata_fetch]" 
                                   id="enable_metadata_fetch" value="1" 
                                   <?php checked( $settings['enable_metadata_fetch'] ?? true, true ); ?> />
                            <?php esc_html_e( 'Fetch track metadata from server', 'live-radio-player' ); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="debug_mode"><?php esc_html_e( 'Debug Mode', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[debug_mode]" 
                                   id="debug_mode" value="1" 
                                   <?php checked( $settings['debug_mode'] ?? false, true ); ?> />
                            <?php esc_html_e( 'Enable debug logging (logs only, no frontend output)', 'live-radio-player' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * TAB 2: Content Control
     */
    private function render_tab_content_control( $settings ) {
        ?>
        <div class="lrp-tab-content">
            <h2><?php esc_html_e( 'Data & Content Control', 'live-radio-player' ); ?></h2>
            <p><?php esc_html_e( 'Control what information is displayed in the player.', 'live-radio-player' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Display Elements', 'live-radio-player' ); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[show_artist]" 
                                       value="1" <?php checked( $settings['show_artist'] ?? true, true ); ?> />
                                <?php esc_html_e( 'Show Artist', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[show_title]" 
                                       value="1" <?php checked( $settings['show_title'] ?? true, true ); ?> />
                                <?php esc_html_e( 'Show Track Title', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[show_album]" 
                                       value="1" <?php checked( $settings['show_album'] ?? true, true ); ?> />
                                <?php esc_html_e( 'Show Album Name', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[show_artwork]" 
                                       value="1" <?php checked( $settings['show_artwork'] ?? true, true ); ?> />
                                <?php esc_html_e( 'Show Album Artwork', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[show_listeners]" 
                                       value="1" <?php checked( $settings['show_listeners'] ?? true, true ); ?> />
                                <?php esc_html_e( 'Show Listener Count', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[show_track_time]" 
                                       value="1" <?php checked( $settings['show_track_time'] ?? false, true ); ?> />
                                <?php esc_html_e( 'Show Track Time (elapsed / remaining)', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[show_status]" 
                                       value="1" <?php checked( $settings['show_status'] ?? true, true ); ?> />
                                <?php esc_html_e( 'Show Stream Status (Online / Offline)', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[show_nowplaying_label]" 
                                       value="1" <?php checked( $settings['show_nowplaying_label'] ?? true, true ); ?> />
                                <?php esc_html_e( 'Show "Now Playing" Label', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[show_station_name]" 
                                       value="1" <?php checked( $settings['show_station_name'] ?? false, true ); ?> />
                                <?php esc_html_e( 'Show Station Name', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[show_lyrics]" 
                                       value="1" <?php checked( $settings['show_lyrics'] ?? false, true ); ?> />
                                <?php esc_html_e( 'Show Lyrics Section', 'live-radio-player' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="fallback_text"><?php esc_html_e( 'Fallback Text', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[fallback_text]" id="fallback_text" 
                               value="<?php echo esc_attr( $settings['fallback_text'] ?? 'No track information available' ); ?>" 
                               class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Text displayed when no metadata is available.', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="fallback_image"><?php esc_html_e( 'Fallback Image', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[fallback_image]" id="fallback_image" 
                               value="<?php echo esc_attr( $settings['fallback_image'] ?? '' ); ?>" class="regular-text" />
                        <button type="button" class="button lrp-upload-image"><?php esc_html_e( 'Select Image', 'live-radio-player' ); ?></button>
                        <p class="description"><?php esc_html_e( 'Image displayed when no artwork is available.', 'live-radio-player' ); ?></p>
                        <?php if ( ! empty( $settings['fallback_image'] ) ) : ?>
                            <div class="lrp-image-preview">
                                <img src="<?php echo esc_url( $settings['fallback_image'] ); ?>" style="max-width: 150px; height: auto;" />
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * TAB 3: Player Layout
     */
    private function render_tab_player_layout( $settings ) {
        ?>
        <div class="lrp-tab-content">
            <h2><?php esc_html_e( 'Player Layout', 'live-radio-player' ); ?></h2>
            <p><?php esc_html_e( 'Configure the structural layout of the player.', 'live-radio-player' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Layout Type', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[layout_type]" 
                                       value="minimal" <?php checked( $settings['layout_type'] ?? 'card', 'minimal' ); ?> />
                                <?php esc_html_e( 'Minimal', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[layout_type]" 
                                       value="card" <?php checked( $settings['layout_type'] ?? 'card', 'card' ); ?> />
                                <?php esc_html_e( 'Card', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[layout_type]" 
                                       value="full" <?php checked( $settings['layout_type'] ?? 'card', 'full' ); ?> />
                                <?php esc_html_e( 'Full', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[layout_type]" 
                                       value="sidebar" <?php checked( $settings['layout_type'] ?? 'card', 'sidebar' ); ?> />
                                <?php esc_html_e( 'Sidebar', 'live-radio-player' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Orientation', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[orientation]" 
                                       value="horizontal" <?php checked( $settings['orientation'] ?? 'horizontal', 'horizontal' ); ?> />
                                <?php esc_html_e( 'Horizontal', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[orientation]" 
                                       value="vertical" <?php checked( $settings['orientation'] ?? 'horizontal', 'vertical' ); ?> />
                                <?php esc_html_e( 'Vertical', 'live-radio-player' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Alignment', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[alignment]" 
                                       value="left" <?php checked( $settings['alignment'] ?? 'center', 'left' ); ?> />
                                <?php esc_html_e( 'Left', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[alignment]" 
                                       value="center" <?php checked( $settings['alignment'] ?? 'center', 'center' ); ?> />
                                <?php esc_html_e( 'Center', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[alignment]" 
                                       value="right" <?php checked( $settings['alignment'] ?? 'center', 'right' ); ?> />
                                <?php esc_html_e( 'Right', 'live-radio-player' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sticky_player"><?php esc_html_e( 'Sticky Player', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[sticky_player]" 
                                   id="sticky_player" value="1" <?php checked( $settings['sticky_player'] ?? false, true ); ?> />
                            <?php esc_html_e( 'Make player stick to top/bottom when scrolling', 'live-radio-player' ); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Width', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[player_width]" 
                                       value="auto" <?php checked( $settings['player_width'] ?? 'auto', 'auto' ); ?> />
                                <?php esc_html_e( 'Auto', 'live-radio-player' ); ?>
                            </label><br />
                            
                            <label>
                                <input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[player_width]" 
                                       value="custom" <?php checked( $settings['player_width'] ?? 'auto', 'custom' ); ?> />
                                <?php esc_html_e( 'Custom:', 'live-radio-player' ); ?>
                                <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[custom_width]" 
                                       value="<?php echo esc_attr( $settings['custom_width'] ?? '100%' ); ?>" 
                                       class="small-text" placeholder="100%" />
                                <span class="description"><?php esc_html_e( '(px or %)', 'live-radio-player' ); ?></span>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="facebook_url"><?php esc_html_e( 'Facebook Page URL', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="url" name="<?php echo esc_attr( $this->option_name ); ?>[facebook_url]" 
                               id="facebook_url" value="<?php echo esc_attr( $settings['facebook_url'] ?? '' ); ?>" 
                               class="regular-text" placeholder="https://facebook.com/yourpage" />
                        <p class="description"><?php esc_html_e( 'Display "Like us on Facebook" button above artwork (leave empty to hide)', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * TAB 4: Visual Style Builder
     */
    private function render_tab_visual_style( $settings ) {
        $override_preset = $settings['override_preset'] ?? false;
        $disabled_class = ! $override_preset ? ' lrp-disabled' : '';
        $disabled_attr = ! $override_preset ? 'disabled' : '';
        
        ?>
        <div class="lrp-tab-content<?php echo esc_attr( $disabled_class ); ?>" id="lrp-visual-style">
            <h2><?php esc_html_e( 'Visual Style Builder', 'live-radio-player' ); ?></h2>
            <p><?php esc_html_e( 'Customize the visual appearance without CSS.', 'live-radio-player' ); ?></p>
            
            <?php if ( ! $override_preset ) : ?>
                <div class="notice notice-info inline">
                    <p><?php esc_html_e( 'Visual settings are disabled. Enable "Override Preset Settings" in Theme Presets tab to customize.', 'live-radio-player' ); ?></p>
                </div>
            <?php endif; ?>
            
            <h3><?php esc_html_e( 'Colors', 'live-radio-player' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="bg_color"><?php esc_html_e( 'Background Color', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[bg_color]" id="bg_color" 
                               value="<?php echo esc_attr( $settings['bg_color'] ?? '#ffffff' ); ?>" 
                               class="lrp-color-picker" <?php echo $disabled_attr; ?> />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="accent_color"><?php esc_html_e( 'Primary Accent Color', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[accent_color]" id="accent_color" 
                               value="<?php echo esc_attr( $settings['accent_color'] ?? '#007cba' ); ?>" 
                               class="lrp-color-picker" <?php echo $disabled_attr; ?> />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="text_color"><?php esc_html_e( 'Text Color', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[text_color]" id="text_color" 
                               value="<?php echo esc_attr( $settings['text_color'] ?? '#333333' ); ?>" 
                               class="lrp-color-picker" <?php echo $disabled_attr; ?> />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="button_color"><?php esc_html_e( 'Button Color', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[button_color]" id="button_color" 
                               value="<?php echo esc_attr( $settings['button_color'] ?? '#007cba' ); ?>" 
                               class="lrp-color-picker" <?php echo $disabled_attr; ?> />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="button_hover_color"><?php esc_html_e( 'Button Hover Color', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[button_hover_color]" id="button_hover_color" 
                               value="<?php echo esc_attr( $settings['button_hover_color'] ?? '#005a87' ); ?>" 
                               class="lrp-color-picker" <?php echo $disabled_attr; ?> />
                    </td>
                </tr>
            </table>
            
            <h3><?php esc_html_e( 'Background Image', 'live-radio-player' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="bg_image"><?php esc_html_e( 'Background Image URL', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[bg_image]" id="bg_image" 
                               value="<?php echo esc_attr( $settings['bg_image'] ?? '' ); ?>" 
                               class="regular-text" <?php echo $disabled_attr; ?> />
                        <button type="button" class="button lrp-upload-image" data-target="#bg_image" <?php echo $disabled_attr; ?>>
                            <?php esc_html_e( 'Upload Image', 'live-radio-player' ); ?>
                        </button>
                        <p class="description"><?php esc_html_e( 'Upload or enter URL for background logo/watermark image', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="bg_image_opacity"><?php esc_html_e( 'Image Opacity (%)', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="range" name="<?php echo esc_attr( $this->option_name ); ?>[bg_image_opacity]" id="bg_image_opacity" 
                               value="<?php echo esc_attr( $settings['bg_image_opacity'] ?? 20 ); ?>" 
                               min="0" max="100" step="5" <?php echo $disabled_attr; ?> />
                        <span class="lrp-slider-value"><?php echo esc_html( $settings['bg_image_opacity'] ?? 20 ); ?>%</span>
                        <p class="description"><?php esc_html_e( 'Transparency level (0 = invisible, 100 = fully visible)', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="bg_image_angle"><?php esc_html_e( 'Image Rotation Angle', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[bg_image_angle]" id="bg_image_angle" 
                               value="<?php echo esc_attr( $settings['bg_image_angle'] ?? 0 ); ?>" 
                               min="-180" max="180" step="5" <?php echo $disabled_attr; ?> />
                        <span> degrees</span>
                        <p class="description"><?php esc_html_e( 'Rotate the image (-180 to 180 degrees)', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="bg_image_size"><?php esc_html_e( 'Image Size', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr( $this->option_name ); ?>[bg_image_size]" id="bg_image_size" <?php echo $disabled_attr; ?>>
                            <option value="auto" <?php selected( $settings['bg_image_size'] ?? 'auto', 'auto' ); ?>><?php esc_html_e( 'Original Size', 'live-radio-player' ); ?></option>
                            <option value="cover" <?php selected( $settings['bg_image_size'] ?? 'auto', 'cover' ); ?>><?php esc_html_e( 'Cover (Fill)', 'live-radio-player' ); ?></option>
                            <option value="contain" <?php selected( $settings['bg_image_size'] ?? 'auto', 'contain' ); ?>><?php esc_html_e( 'Contain (Fit)', 'live-radio-player' ); ?></option>
                            <option value="50%" <?php selected( $settings['bg_image_size'] ?? 'auto', '50%' ); ?>><?php esc_html_e( '50% Size', 'live-radio-player' ); ?></option>
                            <option value="75%" <?php selected( $settings['bg_image_size'] ?? 'auto', '75%' ); ?>><?php esc_html_e( '75% Size', 'live-radio-player' ); ?></option>
                            <option value="100%" <?php selected( $settings['bg_image_size'] ?? 'auto', '100%' ); ?>><?php esc_html_e( '100% Size', 'live-radio-player' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'How the image should be sized within the player', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="bg_image_position"><?php esc_html_e( 'Image Position', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr( $this->option_name ); ?>[bg_image_position]" id="bg_image_position" <?php echo $disabled_attr; ?>>
                            <option value="center" <?php selected( $settings['bg_image_position'] ?? 'center', 'center' ); ?>><?php esc_html_e( 'Center', 'live-radio-player' ); ?></option>
                            <option value="top left" <?php selected( $settings['bg_image_position'] ?? 'center', 'top left' ); ?>><?php esc_html_e( 'Top Left', 'live-radio-player' ); ?></option>
                            <option value="top center" <?php selected( $settings['bg_image_position'] ?? 'center', 'top center' ); ?>><?php esc_html_e( 'Top Center', 'live-radio-player' ); ?></option>
                            <option value="top right" <?php selected( $settings['bg_image_position'] ?? 'center', 'top right' ); ?>><?php esc_html_e( 'Top Right', 'live-radio-player' ); ?></option>
                            <option value="center left" <?php selected( $settings['bg_image_position'] ?? 'center', 'center left' ); ?>><?php esc_html_e( 'Center Left', 'live-radio-player' ); ?></option>
                            <option value="center right" <?php selected( $settings['bg_image_position'] ?? 'center', 'center right' ); ?>><?php esc_html_e( 'Center Right', 'live-radio-player' ); ?></option>
                            <option value="bottom left" <?php selected( $settings['bg_image_position'] ?? 'center', 'bottom left' ); ?>><?php esc_html_e( 'Bottom Left', 'live-radio-player' ); ?></option>
                            <option value="bottom center" <?php selected( $settings['bg_image_position'] ?? 'center', 'bottom center' ); ?>><?php esc_html_e( 'Bottom Center', 'live-radio-player' ); ?></option>
                            <option value="bottom right" <?php selected( $settings['bg_image_position'] ?? 'center', 'bottom right' ); ?>><?php esc_html_e( 'Bottom Right', 'live-radio-player' ); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <h3><?php esc_html_e( 'Typography', 'live-radio-player' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="font_size"><?php esc_html_e( 'Font Size', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="range" name="<?php echo esc_attr( $this->option_name ); ?>[font_size]" id="font_size" 
                               value="<?php echo esc_attr( $settings['font_size'] ?? 16 ); ?>" 
                               min="12" max="24" step="1" <?php echo $disabled_attr; ?> />
                        <span class="lrp-slider-value"><?php echo esc_html( $settings['font_size'] ?? 16 ); ?>px</span>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="font_weight"><?php esc_html_e( 'Font Weight', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr( $this->option_name ); ?>[font_weight]" id="font_weight" <?php echo $disabled_attr; ?>>
                            <option value="300" <?php selected( $settings['font_weight'] ?? '400', '300' ); ?>>Light (300)</option>
                            <option value="400" <?php selected( $settings['font_weight'] ?? '400', '400' ); ?>>Normal (400)</option>
                            <option value="500" <?php selected( $settings['font_weight'] ?? '400', '500' ); ?>>Medium (500)</option>
                            <option value="600" <?php selected( $settings['font_weight'] ?? '400', '600' ); ?>>Semi-Bold (600)</option>
                            <option value="700" <?php selected( $settings['font_weight'] ?? '400', '700' ); ?>>Bold (700)</option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="line_height"><?php esc_html_e( 'Line Height', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[line_height]" id="line_height" 
                               value="<?php echo esc_attr( $settings['line_height'] ?? 1.5 ); ?>" 
                               min="1" max="3" step="0.1" <?php echo $disabled_attr; ?> />
                    </td>
                </tr>
            </table>
            
            <h3><?php esc_html_e( 'UI Elements', 'live-radio-player' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="border_radius"><?php esc_html_e( 'Border Radius', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="range" name="<?php echo esc_attr( $this->option_name ); ?>[border_radius]" id="border_radius" 
                               value="<?php echo esc_attr( $settings['border_radius'] ?? 8 ); ?>" 
                               min="0" max="50" step="1" <?php echo $disabled_attr; ?> />
                        <span class="lrp-slider-value"><?php echo esc_html( $settings['border_radius'] ?? 8 ); ?>px</span>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="shadow"><?php esc_html_e( 'Shadow', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr( $this->option_name ); ?>[shadow]" id="shadow" <?php echo $disabled_attr; ?>>
                            <option value="none" <?php selected( $settings['shadow'] ?? 'soft', 'none' ); ?>><?php esc_html_e( 'None', 'live-radio-player' ); ?></option>
                            <option value="soft" <?php selected( $settings['shadow'] ?? 'soft', 'soft' ); ?>><?php esc_html_e( 'Soft', 'live-radio-player' ); ?></option>
                            <option value="strong" <?php selected( $settings['shadow'] ?? 'soft', 'strong' ); ?>><?php esc_html_e( 'Strong', 'live-radio-player' ); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="padding"><?php esc_html_e( 'Padding', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="range" name="<?php echo esc_attr( $this->option_name ); ?>[padding]" id="padding" 
                               value="<?php echo esc_attr( $settings['padding'] ?? 20 ); ?>" 
                               min="0" max="50" step="5" <?php echo $disabled_attr; ?> />
                        <span class="lrp-slider-value"><?php echo esc_html( $settings['padding'] ?? 20 ); ?>px</span>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="spacing"><?php esc_html_e( 'Spacing Between Elements', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="range" name="<?php echo esc_attr( $this->option_name ); ?>[spacing]" id="spacing" 
                               value="<?php echo esc_attr( $settings['spacing'] ?? 15 ); ?>" 
                               min="0" max="40" step="5" <?php echo $disabled_attr; ?> />
                        <span class="lrp-slider-value"><?php echo esc_html( $settings['spacing'] ?? 15 ); ?>px</span>
                    </td>
                </tr>
            </table>
            
            <h3><?php esc_html_e( 'Text Customization', 'live-radio-player' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="nowplaying_text"><?php esc_html_e( 'Now Playing Label Text', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[nowplaying_text]" id="nowplaying_text" 
                               value="<?php echo esc_attr( $settings['nowplaying_text'] ?? 'Now Playing' ); ?>" 
                               class="regular-text" <?php echo $disabled_attr; ?> />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="nowplaying_font_size"><?php esc_html_e( 'Now Playing Font Size', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="range" name="<?php echo esc_attr( $this->option_name ); ?>[nowplaying_font_size]" id="nowplaying_font_size" 
                               value="<?php echo esc_attr( $settings['nowplaying_font_size'] ?? 12 ); ?>" 
                               min="8" max="24" step="1" <?php echo $disabled_attr; ?> />
                        <span class="lrp-slider-value"><?php echo esc_html( $settings['nowplaying_font_size'] ?? 12 ); ?>px</span>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="nowplaying_color"><?php esc_html_e( 'Now Playing Color', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[nowplaying_color]" id="nowplaying_color" 
                               value="<?php echo esc_attr( $settings['nowplaying_color'] ?? '#667eea' ); ?>" 
                               class="lrp-color-picker" <?php echo $disabled_attr; ?> />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="nowplaying_underline"><?php esc_html_e( 'Now Playing Underline', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[nowplaying_underline]" 
                                   id="nowplaying_underline" value="1" 
                                   <?php checked( $settings['nowplaying_underline'] ?? true, true ); ?> <?php echo $disabled_attr; ?> />
                            <?php esc_html_e( 'Show decorative underline', 'live-radio-player' ); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="artist_font_size"><?php esc_html_e( 'Artist/Group Font Size', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="range" name="<?php echo esc_attr( $this->option_name ); ?>[artist_font_size]" id="artist_font_size" 
                               value="<?php echo esc_attr( $settings['artist_font_size'] ?? 28 ); ?>" 
                               min="14" max="48" step="1" <?php echo $disabled_attr; ?> />
                        <span class="lrp-slider-value"><?php echo esc_html( $settings['artist_font_size'] ?? 28 ); ?>px</span>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="artist_color"><?php esc_html_e( 'Artist/Group Color', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[artist_color]" id="artist_color" 
                               value="<?php echo esc_attr( $settings['artist_color'] ?? '#667eea' ); ?>" 
                               class="lrp-color-picker" <?php echo $disabled_attr; ?> />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="artist_font_weight"><?php esc_html_e( 'Artist/Group Font Weight', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr( $this->option_name ); ?>[artist_font_weight]" id="artist_font_weight" <?php echo $disabled_attr; ?>>
                            <option value="400" <?php selected( $settings['artist_font_weight'] ?? '800', '400' ); ?>>Normal (400)</option>
                            <option value="500" <?php selected( $settings['artist_font_weight'] ?? '800', '500' ); ?>>Medium (500)</option>
                            <option value="600" <?php selected( $settings['artist_font_weight'] ?? '800', '600' ); ?>>Semi-Bold (600)</option>
                            <option value="700" <?php selected( $settings['artist_font_weight'] ?? '800', '700' ); ?>>Bold (700)</option>
                            <option value="800" <?php selected( $settings['artist_font_weight'] ?? '800', '800' ); ?>>Extra-Bold (800)</option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="title_font_size"><?php esc_html_e( 'Song Title Font Size', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="range" name="<?php echo esc_attr( $this->option_name ); ?>[title_font_size]" id="title_font_size" 
                               value="<?php echo esc_attr( $settings['title_font_size'] ?? 18 ); ?>" 
                               min="12" max="36" step="1" <?php echo $disabled_attr; ?> />
                        <span class="lrp-slider-value"><?php echo esc_html( $settings['title_font_size'] ?? 18 ); ?>px</span>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="title_color"><?php esc_html_e( 'Song Title Color', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[title_color]" id="title_color" 
                               value="<?php echo esc_attr( $settings['title_color'] ?? '#333333' ); ?>" 
                               class="lrp-color-picker" <?php echo $disabled_attr; ?> />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="title_font_weight"><?php esc_html_e( 'Song Title Font Weight', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr( $this->option_name ); ?>[title_font_weight]" id="title_font_weight" <?php echo $disabled_attr; ?>>
                            <option value="400" <?php selected( $settings['title_font_weight'] ?? '600', '400' ); ?>>Normal (400)</option>
                            <option value="500" <?php selected( $settings['title_font_weight'] ?? '600', '500' ); ?>>Medium (500)</option>
                            <option value="600" <?php selected( $settings['title_font_weight'] ?? '600', '600' ); ?>>Semi-Bold (600)</option>
                            <option value="700" <?php selected( $settings['title_font_weight'] ?? '600', '700' ); ?>>Bold (700)</option>
                            <option value="800" <?php selected( $settings['title_font_weight'] ?? '600', '800' ); ?>>Extra-Bold (800)</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * TAB 5: Theme Presets
     */
    private function render_tab_theme_presets( $settings ) {
        ?>
        <div class="lrp-tab-content">
            <h2><?php esc_html_e( 'Theme Presets', 'live-radio-player' ); ?></h2>
            <p><?php esc_html_e( 'Choose from predefined player themes or customize your own.', 'live-radio-player' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="theme_preset"><?php esc_html_e( 'Preset Selector', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr( $this->option_name ); ?>[theme_preset]" id="theme_preset" class="regular-text">
                            <option value="classic" <?php selected( $settings['theme_preset'] ?? 'classic', 'classic' ); ?>><?php esc_html_e( 'Classic Radio', 'live-radio-player' ); ?></option>
                            <option value="modern" <?php selected( $settings['theme_preset'] ?? 'classic', 'modern' ); ?>><?php esc_html_e( 'Modern Card', 'live-radio-player' ); ?></option>
                            <option value="dark" <?php selected( $settings['theme_preset'] ?? 'classic', 'dark' ); ?>><?php esc_html_e( 'Dark Night', 'live-radio-player' ); ?></option>
                            <option value="minimal" <?php selected( $settings['theme_preset'] ?? 'classic', 'minimal' ); ?>><?php esc_html_e( 'Minimal Mono', 'live-radio-player' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Select a predefined theme style.', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="override_preset"><?php esc_html_e( 'Override Preset Settings', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[override_preset]" 
                                   id="override_preset" value="1" <?php checked( $settings['override_preset'] ?? false, true ); ?> />
                            <?php esc_html_e( 'Allow customization in Player Layout and Visual Style tabs', 'live-radio-player' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'When disabled, Player Layout and Visual Style settings will be locked and the preset theme will be used.', 'live-radio-player' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <div class="lrp-theme-previews">
                <h3><?php esc_html_e( 'Theme Previews', 'live-radio-player' ); ?></h3>
                <div class="lrp-preview-grid">
                    <div class="lrp-preview-item" data-theme="classic">
                        <div class="lrp-preview-box lrp-theme-classic">
                            <div class="preview-content"><?php esc_html_e( 'Classic Radio', 'live-radio-player' ); ?></div>
                        </div>
                    </div>
                    <div class="lrp-preview-item" data-theme="modern">
                        <div class="lrp-preview-box lrp-theme-modern">
                            <div class="preview-content"><?php esc_html_e( 'Modern Card', 'live-radio-player' ); ?></div>
                        </div>
                    </div>
                    <div class="lrp-preview-item" data-theme="dark">
                        <div class="lrp-preview-box lrp-theme-dark">
                            <div class="preview-content"><?php esc_html_e( 'Dark Night', 'live-radio-player' ); ?></div>
                        </div>
                    </div>
                    <div class="lrp-preview-item" data-theme="minimal">
                        <div class="lrp-preview-box lrp-theme-minimal">
                            <div class="preview-content"><?php esc_html_e( 'Minimal Mono', 'live-radio-player' ); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * TAB 6: Lyrics & Artwork
     */
    private function render_tab_lyrics_artwork( $settings ) {
        ?>
        <div class="lrp-tab-content">
            <h2><?php esc_html_e( 'Lyrics & Artwork (External APIs)', 'live-radio-player' ); ?></h2>
            <p><?php esc_html_e( 'Configure external content enrichment from free public APIs.', 'live-radio-player' ); ?></p>
            
            <h3><?php esc_html_e( 'Lyrics', 'live-radio-player' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="enable_lyrics"><?php esc_html_e( 'Enable Lyrics', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enable_lyrics]" 
                                   id="enable_lyrics" value="1" <?php checked( $settings['enable_lyrics'] ?? false, true ); ?> />
                            <?php esc_html_e( 'Fetch lyrics for current track', 'live-radio-player' ); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="enable_karaoke_lyrics"><?php esc_html_e( 'Karaoke Mode', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enable_karaoke_lyrics]" 
                                   id="enable_karaoke_lyrics" value="1" <?php checked( $settings['enable_karaoke_lyrics'] ?? true, true ); ?> />
                            <?php esc_html_e( 'Enable synchronized lyrics (highlights current line in real-time)', 'live-radio-player' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'When available, lyrics will sync with audio playback like karaoke.', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="lyrics_source"><?php esc_html_e( 'Lyrics Source', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr( $this->option_name ); ?>[lyrics_source]" id="lyrics_source">
                            <option value="free_api" <?php selected( $settings['lyrics_source'] ?? 'free_api', 'free_api' ); ?>>
                                <?php esc_html_e( 'Free Public APIs (LRCLIB + lyrics.ovh)', 'live-radio-player' ); ?>
                            </option>
                        </select>
                        <p class="description"><?php esc_html_e( 'No API key required. LRCLIB provides synced lyrics for karaoke mode.', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="lyrics_font_size"><?php esc_html_e( 'Font Size', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[lyrics_font_size]" 
                               id="lyrics_font_size" value="<?php echo esc_attr( $settings['lyrics_font_size'] ?? 14 ); ?>" 
                               min="10" max="24" /> px
                        <p class="description"><?php esc_html_e( 'Font size for lyrics text (default: 14px)', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="lyrics_text_color"><?php esc_html_e( 'Text Color', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[lyrics_text_color]" 
                               id="lyrics_text_color" value="<?php echo esc_attr( $settings['lyrics_text_color'] ?? '#ffffff' ); ?>" 
                               class="lrp-color-picker" />
                        <p class="description"><?php esc_html_e( 'Color for inactive lyrics lines (default: white)', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="lyrics_active_color"><?php esc_html_e( 'Active Line Color (Karaoke)', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[lyrics_active_color]" 
                               id="lyrics_active_color" value="<?php echo esc_attr( $settings['lyrics_active_color'] ?? '#ffd700' ); ?>" 
                               class="lrp-color-picker" />
                        <p class="description"><?php esc_html_e( 'Color for currently playing line in karaoke mode (default: gold)', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="lyrics_cache_duration"><?php esc_html_e( 'Cache Duration', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[lyrics_cache_duration]" 
                               id="lyrics_cache_duration" value="<?php echo esc_attr( $settings['lyrics_cache_duration'] ?? 1440 ); ?>" 
                               min="60" max="10080" /> <?php esc_html_e( 'minutes', 'live-radio-player' ); ?>
                        <p class="description"><?php esc_html_e( 'How long to cache lyrics (default: 1440 minutes = 24 hours)', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
            </table>
            
            <h3><?php esc_html_e( 'Artwork', 'live-radio-player' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="enable_external_artwork"><?php esc_html_e( 'Enable External Album Artwork', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enable_external_artwork]" 
                                   id="enable_external_artwork" value="1" <?php checked( $settings['enable_external_artwork'] ?? false, true ); ?> />
                            <?php esc_html_e( 'Fetch album artwork from external sources', 'live-radio-player' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Currently disabled - will use fallback image only.', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="artwork_size"><?php esc_html_e( 'Preferred Image Size', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr( $this->option_name ); ?>[artwork_size]" id="artwork_size">
                            <option value="small" <?php selected( $settings['artwork_size'] ?? 'medium', 'small' ); ?>><?php esc_html_e( 'Small (150x150)', 'live-radio-player' ); ?></option>
                            <option value="medium" <?php selected( $settings['artwork_size'] ?? 'medium', 'medium' ); ?>><?php esc_html_e( 'Medium (300x300)', 'live-radio-player' ); ?></option>
                            <option value="large" <?php selected( $settings['artwork_size'] ?? 'medium', 'large' ); ?>><?php esc_html_e( 'Large (600x600)', 'live-radio-player' ); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="artwork_cache_duration"><?php esc_html_e( 'Cache Duration', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[artwork_cache_duration]" 
                               id="artwork_cache_duration" value="<?php echo esc_attr( $settings['artwork_cache_duration'] ?? 1440 ); ?>" 
                               min="60" max="10080" /> <?php esc_html_e( 'minutes', 'live-radio-player' ); ?>
                    </td>
                </tr>
            </table>
            
            <h3><?php esc_html_e( 'Fallback', 'live-radio-player' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="custom_lyrics_message"><?php esc_html_e( 'Custom Lyrics Message', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[custom_lyrics_message]" 
                               id="custom_lyrics_message" value="<?php echo esc_attr( $settings['custom_lyrics_message'] ?? 'Lyrics not available' ); ?>" 
                               class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Message shown when lyrics cannot be found.', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * TAB 7: Performance
     */
    private function render_tab_performance( $settings ) {
        ?>
        <div class="lrp-tab-content">
            <h2><?php esc_html_e( 'Advanced / Performance', 'live-radio-player' ); ?></h2>
            <p><?php esc_html_e( 'Configure performance and behavior settings.', 'live-radio-player' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="api_mode"><?php esc_html_e( 'API Mode', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr( $this->option_name ); ?>[api_mode]" id="api_mode">
                            <option value="rest" <?php selected( $settings['api_mode'] ?? 'rest', 'rest' ); ?>><?php esc_html_e( 'REST API', 'live-radio-player' ); ?></option>
                            <option value="ajax" <?php selected( $settings['api_mode'] ?? 'rest', 'ajax' ); ?>><?php esc_html_e( 'AJAX', 'live-radio-player' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Method used to fetch metadata. REST API is recommended.', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="lazy_load"><?php esc_html_e( 'Lazy Load Player', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[lazy_load]" 
                                   id="lazy_load" value="1" <?php checked( $settings['lazy_load'] ?? false, true ); ?> />
                            <?php esc_html_e( 'Load player only when visible', 'live-radio-player' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Improves page load performance.', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="allow_multiple_players"><?php esc_html_e( 'Allow Multiple Players per Page', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[allow_multiple_players]" 
                                   id="allow_multiple_players" value="1" <?php checked( $settings['allow_multiple_players'] ?? true, true ); ?> />
                            <?php esc_html_e( 'Allow multiple player instances on same page', 'live-radio-player' ); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="force_reload_on_change"><?php esc_html_e( 'Force Reload on Track Change', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[force_reload_on_change]" 
                                   id="force_reload_on_change" value="1" <?php checked( $settings['force_reload_on_change'] ?? false, true ); ?> />
                            <?php esc_html_e( 'Reload audio stream when track changes', 'live-radio-player' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'May help with some streams but can cause brief interruptions.', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Clear Cache', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <button type="button" class="button button-secondary" id="lrp-clear-cache">
                            <?php esc_html_e( 'Clear All Cache', 'live-radio-player' ); ?>
                        </button>
                        <p class="description"><?php esc_html_e( 'Clear all cached metadata and lyrics.', 'live-radio-player' ); ?></p>
                        <div id="lrp-cache-message"></div>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * TAB 8: Custom Code
     */
    private function render_tab_custom_code( $settings ) {
        ?>
        <div class="lrp-tab-content">
            <h2><?php esc_html_e( 'Custom Code (Advanced Users)', 'live-radio-player' ); ?></h2>
            
            <div class="notice notice-warning inline">
                <p><strong><?php esc_html_e( 'Warning:', 'live-radio-player' ); ?></strong> <?php esc_html_e( 'These settings are for advanced users. Incorrect code may break the player.', 'live-radio-player' ); ?></p>
            </div>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="custom_css"><?php esc_html_e( 'Custom CSS', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <textarea name="<?php echo esc_attr( $this->option_name ); ?>[custom_css]" id="custom_css" 
                                  rows="10" class="large-text code"><?php echo esc_textarea( $settings['custom_css'] ?? '' ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Add custom CSS rules for the player.', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="custom_player_class"><?php esc_html_e( 'Custom Player Class', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[custom_player_class]" 
                               id="custom_player_class" value="<?php echo esc_attr( $settings['custom_player_class'] ?? '' ); ?>" 
                               class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Add custom CSS class(es) to the player wrapper.', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="disable_plugin_css"><?php esc_html_e( 'Disable Plugin CSS', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[disable_plugin_css]" 
                                   id="disable_plugin_css" value="1" <?php checked( $settings['disable_plugin_css'] ?? false, true ); ?> />
                            <?php esc_html_e( 'Do not load default plugin CSS', 'live-radio-player' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Use if you want complete control over styling.', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="disable_plugin_js"><?php esc_html_e( 'Disable Plugin JS', 'live-radio-player' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[disable_plugin_js]" 
                                   id="disable_plugin_js" value="1" <?php checked( $settings['disable_plugin_js'] ?? false, true ); ?> />
                            <?php esc_html_e( 'Do not load default plugin JavaScript', 'live-radio-player' ); ?>
                        </label>
                        <p class="description"><strong><?php esc_html_e( 'Warning:', 'live-radio-player' ); ?></strong> <?php esc_html_e( 'Player will not function unless you provide your own JavaScript.', 'live-radio-player' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * TAB 9: Integration
     */
    private function render_tab_integration( $settings ) {
        ?>
        <div class="lrp-tab-content">
            <h2><?php esc_html_e( 'Shortcode & Integration', 'live-radio-player' ); ?></h2>
            <p><?php esc_html_e( 'Use these methods to add the player to your site.', 'live-radio-player' ); ?></p>
            
            <h3><?php esc_html_e( 'Shortcode', 'live-radio-player' ); ?></h3>
            <p><?php esc_html_e( 'Basic usage:', 'live-radio-player' ); ?></p>
            <pre class="lrp-code-block"><code>[live_radio_player]</code></pre>
            
            <p><?php esc_html_e( 'With parameters:', 'live-radio-player' ); ?></p>
            <pre class="lrp-code-block"><code>[live_radio_player theme="dark" lyrics="on"]</code></pre>
            
            <h4><?php esc_html_e( 'Available Parameters:', 'live-radio-player' ); ?></h4>
            <ul class="lrp-param-list">
                <li><code>theme</code> - classic, modern, dark, minimal</li>
                <li><code>lyrics</code> - on, off</li>
                <li><code>layout</code> - minimal, card, full, sidebar</li>
                <li><code>orientation</code> - horizontal, vertical</li>
            </ul>
            
            <h3><?php esc_html_e( 'Gutenberg Block', 'live-radio-player' ); ?></h3>
            <p><?php esc_html_e( 'Search for "Live Radio Player" in the block inserter.', 'live-radio-player' ); ?></p>
            
            <h3><?php esc_html_e( 'PHP Template Tag', 'live-radio-player' ); ?></h3>
            <p><?php esc_html_e( 'Add to your theme template files:', 'live-radio-player' ); ?></p>
            <pre class="lrp-code-block"><code>&lt;?php echo do_shortcode('[live_radio_player]'); ?&gt;</code></pre>
            
            <h3><?php esc_html_e( 'Widget', 'live-radio-player' ); ?></h3>
            <p><?php esc_html_e( 'Add a "Shortcode" widget and use the shortcode above.', 'live-radio-player' ); ?></p>
        </div>
        <?php
    }
    
    /**
     * TAB 10: Diagnostics
     */
    private function render_tab_diagnostics( $settings ) {
        $stream_manager = LRP_Stream_Manager::get_instance();
        $stats = $stream_manager->get_statistics();
        $metadata = $stream_manager->get_metadata();
        
        global $wp_version;
        
        ?>
        <div class="lrp-tab-content">
            <h2><?php esc_html_e( 'Status & Diagnostics', 'live-radio-player' ); ?></h2>
            
            <div class="lrp-diagnostics-grid">
                <div class="lrp-diag-box">
                    <h3><?php esc_html_e( 'Current Stream Status', 'live-radio-player' ); ?></h3>
                    <table class="widefat">
                        <tr>
                            <td><strong><?php esc_html_e( 'Status:', 'live-radio-player' ); ?></strong></td>
                            <td>
                                <?php if ( $stats['status'] === 'online' ) : ?>
                                    <span class="lrp-status-badge lrp-status-online"><?php esc_html_e( 'Online', 'live-radio-player' ); ?></span>
                                <?php else : ?>
                                    <span class="lrp-status-badge lrp-status-offline"><?php esc_html_e( 'Offline', 'live-radio-player' ); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Stream Type:', 'live-radio-player' ); ?></strong></td>
                            <td><?php echo esc_html( ucfirst( $stats['stream_type'] ) ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Current Track:', 'live-radio-player' ); ?></strong></td>
                            <td><?php echo esc_html( $stats['current_track'] ?: 'N/A' ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Listeners:', 'live-radio-player' ); ?></strong></td>
                            <td><?php echo esc_html( $stats['listeners'] ); ?></td>
                        </tr>
                    </table>
                </div>
                
                <div class="lrp-diag-box">
                    <h3><?php esc_html_e( 'Last Metadata Fetch', 'live-radio-player' ); ?></h3>
                    <table class="widefat">
                        <tr>
                            <td><strong><?php esc_html_e( 'Artist:', 'live-radio-player' ); ?></strong></td>
                            <td><?php echo esc_html( $metadata['artist'] ?: 'N/A' ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Title:', 'live-radio-player' ); ?></strong></td>
                            <td><?php echo esc_html( $metadata['title'] ?: 'N/A' ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Last Update:', 'live-radio-player' ); ?></strong></td>
                            <td><?php echo esc_html( $stats['last_update'] ); ?></td>
                        </tr>
                    </table>
                </div>
                
                <div class="lrp-diag-box">
                    <h3><?php esc_html_e( 'Cache Status', 'live-radio-player' ); ?></h3>
                    <table class="widefat">
                        <tr>
                            <td><strong><?php esc_html_e( 'Metadata Cache:', 'live-radio-player' ); ?></strong></td>
                            <td><?php esc_html_e( 'Active', 'live-radio-player' ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Cache Duration:', 'live-radio-player' ); ?></strong></td>
                            <td><?php echo esc_html( $settings['refresh_interval'] ?? 30 ); ?> <?php esc_html_e( 'seconds', 'live-radio-player' ); ?></td>
                        </tr>
                    </table>
                </div>
                
                <div class="lrp-diag-box">
                    <h3><?php esc_html_e( 'System Information', 'live-radio-player' ); ?></h3>
                    <table class="widefat">
                        <tr>
                            <td><strong><?php esc_html_e( 'Plugin Version:', 'live-radio-player' ); ?></strong></td>
                            <td><?php echo esc_html( LRP_VERSION ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'WordPress Version:', 'live-radio-player' ); ?></strong></td>
                            <td><?php echo esc_html( $wp_version ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'PHP Version:', 'live-radio-player' ); ?></strong></td>
                            <td><?php echo esc_html( PHP_VERSION ); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings( $input ) {
        // Get existing settings to merge with new input
        $existing = get_option( $this->option_name, array() );
        $sanitized = is_array( $existing ) ? $existing : array();
        
        // Text fields
        $text_fields = array( 'stream_url', 'mount_point', 'fallback_text', 'custom_lyrics_message', 'custom_player_class', 'custom_width', 'facebook_url', 'nowplaying_text', 'bg_image' );
        foreach ( $text_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                if ( $field === 'bg_image' ) {
                    $sanitized[ $field ] = esc_url_raw( $input[ $field ] );
                } else {
                    $sanitized[ $field ] = sanitize_text_field( $input[ $field ] );
                }
            }
        }
        
        // URL field
        if ( isset( $input['stream_url'] ) ) {
            $sanitized['stream_url'] = esc_url_raw( $input['stream_url'] );
        }
        
        // Textarea fields
        if ( isset( $input['custom_css'] ) ) {
            $sanitized['custom_css'] = wp_strip_all_tags( $input['custom_css'] );
        }
        
        // Select/radio fields
        $select_fields = array(
            'stream_type', 'stream_format', 'layout_type', 'orientation', 'alignment',
            'player_width', 'theme_preset', 'lyrics_source', 'lyrics_language',
            'artwork_size', 'api_mode', 'shadow', 'font_weight', 'artist_font_weight', 'title_font_weight',
            'bg_image_size', 'bg_image_position'
        );
        
        foreach ( $select_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_key( $input[ $field ] );
            }
        }
        
        // Number fields
        $number_fields = array(
            'sid', 'refresh_interval', 'connection_timeout', 'font_size', 'border_radius',
            'padding', 'spacing', 'lyrics_cache_duration', 'artwork_cache_duration', 'lyrics_font_size',
            'nowplaying_font_size', 'artist_font_size', 'title_font_size', 'bg_image_opacity', 'bg_image_angle'
        );
        
        foreach ( $number_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $sanitized[ $field ] = absint( $input[ $field ] );
            }
        }
        
        // Float fields
        if ( isset( $input['line_height'] ) ) {
            $sanitized['line_height'] = floatval( $input['line_height'] );
        }
        
        // Color fields
        $color_fields = array( 'bg_color', 'accent_color', 'text_color', 'button_color', 'button_hover_color', 'lyrics_text_color', 'lyrics_active_color', 'nowplaying_color', 'artist_color', 'title_color' );
        foreach ( $color_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_hex_color( $input[ $field ] );
            }
        }
        
        // Detect which tab was submitted
        $current_tab = isset( $input['_current_tab'] ) ? $input['_current_tab'] : '';
        
        // Define checkboxes by tab
        $tab_checkboxes = array(
            'content_control' => array( 'show_artist', 'show_title', 'show_album', 'show_artwork', 
                                       'show_listeners', 'show_track_time', 'show_status', 'show_nowplaying_label', 
                                       'show_station_name', 'show_lyrics' ),
            'stream_settings' => array( 'enable_metadata_fetch', 'debug_mode' ),
            'player_layout' => array( 'sticky_player' ),
            'visual_style' => array( 'nowplaying_underline' ),
            'theme_presets' => array( 'override_preset' ),
            'lyrics_artwork' => array( 'enable_lyrics', 'enable_karaoke_lyrics', 'enable_external_artwork' ),
            'performance' => array( 'lazy_load', 'allow_multiple_players', 'force_reload_on_change' ),
            'custom_code' => array( 'disable_plugin_css', 'disable_plugin_js' )
        );
        
        // Update checkboxes for the current tab only
        if ( isset( $tab_checkboxes[ $current_tab ] ) ) {
            foreach ( $tab_checkboxes[ $current_tab ] as $field ) {
                $sanitized[ $field ] = isset( $input[ $field ] ) && $input[ $field ] === '1';
            }
        }
        
        // Image URL
        if ( isset( $input['fallback_image'] ) ) {
            $sanitized['fallback_image'] = esc_url_raw( $input['fallback_image'] );
        }
        
        return $sanitized;
    }
    
    /**
     * AJAX handler for clearing cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer( 'lrp_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'live-radio-player' ) ) );
        }
        
        $stream_manager = LRP_Stream_Manager::get_instance();
        $stream_manager->clear_cache();
        
        $lyrics_service = LRP_Lyrics_Service::get_instance();
        $lyrics_service->clear_cache();
        
        wp_send_json_success( array( 'message' => __( 'Cache cleared successfully!', 'live-radio-player' ) ) );
    }
}
