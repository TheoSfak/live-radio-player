/**
 * Gutenberg Block - Live Radio Player
 * Classic block without React build process
 */

(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor || wp.editor;
    const { PanelBody, SelectControl } = wp.components;
    const { __ } = wp.i18n;
    const { ServerSideRender } = wp.serverSideRender || wp.components;
    
    registerBlockType('live-radio-player/player', {
        title: __('Live Radio Player', 'live-radio-player'),
        description: __('Add a live radio streaming player', 'live-radio-player'),
        icon: 'controls-volumeon',
        category: 'embed',
        
        attributes: {
            theme: {
                type: 'string',
                default: 'classic'
            },
            lyrics: {
                type: 'string',
                default: 'off'
            },
            layout: {
                type: 'string',
                default: 'card'
            },
            orientation: {
                type: 'string',
                default: 'horizontal'
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            
            return wp.element.createElement(
                'div',
                {},
                wp.element.createElement(
                    InspectorControls,
                    {},
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Player Settings', 'live-radio-player') },
                        wp.element.createElement(SelectControl, {
                            label: __('Theme', 'live-radio-player'),
                            value: attributes.theme,
                            options: [
                                { label: __('Classic Radio', 'live-radio-player'), value: 'classic' },
                                { label: __('Modern Card', 'live-radio-player'), value: 'modern' },
                                { label: __('Dark Night', 'live-radio-player'), value: 'dark' },
                                { label: __('Minimal Mono', 'live-radio-player'), value: 'minimal' }
                            ],
                            onChange: function(value) {
                                setAttributes({ theme: value });
                            }
                        }),
                        wp.element.createElement(SelectControl, {
                            label: __('Layout', 'live-radio-player'),
                            value: attributes.layout,
                            options: [
                                { label: __('Minimal', 'live-radio-player'), value: 'minimal' },
                                { label: __('Card', 'live-radio-player'), value: 'card' },
                                { label: __('Full', 'live-radio-player'), value: 'full' },
                                { label: __('Sidebar', 'live-radio-player'), value: 'sidebar' }
                            ],
                            onChange: function(value) {
                                setAttributes({ layout: value });
                            }
                        }),
                        wp.element.createElement(SelectControl, {
                            label: __('Orientation', 'live-radio-player'),
                            value: attributes.orientation,
                            options: [
                                { label: __('Horizontal', 'live-radio-player'), value: 'horizontal' },
                                { label: __('Vertical', 'live-radio-player'), value: 'vertical' }
                            ],
                            onChange: function(value) {
                                setAttributes({ orientation: value });
                            }
                        }),
                        wp.element.createElement(SelectControl, {
                            label: __('Show Lyrics', 'live-radio-player'),
                            value: attributes.lyrics,
                            options: [
                                { label: __('On', 'live-radio-player'), value: 'on' },
                                { label: __('Off', 'live-radio-player'), value: 'off' }
                            ],
                            onChange: function(value) {
                                setAttributes({ lyrics: value });
                            }
                        })
                    )
                ),
                wp.element.createElement(
                    'div',
                    { className: 'lrp-block-preview' },
                    wp.element.createElement('div', {
                        className: 'lrp-block-placeholder',
                        style: { padding: '20px', border: '2px dashed #ccc', textAlign: 'center' }
                    }, __('Live Radio Player - Preview in frontend', 'live-radio-player'))
                )
            );
        },
        
        save: function() {
            return null; // Server-side rendering
        }
    });
    
})(window.wp);
