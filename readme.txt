=== Forminator Export Formats ===
Contributors: FrancoTaaber
Tags: forminator, export, csv, excel, json, xml, pdf, html, spreadsheet
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Extend Forminator with multiple export formats: CSV, Excel, JSON, XML, PDF, and HTML.

== Description ==

**Forminator Export Formats** is a powerful add-on for [Forminator](https://wordpress.org/plugins/forminator/) that extends the built-in CSV export with professional-grade multi-format export capabilities.

= Features =

* **CSV Export** - Enhanced CSV with customizable delimiters, Excel compatibility (BOM), and injection prevention
* **Excel Export** - Native .xlsx format without external dependencies
* **JSON Export** - Structured data with pretty-printing and metadata options
* **XML Export** - Customizable element names and schemas
* **PDF Export** - Professional tabular format using TCPDF
* **HTML Export** - Styled responsive tables with multiple themes

= Why Choose This Plugin? =

* ✅ No external services required - all processing done locally
* ✅ Memory optimized for large exports (streaming support)
* ✅ Secure - proper input sanitization, nonce verification, capability checks
* ✅ Internationalization ready
* ✅ Developer friendly with hooks and filters

= Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher
* Forminator 1.20.0 or higher

== Installation ==

1. Upload `forminator-export-formats` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure Forminator is installed and activated
4. Go to Forminator → Export Formats to configure settings

== Frequently Asked Questions ==

= Does this replace Forminator's built-in export? =

No, it enhances it. When you click "Export" on the submissions page, you'll see our enhanced modal with multiple format options instead of the default CSV-only export.

= Can I export large amounts of data? =

Yes! The plugin uses streaming and memory optimization for large exports. CSV, JSON, and XML exports stream directly to the browser to minimize memory usage.

= Is my data secure? =

Absolutely. All exports require proper WordPress capabilities (forminator-entries permission), verify nonces, and sanitize all inputs. No data is sent to external servers.

= How do I get updates? =

The plugin includes auto-update functionality via GitHub releases. You can also check for updates manually from the Plugins page.

== Screenshots ==

1. Export modal with format selection
2. Settings page with format-specific options
3. PDF export example

== Changelog ==

= 1.0.0 =
* Initial release
* CSV, Excel, JSON, XML, PDF, and HTML export formats
* Format-specific options and settings
* Memory-optimized streaming exports
* Auto-update support via GitHub

== Upgrade Notice ==

= 1.0.0 =
Initial release.

== Developer Notes ==

= Hooks =

**Actions:**
* `forminator_export_formats_register_exporters` - Register custom exporters
* `forminator_export_formats_before_export` - Before export processing
* `forminator_export_formats_after_export` - After export processing

**Filters:**
* `forminator_export_formats_enabled_formats` - Modify enabled formats
* `forminator_export_formats_exporter_options` - Modify exporter options
* `forminator_export_formats_export_data` - Modify export data before processing

= Example: Custom Exporter =

```php
add_action('forminator_export_formats_register_exporters', function($registry, $options) {
    require_once 'class-my-exporter.php';
    $registry->register(new My_Custom_Exporter($options));
}, 10, 2);
```
