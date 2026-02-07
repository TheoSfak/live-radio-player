<?php
/**
 * Lyrics Service
 *
 * @package Live_Radio_Player
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles lyrics fetching from free public APIs
 */
class LRP_Lyrics_Service {
    
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
        // Initialization handled on demand
    }
    
    /**
     * Get lyrics for artist and title
     *
     * @param string $artist Artist name
     * @param string $title Track title
     * @return array {lyrics, source, cached}
     */
    public function get_lyrics( $artist, $title ) {
        if ( empty( $artist ) || empty( $title ) ) {
            return $this->get_empty_lyrics();
        }
        
        $settings = get_option( 'lrp_settings', array() );
        
        if ( ! isset( $settings['enable_lyrics'] ) || ! $settings['enable_lyrics'] ) {
            return $this->get_empty_lyrics();
        }
        
        // Check cache first
        $cache_key = 'lrp_lyrics_' . md5( strtolower( $artist . $title ) );
        $cached = get_transient( $cache_key );
        
        if ( false !== $cached ) {
            $cached['cached'] = true;
            return $cached;
        }
        
        // Fetch from API
        $lyrics_data = $this->fetch_from_api( $artist, $title );
        
        // Cache the result
        $cache_duration = isset( $settings['lyrics_cache_duration'] ) ? absint( $settings['lyrics_cache_duration'] ) : 1440;
        set_transient( $cache_key, $lyrics_data, $cache_duration * MINUTE_IN_SECONDS );
        
        $lyrics_data['cached'] = false;
        return $lyrics_data;
    }
    
    /**
     * Fetch lyrics from free public API with multiple providers and fallback
     *
     * @param string $artist Artist name
     * @param string $title Track title
     * @return array
     */
    private function fetch_from_api( $artist, $title ) {
        $settings = get_option( 'lrp_settings', array() );
        $debug = isset( $settings['debug_mode'] ) && $settings['debug_mode'];
        
        // Try multiple free APIs in order of preference
        $providers = array(
            'lrclib' => array( $this, 'fetch_from_lrclib' ),
            'lyrics.ovh' => array( $this, 'fetch_from_lyrics_ovh' ),
            'greeklyrics.gr' => array( $this, 'fetch_from_greeklyrics' ),
        );
        
        foreach ( $providers as $provider_name => $callback ) {
            if ( $debug ) {
                error_log( '[LRP] Trying lyrics provider: ' . $provider_name );
            }
            
            $result = call_user_func( $callback, $artist, $title, $debug );
            
            if ( ! empty( $result['lyrics'] ) ) {
                if ( $debug ) {
                    error_log( '[LRP] Successfully fetched lyrics from ' . $provider_name . ', length: ' . strlen( $result['lyrics'] ) );
                }
                return $result;
            }
        }
        
        if ( $debug ) {
            error_log( '[LRP] No lyrics found from any provider' );
        }
        
        return $this->get_empty_lyrics();
    }
    
    /**
     * Fetch lyrics from LRCLIB.net API
     * Free, no API key, excellent coverage
     * Supports both plain and synced (LRC) lyrics for karaoke-style display
     *
     * @param string $artist Artist name
     * @param string $title Track title
     * @param bool $debug Debug mode
     * @return array
     */
    private function fetch_from_lrclib( $artist, $title, $debug = false ) {
        $api_url = add_query_arg(
            array(
                'artist_name' => sanitize_text_field( $artist ),
                'track_name' => sanitize_text_field( $title )
            ),
            'https://lrclib.net/api/get'
        );
        
        if ( $debug ) {
            error_log( '[LRP] LRCLIB API URL: ' . $api_url );
        }
        
        $response = wp_remote_get( $api_url, array(
            'timeout' => 10,
            'sslverify' => true,
            'headers' => array(
                'User-Agent' => 'Live Radio Player WordPress Plugin'
            )
        ) );
        
        if ( is_wp_error( $response ) ) {
            if ( $debug ) {
                error_log( '[LRP] LRCLIB API error: ' . $response->get_error_message() );
            }
            return $this->get_empty_lyrics();
        }
        
        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        
        if ( $debug ) {
            error_log( '[LRP] LRCLIB API response status: ' . $status_code );
            error_log( '[LRP] LRCLIB API response: ' . substr( $body, 0, 200 ) );
        }
        
        if ( 200 !== $status_code ) {
            return $this->get_empty_lyrics();
        }
        
        $data = json_decode( $body, true );
        
        // LRCLIB returns both plain and synced lyrics
        // Synced lyrics (LRC format) enable karaoke-style display
        if ( ! empty( $data['plainLyrics'] ) ) {
            $result = array(
                'lyrics' => sanitize_textarea_field( $data['plainLyrics'] ),
                'source' => 'lrclib.net',
                'artist' => sanitize_text_field( $artist ),
                'title' => sanitize_text_field( $title )
            );
            
            // Add synced lyrics if available for karaoke mode
            if ( ! empty( $data['syncedLyrics'] ) ) {
                $result['synced_lyrics'] = $data['syncedLyrics'];
                $result['is_synced'] = true;
            }
            
            return $result;
        }
        
        return $this->get_empty_lyrics();
    }
    
    /**
     * Fetch lyrics from lyrics.ovh API
     * Free, no API key required
     *
     * @param string $artist Artist name
     * @param string $title Track title
     * @param bool $debug Debug mode
     * @return array
     */
    private function fetch_from_lyrics_ovh( $artist, $title, $debug = false ) {
        $api_url = sprintf(
            'https://api.lyrics.ovh/v1/%s/%s',
            urlencode( sanitize_text_field( $artist ) ),
            urlencode( sanitize_text_field( $title ) )
        );
        
        if ( $debug ) {
            error_log( '[LRP] Lyrics.ovh API URL: ' . $api_url );
        }
        
        $response = wp_remote_get( $api_url, array(
            'timeout' => 10,
            'sslverify' => true
        ) );
        
        if ( is_wp_error( $response ) ) {
            if ( $debug ) {
                error_log( '[LRP] Lyrics.ovh API error: ' . $response->get_error_message() );
            }
            return $this->get_empty_lyrics();
        }
        
        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        
        if ( $debug ) {
            error_log( '[LRP] Lyrics.ovh API response status: ' . $status_code );
            error_log( '[LRP] Lyrics.ovh API response: ' . substr( $body, 0, 200 ) );
        }
        
        if ( 200 !== $status_code ) {
            return $this->get_empty_lyrics();
        }
        
        $data = json_decode( $body, true );
        
        if ( ! empty( $data['lyrics'] ) ) {
            return array(
                'lyrics' => sanitize_textarea_field( $data['lyrics'] ),
                'source' => 'lyrics.ovh',
                'artist' => sanitize_text_field( $artist ),
                'title' => sanitize_text_field( $title )
            );
        }
        
        return $this->get_empty_lyrics();
    }
    
    /**
     * Fetch lyrics from GreekLyrics.gr
     * Best source for Greek lyrics
     *
     * @param string $artist Artist name
     * @param string $title Track title
     * @param bool $debug Debug mode
     * @return array
     */
    private function fetch_from_greeklyrics( $artist, $title, $debug = false ) {
        // Clean artist and title for URL
        $artist_slug = $this->greeklyrics_slug( $artist );
        $title_slug = $this->greeklyrics_slug( $title );
        
        // GreekLyrics.gr URL pattern: https://www.greeklyrics.gr/artist-title
        $url = sprintf(
            'https://www.greeklyrics.gr/%s-%s',
            $artist_slug,
            $title_slug
        );
        
        if ( $debug ) {
            error_log( '[LRP] GreekLyrics URL: ' . $url );
        }
        
        $response = wp_remote_get( $url, array(
            'timeout' => 15,
            'sslverify' => true,
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            )
        ) );
        
        if ( is_wp_error( $response ) ) {
            if ( $debug ) {
                error_log( '[LRP] GreekLyrics error: ' . $response->get_error_message() );
            }
            return $this->get_empty_lyrics();
        }
        
        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        
        if ( $debug ) {
            error_log( '[LRP] GreekLyrics response status: ' . $status_code );
        }
        
        if ( 200 !== $status_code ) {
            return $this->get_empty_lyrics();
        }
        
        // Parse HTML to extract lyrics
        $lyrics = $this->parse_greeklyrics_html( $body, $debug );
        
        if ( ! empty( $lyrics ) ) {
            return array(
                'lyrics' => sanitize_textarea_field( $lyrics ),
                'source' => 'greeklyrics.gr',
                'artist' => sanitize_text_field( $artist ),
                'title' => sanitize_text_field( $title )
            );
        }
        
        return $this->get_empty_lyrics();
    }
    
    /**
     * Convert artist/title to GreekLyrics.gr URL slug
     * Converts Greek and Latin characters to URL-friendly format
     *
     * @param string $text
     * @return string
     */
    private function greeklyrics_slug( $text ) {
        // Remove special characters and extra spaces
        $text = trim( $text );
        $text = preg_replace( '/[^\p{L}\p{N}\s-]/u', '', $text );
        $text = preg_replace( '/\s+/', '-', $text );
        $text = strtolower( $text );
        
        // Greek to Latin transliteration for URL
        $greek_to_latin = array(
            'α' => 'a', 'ά' => 'a', 'β' => 'v', 'γ' => 'g', 'δ' => 'd',
            'ε' => 'e', 'έ' => 'e', 'ζ' => 'z', 'η' => 'i', 'ή' => 'i',
            'θ' => 'th', 'ι' => 'i', 'ί' => 'i', 'ϊ' => 'i', 'ΐ' => 'i',
            'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => 'ks',
            'ο' => 'o', 'ό' => 'o', 'π' => 'p', 'ρ' => 'r', 'σ' => 's',
            'ς' => 's', 'τ' => 't', 'υ' => 'y', 'ύ' => 'y', 'ϋ' => 'y',
            'ΰ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'o',
            'ώ' => 'o'
        );
        
        $text = strtr( $text, $greek_to_latin );
        
        return $text;
    }
    
    /**
     * Parse GreekLyrics.gr HTML to extract lyrics
     *
     * @param string $html
     * @param bool $debug
     * @return string
     */
    private function parse_greeklyrics_html( $html, $debug = false ) {
        // Common patterns for lyrics containers on GreekLyrics.gr
        $patterns = array(
            '/<div[^>]*class="[^"]*lyrics[^"]*"[^>]*>(.*?)<\/div>/is',
            '/<div[^>]*id="[^"]*lyrics[^"]*"[^>]*>(.*?)<\/div>/is',
            '/<pre[^>]*>(.*?)<\/pre>/is',
        );
        
        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $html, $matches ) ) {
                $lyrics = $matches[1];
                
                // Clean HTML tags
                $lyrics = strip_tags( $lyrics, '<br>' );
                $lyrics = str_replace( array( '<br>', '<br/>', '<br />' ), "\n", $lyrics );
                $lyrics = html_entity_decode( $lyrics, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                $lyrics = trim( $lyrics );
                
                if ( ! empty( $lyrics ) && strlen( $lyrics ) > 50 ) {
                    if ( $debug ) {
                        error_log( '[LRP] GreekLyrics parsed successfully, length: ' . strlen( $lyrics ) );
                    }
                    return $lyrics;
                }
            }
        }
        
        if ( $debug ) {
            error_log( '[LRP] Failed to parse GreekLyrics HTML' );
        }
        
        return '';
    }
    
    /**
     * Get empty lyrics structure
     *
     * @return array
     */
    private function get_empty_lyrics() {
        $settings = get_option( 'lrp_settings', array() );
        $message = isset( $settings['custom_lyrics_message'] ) ? $settings['custom_lyrics_message'] : 'Lyrics not available';
        
        return array(
            'lyrics' => '',
            'source' => '',
            'message' => sanitize_text_field( $message )
        );
    }
    
    /**
     * Clear lyrics cache
     */
    public function clear_cache() {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lrp_lyrics_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lrp_lyrics_%'" );
    }
}
