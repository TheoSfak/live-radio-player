/**
 * Live Radio Player - Frontend JavaScript
 * Vanilla JavaScript for audio playback and metadata updates
 */

(function() {
    'use strict';
    
    /**
     * Player class
     */
    class LiveRadioPlayer {
        constructor(element) {
            this.element = element;
            this.audio = element.querySelector('.lrp-audio-element');
            this.playButton = element.querySelector('.lrp-play-button');
            this.volumeSlider = element.querySelector('.lrp-volume-slider');
            this.volumeButton = element.querySelector('.lrp-volume-button');
            this.lyricsToggle = element.querySelector('.lrp-lyrics-toggle');
            this.lyricsContent = element.querySelector('.lrp-lyrics-content');
            
            this.isPlaying = false;
            this.currentTrack = null;
            this.updateInterval = null;
            this.timeUpdateInterval = null;
            this.trackStartTime = null;
            this.trackDuration = 0;
            
            this.init();
        }
        
        init() {
            if (typeof lrpConfig === 'undefined' || !lrpConfig.apiUrl || !this.audio) {
                return;
            }
            
            this.setupAudio();
            this.attachEventListeners();
            this.loadInitialMetadata();
            this.startMetadataUpdates();
        }
        
        setupAudio() {
            // Build stream URL
            const streamUrl = lrpConfig.streamUrl + (lrpConfig.mountPoint || '');
            console.log('[LRP Debug] Stream URL:', streamUrl);
            console.log('[LRP Debug] Setting audio.src to:', streamUrl);
            
            if (!streamUrl || streamUrl === 'undefined') {
                console.error('[LRP Debug] ERROR: Invalid stream URL!');
                return;
            }
            
            this.audio.src = streamUrl;
            console.log('[LRP Debug] Audio.src set successfully');
            
            // Set initial volume
            const volume = this.volumeSlider ? parseInt(this.volumeSlider.value) : 70;
            this.audio.volume = volume / 100;
        }
        
        attachEventListeners() {
            // Play/Pause button
            if (this.playButton) {
                this.playButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.togglePlay();
                });
                // Prevent focus scroll on mobile
                this.playButton.addEventListener('focus', (e) => {
                    e.target.blur();
                });
            }
            
            // Volume controls
            if (this.volumeSlider) {
                this.volumeSlider.addEventListener('input', (e) => {
                    this.audio.volume = e.target.value / 100;
                });
            }
            
            if (this.volumeButton) {
                this.volumeButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleMute();
                });
            }
            
            // Lyrics toggle
            if (this.lyricsToggle) {
                this.lyricsToggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleLyrics();
                });
                // Prevent focus scroll on mobile
                this.lyricsToggle.addEventListener('focus', (e) => {
                    e.target.blur();
                });
            }
            
            // Audio events
            this.audio.addEventListener('playing', () => this.onPlaying());
            this.audio.addEventListener('pause', () => this.onPause());
            this.audio.addEventListener('error', (e) => this.onError(e));
            this.audio.addEventListener('loadstart', () => this.onLoadStart());
            this.audio.addEventListener('canplay', () => this.onCanPlay());
        }
        
        togglePlay() {
            if (this.isPlaying) {
                this.pause();
            } else {
                this.play();
            }
        }
        
        play() {
            console.log('[LRP Debug] Play button clicked on page:', window.location.pathname);
            console.log('[LRP Debug] Audio src before play:', this.audio.src);
            
            const promise = this.audio.play();
            console.log('[LRP Debug] Playback started successfully!');
                    
            if (promise !== undefined) {
                promise.then(() => {
                    this.isPlaying = true;
                }).catch((error) => {
                    console.error('Playback failed:', error);
                    this.showError('Playback failed. Please try again.');
                });
            }
        }
        
        pause() {
            this.audio.pause();
            this.isPlaying = false;
        }
        
        toggleMute() {
            this.audio.muted = !this.audio.muted;
            this.updateVolumeIcon();
        }
        
        onPlaying() {
            this.isPlaying = true;
            this.updatePlayButton();
            this.hideLoader();
        }
        
        onPause() {
            this.isPlaying = false;
            this.updatePlayButton();
        }
        
        onError(e) {
            console.error('Audio error:', e);
            this.showError('Failed to load stream');
            this.hideLoader();
        }
        
        onLoadStart() {
            this.showLoader();
        }
        
        onCanPlay() {
            this.hideLoader();
        }
        
        updatePlayButton() {
            if (!this.playButton) return;
            
            const playIcon = this.playButton.querySelector('.lrp-icon-play');
            const pauseIcon = this.playButton.querySelector('.lrp-icon-pause');
            
            if (this.isPlaying) {
                if (playIcon) playIcon.style.display = 'none';
                if (pauseIcon) pauseIcon.style.display = 'block';
                this.playButton.setAttribute('aria-label', 'Pause');
            } else {
                if (playIcon) playIcon.style.display = 'block';
                if (pauseIcon) pauseIcon.style.display = 'none';
                this.playButton.setAttribute('aria-label', 'Play');
            }
        }
        
        updateVolumeIcon() {
            // Update volume icon based on mute state
            const icon = this.volumeButton.querySelector('.lrp-icon-volume');
            if (icon) {
                icon.style.opacity = this.audio.muted ? '0.5' : '1';
            }
        }
        
        showLoader() {
            const loader = this.playButton.querySelector('.lrp-button-loader');
            if (loader) loader.style.display = 'block';
        }
        
        hideLoader() {
            const loader = this.playButton.querySelector('.lrp-button-loader');
            if (loader) loader.style.display = 'none';
        }
        
        showError(message) {
            // Could show error in UI
            console.error(message);
        }
        
        loadInitialMetadata() {
            this.fetchMetadata();
        }
        
        startMetadataUpdates() {
            // Clear any existing interval
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
            }
            
            // Check if config is valid
            if (!lrpConfig || !lrpConfig.refreshInterval) {
                console.error('[LRP Debug] Cannot start metadata updates: refresh interval not configured');
                return;
            }
            
            // Start periodic updates
            this.updateInterval = setInterval(() => {
                this.fetchMetadata();
            }, lrpConfig.refreshInterval);
        }
        
        fetchMetadata() {
            if (!lrpConfig || !lrpConfig.apiUrl) {
                console.error('[LRP Debug] Cannot fetch metadata: API URL not configured');
                return;
            }
            
            const metadataUrl = lrpConfig.apiUrl + '/metadata';
            console.log('[LRP Debug] Fetching metadata from:', metadataUrl);
            
            // Use XMLHttpRequest as fallback since fetch might be overridden by theme/plugins
            const xhr = new XMLHttpRequest();
            xhr.open('GET', metadataUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            xhr.onload = () => {
                console.log('[LRP Debug] Metadata response status:', xhr.status);
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        console.log('[LRP Debug] Metadata received:', data);
                        if (data.success) {
                            this.updateMetadata(data.data, data.display);
                        } else {
                            console.error('[LRP Debug] Metadata fetch failed:', data);
                        }
                    } catch (error) {
                        console.error('[LRP Debug] Failed to parse metadata:', error);
                    }
                } else {
                    console.error('[LRP Debug] Metadata request failed with status:', xhr.status);
                }
            };
            
            xhr.onerror = () => {
                console.error('[LRP Debug] Network error fetching metadata');
            };
            
            xhr.send();
        }
        
        updateMetadata(metadata, display) {
            console.log('[LRP Debug] Updating metadata:', metadata);
            console.log('[LRP Debug] Display settings:', display);
            
            // Update status
            this.updateStatus(metadata.stream_status);
            
            // Update artist
            if (display.show_artist) {
                const artistEl = this.element.querySelector('.lrp-artist');
                if (artistEl) {
                    artistEl.textContent = metadata.artist || display.fallback_text;
                }
            }
            
            // Update title
            if (display.show_title) {
                const titleEl = this.element.querySelector('.lrp-title');
                if (titleEl) {
                    titleEl.textContent = metadata.title || '';
                }
            }
            
            // Update album
            if (display.show_album) {
                const albumEl = this.element.querySelector('.lrp-album');
                if (albumEl) {
                    albumEl.textContent = metadata.album || '';
                    albumEl.style.display = metadata.album ? 'block' : 'none';
                }
            }
            
            // Update listeners
            if (display.show_listeners) {
                const listenersEl = this.element.querySelector('.lrp-listeners-count');
                if (listenersEl) {
                    listenersEl.textContent = metadata.listeners || 0;
                }
            }
            
            // Update artwork
            if (display.show_artwork) {
                const artworkImg = this.element.querySelector('.lrp-artwork');
                console.log('[LRP Debug] Artwork element:', artworkImg);
                console.log('[LRP Debug] Artwork URL from API:', metadata.artwork_url);
                console.log('[LRP Debug] Fallback image:', display.fallback_image);
                
                if (artworkImg && metadata.artwork_url) {
                    console.log('[LRP Debug] Setting artwork to:', metadata.artwork_url);
                    artworkImg.src = metadata.artwork_url;
                    artworkImg.alt = (metadata.artist || '') + ' - ' + (metadata.title || '');
                } else if (artworkImg && display.fallback_image) {
                    console.log('[LRP Debug] Using fallback image:', display.fallback_image);
                    artworkImg.src = display.fallback_image;
                    artworkImg.alt = 'Album artwork';
                } else {
                    console.log('[LRP Debug] No artwork available');
                }
            }
            
            // Check if track changed
            const newTrack = metadata.artist + ' - ' + metadata.title;
            if (this.currentTrack !== newTrack) {
                this.onTrackChange(metadata);
                this.currentTrack = newTrack;
            }
            
            // Update track time display
            if (display.show_track_time && metadata.duration_ms) {
                this.trackDuration = metadata.duration_ms;
                this.startTrackTimeUpdates();
            }
        }
        
        updateStatus(status) {
            console.log('[LRP Debug] Updating status to:', status);
            const statusIndicator = this.element.querySelector('.lrp-status-indicator');
            const statusText = this.element.querySelector('.lrp-status-text');
            
            if (statusIndicator) {
                statusIndicator.setAttribute('data-status', status);
            }
            
            if (statusText) {
                statusText.textContent = status === 'online' ? 'Online' : 'Offline';
            }
        }
        
        onTrackChange(metadata) {
            console.log('[LRP Debug] Track changed:', metadata);
            console.log('[LRP Debug] forceReload:', lrpConfig.forceReload, 'enableLyrics:', lrpConfig.enableLyrics);
            
            // Reset track start time for new track
            this.trackStartTime = Date.now();
            this.trackDuration = metadata.duration_ms || 0;
            
            // Reload stream if configured
            if (lrpConfig.forceReload && this.isPlaying) {
                console.log('[LRP Debug] Reloading stream due to track change');
                this.audio.load();
                this.play();
            }
            
            // Load lyrics if enabled
            if (lrpConfig.enableLyrics && metadata.artist && metadata.title) {
                console.log('[LRP Debug] Loading lyrics for track change');
                this.loadLyrics(metadata.artist, metadata.title);
            }
        }
        
        /**
         * Start track time updates
         */
        startTrackTimeUpdates() {
            // Clear any existing interval
            if (this.timeUpdateInterval) {
                clearInterval(this.timeUpdateInterval);
            }
            
            // Update immediately
            this.updateTrackTime();
            
            // Update every second
            this.timeUpdateInterval = setInterval(() => {
                this.updateTrackTime();
            }, 1000);
        }
        
        /**
         * Update track time display
         */
        updateTrackTime() {
            const elapsedEl = this.element.querySelector('.lrp-time-elapsed');
            const remainingEl = this.element.querySelector('.lrp-time-remaining');
            
            if (!elapsedEl || !remainingEl || !this.trackStartTime) return;
            
            const now = Date.now();
            const elapsedMs = now - this.trackStartTime;
            
            // Format elapsed time
            elapsedEl.textContent = this.formatTime(elapsedMs);
            
            // Calculate and format remaining time
            if (this.trackDuration > 0) {
                const remainingMs = Math.max(0, this.trackDuration - elapsedMs);
                remainingEl.textContent = '-' + this.formatTime(remainingMs);
                
                // If track should have ended, stop updating (wait for next metadata)
                if (remainingMs <= 0) {
                    remainingEl.textContent = '-0:00';
                }
            } else {
                remainingEl.textContent = '--:--';
            }
        }
        
        /**
         * Format milliseconds to mm:ss
         */
        formatTime(ms) {
            const totalSeconds = Math.floor(ms / 1000);
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            return minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
        }
        
        toggleLyrics() {
            if (!this.lyricsContent) return;
            
            // Save current scroll position
            const scrollX = window.pageXOffset || document.documentElement.scrollLeft;
            const scrollY = window.pageYOffset || document.documentElement.scrollTop;
            
            const isVisible = this.lyricsContent.style.display !== 'none';
            
            if (isVisible) {
                this.lyricsContent.style.display = 'none';
                this.lyricsToggle.textContent = 'Show Lyrics';
            } else {
                this.lyricsContent.style.display = 'block';
                this.lyricsToggle.textContent = 'Hide Lyrics';
            }
            
            // Restore scroll position after DOM update
            requestAnimationFrame(() => {
                window.scrollTo(scrollX, scrollY);
            });
        }
        
        loadLyrics(artist, title) {
            console.log('[LRP Debug] loadLyrics called with artist:', artist, 'title:', title);
            
            if (!this.lyricsContent) {
                console.log('[LRP Debug] No lyrics content element found');
                return;
            }
            
            const lyricsText = this.lyricsContent.querySelector('.lrp-lyrics-text');
            const lyricsLoader = this.lyricsContent.querySelector('.lrp-lyrics-loader');
            
            console.log('[LRP Debug] Lyrics elements:', {lyricsText, lyricsLoader});
            
            if (!lyricsText || !lyricsLoader) {
                console.log('[LRP Debug] Missing lyrics text or loader element');
                return;
            }
            
            // Show loader
            lyricsLoader.style.display = 'block';
            lyricsText.textContent = '';
            lyricsText.className = 'lrp-lyrics-text'; // Reset classes
            
            // Apply custom styling from settings
            if (lrpConfig.lyricsFontSize) {
                lyricsText.style.fontSize = lrpConfig.lyricsFontSize + 'px';
            }
            if (lrpConfig.lyricsColor) {
                lyricsText.style.color = lrpConfig.lyricsColor;
            }
            
            // Fetch lyrics using XMLHttpRequest (theme overrides fetch)
            const url = lrpConfig.apiUrl + '/lyrics?artist=' + encodeURIComponent(artist) + '&title=' + encodeURIComponent(title);
            console.log('[LRP Debug] Fetching lyrics from:', url);
            
            const xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            xhr.onload = () => {
                console.log('[LRP Debug] Lyrics response status:', xhr.status);
                console.log('[LRP Debug] Lyrics response text:', xhr.responseText.substring(0, 500));
                
                lyricsLoader.style.display = 'none';
                
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        console.log('[LRP Debug] Parsed lyrics data:', data);
                        
                        if (data.success && data.data.lyrics) {
                            console.log('[LRP Debug] Lyrics found, length:', data.data.lyrics.length);
                            
                            // Display plain text lyrics
                            lyricsText.textContent = data.data.lyrics;
                        } else {
                            console.log('[LRP Debug] No lyrics in response, showing message:', data.data.message);
                            lyricsText.textContent = data.data.message || 'Lyrics not available';
                        }
                    } catch (error) {
                        console.error('[LRP Debug] Failed to parse lyrics:', error);
                        lyricsText.textContent = 'Failed to load lyrics';
                    }
                } else {
                    console.error('[LRP Debug] Bad status code:', xhr.status);
                    lyricsText.textContent = 'Failed to load lyrics';
                }
            };
            
            xhr.onerror = () => {
                console.error('[LRP Debug] Lyrics network error');
                lyricsLoader.style.display = 'none';
                lyricsText.textContent = 'Network error loading lyrics';
            };
            
            xhr.send();
        }
        
        destroy() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
            }
            
            this.pause();
        }
    }
    
    /**
     * Initialize all players on page load
     */
    function initPlayers() {
        const players = document.querySelectorAll('.lrp-player');
        
        if (players.length === 0) {
            return;
        }
        
        players.forEach((playerElement, index) => {
            new LiveRadioPlayer(playerElement);
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPlayers);
    } else {
        initPlayers();
    }
    
})();
