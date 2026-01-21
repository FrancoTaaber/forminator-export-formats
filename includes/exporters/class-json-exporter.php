<?php
/**
 * JSON Exporter class.
 *
 * @package Forminator_Export_Formats
 */

namespace Forminator_Export_Formats\Exporters;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class JSON_Exporter
 *
 * Exports data to JSON format.
 *
 * @since 1.0.0
 */
class JSON_Exporter extends Abstract_Exporter
{

    /**
     * Get format ID.
     *
     * @return string
     */
    public function get_format_id()
    {
        return 'json';
    }

    /**
     * Get format name.
     *
     * @return string
     */
    public function get_format_name()
    {
        return __('JSON', 'forminator-export-formats');
    }

    /**
     * Get format description.
     *
     * @return string
     */
    public function get_format_description()
    {
        return __('JavaScript Object Notation. Ideal for API integrations and data processing.', 'forminator-export-formats');
    }

    /**
     * Get MIME type.
     *
     * @return string
     */
    public function get_mime_type()
    {
        return 'application/json; charset=UTF-8';
    }

    /**
     * Get file extension.
     *
     * @return string
     */
    public function get_file_extension()
    {
        return 'json';
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
            'pretty_print' => isset($this->plugin_options['json_pretty']) ? $this->plugin_options['json_pretty'] : true,
            'include_meta' => true,
            'structure' => 'nested', // 'nested' or 'flat'.
            'unicode_escape' => false,
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
                'id' => 'pretty_print',
                'type' => 'checkbox',
                'label' => __('Pretty Print', 'forminator-export-formats'),
                'description' => __('Format JSON with indentation for readability.', 'forminator-export-formats'),
                'default' => true,
            ),
            array(
                'id' => 'include_meta',
                'type' => 'checkbox',
                'label' => __('Include Metadata', 'forminator-export-formats'),
                'description' => __('Include form name, export date, and entry count.', 'forminator-export-formats'),
                'default' => true,
            ),
            array(
                'id' => 'structure',
                'type' => 'select',
                'label' => __('Data Structure', 'forminator-export-formats'),
                'options' => array(
                    'nested' => __('Nested (field names as keys)', 'forminator-export-formats'),
                    'flat' => __('Flat (arrays with headers)', 'forminator-export-formats'),
                ),
                'default' => 'nested',
            ),
        );
    }

    /**
     * Export data to JSON.
     *
     * @param array $headers Headers.
     * @param array $rows    Data rows.
     * @param array $meta    Metadata.
     * @param array $options Options.
     * @return string JSON content.
     */
    public function export(array $headers, array $rows, array $meta, array $options = array())
    {
        $options = $this->merge_options($options);

        // Build data structure.
        if ('nested' === $options['structure']) {
            $data = $this->build_nested_structure($headers, $rows, $meta, $options);
        } else {
            $data = $this->build_flat_structure($headers, $rows, $meta, $options);
        }

        // Encode flags.
        $flags = JSON_UNESCAPED_SLASHES;

        if ($options['pretty_print']) {
            $flags |= JSON_PRETTY_PRINT;
        }

        if (!$options['unicode_escape']) {
            $flags |= JSON_UNESCAPED_UNICODE;
        }

        return wp_json_encode($data, $flags);
    }

    /**
     * Stream JSON output.
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

        $pretty = $options['pretty_print'];
        $indent = $pretty ? '  ' : '';
        $nl = $pretty ? "\n" : '';

        // Start JSON object.
        echo '{' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        // Add metadata if enabled.
        if ($options['include_meta']) {
            echo $indent . '"meta": ' . wp_json_encode($this->sanitize_meta($meta), $pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE : JSON_UNESCAPED_UNICODE) . ',' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        // Add headers.
        echo $indent . '"headers": ' . wp_json_encode($headers, JSON_UNESCAPED_UNICODE) . ',' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        // Start entries array.
        echo $indent . '"entries": [' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        // Stream entries.
        $total = count($rows);
        $i = 0;

        foreach ($rows as $row) {
            if ('nested' === $options['structure']) {
                $entry = $this->row_to_object($headers, $row);
            } else {
                $entry = array_map(array($this, 'sanitize_value'), $row);
            }

            $json = wp_json_encode($entry, JSON_UNESCAPED_UNICODE);
            $sep = ($i < $total - 1) ? ',' : '';

            echo $indent . $indent . $json . $sep . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            $i++;

            // Flush periodically.
            if (0 === $i % 100) {
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }
        }

        // Close entries array.
        echo $indent . ']' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        // Close JSON object.
        echo '}' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Build nested data structure.
     *
     * @param array $headers Headers.
     * @param array $rows    Data rows.
     * @param array $meta    Metadata.
     * @param array $options Options.
     * @return array
     */
    private function build_nested_structure($headers, $rows, $meta, $options)
    {
        $data = array();

        // Add metadata.
        if ($options['include_meta']) {
            $data['meta'] = $this->sanitize_meta($meta);
        }

        // Add entries as objects.
        $data['entries'] = array();

        foreach ($rows as $row) {
            $data['entries'][] = $this->row_to_object($headers, $row);
        }

        return $data;
    }

    /**
     * Build flat data structure.
     *
     * @param array $headers Headers.
     * @param array $rows    Data rows.
     * @param array $meta    Metadata.
     * @param array $options Options.
     * @return array
     */
    private function build_flat_structure($headers, $rows, $meta, $options)
    {
        $data = array();

        // Add metadata.
        if ($options['include_meta']) {
            $data['meta'] = $this->sanitize_meta($meta);
        }

        // Add headers.
        $data['headers'] = $headers;

        // Add rows as arrays.
        $data['entries'] = array();

        foreach ($rows as $row) {
            $data['entries'][] = array_map(array($this, 'sanitize_value'), $row);
        }

        return $data;
    }

    /**
     * Convert a row to an object using headers as keys.
     *
     * @param array $headers Headers.
     * @param array $row     Row data.
     * @return array Associative array.
     */
    private function row_to_object($headers, $row)
    {
        $obj = array();

        foreach ($headers as $i => $header) {
            $key = $this->sanitize_key($header);
            $value = isset($row[$i]) ? $row[$i] : '';
            $obj[$key] = $this->sanitize_value($value);
        }

        return $obj;
    }

    /**
     * Sanitize a key for JSON.
     *
     * @param string $key Key to sanitize.
     * @return string
     */
    private function sanitize_key($key)
    {
        // Convert to lowercase, replace spaces with underscores.
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_]/', '_', $key);
        $key = preg_replace('/_+/', '_', $key);
        $key = trim($key, '_');

        // Ensure key is not empty.
        if (empty($key)) {
            $key = 'field';
        }

        return $key;
    }

    /**
     * Sanitize metadata.
     *
     * @param array $meta Metadata.
     * @return array
     */
    private function sanitize_meta($meta)
    {
        return array(
            'form_id' => isset($meta['form_id']) ? (int) $meta['form_id'] : 0,
            'form_name' => isset($meta['form_name']) ? sanitize_text_field($meta['form_name']) : '',
            'form_type' => isset($meta['form_type']) ? sanitize_text_field($meta['form_type']) : '',
            'export_date' => isset($meta['export_date']) ? sanitize_text_field($meta['export_date']) : '',
            'entries_count' => isset($meta['entries_count']) ? (int) $meta['entries_count'] : 0,
        );
    }
}
