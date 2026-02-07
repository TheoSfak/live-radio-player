<?php
/**
 * Icecast Stream Provider
 *
 * @package Live_Radio_Player
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Icecast provider implementation
 */
class LRP_Icecast_Provider implements LRP_Stream_Provider_Interface {
    
    /**
     * Fetch metadata from Icecast stream
     *
     * @param array $config Stream configuration
     * @return array Normalized metadata
     */
    public function fetch_metadata( $config ) {
        $stream_url = trailingslashit( esc_url_raw( $config['stream_url'] ) );
        $mount_point = ltrim( sanitize_text_field( $config['mount_point'] ), '/' );
        $timeout = isset( $config['connection_timeout'] ) ? absint( $config['connection_timeout'] ) : 5;
        
        // Icecast JSON stats endpoint
        $stats_url = $stream_url . 'status-json.xsl';
        
        $response = wp_remote_get( $stats_url, array(
            'timeout' => $timeout,
            'sslverify' => false
        ) );
        
        if ( is_wp_error( $response ) ) {
            return $this->get_error_response( $response->get_error_message() );
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( ! $data || ! isset( $data['icestats']['source'] ) ) {
            return $this->get_error_response( 'Invalid response from Icecast server' );
        }
        
        // Find the correct mount
        $sources = $data['icestats']['source'];
        
        // Handle single mount vs multiple mounts
        if ( isset( $sources['listenurl'] ) ) {
            $sources = array( $sources );
        }
        
        $mount_data = null;
        foreach ( $sources as $source ) {
            if ( isset( $source['listenurl'] ) && strpos( $source['listenurl'], $mount_point ) !== false ) {
                $mount_data = $source;
                break;
            }
        }
        
        if ( ! $mount_data ) {
            return $this->get_error_response( 'Mount point not found' );
        }
        
        return $this->normalize_data( $mount_data );
    }
    
    /**
     * Check if stream is online
     *
     * @param array $config Stream configuration
     * @return bool
     */
    public function is_stream_online( $config ) {
        $metadata = $this->fetch_metadata( $config );
        return ( $metadata['stream_status'] === 'online' );
    }
    
    /**
     * Normalize Icecast data
     *
     * @param array $raw_data Raw Icecast data
     * @return array Normalized data
     */
    public function normalize_data( $raw_data ) {
        $title = isset( $raw_data['title'] ) ? sanitize_text_field( $raw_data['title'] ) : '';
        
        // Parse artist and title from "Artist - Title" format
        // Support both " - " and "-" (with or without spaces)
        $artist = '';
        $track_title = '';
        
        // Try with space-dash-space first
        if ( strpos( $title, ' - ' ) !== false ) {
            $parts = explode( ' - ', $title, 2 );
            $artist = sanitize_text_field( trim( $parts[0] ) );
            $track_title = sanitize_text_field( trim( $parts[1] ) );
        }
        // Try with just dash (no spaces)
        elseif ( strpos( $title, '-' ) !== false ) {
            $parts = explode( '-', $title, 2 );
            $artist = sanitize_text_field( trim( $parts[0] ) );
            $track_title = sanitize_text_field( trim( $parts[1] ) );
        }
        // No separator found
        else {
            $track_title = $title;
        }
        
        // If no track title, set a friendly message
        if ( empty( $artist ) && empty( $track_title ) ) {
            $artist = 'No track playing';
        }
        
        return array(
            'artist' => $artist,
            'title' => $track_title,
            'album' => '', // Icecast doesn't typically provide album info
            'listeners' => isset( $raw_data['listeners'] ) ? absint( $raw_data['listeners'] ) : 0,
            'stream_status' => 'online',
            'raw_title' => $title
        );
    }
    
    /**
     * Get error response
     *
     * @param string $message Error message
     * @return array
     */
    private function get_error_response( $message ) {
        return array(
            'artist' => '',
            'title' => '',
            'album' => '',
            'listeners' => 0,
            'stream_status' => 'offline',
            'error' => sanitize_text_field( $message )
        );
    }
}
