<?php
/**
 * CSV Exporter class.
 *
 * @package Forminator_Export_Formats
 */

namespace Forminator_Export_Formats\Exporters;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CSV_Exporter
 *
 * Exports data to CSV format with enhanced options.
 *
 * @since 1.0.0
 */
class CSV_Exporter extends Abstract_Exporter
{

    /**
     * Get format ID.
     *
     * @return string
     */
    public function get_format_id()
    {
        return 'csv';
    }

    /**
     * Get format name.
     *
     * @return string
     */
    public function get_format_name()
    {
        return __('CSV', 'forminator-export-formats');
    }

    /**
     * Get format description.
     *
     * @return string
     */
    public function get_format_description()
    {
        return __('Comma-separated values file. Compatible with Excel, Google Sheets, and other spreadsheet applications.', 'forminator-export-formats');
    }

    /**
     * Get MIME type.
     *
     * @return string
     */
    public function get_mime_type()
    {
        return 'text/csv; charset=UTF-8';
    }

    /**
     * Get file extension.
     *
     * @return string
     */
    public function get_file_extension()
    {
        return 'csv';
    }

    /**
     * Get icon.
     *
     * @return string
     */
    public function get_icon()
    {
        return 'sui-icon-page';
    }

    /**
     * Check if streaming is supported.
     *
     * @return bool
     */
    public function supports_streaming()
    {
        return true;
    }

    /**
     * Get default options.
     *
     * @return array
     */
    public function get_default_options()
    {
        return array(
            'delimiter' => isset($this->plugin_options['csv_delimiter']) ? $this->plugin_options['csv_delimiter'] : ',',
            'enclosure' => isset($this->plugin_options['csv_enclosure']) ? $this->plugin_options['csv_enclosure'] : '"',
            'escape_char' => '\\',
            'bom' => isset($this->plugin_options['csv_bom']) ? $this->plugin_options['csv_bom'] : true,
            'line_ending' => "\r\n",
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
                'id' => 'delimiter',
                'type' => 'select',
                'label' => __('Delimiter', 'forminator-export-formats'),
                'options' => array(
                    ',' => __('Comma (,)', 'forminator-export-formats'),
                    ';' => __('Semicolon (;)', 'forminator-export-formats'),
                    "\t" => __('Tab', 'forminator-export-formats'),
                    '|' => __('Pipe (|)', 'forminator-export-formats'),
                ),
                'default' => ',',
            ),
            array(
                'id' => 'enclosure',
                'type' => 'select',
                'label' => __('Text Enclosure', 'forminator-export-formats'),
                'options' => array(
                    '"' => __('Double Quote (")', 'forminator-export-formats'),
                    '\'' => __("Single Quote (')", 'forminator-export-formats'),
                ),
                'default' => '"',
            ),
            array(
                'id' => 'bom',
                'type' => 'checkbox',
                'label' => __('Add BOM for Excel', 'forminator-export-formats'),
                'description' => __('Add UTF-8 Byte Order Mark for better Excel compatibility.', 'forminator-export-formats'),
                'default' => true,
            ),
        );
    }

    /**
     * Export data to CSV.
     *
     * @param array $headers Headers.
     * @param array $rows    Data rows.
     * @param array $meta    Metadata.
     * @param array $options Options.
     * @return string CSV content.
     */
    public function export(array $headers, array $rows, array $meta, array $options = array())
    {
        $options = $this->merge_options($options);

        // Open memory stream.
        $handle = fopen('php://temp', 'r+');
        if (false === $handle) {
            return '';
        }

        // Write BOM if enabled.
        if ($options['bom']) {
            fwrite($handle, "\xEF\xBB\xBF");
        }

        // Write headers.
        $this->write_csv_row($handle, $headers, $options);

        // Write data rows.
        foreach ($rows as $row) {
            $this->write_csv_row($handle, $row, $options);
        }

        // Get content.
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    /**
     * Stream export directly to output.
     *
     * @param array $headers Headers.
     * @param array $rows    Data rows.
     * @param array $meta    Metadata.
     * @param array $options Options.
     * @return void
     */
    public function stream(array $headers, array $rows, array $meta, array $options = array())
    {
        $options = $this->merge_options($options);

        // Open output stream.
        $handle = fopen('php://output', 'w');
        if (false === $handle) {
            return;
        }

        // Write BOM if enabled.
        if ($options['bom']) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo "\xEF\xBB\xBF";
        }

        // Write headers.
        $this->write_csv_row($handle, $headers, $options);

        // Write data rows in chunks to manage memory.
        $chunk_size = 500;
        $total = count($rows);

        for ($i = 0; $i < $total; $i += $chunk_size) {
            $chunk = array_slice($rows, $i, $chunk_size);

            foreach ($chunk as $row) {
                $this->write_csv_row($handle, $row, $options);
            }

            // Flush output buffer.
            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            // Check memory.
            if ($this->is_memory_critical()) {
                $this->cleanup_memory();
            }
        }

        fclose($handle);
    }

    /**
     * Write a CSV row.
     *
     * @param resource $handle  File handle.
     * @param array    $row     Row data.
     * @param array    $options Options.
     * @return void
     */
    private function write_csv_row($handle, array $row, array $options)
    {
        // Sanitize values.
        $sanitized = array_map(array($this, 'sanitize_csv_value'), $row);

        // Use fputcsv with custom delimiter and enclosure.
        fputcsv(
            $handle,
            $sanitized,
            $options['delimiter'],
            $options['enclosure'],
            $options['escape_char']
        );
    }

    /**
     * Sanitize a value for CSV output.
     *
     * Prevents CSV injection attacks.
     *
     * @param mixed $value Value to sanitize.
     * @return string
     */
    private function sanitize_csv_value($value)
    {
        $value = $this->sanitize_value($value);
        $value = $this->ensure_utf8($value);

        // Prevent CSV injection by escaping leading special characters.
        $dangerous_chars = array('=', '+', '-', '@', "\t", "\r");
        $first_char = substr($value, 0, 1);

        if (in_array($first_char, $dangerous_chars, true)) {
            $value = "'" . $value;
        }

        return $value;
    }
}
