<?php
/**
 * Abstract Exporter class.
 *
 * @package Forminator_Export_Formats
 */

namespace Forminator_Export_Formats\Exporters;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Abstract_Exporter
 *
 * Base class for all exporters with common functionality.
 *
 * @since 1.0.0
 */
abstract class Abstract_Exporter implements Exporter_Interface
{

    /**
     * Plugin options.
     *
     * @var array
     */
    protected $plugin_options = array();

    /**
     * Constructor.
     *
     * @param array $plugin_options Plugin options.
     */
    public function __construct(array $plugin_options = array())
    {
        $this->plugin_options = $plugin_options;
    }

    /**
     * Get format description.
     *
     * @return string
     */
    public function get_format_description()
    {
        return '';
    }

    /**
     * Get icon.
     *
     * @return string
     */
    public function get_icon()
    {
        return 'sui-icon-download';
    }

    /**
     * Check if streaming is supported.
     *
     * @return bool
     */
    public function supports_streaming()
    {
        return false;
    }

    /**
     * Stream export (default implementation calls export()).
     *
     * @param array $headers Headers.
     * @param array $rows    Rows.
     * @param array $meta    Metadata.
     * @param array $options Options.
     * @return void
     */
    public function stream(array $headers, array $rows, array $meta, array $options = array())
    {
        // Default: just output the export content.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is binary output.
        echo $this->export($headers, $rows, $meta, $options);
    }

    /**
     * Get default options.
     *
     * @return array
     */
    public function get_default_options()
    {
        return array();
    }

    /**
     * Get options form fields.
     *
     * @return array
     */
    public function get_options_fields()
    {
        return array();
    }

    /**
     * Validate options.
     *
     * @param array $options Options to validate.
     * @return array Validated options.
     */
    public function validate_options(array $options)
    {
        $defaults = $this->get_default_options();
        return wp_parse_args($options, $defaults);
    }

    /**
     * Merge options with defaults.
     *
     * @param array $options User-provided options.
     * @return array Merged options.
     */
    protected function merge_options(array $options)
    {
        return wp_parse_args($options, $this->get_default_options());
    }

    /**
     * Sanitize a value for safe output.
     *
     * @param mixed $value Value to sanitize.
     * @return string Sanitized value.
     */
    protected function sanitize_value($value)
    {
        if (is_array($value)) {
            return implode(', ', array_map(array($this, 'sanitize_value'), $value));
        }

        if (is_object($value)) {
            return '';
        }

        return (string) $value;
    }

    /**
     * Generate export filename.
     *
     * @param array $meta Export metadata.
     * @return string Filename without extension.
     */
    protected function generate_filename(array $meta)
    {
        $parts = array(
            'forminator',
            isset($meta['form_name']) ? sanitize_title($meta['form_name']) : 'export',
            gmdate('Y-m-d-His'),
        );

        return implode('-', array_filter($parts));
    }

    /**
     * Format a date value.
     *
     * @param string $date   Date string.
     * @param string $format Date format.
     * @return string Formatted date.
     */
    protected function format_date($date, $format = '')
    {
        if (empty($format)) {
            $format = get_option('date_format') . ' ' . get_option('time_format');
        }

        $timestamp = strtotime($date);
        if (false === $timestamp) {
            return $date;
        }

        return wp_date($format, $timestamp);
    }

    /**
     * Convert string to UTF-8.
     *
     * @param string $string Input string.
     * @return string UTF-8 encoded string.
     */
    protected function ensure_utf8($string)
    {
        if (!is_string($string)) {
            return $string;
        }

        $encoding = mb_detect_encoding($string, 'UTF-8, ISO-8859-1, Windows-1252', true);

        if ('UTF-8' !== $encoding && false !== $encoding) {
            $string = mb_convert_encoding($string, 'UTF-8', $encoding);
        }

        return $string;
    }

    /**
     * Escape special characters for specific format.
     *
     * @param string $value  Value to escape.
     * @param string $format Format type (csv, xml, json, etc.).
     * @return string Escaped value.
     */
    protected function escape_for_format($value, $format = '')
    {
        $value = $this->ensure_utf8($value);

        switch ($format) {
            case 'xml':
                return htmlspecialchars($value, ENT_XML1, 'UTF-8');
            case 'html':
                return esc_html($value);
            case 'json':
                // JSON encoding handles escaping.
                return $value;
            case 'csv':
                // CSV escaping is handled by fputcsv().
                return $value;
            default:
                return $value;
        }
    }

    /**
     * Get memory limit in bytes.
     *
     * @return int Memory limit in bytes.
     */
    protected function get_memory_limit()
    {
        $limit = ini_get('memory_limit');

        if ('-1' === $limit) {
            return PHP_INT_MAX;
        }

        $value = (int) $limit;
        $unit = strtoupper(substr($limit, -1));

        switch ($unit) {
            case 'G':
                $value *= 1024;
            // Fall through.
            case 'M':
                $value *= 1024;
            // Fall through.
            case 'K':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Check if memory usage is approaching limit.
     *
     * @param float $threshold Threshold percentage (0.0 - 1.0).
     * @return bool True if memory usage is above threshold.
     */
    protected function is_memory_critical($threshold = 0.8)
    {
        $limit = $this->get_memory_limit();
        $usage = memory_get_usage(true);

        return ($usage / $limit) > $threshold;
    }

    /**
     * Clean up memory.
     *
     * @return void
     */
    protected function cleanup_memory()
    {
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
}
