<?php
/**
 * Gutenberg Block
 *
 * @package Live_Radio_Player
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gutenberg block registration
 */
class LRP_Gutenberg_Block {
    
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
        add_action( 'init', array( $this, 'register_block' ) );
    }
    
    /**
     * Register Gutenberg block
     */
    public function register_block() {
        // Register block type
        register_block_type( 'live-radio-player/player', array(
            'editor_script' => 'lrp-block-editor',
            'editor_style' => 'lrp-block-editor-style',
            'render_callback' => array( $this, 'render_block' ),
            'attributes' => array(
                'theme' => array(
                    'type' => 'string',
                    'default' => 'classic'
                ),
                'lyrics' => array(
                    'type' => 'string',
                    'default' => 'off'
                ),
                'layout' => array(
                    'type' => 'string',
                    'default' => 'card'
                ),
                'orientation' => array(
                    'type' => 'string',
                    'default' => 'horizontal'
                )
            )
        ) );
        
        // Register block editor script
        wp_register_script(
            'lrp-block-editor',
            LRP_PLUGIN_URL . 'assets/js/block.js',
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-i18n' ),
            LRP_VERSION,
            true
        );
    }
    
    /**
     * Render block callback
     */
    public function render_block( $attributes ) {
        $shortcode_atts = array(
            'theme' => sanitize_key( $attributes['theme'] ?? 'classic' ),
            'lyrics' => sanitize_key( $attributes['lyrics'] ?? 'off' ),
            'layout' => sanitize_key( $attributes['layout'] ?? 'card' ),
            'orientation' => sanitize_key( $attributes['orientation'] ?? 'horizontal' )
        );
        
        return do_shortcode( sprintf(
            '[live_radio_player theme="%s" lyrics="%s" layout="%s" orientation="%s"]',
            $shortcode_atts['theme'],
            $shortcode_atts['lyrics'],
            $shortcode_atts['layout'],
            $shortcode_atts['orientation']
        ) );
    }
}
