=== Tailwind Gutenberg Block by tiny.magicwp.pl ===
Contributors: tinymagicwp
Tags: tailwind, tailwindcss, template, block-theme, gutenberg-block
Requires at least: 6.8
Tested up to: 6.9
Stable tag: 1.0.10
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Enable Tailwind CSS v3 for WordPress block templates via checkbox. Zero bloat, zero spam, zero cost.

== Description ==

**The simplest way to use Tailwind CSS v3 in WordPress block templates.**

This plugin does exactly one thing, and it does it perfectly: by default, your theme's CSS and JavaScript load normally. For any specific template, you can enable Tailwind CSS v3 via a simple checkbox in Site Editor. When enabled, it automatically removes WordPress default styles and scripts, replacing them with Tailwind CSS v3 from CDN. That's it. Nothing more, nothing less.

**Why choose this plugin?**

* **Zero bloat** - No unnecessary features, no dashboard widgets, no admin notices, no extra CSS or JavaScript files (except Tailwind CDN)
* **Zero spam** - No promotional content, no upsells, no "upgrade to pro" messages
* **Zero cost** - Completely free, forever. No hidden fees, no premium versions
* **Lightning fast** - Minimal code means minimal impact on your site's performance
* **Clean output** - Removes WordPress default styles and scripts, leaving only Tailwind CSS
* **Simple control** - One checkbox in Site Editor to enable Tailwind for any template
* **Future-proof** - Built with modern PHP 8.4 standards and WordPress best practices

**How it works:**

1. **By default** - Your theme's CSS and JavaScript load normally on all templates. The plugin does nothing until you enable it.
2. **Per-template control** - In Site Editor, open any template and check "Użyj Tailwind CSS" in the sidebar panel
3. **When enabled** - For that specific template only:
   * Loads Tailwind CSS v3 from CDN (cdn.tailwindcss.com/3.4.17)
   * Removes WordPress default styles (wp-block-library, theme styles, etc.) while keeping admin bar styles
   * Removes WordPress default scripts (wp-embed, comment-reply, jquery) while keeping admin bar scripts
   * Other templates remain unaffected and continue using theme styles
4. Includes a custom HTML block for writing Tailwind-styled content in the block editor
5. Automatically creates a "Page for Tailwind V3" template on activation (with Tailwind enabled by default)

**What it doesn't do:**

* No custom Tailwind configuration
* No build process or compilation
* No custom utility classes
* No design system or components
* No premium features locked behind paywalls
* No analytics or tracking

If you need a simple, clean solution to use Tailwind CSS in WordPress block templates without any unnecessary complexity, this is the plugin for you.

== Frequently Asked Questions ==

= Do I need to modify any theme files? =

No. The plugin works entirely through WordPress block templates. You can enable Tailwind for any template using the checkbox in Site Editor sidebar.

= Will this slow down my website? =

No. The plugin is extremely lightweight. It only loads Tailwind CSS CDN (from cdn.tailwindcss.com) and removes unnecessary WordPress styles/scripts, which can actually improve performance.

= Does this plugin collect any data? =

No. This plugin does not collect, store, or transmit any data. It only loads Tailwind CSS from CDN and removes WordPress default styles/scripts.

= Can I use this with any block theme? =

Yes. The plugin works with any WordPress block theme (themes that support block templates).

= Will this break my existing styles? =

No. By default, the plugin does nothing and your theme's CSS/JS load normally. The plugin only affects templates where you explicitly enable Tailwind via the checkbox. Other templates remain completely unaffected and continue using your theme's styles. You have full control - enable Tailwind only for templates where you want it.

= Can I customize Tailwind configuration? =

No. The plugin uses Tailwind CSS v3 from CDN (cdn.tailwindcss.com/3.4.17) with default configuration. For custom Tailwind configuration, you would need to set up your own build process.

= Which version of Tailwind CSS does this use? =

The plugin uses Tailwind CSS v3.4.17 from CDN. This is the latest stable version of Tailwind CSS v3.

= Will there be premium features or paid versions? =

No. This plugin will always be completely free with no premium versions or paid features.

== Changelog ==

= 1.0.10 =
* Added cache clear link in admin bar for frontend pages
* Administrators can now clear cache for current page directly from admin bar
* Cache clearing automatically reloads the page after clearing

= 1.0.7 =
* Refactored code structure (admin/, public/, includes/)
* Added checkbox in Site Editor sidebar to enable Tailwind for any template
* Improved template detection logic
* Fixed REST API endpoints for template meta fields
* Removed unnecessary filters

= 1.0.0 =
* Initial release
* Tailwind CSS CDN integration
* WordPress styles removal
* Custom HTML Tailwind block
* Template creation on activation

== Upgrade Notice ==

= 1.0.10 =
Added convenient cache clearing feature in admin bar. Administrators can now clear cache for any page directly from the frontend. Recommended update for better cache management.

= 1.0.7 =
Major refactoring with improved code structure. All existing functionality preserved. Update recommended for better maintainability.

= 1.0.0 =
Initial release. Install and start using Tailwind CSS in your WordPress block templates immediately.
