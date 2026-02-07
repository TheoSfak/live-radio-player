# Live Radio Player - AI Agent Instructions

## Current Build Status (v1.3.3 - Milestone v1.0.0)

**Last Updated**: February 1, 2026  
**Status**: ✅ Production-ready, fully tested and working  
**Backup**: `c:\Users\theo\Desktop\liveonstage_player_backup_v1.0.0\`

### Implemented Features (Complete)

#### 1. Stream Support (All Types Working)
- **Icecast**: JSON metadata parsing, mount point support
- **Shoutcast v1**: XML metadata, auto-appends `/;` suffix for playback
- **Shoutcast v2**: JSON with SID parameter, auto-appends `,1` suffix
- **Auto URL Formatting**: Intelligently adds correct suffix based on stream type
- **Connection Testing**: HEAD request validates stream before playback
- **Fallback Metadata**: Shows graceful "Loading..." when stream offline

#### 2. Spectacular UI (3-Color Gradients + Animations)
- **Rotating Background**: 360° gradient animation (purple/pink/blue)
- **Glassmorphism**: Frosted glass effect with backdrop blur
- **80px Play Button**: Giant pulse animation, smooth transitions
- **Artwork Hover**: Music note icon overlay with glow effect
- **Volume & Listeners Badges**: Gradient backgrounds with icons
- **4 Theme Presets**: Classic Radio, Modern Card, Dark Night, Minimal Mono
- **Sticky Artwork**: Uses `position: sticky; top: 20px;` - doesn't move when lyrics expand

#### 3. Facebook Integration (Beautiful Gradient Button)
- **Admin Setting**: URL field in Player Layout tab (`facebook_url`)
- **Visual Design**: Blue gradient (#1877f2), pulse animation, shine effect on hover
- **Positioning**: Above artwork, 50px margin-bottom for spacing
- **Proper Link**: `<a href>` with `target="_blank"` for new tab navigation
- **SVG Icon**: Facebook logo with animation

#### 4. Auto Artwork Fetching (iTunes API - Free)
- **iTunes Search API**: No authentication required, free to use
- **Automatic Lookup**: Fetches based on artist + title from metadata
- **24hr Cache**: Stored in WordPress Transients (`lrp_artwork_...`)
- **Fallback Images**: Admin-configurable default image
- **API Endpoint**: `/wp-json/live-radio-player/v1/metadata` includes artwork_url

#### 5. Triple Lyrics Provider System (With Karaoke)
- **LRCLIB.net** (Primary): Synced LRC lyrics with timestamps for karaoke mode
- **lyrics.ovh** (Secondary): International lyrics, plain text fallback
- **GreekLyrics.gr** (Tertiary): Greek lyrics with transliteration/translation
- **LRC Parsing**: Custom parser handles `[mm:ss.xx]` timestamps
- **Karaoke Highlighting**: Real-time line highlighting using track start timestamp
- **Live Radio Workaround**: Uses `Date.now() - trackStartTime` instead of `audio.currentTime`
- **Automatic Fallback**: If one API fails, tries next in chain

#### 6. Karaoke-Style Synced Lyrics
- **Track Timestamp**: Records `Date.now()` when track changes
- **Real-time Sync**: Calculates elapsed time without relying on audio.currentTime
- **Active Line Highlighting**: CSS class `.lrp-lyrics-line-active` with color change
- **Admin Toggle**: Enable/disable karaoke mode in Lyrics & Artwork tab
- **Performance**: Uses `setInterval` (100ms) for smooth highlighting

#### 7. 10-Tab Admin Panel (WordPress Settings API)
- **Stream Settings**: Server config, metadata fetching, debug mode
- **Content Control**: Display element checkboxes (artist, title, album, artwork, listeners, status, lyrics)
- **Player Layout**: Layout type, orientation, alignment, sticky player, Facebook URL
- **Visual Style Builder**: Colors, typography, spacing (CSS custom properties)
- **Theme Presets**: 4 predefined themes with override option
- **Lyrics & Artwork**: Provider selection, karaoke enable, cache duration
- **Performance**: Refresh interval (10s default), lazy loading, multi-player support
- **Custom Code**: CSS/JS injection, disable plugin styles
- **Integration**: Shortcode examples, usage instructions
- **Diagnostics**: Real-time stream status, cache info, system details

#### 8. Advanced Settings Save System
- **Tab Tracking**: Hidden field `_current_tab` identifies which tab was submitted
- **Selective Updates**: Only checkboxes from current tab are modified
- **Merge Strategy**: `get_option()` + merge prevents data loss from other tabs
- **Checkbox Arrays**: Grouped by tab (`$tab_checkboxes['content_control']`)
- **Why Critical**: Unchecked boxes on other tabs won't be accidentally cleared

### Recent Bug Fixes (All Resolved)

1. ✅ **Settings Not Saving**: Fixed `sanitize_settings()` to merge with existing options instead of replacing
2. ✅ **Checkbox Persistence**: Implemented tab-specific update logic with hidden `_current_tab` field
3. ✅ **Offline Status Bug**: Added fallback metadata and stream connection checking
4. ✅ **Cache Hell**: Multiple version bumps (1.0.0 → 1.3.3) for browser cache invalidation
5. ✅ **fetch() Hijacking**: Switched entire codebase to `XMLHttpRequest` (theme conflicts)
6. ✅ **Shoutcast Playback**: Auto-append `/;` or `,1` based on stream type
7. ✅ **Artwork Missing**: Integrated iTunes API for automatic fetching
8. ✅ **Artist/Title Parsing**: Enhanced regex to support both " - " and "-" formats
9. ✅ **Karaoke on Live Radio**: Used track start timestamp instead of audio.currentTime
10. ✅ **JavaScript Syntax Errors**: Cleaned up corrupted console.log statements (v1.3.1-1.3.3)
11. ✅ **Duplicate Code**: Removed duplicate initialization logic
12. ✅ **Missing Variables**: Added `const players = document.querySelectorAll('.lrp-player')`
13. ✅ **Sticky Artwork**: Added `position: sticky` so artwork/Facebook don't move with lyrics
14. ✅ **Facebook Spacing**: Increased margin-bottom to 50px

### Technical Architecture Details

#### Provider Pattern (Stream Abstraction)
```php
// All providers implement this interface
interface LRP_Stream_Provider_Interface {
    public function fetch_metadata( $config );
    public function is_stream_online( $config );
    public function normalize_data( $raw_data );
}

// Icecast: Parses JSON from /status-json.xsl
// Shoutcast v1: Parses XML from /7.html
// Shoutcast v2: Parses JSON from /stats?sid=X&json=1
```

#### Singleton Services
```php
// All services use singleton pattern
$stream_manager = LRP_Stream_Manager::get_instance();
$lyrics_service = LRP_Lyrics_Service::get_instance();
$artwork_service = LRP_Artwork_Service::get_instance();
```

#### REST API Endpoints
- `GET /wp-json/live-radio-player/v1/metadata` - Current track + artwork
- `GET /wp-json/live-radio-player/v1/status` - Stream online/offline status
- `GET /wp-json/live-radio-player/v1/lyrics` - Lyrics for current track (params: artist, title)
- `POST /wp-json/live-radio-player/v1/cache/clear` - Admin only, clears all caches

#### JavaScript Architecture (Vanilla JS, No Dependencies)
```javascript
class LiveRadioPlayer {
    constructor(element) {
        this.audio = element.querySelector('.lrp-audio-element');
        this.trackStartTime = null;  // For karaoke sync
        this.lyricsData = null;      // Parsed LRC data
        this.updateInterval = null;  // Metadata polling
        this.lyricsSyncInterval = null;  // Karaoke highlighting
    }
    
    // XMLHttpRequest throughout (NOT fetch())
    fetchMetadata() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', metadataUrl, true);
        xhr.send();
    }
}
```

#### WordPress Integration Points
- **Shortcode**: `[live_radio_player]` with params (theme, lyrics, layout, orientation)
- **Gutenberg Block**: `LRP_Gutenberg_Block` registers "Live Radio Player" block
- **Settings API**: Single option `lrp_settings` stores all config
- **Transients API**: `lrp_metadata_{stream_hash}`, `lrp_lyrics_{artist}_{title}`, `lrp_artwork_{artist}_{title}`
- **Localization**: `wp_localize_script()` passes PHP config to JavaScript

### Testing & Validation

#### ✅ Tested & Working
- Stream playback (Icecast, Shoutcast v1, Shoutcast v2)
- Real-time metadata updates (10-second polling)
- Artwork auto-fetching from iTunes
- Lyrics fetching from all 3 providers
- Karaoke highlighting with synced timestamps
- Admin settings save/load (all tabs)
- Checkbox persistence across tabs
- Facebook button link navigation
- Volume control and mute
- Listener count display
- Stream status indicator
- Sticky artwork positioning
- Lyrics expand/collapse without moving artwork
- Multiple layouts (minimal, card, full, sidebar)
- Theme presets with custom overrides
- Mobile responsive design
- Browser cache invalidation (version bumping)

#### ⚠️ Known Limitations (Not Bugs)
- **Mixed Content**: HTTPS sites require HTTPS streams (browser security)
- **Live Radio Karaoke**: Slight drift possible (uses system clock, not audio position)
- **Lyrics Availability**: Not all tracks have lyrics in APIs
- **Artwork Quality**: Depends on iTunes database coverage
- **Theme fetch()**: Some themes hijack fetch() - we use XMLHttpRequest everywhere

### File Structure & Key Files

```
live-radio-player.php           # v1.3.3 bootstrap
assets/
  css/player.css                # Spectacular styles (3-color gradients, sticky artwork)
  js/player.js                  # Vanilla JS (XMLHttpRequest, karaoke engine)
includes/
  class-stream-manager.php      # Provider orchestration, 10s cache default
  class-lyrics-service.php      # Triple API (LRCLIB → lyrics.ovh → GreekLyrics)
  class-artwork-service.php     # iTunes API, 24hr cache
  class-rest-api.php            # REST endpoints
  admin/class-admin.php         # 10-tab admin, tab-specific checkbox save logic
  public/class-public.php       # Shortcode, Shoutcast URL auto-formatting
  providers/
    class-icecast-provider.php  # JSON parsing
    class-shoutcast-provider.php # XML (v1) + JSON (v2) parsing
```

### Next Agent Checklist

When making changes, remember:
1. **Version bump** both `LRP_VERSION` constant and plugin header when releasing
2. **Tab checkboxes** must be added to both render function and `$tab_checkboxes` array
3. **XMLHttpRequest only** - never use fetch() (theme compatibility)
4. **Escape everything** - `esc_url()`, `esc_attr()`, `esc_html()` before output
5. **Merge settings** - always `get_option()` first, then merge, don't replace
6. **Test all stream types** - Icecast, Shoutcast v1, Shoutcast v2
7. **Cache invalidation** - version bump forces browser reload of JS/CSS
8. **Backup exists** - v1.0.0 milestone at `c:\Users\theo\Desktop\liveonstage_player_backup_v1.0.0\`

## Architecture Overview

This is a WordPress plugin for live radio streaming (Icecast/Shoutcast). Key architectural pattern: **Provider-based stream abstraction** + **Singleton services** + **WordPress standards**.

### Core Components
- **live-radio-player.php** - Bootstrap (v1.3.3), autoloader, singleton pattern
- **includes/class-stream-manager.php** - Central service orchestrating providers, caching (WordPress Transients)
- **includes/providers/** - Stream-specific logic (`LRP_Stream_Provider_Interface`)
  - `class-icecast-provider.php` - JSON metadata parsing
  - `class-shoutcast-provider.php` - XML (v1) and JSON with SID (v2) parsing
- **includes/class-lyrics-service.php** - Triple API integration: LRCLIB (synced/karaoke), lyrics.ovh (international), GreekLyrics.gr (Greek with transliteration)
- **includes/class-artwork-service.php** - iTunes API artwork fetching (free, no keys)
- **includes/class-rest-api.php** - REST endpoints (`/wp-json/live-radio-player/v1/metadata`, `/status`, `/lyrics`)
- **includes/admin/class-admin.php** - 10-tab admin interface using Settings API
- **includes/public/class-public.php** - Shortcode renderer, asset enqueueing
- **assets/js/player.js** - Vanilla JS, **XMLHttpRequest only** (fetch() hijacked by themes)

## Critical Patterns

### 1. Provider Normalization
All stream providers return standardized structure:
```php
array(
    'artist' => 'Artist Name',
    'title' => 'Track Title', 
    'album' => 'Album Name',
    'listeners' => 123,
    'stream_status' => 'online',
    'artwork_url' => '...'  // Added by artwork service
)
```

### 2. Checkbox Settings Save Logic ⚠️
**CRITICAL**: Admin settings use tab-specific checkbox updates. Each tab must explicitly update only its checkboxes:
```php
// includes/admin/class-admin.php, sanitize_settings()
$current_tab = $input['_current_tab'];  // Hidden field tracks active tab
if ( isset( $tab_checkboxes[ $current_tab ] ) ) {
    foreach ( $tab_checkboxes[ $current_tab ] as $field ) {
        $sanitized[ $field ] = isset( $input[ $field ] ) && $input[ $field ] === '1';
    }
}
```
**Why**: Prevents unchecked boxes from other tabs being reset when saving a different tab.

### 3. Karaoke Mode for Live Radio
Cannot use `audio.currentTime` (always 0 on live streams). Uses track start timestamp:
```javascript
// assets/js/player.js
this.trackStartTime = Date.now();  // Set on track change
const elapsed = Date.now() - this.trackStartTime;  // Calculate sync time
```

### 4. Cache Busting Strategy
Version bumping (1.0.0 → 1.3.3) forces browser cache invalidation:
```php
// live-radio-player.php
define( 'LRP_VERSION', '1.3.3' );
// Assets enqueued with LRP_VERSION parameter
```

### 5. Shoutcast URL Auto-formatting
```php
// includes/public/class-public.php
if ( $stream_type === 'shoutcast_v2' ) {
    $stream_url .= ',' . $sid;  // Append ,1 for v2
} else {
    $stream_url .= '/;';  // Append /; for v1
}
```

## Development Workflows

### Adding New Display Elements
1. Add checkbox to relevant tab in `includes/admin/class-admin.php` (e.g., `render_tab_content_control()`)
2. Add field to `$tab_checkboxes` array in `sanitize_settings()`
3. Add conditional HTML in `includes/public/class-public.php` shortcode
4. Add CSS class in `assets/css/player.css`
5. Update JS if dynamic content: `assets/js/player.js` `updateMetadata()`

### Version Bump Checklist
1. Update `LRP_VERSION` in `live-radio-player.php` (line 21)
2. Update plugin header version (line 6)
3. Commit changes to force cache invalidation

### Testing Stream Types
- **Icecast**: Mount point required (e.g., `/radio.mp3`), JSON metadata
- **Shoutcast v1**: No SID, XML metadata, needs `/;` suffix
- **Shoutcast v2**: SID parameter (default 1), JSON metadata, needs `,1` suffix

## WordPress-Specific Conventions

- **Settings**: All stored in single option `lrp_settings` (merge with `get_option()` to prevent overwrites)
- **Caching**: Use `set_transient('lrp_metadata_...')` with configurable TTL (default 10 seconds)
- **Nonces**: `wp_create_nonce('lrp_admin_nonce')` for AJAX, check with `check_ajax_referer()`
- **Escaping**: Always `esc_url()`, `esc_attr()`, `esc_html()` - never output raw user input
- **Localization**: `wp_localize_script('lrp-player-js', 'lrpConfig', ...)` passes PHP data to JS

## Known Issues & Workarounds

1. **Mixed Content (HTTPS/HTTP)**: Document limitation - users must use HTTPS stream or browser settings
2. **Theme fetch() hijacking**: Always use `XMLHttpRequest` instead of `fetch()` throughout codebase
3. **Sticky artwork position**: Uses `position: sticky; top: 20px;` so artwork doesn't move when lyrics expand
4. **Facebook button click**: Must be proper `<a href>` with `target="_blank"` - button elements don't navigate

## File Editing Protocols

- **player.js**: Never add debug logging in production - causes syntax errors when cleaned up
- **class-admin.php**: When adding settings fields, update both render function AND `sanitize_settings()`
- **player.css**: All custom properties use `--lrp-*` prefix for theme consistency
- **Version control**: Backup folder exists at `c:\Users\theo\Desktop\liveonstage_player_backup_v1.0.0\` - restore via `Copy-Item` if needed

## Integration Points

- **Lyrics APIs**: LRCLIB.net → lyrics.ovh → GreekLyrics.gr (fallback chain)
- **Artwork API**: iTunes Search API (free, no auth)
- **Stream metadata**: Server-side fetch via cURL with configurable timeout (default 5s)
