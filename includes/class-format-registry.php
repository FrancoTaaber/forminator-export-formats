<?php
/**
 * Format Registry class.
 *
 * @package Forminator_Export_Formats
 */

namespace Forminator_Export_Formats;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Format_Registry
 *
 * Manages registration and retrieval of export formats.
 *
 * @since 1.0.0
 */
class Format_Registry
{

    /**
     * Registered exporters.
     *
     * @var array<string, Exporters\Exporter_Interface>
     */
    private $exporters = array();

    /**
     * Register an exporter.
     *
     * @param Exporters\Exporter_Interface $exporter The exporter instance.
     * @return bool True if registered successfully, false if already exists.
     */
    public function register(Exporters\Exporter_Interface $exporter)
    {
        $format_id = $exporter->get_format_id();

        if (isset($this->exporters[$format_id])) {
            return false;
        }

        $this->exporters[$format_id] = $exporter;

        return true;
    }

    /**
     * Unregister an exporter.
     *
     * @param string $format_id The format ID to unregister.
     * @return bool True if unregistered, false if not found.
     */
    public function unregister($format_id)
    {
        if (!isset($this->exporters[$format_id])) {
            return false;
        }

        unset($this->exporters[$format_id]);

        return true;
    }

    /**
     * Get an exporter by format ID.
     *
     * @param string $format_id The format ID.
     * @return Exporters\Exporter_Interface|null The exporter or null if not found.
     */
    public function get($format_id)
    {
        return isset($this->exporters[$format_id]) ? $this->exporters[$format_id] : null;
    }

    /**
     * Check if a format is registered.
     *
     * @param string $format_id The format ID.
     * @return bool
     */
    public function has($format_id)
    {
        return isset($this->exporters[$format_id]);
    }

    /**
     * Get all registered exporters.
     *
     * @return array<string, Exporters\Exporter_Interface>
     */
    public function get_all()
    {
        return $this->exporters;
    }

    /**
     * Get all registered format IDs.
     *
     * @return array
     */
    public function get_format_ids()
    {
        return array_keys($this->exporters);
    }

    /**
     * Get formats for dropdown/select.
     *
     * @return array Associative array of format_id => format_name.
     */
    public function get_formats_for_select()
    {
        $formats = array();

        foreach ($this->exporters as $format_id => $exporter) {
            $formats[$format_id] = $exporter->get_format_name();
        }

        return $formats;
    }

    /**
     * Get format info for all registered formats.
     *
     * @return array Array of format information.
     */
    public function get_formats_info()
    {
        $info = array();

        foreach ($this->exporters as $format_id => $exporter) {
            $info[$format_id] = array(
                'id' => $format_id,
                'name' => $exporter->get_format_name(),
                'description' => $exporter->get_format_description(),
                'extension' => $exporter->get_file_extension(),
                'mime_type' => $exporter->get_mime_type(),
                'icon' => $exporter->get_icon(),
            );
        }

        return $info;
    }
}
