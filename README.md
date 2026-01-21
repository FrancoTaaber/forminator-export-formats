# Forminator Export Formats

[![CI](https://github.com/FrancoTaaber/forminator-export-formats/actions/workflows/ci.yml/badge.svg)](https://github.com/FrancoTaaber/forminator-export-formats/actions/workflows/ci.yml)
[![Release](https://img.shields.io/github/v/release/FrancoTaaber/forminator-export-formats)](https://github.com/FrancoTaaber/forminator-export-formats/releases)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![PHP](https://img.shields.io/badge/php-%3E%3D7.4-8892BF.svg)](https://php.net/)
[![WordPress](https://img.shields.io/badge/wordpress-%3E%3D5.8-21759B.svg)](https://wordpress.org/)

Extend Forminator with multiple export formats: CSV, Excel, JSON, XML, PDF, and HTML.

## Features

- **CSV** - Enhanced with customizable delimiters and Excel compatibility
- **Excel (.xlsx)** - Native Excel format with bold headers and frozen rows
- **JSON** - Structured data with nested or flat formats
- **XML** - Customizable element names for easy integration
- **PDF** - Print-ready format with configurable orientation
- **HTML** - Styled tables with multiple themes

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Forminator 1.20.0+

## Installation

### From GitHub Releases

1. Download the latest `forminator-export-formats.zip` from [Releases](https://github.com/FrancoTaaber/forminator-export-formats/releases)
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate

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

## Development

### Build

```bash
./build.sh
```

Creates `build/forminator-export-formats.zip` ready for distribution.

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

### 1.2.0
- Added Entry ID option for exports
- Improved Excel formatting
- Bug fixes

### 1.0.0
- Initial release
- Support for CSV, Excel, JSON, XML, PDF, HTML formats

## License

GPL v2 or later. See [LICENSE](LICENSE) file.

## Credits

Developed by [Franco Taaber](https://francotaaber.com)
