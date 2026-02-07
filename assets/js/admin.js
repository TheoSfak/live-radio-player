/**
 * Admin Panel JavaScript
 * Tab switching, color pickers, and interactive controls
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize color pickers
        if ($.fn.wpColorPicker) {
            $('.lrp-color-picker').wpColorPicker();
        }
        
        // Stream type toggle
        $('#stream_type').on('change', function() {
            toggleStreamTypeFields($(this).val());
        }).trigger('change');
        
        function toggleStreamTypeFields(type) {
            if (type === 'icecast') {
                $('.lrp-icecast-only').show();
                $('.lrp-shoutcast-only').hide();
            } else {
                $('.lrp-icecast-only').hide();
                $('.lrp-shoutcast-only').show();
            }
        }
        
        // Override preset checkbox
        $('#override_preset').on('change', function() {
            toggleStyleFields($(this).is(':checked'));
        });
        
        function toggleStyleFields(enabled) {
            const styleTab = $('#lrp-visual-style');
            
            if (enabled) {
                styleTab.removeClass('lrp-disabled');
                styleTab.find('input, select, textarea').prop('disabled', false);
            } else {
                styleTab.addClass('lrp-disabled');
                styleTab.find('input, select, textarea').prop('disabled', true);
            }
        }
        
        // Slider value display
        $('input[type="range"]').on('input', function() {
            const value = $(this).val();
            $(this).siblings('.lrp-slider-value').text(value + 'px');
        });
        
        // Media uploader for fallback image
        $('.lrp-upload-image').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const inputField = button.siblings('input[type="text"]');
            
            const mediaUploader = wp.media({
                title: 'Select Fallback Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                inputField.val(attachment.url);
                
                // Update preview
                let preview = button.siblings('.lrp-image-preview');
                if (preview.length === 0) {
                    preview = $('<div class="lrp-image-preview"></div>');
                    button.after(preview);
                }
                preview.html('<img src="' + attachment.url + '" style="max-width: 150px; height: auto; margin-top: 10px;" />');
            });
            
            mediaUploader.open();
        });
        
        // Clear cache button
        $('#lrp-clear-cache').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const messageDiv = $('#lrp-cache-message');
            
            button.prop('disabled', true).text('Clearing...');
            
            $.ajax({
                url: lrpAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'lrp_clear_cache',
                    nonce: lrpAdmin.nonce
                },
                success: function(response) {
                    button.prop('disabled', false).text('Clear All Cache');
                    
                    if (response.success) {
                        messageDiv.html('<div class="notice notice-success inline" style="margin-top: 10px;"><p>' + response.data.message + '</p></div>');
                    } else {
                        messageDiv.html('<div class="notice notice-error inline" style="margin-top: 10px;"><p>' + response.data.message + '</p></div>');
                    }
                    
                    setTimeout(function() {
                        messageDiv.html('');
                    }, 3000);
                },
                error: function() {
                    button.prop('disabled', false).text('Clear All Cache');
                    messageDiv.html('<div class="notice notice-error inline" style="margin-top: 10px;"><p>Error clearing cache</p></div>');
                }
            });
        });
        
        // Theme preset preview click
        $('.lrp-preview-item').on('click', function() {
            const theme = $(this).data('theme');
            $('#theme_preset').val(theme).trigger('change');
            
            $('.lrp-preview-item').removeClass('active');
            $(this).addClass('active');
        });
        
        // Highlight active theme
        const activeTheme = $('#theme_preset').val();
        $('.lrp-preview-item[data-theme="' + activeTheme + '"]').addClass('active');
        
        // Auto-save indicator (optional)
        $('.lrp-admin-form').on('change', 'input, select, textarea', function() {
            // Could add unsaved changes indicator
        });
        
    });
    
})(jQuery);
