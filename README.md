# Forminator Export Formats

Extend Forminator with multiple export formats: CSV, Excel, JSON, XML, PDF, and HTML.

## Description

This plugin adds multi-format export functionality to Forminator form submissions. Instead of just CSV, you can now export your data in 6 different formats.

## Features

- **CSV** - Enhanced with customizable delimiters and Excel compatibility
- **Excel (.xlsx)** - Native Excel format with bold headers and frozen rows
- **JSON** - Structured data with nested or flat formats
- **XML** - Customizable element names for easy integration
- **PDF** - Print-ready format with configurable orientation
- **HTML** - Styled tables with multiple themes

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Forminator 1.20 or higher

## Installation

1. Upload the `forminator-export-formats` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure Forminator is installed and activated

## Usage

1. Navigate to **Forminator → Submissions**
2. Select a form
3. Click the **Export** button
4. Choose your desired format
5. Configure format-specific options
6. Click **Download**

## Settings

Access global settings at **Forminator → Export Formats**:

- Set default export format
- Enable/disable specific formats
- Configure default options for each format

## For Developers

### Adding Custom Export Formats

```php
add_action('forminator_export_formats_register_exporters', function($registry, $options) {
    require_once 'path/to/my-custom-exporter.php';
    $registry->register(new My_Custom_Exporter($options));
}, 10, 2);
```

Your custom exporter must implement `Forminator_Export_Formats\Exporters\Exporter_Interface`.

### Filters

```php
// Modify export data before processing
add_filter('forminator_export_formats_before_download', function($form_id, $form_type, $format) {
    // Your code here
}, 10, 3);
```

## Changelog

### 1.0.0
- Initial release
- Support for CSV, Excel, JSON, XML, PDF, HTML formats
- Settings page for global configuration
- Format-specific options

## License

GPL v2 or later
