# Live Radio Player

[![WordPress Plugin Version](https://img.shields.io/badge/version-1.3.3-blue.svg)](https://wordpress.org/plugins/live-radio-player/)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892BF.svg)](https://php.net/)
[![WordPress Version](https://img.shields.io/badge/wordpress-%3E%3D5.8-21759B.svg)](https://wordpress.org/)
[![License](https://img.shields.io/badge/license-GPLv2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

A production-ready WordPress plugin for live radio streaming supporting Icecast and Shoutcast, featuring spectacular 3-color gradient animations, lyrics display, and a comprehensive 10-tab admin panel.

## ☕ Support Development

If you find this plugin useful, please consider supporting its development:

[![Donate with PayPal](https://img.shields.io/badge/Donate-PayPal-blue.svg?logo=paypal)](https://www.paypal.com/donate/?business=theodore.sfakianakis@gmail.com&currency_code=EUR)

## ✨ Features

### 🎵 Stream Support
- **Icecast** - Full JSON metadata parsing, mount point support
- **Shoutcast v1** - XML metadata, auto-appends `/;` suffix for playback
- **Shoutcast v2** - JSON with SID parameter, auto-appends `,1` suffix
- **Auto URL Formatting** - Intelligently adds correct suffix based on stream type
- **Connection Testing** - HEAD request validates stream before playback

### 🎨 Spectacular UI
- **Rotating Background** - 360° gradient animation (purple/pink/blue)
- **Glassmorphism** - Frosted glass effect with backdrop blur
- **80px Play Button** - Giant pulse animation with smooth transitions
- **Artwork Hover** - Music note icon overlay with glow effect
- **Volume & Listeners Badges** - Gradient backgrounds with icons
- **4 Theme Presets** - Classic Radio, Modern Card, Dark Night, Minimal Mono
- **Sticky Artwork** - Doesn't move when lyrics expand

### 🎤 Triple Lyrics Provider System
- **LRCLIB.net** (Primary) - High quality lyrics database
- **lyrics.ovh** (Secondary) - International lyrics
- **GreekLyrics.gr** (Tertiary) - Greek lyrics with transliteration/translation
- **Automatic Fallback** - If one API fails, tries next in chain

### 🖼️ Auto Artwork Fetching
- **iTunes Search API** - No authentication required, free to use
- **Automatic Lookup** - Fetches based on artist + title from metadata
- **24hr Cache** - Stored in WordPress Transients
- **Fallback Images** - Admin-configurable default image

### ⏱️ Track Time Display
- **Elapsed Time** - Shows how long the current track has been playing
- **Remaining Time** - Countdown showing time until track ends
- **iTunes Duration** - Fetches accurate track duration from iTunes API
- **Monospace Font** - Clean, readable time display

### 📱 Facebook Integration
- **Beautiful Gradient Button** - Blue gradient with pulse animation
- **Admin Setting** - URL field in Player Layout tab
- **Proper Link** - Opens in new tab

### ⚙️ 10-Tab Admin Panel
1. **Stream Settings** - Server config, metadata fetching, debug mode
2. **Content Control** - Display element toggles (including track time)
3. **Player Layout** - Layout type, orientation, alignment, sticky player
4. **Visual Style Builder** - Colors, typography, spacing
5. **Theme Presets** - 4 predefined themes with override option
6. **Lyrics & Artwork** - Provider selection, cache duration
7. **Performance** - Refresh interval, lazy loading, multi-player support
8. **Custom Code** - CSS/JS injection
9. **Integration** - Shortcode examples, usage instructions
10. **Diagnostics** - Real-time stream status, cache info

## 📦 Installation

1. Upload the `live-radio-player` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to "Live Radio" in the admin menu to configure your stream
4. Add the player to your site using shortcode, block, or template tag

## ⚡ Quick Start

### Basic Usage
```
[live_radio_player]
```

### With Parameters
```
[live_radio_player theme="dark" lyrics="on" layout="card" orientation="horizontal"]
```

### Available Parameters
| Parameter | Values | Description |
|-----------|--------|-------------|
| `theme` | classic, modern, dark, minimal | Visual theme preset |
| `lyrics` | on, off | Enable/disable lyrics |
| `layout` | minimal, card, full, sidebar | Player layout style |
| `orientation` | horizontal, vertical | Player orientation |

## 🔧 Configuration

### Stream Setup

1. Navigate to **Live Radio → Stream Settings**
2. Select your stream type (Icecast or Shoutcast)
3. Enter your stream URL (e.g., `http://stream.example.com:8000/`)
4. For Icecast: Enter mount point (e.g., `/radio.mp3`)
5. For Shoutcast v2: Enter SID (default: 1)
6. Set metadata refresh interval (default: 10 seconds)
7. Save changes

### Theme Customization

1. Go to **Live Radio → Theme Presets**
2. Select a predefined theme
3. Enable "Override Preset Settings" to customize further
4. Use **Visual Style Builder** for detailed customization

## 🔌 Integration Options

- **Shortcode**: `[live_radio_player]`
- **Gutenberg Block**: Search for "Live Radio Player" in block inserter
- **PHP Template Tag**: `<?php echo do_shortcode('[live_radio_player]'); ?>`
- **Widget Compatible**: Use shortcode in any widget area

## 🛠️ Developer Features

### Provider Architecture
```php
// All providers implement this interface
interface LRP_Stream_Provider_Interface {
    public function fetch_metadata( $config );
    public function is_stream_online( $config );
    public function normalize_data( $raw_data );
}
```

### Normalized Metadata Structure
```php
array(
    'artist'        => 'Artist Name',
    'title'         => 'Track Title',
    'album'         => 'Album Name',
    'listeners'     => 123,
    'stream_status' => 'online',
    'artwork_url'   => 'https://...'
)
```

### REST API Endpoints
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/wp-json/live-radio-player/v1/metadata` | GET | Current track + artwork |
| `/wp-json/live-radio-player/v1/status` | GET | Stream online/offline status |
| `/wp-json/live-radio-player/v1/lyrics` | GET | Lyrics for current track |
| `/wp-json/live-radio-player/v1/cache/clear` | POST | Clear caches (admin only) |

### JavaScript Architecture
```javascript
// Vanilla JS, XMLHttpRequest throughout (NOT fetch() - theme compatibility)
class LiveRadioPlayer {
    constructor(element) {
        this.audio = element.querySelector('.lrp-audio-element');
        this.trackStartTime = null;  // For track time sync
    }
}
```

## 📋 Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Active streaming server (Icecast or Shoutcast)

## 🔒 Security & Compliance

- ✅ No obfuscated code
- ✅ No paid APIs required
- ✅ No tracking or external calls (except configured stream and optional lyrics API)
- ✅ GPL v2 licensed
- ✅ Uses WordPress APIs only (Settings API, REST API, Transients)
- ✅ Proper sanitization, escaping, and nonces
- ✅ GDPR compliant

## 🐛 Troubleshooting

### Stream Not Playing
1. Check stream URL is correct and accessible
2. Verify mount point (Icecast) or SID (Shoutcast) is correct
3. Check browser console for errors
4. Enable Debug Mode in Stream Settings

### Metadata Not Updating
1. Verify "Enable Server-side Metadata Fetching" is checked
2. Check stream server is providing metadata
3. Try clearing cache in Performance tab

### Lyrics Not Loading
1. Ensure "Enable Lyrics" is checked in Lyrics & Artwork tab
2. Check that artist and title are being detected correctly
3. Some tracks may not have lyrics available

## 📝 Changelog

### 1.4.2 (2026-02-07)
- Removed karaoke feature (caused auto-scroll issues)
- Simplified lyrics display (plain text only)
- Removed karaoke-related admin settings
- Improved page scroll stability

### 1.4.0 (2026-02-07)
- **NEW**: Track time display (elapsed / remaining)
- Track duration fetched from iTunes API
- Monospace font for time display
- Theme-specific time styling
- Admin setting to enable/disable track time

### 1.3.3 (2026-02-07)
- Facebook button integration
- Sticky artwork positioning
- Bug fixes and performance improvements

### 1.0.0 (2026-02-01)
- Initial release
- Icecast and Shoutcast support
- 10-tab admin panel
- 4 theme presets
- Triple lyrics provider system
- iTunes artwork fetching
- REST API endpoints
- Gutenberg block

## 👨‍💻 Author

**Theodore Sfakianakis**

- 📧 Email: theodore.sfakianakis@gmail.com
- 🐙 GitHub: [GitHub Profile](https://github.com/theodorefakianakis)

## 💖 Support

If this plugin helps your radio station, please consider:

- ⭐ Starring this repository
- 🐛 Reporting issues
- 💡 Suggesting features
- ☕ [Donating via PayPal](https://www.paypal.com/donate/?business=theodore.sfakianakis@gmail.com&currency_code=EUR)

## 📄 License

This plugin is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

## 🙏 Credits

- Lyrics powered by [LRCLIB.net](https://lrclib.net/), [lyrics.ovh](https://lyrics.ovh/), and [GreekLyrics.gr](https://greeklyrics.gr/)
- Artwork powered by [iTunes Search API](https://developer.apple.com/library/archive/documentation/AudioVideo/Conceptual/iTuneSearchAPI/)
- Built with WordPress APIs
- Icons from WordPress Dashicons

---

**Live Radio Player** - Professional streaming for WordPress 🎵
