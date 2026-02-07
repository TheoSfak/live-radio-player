=== Live Radio Player ===
Contributors: theodorefakianakis
Donate link: https://www.paypal.com/donate/?business=theodore.sfakianakis@gmail.com&currency_code=EUR
Tags: radio, streaming, icecast, shoutcast, player, audio, live stream, karaoke, lyrics
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.4.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Production-ready live radio streaming plugin with spectacular UI, karaoke-style synced lyrics, track time display, and comprehensive admin panel supporting Icecast and Shoutcast.

== Description ==

Live Radio Player is a professional WordPress plugin for adding live radio streaming to your website. It supports both Icecast and Shoutcast streaming servers and provides a spectacular player with 3-color gradient animations, karaoke-style synced lyrics, and an extensive 10-tab admin panel.

= ✨ Key Features =

* **Multiple Stream Support**: Icecast, Shoutcast v1, and Shoutcast v2
* **Spectacular UI**: Rotating 3-color gradients, glassmorphism, pulse animations
* **Karaoke Mode**: Real-time synced lyrics with line highlighting
* **Triple Lyrics System**: LRCLIB.net → lyrics.ovh → GreekLyrics.gr fallback chain
* **Auto Artwork**: iTunes API integration (free, no keys required)
* **Facebook Button**: Beautiful gradient button with admin URL setting
* **4 Theme Presets**: Classic Radio, Modern Card, Dark Night, Minimal Mono
* **10-Tab Admin Panel**: Complete customization without coding
* **Visual Style Builder**: Colors, typography, spacing without CSS
* **Multiple Layouts**: Minimal, Card, Full, Sidebar
* **Gutenberg Block**: Native block editor support
* **REST API**: Built-in endpoints for metadata, status, and lyrics
* **Mobile Responsive**: Works perfectly on all devices
* **Performance Optimized**: Server-side caching with WordPress Transients
* **Secure**: Follows WordPress security best practices

= 🎵 Stream Support =

**Icecast**
* Full JSON stats support from /status-json.xsl
* Mount point configuration
* Automatic metadata parsing

**Shoutcast v1**
* XML stats support
* Auto-appends /; suffix for playback
* Backward compatible

**Shoutcast v2**
* JSON stats support with SID parameter
* Auto-appends ,1 suffix for playback
* Enhanced metadata

= 🎤 Lyrics Providers =

The plugin includes a triple lyrics provider system with automatic fallback:

1. **LRCLIB.net** (Primary) - Synced LRC lyrics with timestamps for karaoke mode
2. **lyrics.ovh** (Secondary) - International lyrics database
3. **GreekLyrics.gr** (Tertiary) - Greek lyrics with transliteration

= ⚙️ Admin Panel Tabs =

**Tab 1 - Stream Settings**
Configure your streaming server connection, stream type, format, and metadata refresh intervals.

**Tab 2 - Content Control**
Toggle display elements including artist, title, album, artwork, listeners, status, and lyrics.

**Tab 3 - Player Layout**
Choose layout type, orientation, alignment, sticky player, width settings, and Facebook URL.

**Tab 4 - Visual Style Builder**
Customize colors, typography, borders, shadows, padding, and spacing without writing CSS.

**Tab 5 - Theme Presets**
Select from 4 predefined themes or override with custom settings.

**Tab 6 - Lyrics & Artwork**
Configure lyrics providers, karaoke mode, artwork sources, and cache duration.

**Tab 7 - Performance**
Manage refresh interval (10s default), lazy loading, cache settings, and multi-player support.

**Tab 8 - Custom Code**
Add custom CSS, JavaScript, and advanced customizations.

**Tab 9 - Integration**
View shortcode examples, Gutenberg block info, and integration methods.

**Tab 10 - Diagnostics**
Real-time stream status, metadata information, and system diagnostics.

= 🔌 Integration Options =

* **Shortcode**: `[live_radio_player]`
* **Gutenberg Block**: Search for "Live Radio Player" in block inserter
* **PHP Template Tag**: `<?php echo do_shortcode('[live_radio_player]'); ?>`
* **Widget Compatible**: Use shortcode in any widget area

= 📝 Shortcode Parameters =

`[live_radio_player theme="dark" lyrics="on" layout="card" orientation="horizontal"]`

Available parameters:
* `theme` - classic, modern, dark, minimal
* `lyrics` - on, off
* `layout` - minimal, card, full, sidebar
* `orientation` - horizontal, vertical

= 🛠️ Developer Features =

* **Provider Architecture**: Extensible interface for custom stream providers
* **REST API Endpoints**: Access metadata, status, and lyrics programmatically
* **Normalized Data**: Consistent metadata structure across all providers
* **WordPress Standards**: Follows WordPress Coding Standards
* **Object-Oriented**: Clean OOP architecture with singleton patterns
* **XMLHttpRequest**: Uses XHR instead of fetch() for theme compatibility

= 📋 Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher
* Active streaming server (Icecast or Shoutcast)

= 🔒 Privacy & Compliance =

This plugin:
* Does NOT collect any user data
* Does NOT make tracking calls
* Does NOT use cookies (except WordPress defaults)
* Only connects to your configured streaming server and optional lyrics/artwork APIs
* Is fully GDPR compliant
* Contains NO obfuscated code

== Installation ==

= Automatic Installation =

1. Log in to your WordPress dashboard
2. Navigate to Plugins → Add New
3. Search for "Live Radio Player"
4. Click "Install Now" and then "Activate"
5. Go to Live Radio in admin menu to configure

= Manual Installation =

1. Download the plugin zip file
2. Log in to your WordPress dashboard
3. Navigate to Plugins → Add New → Upload Plugin
4. Choose the zip file and click "Install Now"
5. Activate the plugin
6. Go to Live Radio in admin menu to configure

= FTP Installation =

1. Download and unzip the plugin
2. Upload the `live-radio-player` folder to `/wp-content/plugins/`
3. Activate through the 'Plugins' menu in WordPress
4. Go to Live Radio in admin menu to configure

= Configuration =

1. Navigate to **Live Radio → Stream Settings**
2. Select your stream type (Icecast or Shoutcast)
3. Enter your stream URL (e.g., `http://stream.example.com:8000/`)
4. For Icecast: Enter mount point (e.g., `/radio.mp3`)
5. For Shoutcast v2: Enter SID (default: 1)
6. Set metadata refresh interval (default: 10 seconds)
7. Save changes
8. Customize appearance in other tabs as needed

== Frequently Asked Questions ==

= What streaming servers are supported? =

Live Radio Player supports Icecast and Shoutcast (v1 and v2) streaming servers.

= Do I need an API key? =

No! The plugin works without any API keys. All features including lyrics and artwork use free public APIs that don't require registration.

= How does karaoke mode work? =

When synced lyrics are available from LRCLIB.net, the plugin will highlight each line in real-time as the song plays. This works with live radio by tracking the time since the track started.

= Can I customize the player appearance? =

Yes! The plugin includes 4 theme presets and a comprehensive Visual Style Builder for customizing colors, typography, layout, and more without writing any CSS.

= Can I have multiple players on the same page? =

Yes! The plugin supports multiple player instances on the same page. Each player can have different settings via shortcode parameters.

= Does it work with Gutenberg? =

Yes! The plugin includes a native Gutenberg block. Just search for "Live Radio Player" in the block inserter.

= Is it mobile responsive? =

Yes! The player is fully responsive and works perfectly on all devices including smartphones and tablets.

= How do I display lyrics? =

Enable lyrics in the "Lyrics & Artwork" tab in the admin panel, then use `[live_radio_player lyrics="on"]` shortcode.

= Can I use custom CSS? =

Yes! The "Custom Code" tab allows you to add custom CSS and even disable the default plugin styles if you want complete control.

= Does it affect my site's performance? =

No! The plugin uses server-side caching with WordPress Transients and is optimized for performance. Metadata refreshes every 10 seconds by default.

= How often does metadata update? =

Metadata updates at the interval you set in Stream Settings (default is 10 seconds). This is configurable from 5 to 300 seconds.

= What if my stream is offline? =

The plugin gracefully handles offline streams by displaying an "Offline" status. When the stream comes back online, it will automatically reconnect.

= Can I translate the plugin? =

Yes! The plugin is fully internationalized and ready for translation. The text domain is `live-radio-player`.

= Is it compatible with caching plugins? =

Yes! The plugin works with caching plugins. Metadata updates are handled via REST API which bypasses page caching.

= Does it work with page builders? =

Yes! Use the shortcode in any page builder's text/code module, or use the native Gutenberg block.

= Where does the artwork come from? =

Artwork is automatically fetched from the iTunes Search API based on the artist and title from your stream metadata. No API key required.

== Screenshots ==

1. Frontend player - Modern Card theme with horizontal layout
2. Admin Panel - Stream Settings tab
3. Admin Panel - Visual Style Builder with color pickers
4. Admin Panel - Theme Presets with live previews
5. Admin Panel - Diagnostics showing real-time stream status
6. Frontend player - Dark Night theme with karaoke lyrics
7. Gutenberg block in block editor
8. Frontend player - Minimal layout for sidebar
9. Facebook button integration
10. Lyrics display with karaoke highlighting

== Changelog ==

= 1.4.0 - 2026-02-07 =
* NEW: Track time display showing elapsed and remaining time
* Track duration fetched from iTunes API
* Monospace font for clean time display
* Theme-specific styling for time display
* Admin setting in Content Control tab to enable/disable

= 1.3.3 - 2026-02-07 =
* Added Facebook button integration with gradient design
* Fixed sticky artwork positioning
* Improved spacing and layout consistency
* Bug fixes and performance improvements
* Prepared for GitHub release

= 1.3.2 - 2026-02-05 =
* Fixed JavaScript syntax errors
* Removed duplicate initialization logic
* Added missing variable declarations
* Improved code stability

= 1.3.1 - 2026-02-03 =
* Enhanced artist/title parsing with better regex
* Fixed Shoutcast playback suffix handling
* Improved karaoke sync accuracy

= 1.0.0 - 2026-02-01 =
* Initial release
* Icecast and Shoutcast support (v1 and v2)
* 10-tab comprehensive admin panel
* 4 predefined theme presets
* Visual Style Builder
* Multiple layout options (Minimal, Card, Full, Sidebar)
* Triple lyrics provider system (LRCLIB, lyrics.ovh, GreekLyrics)
* Karaoke mode with synced lyrics
* iTunes artwork auto-fetching
* REST API endpoints
* Gutenberg block support
* Shortcode with parameters
* Real-time metadata updates
* Server-side caching
* Mobile responsive design
* Multiple players per page support
* Performance optimizations
* Security enhancements
* WordPress Coding Standards compliance

== Upgrade Notice ==

= 1.3.3 =
New Facebook button integration and sticky artwork fixes. Recommended update.

= 1.0.0 =
Initial release of Live Radio Player. Install and enjoy professional live streaming on your WordPress site!

== Additional Information ==

= Author =

**Theodore Sfakianakis**
Email: theodore.sfakianakis@gmail.com

= Support =

For support, feature requests, or bug reports, please visit the plugin support forum on WordPress.org or the GitHub repository.

= Contributing =

Developers are welcome to contribute on GitHub. Pull requests, bug reports, and feature suggestions are appreciated!

= Credits =

* Lyrics powered by LRCLIB.net, lyrics.ovh, and GreekLyrics.gr (free public APIs)
* Artwork powered by iTunes Search API
* Built with WordPress APIs
* Icons from WordPress Dashicons

= License =

This plugin is licensed under GPLv2 or later. You are free to use, modify, and distribute this plugin under the terms of the GPL license.
