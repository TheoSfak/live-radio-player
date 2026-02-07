<?php
/**
 * Public-facing functionality
 *
 * @package Live_Radio_Player
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Public class
 */
class LRP_Public {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Player instance counter
     */
    private $player_count = 0;
    
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
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_shortcode( 'live_radio_player', array( $this, 'render_shortcode' ) );
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        $settings = get_option( 'lrp_settings', array() );
        
        // Check if assets should be loaded
        $disable_css = isset( $settings['disable_plugin_css'] ) && $settings['disable_plugin_css'];
        $disable_js = isset( $settings['disable_plugin_js'] ) && $settings['disable_plugin_js'];
        
        if ( ! $disable_css ) {
            wp_enqueue_style(
                'lrp-player-css',
                LRP_PLUGIN_URL . 'assets/css/player.css',
                array(),
                LRP_VERSION
            );
            
            // Add custom CSS
            if ( ! empty( $settings['custom_css'] ) ) {
                wp_add_inline_style( 'lrp-player-css', wp_strip_all_tags( $settings['custom_css'] ) );
            }
        }
        
        if ( ! $disable_js ) {
            // Build proper stream URL based on stream type
            $stream_url = isset( $settings['stream_url'] ) ? esc_url( $settings['stream_url'] ) : '';
            $stream_type = isset( $settings['stream_type'] ) ? $settings['stream_type'] : 'shoutcast';
            $sid = isset( $settings['sid'] ) ? absint( $settings['sid'] ) : 1;
            
            // For Shoutcast streams, append proper suffix if not present for browser playback
            if ( in_array( $stream_type, array( 'shoutcast', 'shoutcast_v1', 'shoutcast_v2' ) ) && ! empty( $stream_url ) ) {
                // Check if already has suffix
                if ( ! preg_match( '/\/;|,\d+/', $stream_url ) ) {
                    $stream_url = rtrim( $stream_url, '/' );
                    // Use ,1 format for Shoutcast v2 with SID, or /; for v1
                    if ( $stream_type === 'shoutcast_v2' ) {
                        $stream_url .= ',' . $sid;
                    } else {
                        $stream_url .= '/;';
                    }
                }
            }
            
            wp_enqueue_script(
                'lrp-player-js',
                LRP_PLUGIN_URL . 'assets/js/player.js',
                array(),
                LRP_VERSION,
                true
            );
            
            wp_localize_script( 'lrp-player-js', 'lrpConfig', array(
                'apiUrl' => rest_url( 'live-radio-player/v1' ),
                'refreshInterval' => isset( $settings['refresh_interval'] ) ? absint( $settings['refresh_interval'] ) * 1000 : 30000,
                'streamUrl' => $stream_url,
                'mountPoint' => isset( $settings['mount_point'] ) ? sanitize_text_field( $settings['mount_point'] ) : '',
                'streamFormat' => isset( $settings['stream_format'] ) ? sanitize_key( $settings['stream_format'] ) : 'mp3',
                'enableLyrics' => isset( $settings['show_lyrics'] ) && $settings['show_lyrics'],
                'forceReload' => isset( $settings['force_reload_on_change'] ) && $settings['force_reload_on_change'],
                'enableKaraoke' => isset( $settings['enable_karaoke_lyrics'] ) && $settings['enable_karaoke_lyrics'],
                'lyricsFontSize' => isset( $settings['lyrics_font_size'] ) ? absint( $settings['lyrics_font_size'] ) : 14,
                'lyricsColor' => isset( $settings['lyrics_text_color'] ) ? sanitize_hex_color( $settings['lyrics_text_color'] ) : '#ffffff',
                'lyricsActiveColor' => isset( $settings['lyrics_active_color'] ) ? sanitize_hex_color( $settings['lyrics_active_color'] ) : '#ffd700'
            ) );
        }
    }
    
    /**
     * Render shortcode
     */
    public function render_shortcode( $atts ) {
        $this->player_count++;
        
        // Force enqueue assets when shortcode is used
        $this->enqueue_assets();
        
        $settings = get_option( 'lrp_settings', array() );
        
        // Parse shortcode attributes
        $atts = shortcode_atts( array(
            'theme' => isset( $settings['theme_preset'] ) ? $settings['theme_preset'] : 'classic',
            'lyrics' => isset( $settings['show_lyrics'] ) && $settings['show_lyrics'] ? 'on' : 'off',
            'layout' => isset( $settings['layout_type'] ) ? $settings['layout_type'] : 'card',
            'orientation' => isset( $settings['orientation'] ) ? $settings['orientation'] : 'horizontal'
        ), $atts, 'live_radio_player' );
        
        // Build player HTML
        return $this->render_player( $atts, $settings );
    }
    
    /**
     * Render player HTML
     */
    private function render_player( $atts, $settings ) {
        $player_id = 'lrp-player-' . $this->player_count;
        
        // Get dynamic styles
        $inline_styles = $this->get_inline_styles( $settings );
        
        // Build CSS classes
        $classes = array(
            'lrp-player',
            'lrp-theme-' . sanitize_html_class( $atts['theme'] ),
            'lrp-layout-' . sanitize_html_class( $atts['layout'] ),
            'lrp-orientation-' . sanitize_html_class( $atts['orientation'] ),
            'lrp-align-' . sanitize_html_class( $settings['alignment'] ?? 'center' )
        );
        
        if ( isset( $settings['sticky_player'] ) && $settings['sticky_player'] ) {
            $classes[] = 'lrp-sticky';
        }
        
        if ( ! empty( $settings['custom_player_class'] ) ) {
            $classes[] = sanitize_html_class( $settings['custom_player_class'] );
        }
        
        ob_start();
        ?>
        <div id="<?php echo esc_attr( $player_id ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" 
             style="<?php echo esc_attr( $inline_styles ); ?>" data-player-id="<?php echo esc_attr( $this->player_count ); ?>">
            
            <div class="lrp-player-inner">
                <?php if ( isset( $settings['show_artwork'] ) && $settings['show_artwork'] ) : ?>
                <div class="lrp-artwork-section">
                    <?php if ( ! empty( $settings['facebook_url'] ) ) : ?>
                    <a href="<?php echo esc_url( $settings['facebook_url'] ); ?>" target="_blank" rel="noopener noreferrer" class="lrp-facebook-button">
                        <svg class="lrp-fb-icon" viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        <span><?php esc_html_e( 'Like us on Facebook', 'live-radio-player' ); ?></span>
                    </a>
                    <?php endif; ?>
                    <div class="lrp-artwork-wrapper">
                        <img src="<?php echo esc_url( $settings['fallback_image'] ?? LRP_PLUGIN_URL . 'assets/images/default-artwork.png' ); ?>" 
                             alt="<?php esc_attr_e( 'Album Artwork', 'live-radio-player' ); ?>" 
                             class="lrp-artwork" />
                        <div class="lrp-artwork-loader"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="lrp-main-section">
                    <?php if ( isset( $settings['show_status'] ) && $settings['show_status'] ) : ?>
                    <div class="lrp-status">
                        <span class="lrp-status-indicator" data-status="offline"></span>
                        <span class="lrp-status-text"><?php esc_html_e( 'Offline', 'live-radio-player' ); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="lrp-metadata">
                        <?php if ( isset( $settings['show_nowplaying_label'] ) && $settings['show_nowplaying_label'] ) : ?>
                        <div class="lrp-nowplaying-label"><?php echo esc_html( $settings['nowplaying_text'] ?? __( 'Now Playing', 'live-radio-player' ) ); ?></div>
                        <?php endif; ?>
                        
                        <?php if ( isset( $settings['show_artist'] ) && $settings['show_artist'] ) : ?>
                        <div class="lrp-artist"><?php echo esc_html( $settings['fallback_text'] ?? 'Loading...' ); ?></div>
                        <?php endif; ?>
                        
                        <?php if ( isset( $settings['show_title'] ) && $settings['show_title'] ) : ?>
                        <div class="lrp-title"></div>
                        <?php endif; ?>
                        
                        <?php if ( isset( $settings['show_album'] ) && $settings['show_album'] ) : ?>
                        <div class="lrp-album"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="lrp-controls">
                        <button class="lrp-play-button" aria-label="<?php esc_attr_e( 'Play', 'live-radio-player' ); ?>">
                            <svg class="lrp-icon lrp-icon-play" viewBox="0 0 24 24" width="24" height="24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            <svg class="lrp-icon lrp-icon-pause" viewBox="0 0 24 24" width="24" height="24" style="display:none;">
                                <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                            </svg>
                            <div class="lrp-button-loader"></div>
                        </button>
                        
                        <div class="lrp-volume-control">
                            <button class="lrp-volume-button" aria-label="<?php esc_attr_e( 'Volume', 'live-radio-player' ); ?>">
                                <svg class="lrp-icon lrp-icon-volume" viewBox="0 0 24 24" width="20" height="20">
                                    <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/>
                                </svg>
                            </button>
                            <input type="range" class="lrp-volume-slider" min="0" max="100" value="70" />
                        </div>
                    </div>
                    
                    <?php if ( isset( $settings['show_track_time'] ) && $settings['show_track_time'] ) : ?>
                    <div class="lrp-track-time">
                        <svg class="lrp-icon" viewBox="0 0 24 24" width="16" height="16">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                        </svg>
                        <span class="lrp-time-elapsed">0:00</span>
                        <span class="lrp-time-separator">/</span>
                        <span class="lrp-time-remaining">-0:00</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ( isset( $settings['show_listeners'] ) && $settings['show_listeners'] ) : ?>
                    <div class="lrp-listeners">
                        <svg class="lrp-icon" viewBox="0 0 24 24" width="16" height="16">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                        </svg>
                        <span class="lrp-listeners-count">0</span>
                        <span class="lrp-listeners-text"><?php esc_html_e( 'listeners', 'live-radio-player' ); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ( $atts['lyrics'] === 'on' && isset( $settings['show_lyrics'] ) && $settings['show_lyrics'] ) : ?>
                    <div class="lrp-lyrics-section">
                        <button class="lrp-lyrics-toggle"><?php esc_html_e( 'Show Lyrics', 'live-radio-player' ); ?></button>
                        <div class="lrp-lyrics-content" style="display:none;">
                            <div class="lrp-lyrics-text"></div>
                            <div class="lrp-lyrics-loader"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <audio class="lrp-audio-element" preload="none"></audio>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get inline styles based on settings
     */
    private function get_inline_styles( $settings ) {
        $styles = array();
        
        // Check if override preset is enabled
        $override = isset( $settings['override_preset'] ) && $settings['override_preset'];
        
        if ( $override ) {
            // Colors
            if ( ! empty( $settings['bg_color'] ) ) {
                $styles[] = '--lrp-bg-color: ' . sanitize_hex_color( $settings['bg_color'] );
            }
            if ( ! empty( $settings['accent_color'] ) ) {
                $styles[] = '--lrp-accent-color: ' . sanitize_hex_color( $settings['accent_color'] );
            }
            if ( ! empty( $settings['text_color'] ) ) {
                $styles[] = '--lrp-text-color: ' . sanitize_hex_color( $settings['text_color'] );
            }
            if ( ! empty( $settings['button_color'] ) ) {
                $styles[] = '--lrp-button-color: ' . sanitize_hex_color( $settings['button_color'] );
            }
            if ( ! empty( $settings['button_hover_color'] ) ) {
                $styles[] = '--lrp-button-hover-color: ' . sanitize_hex_color( $settings['button_hover_color'] );
            }
            
            // Typography
            if ( ! empty( $settings['font_size'] ) ) {
                $styles[] = '--lrp-font-size: ' . absint( $settings['font_size'] ) . 'px';
            }
            if ( ! empty( $settings['font_weight'] ) ) {
                $styles[] = '--lrp-font-weight: ' . absint( $settings['font_weight'] );
            }
            if ( ! empty( $settings['line_height'] ) ) {
                $styles[] = '--lrp-line-height: ' . floatval( $settings['line_height'] );
            }
            
            // UI
            if ( ! empty( $settings['border_radius'] ) ) {
                $styles[] = '--lrp-border-radius: ' . absint( $settings['border_radius'] ) . 'px';
            }
            if ( ! empty( $settings['padding'] ) ) {
                $styles[] = '--lrp-padding: ' . absint( $settings['padding'] ) . 'px';
            }
            if ( ! empty( $settings['spacing'] ) ) {
                $styles[] = '--lrp-spacing: ' . absint( $settings['spacing'] ) . 'px';
            }
            
            // Shadow
            if ( ! empty( $settings['shadow'] ) ) {
                switch ( $settings['shadow'] ) {
                    case 'soft':
                        $styles[] = '--lrp-shadow: 0 2px 8px rgba(0,0,0,0.1)';
                        break;
                    case 'strong':
                        $styles[] = '--lrp-shadow: 0 4px 16px rgba(0,0,0,0.2)';
                        break;
                    default:
                        $styles[] = '--lrp-shadow: none';
                }
            }
            
            // Text Customization
            if ( ! empty( $settings['nowplaying_font_size'] ) ) {
                $styles[] = '--lrp-nowplaying-font-size: ' . absint( $settings['nowplaying_font_size'] ) . 'px';
            }
            if ( ! empty( $settings['nowplaying_color'] ) ) {
                $styles[] = '--lrp-nowplaying-color: ' . sanitize_hex_color( $settings['nowplaying_color'] );
            }
            if ( isset( $settings['nowplaying_underline'] ) && ! $settings['nowplaying_underline'] ) {
                $styles[] = '--lrp-nowplaying-underline-display: none';
            }
            if ( ! empty( $settings['artist_font_size'] ) ) {
                $styles[] = '--lrp-artist-font-size: ' . absint( $settings['artist_font_size'] ) . 'px';
            }
            if ( ! empty( $settings['artist_color'] ) ) {
                $styles[] = '--lrp-artist-color: ' . sanitize_hex_color( $settings['artist_color'] );
            }
            if ( ! empty( $settings['artist_font_weight'] ) ) {
                $styles[] = '--lrp-artist-font-weight: ' . absint( $settings['artist_font_weight'] );
            }
            if ( ! empty( $settings['title_font_size'] ) ) {
                $styles[] = '--lrp-title-font-size: ' . absint( $settings['title_font_size'] ) . 'px';
            }
            if ( ! empty( $settings['title_color'] ) ) {
                $styles[] = '--lrp-title-color: ' . sanitize_hex_color( $settings['title_color'] );
            }
            if ( ! empty( $settings['title_font_weight'] ) ) {
                $styles[] = '--lrp-title-font-weight: ' . absint( $settings['title_font_weight'] );
            }
            
            // Background Image
            if ( ! empty( $settings['bg_image'] ) ) {
                $styles[] = '--lrp-bg-image-url: url(' . esc_url( $settings['bg_image'] ) . ')';
                $styles[] = '--lrp-bg-image-display: block';
                
                if ( isset( $settings['bg_image_opacity'] ) ) {
                    $opacity = absint( $settings['bg_image_opacity'] ) / 100;
                    $styles[] = '--lrp-bg-image-opacity: ' . floatval( $opacity );
                }
                if ( isset( $settings['bg_image_angle'] ) ) {
                    $styles[] = '--lrp-bg-image-angle: ' . intval( $settings['bg_image_angle'] ) . 'deg';
                }
                if ( ! empty( $settings['bg_image_size'] ) ) {
                    $styles[] = '--lrp-bg-image-size: ' . sanitize_text_field( $settings['bg_image_size'] );
                }
                if ( ! empty( $settings['bg_image_position'] ) ) {
                    $styles[] = '--lrp-bg-image-position: ' . sanitize_text_field( $settings['bg_image_position'] );
                }
            }
        }
        
        // Width
        if ( isset( $settings['player_width'] ) && $settings['player_width'] === 'custom' && ! empty( $settings['custom_width'] ) ) {
            $styles[] = 'width: ' . sanitize_text_field( $settings['custom_width'] );
        }
        
        return implode( '; ', $styles );
    }
}
