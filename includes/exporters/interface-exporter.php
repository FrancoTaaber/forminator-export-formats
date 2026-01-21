<?php
/**
 * Exporter Interface.
 *
 * @package Forminator_Export_Formats
 */

namespace Forminator_Export_Formats\Exporters;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface Exporter_Interface
 *
 * Defines the contract for all export format implementations.
 *
 * @since 1.0.0
 */
interface Exporter_Interface
{

    /**
     * Get the unique format identifier.
     *
     * @return string Format ID (e.g., 'csv', 'excel', 'json').
     */
    public function get_format_id();

    /**
     * Get the human-readable format name.
     *
     * @return string Format name (e.g., 'CSV', 'Excel', 'JSON').
     */
    public function get_format_name();

    /**
     * Get the format description.
     *
     * @return string Short description of the format.
     */
    public function get_format_description();

    /**
     * Get the MIME type for the export file.
     *
     * @return string MIME type (e.g., 'text/csv', 'application/json').
     */
    public function get_mime_type();

    /**
     * Get the file extension for the export file.
     *
     * @return string File extension without dot (e.g., 'csv', 'xlsx', 'json').
     */
    public function get_file_extension();

    /**
     * Get the icon class or SVG for the format.
     *
     * @return string Icon class or inline SVG.
     */
    public function get_icon();

    /**
     * Export the data to the format.
     *
     * @param array $headers Array of header labels.
     * @param array $rows    Array of data rows (each row is an array of values).
     * @param array $meta    Metadata about the export (form_id, form_name, etc.).
     * @param array $options Format-specific options.
     * @return string The exported content as a string.
     */
    public function export(array $headers, array $rows, array $meta, array $options = array());

    /**
     * Stream the export directly to output.
     *
     * Used for large exports to avoid memory issues.
     *
     * @param array $headers Array of header labels.
     * @param array $rows    Array of data rows (each row is an array of values).
     * @param array $meta    Metadata about the export.
     * @param array $options Format-specific options.
     * @return void
     */
    public function stream(array $headers, array $rows, array $meta, array $options = array());

    /**
     * Check if this format supports streaming.
     *
     * @return bool True if streaming is supported.
     */
    public function supports_streaming();

    /**
     * Get the default options for this format.
     *
     * @return array Default options.
     */
    public function get_default_options();

    /**
     * Get the options form fields for admin UI.
     *
     * @return array Array of form field definitions.
     */
    public function get_options_fields();

    /**
     * Validate format-specific options.
     *
     * @param array $options Options to validate.
     * @return array|WP_Error Validated options or WP_Error on failure.
     */
    public function validate_options(array $options);
}
