<?php
/**
 * XML Exporter class.
 *
 * @package Forminator_Export_Formats
 */

namespace Forminator_Export_Formats\Exporters;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class XML_Exporter
 *
 * Exports data to XML format.
 *
 * @since 1.0.0
 */
class XML_Exporter extends Abstract_Exporter
{

    /**
     * Get format ID.
     *
     * @return string
     */
    public function get_format_id()
    {
        return 'xml';
    }

    /**
     * Get format name.
     *
     * @return string
     */
    public function get_format_name()
    {
        return __('XML', 'forminator-export-formats');
    }

    /**
     * Get format description.
     *
     * @return string
     */
    public function get_format_description()
    {
        return __('Extensible Markup Language. Ideal for data interchange and system integration.', 'forminator-export-formats');
    }

    /**
     * Get MIME type.
     *
     * @return string
     */
    public function get_mime_type()
    {
        return 'application/xml; charset=UTF-8';
    }

    /**
     * Get file extension.
     *
     * @return string
     */
    public function get_file_extension()
    {
        return 'xml';
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
            'root_element' => isset($this->plugin_options['xml_root']) ? $this->plugin_options['xml_root'] : 'entries',
            'row_element' => isset($this->plugin_options['xml_row']) ? $this->plugin_options['xml_row'] : 'entry',
            'include_meta' => true,
            'pretty_print' => true,
            'include_headers' => true,
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
                'id' => 'root_element',
                'type' => 'text',
                'label' => __('Root Element Name', 'forminator-export-formats'),
                'description' => __('The name of the root XML element.', 'forminator-export-formats'),
                'default' => 'entries',
            ),
            array(
                'id' => 'row_element',
                'type' => 'text',
                'label' => __('Entry Element Name', 'forminator-export-formats'),
                'description' => __('The name of each entry XML element.', 'forminator-export-formats'),
                'default' => 'entry',
            ),
            array(
                'id' => 'include_meta',
                'type' => 'checkbox',
                'label' => __('Include Metadata', 'forminator-export-formats'),
                'description' => __('Include form information in the XML header.', 'forminator-export-formats'),
                'default' => true,
            ),
            array(
                'id' => 'pretty_print',
                'type' => 'checkbox',
                'label' => __('Pretty Print', 'forminator-export-formats'),
                'description' => __('Format XML with indentation.', 'forminator-export-formats'),
                'default' => true,
            ),
        );
    }

    /**
     * Export data to XML.
     *
     * @param array $headers Headers.
     * @param array $rows    Data rows.
     * @param array $meta    Metadata.
     * @param array $options Options.
     * @return string XML content.
     */
    public function export(array $headers, array $rows, array $meta, array $options = array())
    {
        $options = $this->merge_options($options);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = $options['pretty_print'];

        // Create root element.
        $root_name = $this->sanitize_element_name($options['root_element']);
        $root = $dom->createElement($root_name);
        $dom->appendChild($root);

        // Add metadata as attributes or child element.
        if ($options['include_meta']) {
            $meta_element = $this->create_meta_element($dom, $meta);
            $root->appendChild($meta_element);
        }

        // Add entries.
        $row_name = $this->sanitize_element_name($options['row_element']);

        foreach ($rows as $row) {
            $entry_element = $this->create_entry_element($dom, $row_name, $headers, $row);
            $root->appendChild($entry_element);
        }

        return $dom->saveXML();
    }

    /**
     * Stream XML output.
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
        $root = $this->sanitize_element_name($options['root_element']);
        $row_name = $this->sanitize_element_name($options['row_element']);

        // XML declaration.
        echo '<?xml version="1.0" encoding="UTF-8"?>' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        // Root element start.
        echo '<' . esc_attr($root) . '>' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        // Metadata.
        if ($options['include_meta']) {
            echo $indent . '<meta>' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $indent . $indent . '<form_id>' . esc_html($meta['form_id'] ?? '') . '</form_id>' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $indent . $indent . '<form_name>' . esc_html($meta['form_name'] ?? '') . '</form_name>' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $indent . $indent . '<export_date>' . esc_html($meta['export_date'] ?? '') . '</export_date>' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $indent . $indent . '<entries_count>' . esc_html($meta['entries_count'] ?? 0) . '</entries_count>' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $indent . '</meta>' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        // Stream entries.
        $count = 0;
        foreach ($rows as $row) {
            echo $indent . '<' . esc_attr($row_name) . '>' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            foreach ($headers as $i => $header) {
                $field_name = $this->sanitize_element_name($header);
                $field_value = isset($row[$i]) ? $this->sanitize_value($row[$i]) : '';
                echo $indent . $indent . '<' . esc_attr($field_name) . '>' . esc_html($field_value) . '</' . esc_attr($field_name) . '>' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }

            echo $indent . '</' . esc_attr($row_name) . '>' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            $count++;

            // Flush periodically.
            if (0 === $count % 100) {
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }
        }

        // Root element end.
        echo '</' . esc_attr($root) . '>' . $nl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Create metadata element.
     *
     * @param \DOMDocument $dom  DOM document.
     * @param array        $meta Metadata.
     * @return \DOMElement
     */
    private function create_meta_element($dom, $meta)
    {
        $element = $dom->createElement('meta');

        $fields = array(
            'form_id' => isset($meta['form_id']) ? (string) $meta['form_id'] : '',
            'form_name' => isset($meta['form_name']) ? $meta['form_name'] : '',
            'form_type' => isset($meta['form_type']) ? $meta['form_type'] : '',
            'export_date' => isset($meta['export_date']) ? $meta['export_date'] : '',
            'entries_count' => isset($meta['entries_count']) ? (string) $meta['entries_count'] : '0',
        );

        foreach ($fields as $name => $value) {
            $child = $dom->createElement($name);
            $child->appendChild($dom->createTextNode($value));
            $element->appendChild($child);
        }

        return $element;
    }

    /**
     * Create entry element.
     *
     * @param \DOMDocument $dom      DOM document.
     * @param string       $row_name Row element name.
     * @param array        $headers  Headers.
     * @param array        $row      Row data.
     * @return \DOMElement
     */
    private function create_entry_element($dom, $row_name, $headers, $row)
    {
        $element = $dom->createElement($row_name);

        foreach ($headers as $i => $header) {
            $field_name = $this->sanitize_element_name($header);
            $field_value = isset($row[$i]) ? $this->sanitize_value($row[$i]) : '';

            $child = $dom->createElement($field_name);
            $child->appendChild($dom->createTextNode($field_value));
            $element->appendChild($child);
        }

        return $element;
    }

    /**
     * Sanitize element name for XML.
     *
     * XML element names must:
     * - Start with a letter or underscore
     * - Contain only letters, digits, hyphens, underscores, and periods
     * - Not start with "xml" (case-insensitive)
     *
     * @param string $name Element name.
     * @return string Sanitized name.
     */
    private function sanitize_element_name($name)
    {
        // Convert to lowercase and trim.
        $name = strtolower(trim($name));

        // Replace spaces and invalid characters with underscores.
        $name = preg_replace('/[^a-z0-9_\-\.]/', '_', $name);

        // Remove consecutive underscores.
        $name = preg_replace('/_+/', '_', $name);

        // Ensure starts with letter or underscore.
        if (preg_match('/^[0-9\-\.]/', $name)) {
            $name = '_' . $name;
        }

        // Ensure not starting with "xml".
        if (preg_match('/^xml/i', $name)) {
            $name = '_' . $name;
        }

        // Ensure not empty.
        if (empty($name)) {
            $name = 'field';
        }

        return $name;
    }
}
