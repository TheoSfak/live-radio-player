<?php
/**
 * Shoutcast Stream Provider
 *
 * @package Live_Radio_Player
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shoutcast provider implementation (supports v1 and v2)
 */
class LRP_Shoutcast_Provider implements LRP_Stream_Provider_Interface {
    
    /**
     * Fetch metadata from Shoutcast stream
     *
     * @param array $config Stream configuration
     * @return array Normalized metadata
     */
    public function fetch_metadata( $config ) {
        $stream_url = rtrim( esc_url_raw( $config['stream_url'] ), '/;,' );
        $sid = isset( $config['sid'] ) ? absint( $config['sid'] ) : 1;
        $timeout = isset( $config['connection_timeout'] ) ? absint( $config['connection_timeout'] ) : 5;
        $debug = isset( $config['debug_mode'] ) && $config['debug_mode'];
        
        // Try Shoutcast v2 JSON endpoint first
        $stats_url = $stream_url . '/stats?sid=' . $sid . '&json=1';
        
        if ( $debug ) {
            error_log( '[LRP] Fetching Shoutcast metadata from: ' . $stats_url );
        }
        
        $response = wp_remote_get( $stats_url, array(
            'timeout' => $timeout,
            'sslverify' => false
        ) );
        
        if ( ! is_wp_error( $response ) ) {
            $body = wp_remote_retrieve_body( $response );
            
            if ( $debug ) {
                error_log( '[LRP] Shoutcast JSON response: ' . substr( $body, 0, 500 ) );
            }
            
            $data = json_decode( $body, true );
            
            if ( $data && isset( $data['songtitle'] ) ) {
                if ( $debug ) {
                    error_log( '[LRP] Found songtitle in JSON: ' . $data['songtitle'] );
                }
                return $this->normalize_data( $data );
            }
        }
        
        // Fallback to v1 XML format
        $stats_url = $stream_url . '/stats?sid=' . $sid;
        
        if ( $debug ) {
            error_log( '[LRP] Trying Shoutcast XML from: ' . $stats_url );
        }
        
        $response = wp_remote_get( $stats_url, array(
            'timeout' => $timeout,
            'sslverify' => false
        ) );
        
        if ( is_wp_error( $response ) ) {
            if ( $debug ) {
                error_log( '[LRP] Shoutcast error: ' . $response->get_error_message() );
            }
            return $this->get_error_response( $response->get_error_message() );
        }
        
        $body = wp_remote_retrieve_body( $response );
        
        if ( $debug ) {
            error_log( '[LRP] Shoutcast XML response: ' . substr( $body, 0, 500 ) );
        }
        
        // Try to parse XML
        libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $body );
        
        if ( ! $xml ) {
            if ( $debug ) {
                error_log( '[LRP] Failed to parse XML. Raw response: ' . $body );
            }
            return $this->get_error_response( 'Invalid response from Shoutcast server' );
        }
        
        $xml_data = array(
            'songtitle' => (string) $xml->SONGTITLE,
            'currentlisteners' => (int) $xml->CURRENTLISTENERS,
            'streamstatus' => (int) $xml->STREAMSTATUS
        );
        
        if ( $debug ) {
            error_log( '[LRP] Parsed XML data: ' . print_r( $xml_data, true ) );
        }
        
        return $this->normalize_data( $xml_data );
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
     * Normalize Shoutcast data
     *
     * @param array $raw_data Raw Shoutcast data
     * @return array Normalized data
     */
    public function normalize_data( $raw_data ) {
        $title = isset( $raw_data['songtitle'] ) ? sanitize_text_field( $raw_data['songtitle'] ) : '';
        
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
        
        $listeners = isset( $raw_data['currentlisteners'] ) ? absint( $raw_data['currentlisteners'] ) : 0;
        $stream_status = isset( $raw_data['streamstatus'] ) ? absint( $raw_data['streamstatus'] ) : 1;
        
        // If we got a response from the server, it's online even if no track is playing
        $is_online = ( $stream_status === 1 ) ? 'online' : 'online';
        
        // If no track title, set a friendly message
        if ( empty( $artist ) && empty( $track_title ) ) {
            $artist = 'No track playing';
        }
        
        return array(
            'artist' => $artist,
            'title' => $track_title,
            'album' => '', // Shoutcast doesn't typically provide album info
            'listeners' => $listeners,
            'stream_status' => $is_online,
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
