<?php
/**
 * Stream Manager
 *
 * @package Live_Radio_Player
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages stream providers and metadata fetching
 */
class LRP_Stream_Manager {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Stream provider instance
     */
    private $provider = null;
    
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
        // Initialization handled on demand
    }
    
    /**
     * Get current metadata
     *
     * @param bool $force_refresh Force bypass cache
     * @return array Metadata array
     */
    public function get_metadata( $force_refresh = false ) {
        $settings = get_option( 'lrp_settings', array() );
        
        if ( ! isset( $settings['enable_metadata_fetch'] ) || ! $settings['enable_metadata_fetch'] ) {
            return $this->get_fallback_metadata( $settings );
        }
        
        $cache_key = 'lrp_metadata_' . md5( serialize( $settings ) );
        
        if ( ! $force_refresh ) {
            $cached = get_transient( $cache_key );
            if ( false !== $cached ) {
                return $cached;
            }
        }
        
        $provider = $this->get_provider( $settings['stream_type'] );
        
        if ( ! $provider ) {
            return $this->get_empty_metadata();
        }
        
        $metadata = $provider->fetch_metadata( $settings );
        
        // Cache the result - use shorter cache time for faster updates
        $refresh_interval = isset( $settings['refresh_interval'] ) ? absint( $settings['refresh_interval'] ) : 10;
        set_transient( $cache_key, $metadata, $refresh_interval );
        
        // Log if debug mode enabled
        if ( isset( $settings['debug_mode'] ) && $settings['debug_mode'] ) {
            $this->log_metadata( $metadata );
        }
        
        return $metadata;
    }
    
    /**
     * Get stream provider
     *
     * @param string $type Provider type (icecast, shoutcast)
     * @return LRP_Stream_Provider_Interface|null
     */
    private function get_provider( $type ) {
        if ( null !== $this->provider ) {
            return $this->provider;
        }
        
        switch ( $type ) {
            case 'icecast':
                $this->provider = new LRP_Icecast_Provider();
                break;
                
            case 'shoutcast':
            case 'shoutcast_v1':
            case 'shoutcast_v2':
                $this->provider = new LRP_Shoutcast_Provider();
                break;
                
            default:
                return null;
        }
        
        return $this->provider;
    }
    
    /**
     * Check if stream is online
     *
     * @return bool
     */
    public function is_stream_online() {
        $metadata = $this->get_metadata();
        return ( isset( $metadata['stream_status'] ) && $metadata['stream_status'] === 'online' );
    }
    
    /**
     * Get stream statistics
     *
     * @return array
     */
    public function get_statistics() {
        $metadata = $this->get_metadata();
        $settings = get_option( 'lrp_settings', array() );
        
        return array(
            'status' => isset( $metadata['stream_status'] ) ? $metadata['stream_status'] : 'offline',
            'listeners' => isset( $metadata['listeners'] ) ? $metadata['listeners'] : 0,
            'current_track' => isset( $metadata['raw_title'] ) ? $metadata['raw_title'] : '',
            'stream_type' => isset( $settings['stream_type'] ) ? $settings['stream_type'] : '',
            'last_update' => current_time( 'mysql' )
        );
    }
    
    /**
     * Clear all cached metadata
     */
    public function clear_cache() {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lrp_metadata_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lrp_metadata_%'" );
    }
    
    /**
     * Get empty metadata structure
     *
     * @return array
     */
    private function get_empty_metadata() {
        return array(
            'artist' => '',
            'title' => '',
            'album' => '',
            'listeners' => 0,
            'stream_status' => 'offline'
        );
    }
    
    /**
     * Get fallback metadata when server-side fetching is disabled
     *
     * @param array $settings Plugin settings
     * @return array
     */
    private function get_fallback_metadata( $settings ) {
        $fallback_text = isset( $settings['fallback_text'] ) ? $settings['fallback_text'] : 'Live Radio Stream';
        
        // Actually check if stream is online by testing the URL
        $is_online = $this->check_stream_connection( $settings );
        
        return array(
            'artist' => $fallback_text,
            'title' => '',
            'album' => '',
            'listeners' => 0,
            'stream_status' => $is_online ? 'online' : 'offline',
            'raw_title' => $fallback_text
        );
    }
    
    /**
     * Check if stream server is reachable
     *
     * @param array $settings Plugin settings
     * @return bool True if online, false if offline
     */
    private function check_stream_connection( $settings ) {
        if ( empty( $settings['stream_url'] ) ) {
            return false;
        }
        
        $stream_url = trailingslashit( esc_url_raw( $settings['stream_url'] ) );
        $timeout = isset( $settings['connection_timeout'] ) ? absint( $settings['connection_timeout'] ) : 5;
        
        // Try to connect to the stream
        $response = wp_remote_head( $stream_url, array(
            'timeout' => $timeout,
            'sslverify' => false,
            'redirection' => 5
        ) );
        
        // Log for debugging
        if ( isset( $settings['debug_mode'] ) && $settings['debug_mode'] ) {
            $status = is_wp_error( $response ) ? 'ERROR: ' . $response->get_error_message() : 'Response Code: ' . wp_remote_retrieve_response_code( $response );
            error_log( '[Live Radio Player] Stream Connection Check - URL: ' . $stream_url . ' | Status: ' . $status );
        }
        
        if ( is_wp_error( $response ) ) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        
        // Accept 200 OK or 302 redirect (common for streams)
        return in_array( $response_code, array( 200, 302, 301, 307 ) );
    }
    
    /**
     * Log metadata for debugging
     *
     * @param array $metadata
     */
    private function log_metadata( $metadata ) {
        if ( function_exists( 'error_log' ) ) {
            error_log( '[Live Radio Player] Metadata: ' . print_r( $metadata, true ) );
        }
    }
}
