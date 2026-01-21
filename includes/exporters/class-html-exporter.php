<?php
/**
 * HTML Exporter class.
 *
 * @package Forminator_Export_Formats
 */

namespace Forminator_Export_Formats\Exporters;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class HTML_Exporter
 *
 * Exports data to HTML format with styling options.
 *
 * @since 1.0.0
 */
class HTML_Exporter extends Abstract_Exporter
{

    /**
     * Get format ID.
     *
     * @return string
     */
    public function get_format_id()
    {
        return 'html';
    }

    /**
     * Get format name.
     *
     * @return string
     */
    public function get_format_name()
    {
        return __('HTML', 'forminator-export-formats');
    }

    /**
     * Get format description.
     *
     * @return string
     */
    public function get_format_description()
    {
        return __('HTML table format. View in any web browser or embed in web pages.', 'forminator-export-formats');
    }

    /**
     * Get MIME type.
     *
     * @return string
     */
    public function get_mime_type()
    {
        return 'text/html; charset=UTF-8';
    }

    /**
     * Get file extension.
     *
     * @return string
     */
    public function get_file_extension()
    {
        return 'html';
    }

    /**
     * Get icon.
     *
     * @return string
     */
    public function get_icon()
    {
        return 'sui-icon-code';
    }

    /**
     * Get default options.
     *
     * @return array
     */
    public function get_default_options()
    {
        return array(
            'theme' => isset($this->plugin_options['html_theme']) ? $this->plugin_options['html_theme'] : 'light',
            'include_styles' => true,
            'standalone' => true,
            'include_search' => false,
            'include_sort' => false,
            'table_id' => 'forminator-export-table',
            'responsive' => true,
        );
    }

    /**
     * Get options form fields.
     *
     * @return array
     */
    public function get_options_fields()
    {
        return array(
            array(
                'id' => 'theme',
                'type' => 'select',
                'label' => __('Theme', 'forminator-export-formats'),
                'options' => array(
                    'light' => __('Light', 'forminator-export-formats'),
                    'dark' => __('Dark', 'forminator-export-formats'),
                    'minimal' => __('Minimal', 'forminator-export-formats'),
                    'bordered' => __('Bordered', 'forminator-export-formats'),
                ),
                'default' => 'light',
            ),
            array(
                'id' => 'standalone',
                'type' => 'checkbox',
                'label' => __('Standalone Document', 'forminator-export-formats'),
                'description' => __('Include full HTML document structure (for viewing in browser).', 'forminator-export-formats'),
                'default' => true,
            ),
            array(
                'id' => 'include_styles',
                'type' => 'checkbox',
                'label' => __('Include CSS Styles', 'forminator-export-formats'),
                'description' => __('Include inline CSS for styling.', 'forminator-export-formats'),
                'default' => true,
            ),
            array(
                'id' => 'responsive',
                'type' => 'checkbox',
                'label' => __('Responsive Table', 'forminator-export-formats'),
                'description' => __('Make table horizontally scrollable on small screens.', 'forminator-export-formats'),
                'default' => true,
            ),
        );
    }

    /**
     * Export data to HTML.
     *
     * @param array $headers Headers.
     * @param array $rows    Data rows.
     * @param array $meta    Metadata.
     * @param array $options Options.
     * @return string HTML content.
     */
    public function export(array $headers, array $rows, array $meta, array $options = array())
    {
        $options = $this->merge_options($options);

        $html = '';

        // Standalone document wrapper.
        if ($options['standalone']) {
            $html .= $this->get_document_header($meta, $options);
        }

        // Include styles.
        if ($options['include_styles']) {
            $html .= $this->get_styles($options);
        }

        // Content wrapper.
        $html .= '<div class="forminator-export-wrapper">';

        // Header section.
        $html .= $this->get_header_html($meta, $options);

        // Table wrapper for responsive.
        if ($options['responsive']) {
            $html .= '<div class="table-responsive">';
        }

        // Table.
        $html .= $this->get_table_html($headers, $rows, $options);

        if ($options['responsive']) {
            $html .= '</div>';
        }

        // Footer.
        $html .= $this->get_footer_html($meta);

        $html .= '</div>';

        // Close document.
        if ($options['standalone']) {
            $html .= $this->get_document_footer();
        }

        return $html;
    }

    /**
     * Get document header.
     *
     * @param array $meta    Metadata.
     * @param array $options Options.
     * @return string
     */
    private function get_document_header($meta, $options)
    {
        $title = isset($meta['form_name']) ? $meta['form_name'] : __('Export', 'forminator-export-formats');

        return '<!DOCTYPE html>
<html lang="' . esc_attr(get_locale()) . '">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="generator" content="Forminator Export Formats">
	<title>' . esc_html($title) . '</title>
</head>
<body class="theme-' . esc_attr($options['theme']) . '">
';
    }

    /**
     * Get document footer.
     *
     * @return string
     */
    private function get_document_footer()
    {
        return '</body>
</html>';
    }

    /**
     * Get CSS styles.
     *
     * @param array $options Options.
     * @return string
     */
    private function get_styles($options)
    {
        $theme = $options['theme'];

        // Base styles.
        $css = '
<style>
/* Reset and Base */
* {
	box-sizing: border-box;
	margin: 0;
	padding: 0;
}

body {
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
	font-size: 14px;
	line-height: 1.5;
}

.forminator-export-wrapper {
	max-width: 1400px;
	margin: 0 auto;
	padding: 20px;
}

/* Header */
.export-header {
	text-align: center;
	margin-bottom: 24px;
	padding-bottom: 16px;
}

.export-header h1 {
	font-size: 24px;
	font-weight: 600;
	margin-bottom: 8px;
}

.export-header .meta {
	font-size: 13px;
	opacity: 0.7;
}

/* Responsive Table Wrapper */
.table-responsive {
	overflow-x: auto;
	-webkit-overflow-scrolling: touch;
}

/* Table Base */
.export-table {
	width: 100%;
	border-collapse: collapse;
	font-size: 13px;
}

.export-table th,
.export-table td {
	padding: 10px 12px;
	text-align: left;
	vertical-align: top;
}

.export-table th {
	font-weight: 600;
	white-space: nowrap;
}

/* Footer */
.export-footer {
	text-align: center;
	margin-top: 24px;
	padding-top: 16px;
	font-size: 12px;
	opacity: 0.6;
}
';

        // Theme-specific styles.
        switch ($theme) {
            case 'dark':
                $css .= '
body.theme-dark {
	background-color: #1a1a2e;
	color: #eee;
}

.theme-dark .export-header {
	border-bottom: 1px solid #333;
}

.theme-dark .export-table th {
	background-color: #16213e;
	color: #fff;
	border-bottom: 2px solid #0f3460;
}

.theme-dark .export-table td {
	border-bottom: 1px solid #333;
}

.theme-dark .export-table tbody tr:hover {
	background-color: #16213e;
}

.theme-dark .export-footer {
	border-top: 1px solid #333;
}
';
                break;

            case 'minimal':
                $css .= '
body.theme-minimal {
	background-color: #fff;
	color: #333;
}

.theme-minimal .export-header {
	border-bottom: none;
}

.theme-minimal .export-table th {
	font-weight: 500;
	text-transform: uppercase;
	font-size: 11px;
	letter-spacing: 0.5px;
	color: #666;
	border-bottom: 1px solid #ddd;
}

.theme-minimal .export-table td {
	border-bottom: 1px solid #f0f0f0;
}

.theme-minimal .export-table tbody tr:hover td {
	background-color: #fafafa;
}
';
                break;

            case 'bordered':
                $css .= '
body.theme-bordered {
	background-color: #fff;
	color: #333;
}

.theme-bordered .export-header {
	border-bottom: 2px solid #333;
}

.theme-bordered .export-table,
.theme-bordered .export-table th,
.theme-bordered .export-table td {
	border: 1px solid #ccc;
}

.theme-bordered .export-table th {
	background-color: #f5f5f5;
}

.theme-bordered .export-table tbody tr:nth-child(even) {
	background-color: #fafafa;
}

.theme-bordered .export-footer {
	border-top: 2px solid #333;
}
';
                break;

            default: // light.
                $css .= '
body.theme-light {
	background-color: #fff;
	color: #333;
}

.theme-light .export-header {
	border-bottom: 2px solid #e2e8f0;
}

.theme-light .export-table th {
	background-color: #f8fafc;
	border-bottom: 2px solid #e2e8f0;
}

.theme-light .export-table td {
	border-bottom: 1px solid #e2e8f0;
}

.theme-light .export-table tbody tr:hover {
	background-color: #f1f5f9;
}

.theme-light .export-footer {
	border-top: 1px solid #e2e8f0;
}
';
                break;
        }

        $css .= '
/* Print styles */
@media print {
	body {
		-webkit-print-color-adjust: exact;
		print-color-adjust: exact;
	}
	
	.forminator-export-wrapper {
		max-width: none;
		padding: 0;
	}
	
	.export-table {
		page-break-inside: auto;
	}
	
	.export-table tr {
		page-break-inside: avoid;
		page-break-after: auto;
	}
	
	.export-table thead {
		display: table-header-group;
	}
}
</style>
';

        return $css;
    }

    /**
     * Get header HTML.
     *
     * @param array $meta    Metadata.
     * @param array $options Options.
     * @return string
     */
    private function get_header_html($meta, $options)
    {
        $title = isset($meta['form_name']) ? $meta['form_name'] : __('Export', 'forminator-export-formats');

        $html = '<header class="export-header">';
        $html .= '<h1>' . esc_html($title) . '</h1>';

        $meta_parts = array();

        if (isset($meta['export_date'])) {
            $meta_parts[] = sprintf(
                /* translators: %s: Export date */
                __('Exported: %s', 'forminator-export-formats'),
                wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($meta['export_date']))
            );
        }

        if (isset($meta['entries_count'])) {
            $meta_parts[] = sprintf(
                /* translators: %d: Number of entries */
                _n('%d entry', '%d entries', $meta['entries_count'], 'forminator-export-formats'),
                $meta['entries_count']
            );
        }

        if (!empty($meta_parts)) {
            $html .= '<p class="meta">' . esc_html(implode(' • ', $meta_parts)) . '</p>';
        }

        $html .= '</header>';

        return $html;
    }

    /**
     * Get table HTML.
     *
     * @param array $headers Headers.
     * @param array $rows    Data rows.
     * @param array $options Options.
     * @return string
     */
    private function get_table_html($headers, $rows, $options)
    {
        $table_id = sanitize_html_class($options['table_id']);

        $html = '<table class="export-table" id="' . esc_attr($table_id) . '">';

        // Table header.
        $html .= '<thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . esc_html($header) . '</th>';
        }
        $html .= '</tr></thead>';

        // Table body.
        $html .= '<tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $cell_value = $this->sanitize_value($cell);
                // Convert URLs to links.
                $cell_value = $this->linkify($cell_value);
                // Convert email addresses to mailto links.
                $cell_value = $this->emailify($cell_value);
                $html .= '<td>' . $cell_value . '</td>'; // Already escaped in sanitize_value.
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';

        $html .= '</table>';

        return $html;
    }

    /**
     * Get footer HTML.
     *
     * @param array $meta Metadata.
     * @return string
     */
    private function get_footer_html($meta)
    {
        $html = '<footer class="export-footer">';
        $html .= sprintf(
            /* translators: %s: Site name */
            esc_html__('Generated by %s using Forminator Export Formats', 'forminator-export-formats'),
            esc_html(get_bloginfo('name'))
        );
        $html .= '</footer>';

        return $html;
    }

    /**
     * Convert URLs to clickable links.
     *
     * @param string $text Text to process.
     * @return string
     */
    private function linkify($text)
    {
        $text = esc_html($text);

        // Match URLs.
        $pattern = '/(https?:\/\/[^\s<>"\']+)/i';

        return preg_replace_callback(
            $pattern,
            function ($matches) {
                $url = $matches[1];
                return '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($url) . '</a>';
            },
            $text
        );
    }

    /**
     * Convert email addresses to mailto links.
     *
     * @param string $text Text to process.
     * @return string
     */
    private function emailify($text)
    {
        // Match email addresses that are not already in links.
        $pattern = '/(?<!["\'>])([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})(?!["\'])/';

        return preg_replace_callback(
            $pattern,
            function ($matches) {
                $email = $matches[1];
                return '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
            },
            $text
        );
    }
}
