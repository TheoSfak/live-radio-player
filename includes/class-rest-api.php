<?php
/**
 * REST API Endpoints
 *
 * @package Live_Radio_Player
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API handler
 */
class LRP_REST_API {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * API namespace
     */
    private $namespace = 'live-radio-player/v1';
    
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
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Get metadata
        register_rest_route( $this->namespace, '/metadata', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_metadata' ),
            'permission_callback' => '__return_true'
        ) );
        
        // Get stream status
        register_rest_route( $this->namespace, '/status', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_status' ),
            'permission_callback' => '__return_true'
        ) );
        
        // Get lyrics
        register_rest_route( $this->namespace, '/lyrics', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_lyrics' ),
            'permission_callback' => '__return_true',
            'args' => array(
                'artist' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'title' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ) );
        
        // Clear cache (admin only)
        register_rest_route( $this->namespace, '/cache/clear', array(
            'methods' => 'POST',
            'callback' => array( $this, 'clear_cache' ),
            'permission_callback' => array( $this, 'check_admin_permission' )
        ) );
    }
    
    /**
     * Get metadata endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_metadata( $request ) {
        $force_refresh = $request->get_param( 'force' ) === 'true';
        
        $stream_manager = LRP_Stream_Manager::get_instance();
        $metadata = $stream_manager->get_metadata( $force_refresh );
        
        // Fetch artwork and duration if artist and title are available
        $settings = get_option( 'lrp_settings', array() );
        $show_track_time = isset( $settings['show_track_time'] ) && $settings['show_track_time'];
        
        if ( ! empty( $metadata['artist'] ) && ! empty( $metadata['title'] ) ) {
            $artwork_size = isset( $settings['artwork_size'] ) ? $settings['artwork_size'] : 'medium';
            
            // Use get_track_info for both artwork and duration
            $track_info = LRP_Artwork_Service::get_track_info( 
                $metadata['artist'], 
                $metadata['title'], 
                $artwork_size 
            );
            
            if ( $track_info['artwork_url'] ) {
                $metadata['artwork_url'] = $track_info['artwork_url'];
            } elseif ( ! empty( $settings['fallback_image'] ) ) {
                $metadata['artwork_url'] = esc_url_raw( $settings['fallback_image'] );
            }
            
            // Include duration if track time display is enabled
            if ( $show_track_time && $track_info['duration_ms'] > 0 ) {
                $metadata['duration_ms'] = $track_info['duration_ms'];
            }
        } elseif ( ! empty( $settings['fallback_image'] ) ) {
            $metadata['artwork_url'] = esc_url_raw( $settings['fallback_image'] );
        }
        
        $response = array(
            'success' => true,
            'data' => $metadata,
            'display' => array(
                'show_artist' => isset( $settings['show_artist'] ) ? $settings['show_artist'] : true,
                'show_title' => isset( $settings['show_title'] ) ? $settings['show_title'] : true,
                'show_album' => isset( $settings['show_album'] ) ? $settings['show_album'] : true,
                'show_artwork' => isset( $settings['show_artwork'] ) ? $settings['show_artwork'] : true,
                'show_listeners' => isset( $settings['show_listeners'] ) ? $settings['show_listeners'] : true,
                'show_status' => isset( $settings['show_status'] ) ? $settings['show_status'] : true,
                'show_lyrics' => isset( $settings['show_lyrics'] ) ? $settings['show_lyrics'] : false,
                'show_track_time' => isset( $settings['show_track_time'] ) ? $settings['show_track_time'] : false,
                'fallback_text' => isset( $settings['fallback_text'] ) ? $settings['fallback_text'] : '',
                'fallback_image' => isset( $settings['fallback_image'] ) ? $settings['fallback_image'] : ''
            )
        );
        
        return new WP_REST_Response( $response, 200 );
    }
    
    /**
     * Get stream status endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_status( $request ) {
        $stream_manager = LRP_Stream_Manager::get_instance();
        $stats = $stream_manager->get_statistics();
        
        return new WP_REST_Response( array(
            'success' => true,
            'data' => $stats
        ), 200 );
    }
    
    /**
     * Get lyrics endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_lyrics( $request ) {
        $artist = $request->get_param( 'artist' );
        $title = $request->get_param( 'title' );
        
        $lyrics_service = LRP_Lyrics_Service::get_instance();
        $lyrics = $lyrics_service->get_lyrics( $artist, $title );
        
        return new WP_REST_Response( array(
            'success' => true,
            'data' => $lyrics
        ), 200 );
    }
    
    /**
     * Clear cache endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function clear_cache( $request ) {
        $stream_manager = LRP_Stream_Manager::get_instance();
        $stream_manager->clear_cache();
        
        $lyrics_service = LRP_Lyrics_Service::get_instance();
        $lyrics_service->clear_cache();
        
        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Cache cleared successfully'
        ), 200 );
    }
    
    /**
     * Check admin permission
     *
     * @return bool
     */
    public function check_admin_permission() {
        return current_user_can( 'manage_options' );
    }
}
