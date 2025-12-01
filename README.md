# ZuidWest Liveblog

A lightweight WordPress plugin that embeds 24LiveBlog posts using a simple shortcode.

## Requirements

- WordPress 6.8 or higher
- PHP 8.3 or higher

## Installation

1. Download the latest release from [GitHub Releases](https://github.com/oszuidwest/zw-liveblog/releases)
2. Upload the ZIP file via WordPress Admin → Plugins → Add New → Upload Plugin
3. Activate the plugin
4. Add the shortcode to any post or page:
   ```
   [liveblog id="YOUR_LIVEBLOG_ID"]
   ```

## Finding your Liveblog ID

1. Log in to your 24LiveBlog dashboard
2. Open your liveblog event
3. Copy the numeric ID from the URL or embed code

## Features

- **Liveblog Embedding** - Converts the shortcode into a 24LiveBlog embed
- **Ad Hiding** - Removes ad elements from the free 24LiveBlog tier
- **Dark Mode Support** - Basic styling adjustments for dark themes
- **SEO Schema** - Injects LiveBlogPosting JSON-LD markup with up to 100 updates
- **Performance Optimized** - Deferred script loading, 60-second API caching

## Development

```bash
composer install
vendor/bin/phpcs           # Code style (PSR-12 + WordPress)
vendor/bin/phpstan analyse # Static analysis
```

## License

MIT
