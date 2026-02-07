<?php
/**
 * Artwork Service
 *
 * @package Live_Radio_Player
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fetches album artwork from free APIs
 */
class LRP_Artwork_Service {
    
    /**
     * Cache duration in seconds (24 hours)
     */
    const CACHE_DURATION = 86400;
    
    /**
     * Get artwork URL for artist and title
     *
     * @param string $artist Artist name
     * @param string $title Track title
     * @param string $size Size preference (small, medium, large)
     * @return string|false Artwork URL or false if not found
     */
    public static function get_artwork( $artist, $title, $size = 'medium' ) {
        // Validate input
        if ( empty( $artist ) || empty( $title ) ) {
            return false;
        }
        
        // Check cache first
        $cache_key = 'lrp_artwork_' . md5( strtolower( $artist . $title ) );
        $cached = get_transient( $cache_key );
        
        if ( false !== $cached ) {
            return $cached === 'not_found' ? false : $cached;
        }
        
        // Try iTunes API (free, no key required)
        $artwork_url = self::fetch_from_itunes( $artist, $title, $size );
        
        if ( $artwork_url ) {
            set_transient( $cache_key, $artwork_url, self::CACHE_DURATION );
            return $artwork_url;
        }
        
        // Cache negative result to avoid repeated API calls
        set_transient( $cache_key, 'not_found', self::CACHE_DURATION );
        return false;
    }
    
    /**
     * Fetch artwork from iTunes API
     *
     * @param string $artist Artist name
     * @param string $title Track title
     * @param string $size Size preference
     * @return string|false
     */
    private static function fetch_from_itunes( $artist, $title, $size = 'medium' ) {
        // Build search query
        $search_term = sanitize_text_field( $artist . ' ' . $title );
        $api_url = add_query_arg( array(
            'term' => urlencode( $search_term ),
            'media' => 'music',
            'entity' => 'song',
            'limit' => 1
        ), 'https://itunes.apple.com/search' );
        
        // Make request
        $response = wp_remote_get( $api_url, array(
            'timeout' => 5,
            'sslverify' => true
        ) );
        
        if ( is_wp_error( $response ) ) {
            return false;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( ! isset( $data['results'][0]['artworkUrl100'] ) ) {
            return false;
        }
        
        $artwork = $data['results'][0]['artworkUrl100'];
        
        // Adjust size
        switch ( $size ) {
            case 'small':
                $artwork = str_replace( '100x100', '60x60', $artwork );
                break;
            case 'large':
                $artwork = str_replace( '100x100', '600x600', $artwork );
                break;
            case 'xlarge':
                $artwork = str_replace( '100x100', '1000x1000', $artwork );
                break;
            default: // medium
                $artwork = str_replace( '100x100', '300x300', $artwork );
        }
        
        return esc_url_raw( $artwork );
    }
    
    /**
     * Clear artwork cache
     */
    public static function clear_cache() {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lrp_artwork_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lrp_artwork_%'" );
    }
}
