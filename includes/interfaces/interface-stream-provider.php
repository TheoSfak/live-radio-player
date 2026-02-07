<?php
/**
 * Stream Provider Interface
 *
 * @package Live_Radio_Player
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Interface for stream providers
 */
interface LRP_Stream_Provider_Interface {
    
    /**
     * Fetch metadata from stream
     *
     * @param array $config Stream configuration
     * @return array Normalized metadata array
     */
    public function fetch_metadata( $config );
    
    /**
     * Get stream status
     *
     * @param array $config Stream configuration
     * @return bool True if stream is online
     */
    public function is_stream_online( $config );
    
    /**
     * Parse raw stream data into normalized format
     *
     * @param mixed $raw_data Raw data from stream
     * @return array Normalized data: {artist, title, album, listeners, stream_status}
     */
    public function normalize_data( $raw_data );
}
