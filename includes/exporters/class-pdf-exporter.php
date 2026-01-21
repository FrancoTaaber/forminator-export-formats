<?php
/**
 * PDF Exporter class.
 *
 * @package Forminator_Export_Formats
 */

namespace Forminator_Export_Formats\Exporters;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PDF_Exporter
 *
 * Exports data to PDF format using a simple HTML-to-PDF approach.
 * For complex PDFs, TCPDF can be added as an optional dependency.
 *
 * @since 1.0.0
 */
class PDF_Exporter extends Abstract_Exporter
{

    /**
     * Get format ID.
     *
     * @return string
     */
    public function get_format_id()
    {
        return 'pdf';
    }

    /**
     * Get format name.
     *
     * @return string
     */
    public function get_format_name()
    {
        return __('PDF', 'forminator-export-formats');
    }

    /**
     * Get format description.
     *
     * @return string
     */
    public function get_format_description()
    {
        return __('Portable Document Format. Best for printing and sharing formatted reports.', 'forminator-export-formats');
    }

    /**
     * Get MIME type.
     *
     * @return string
     */
    public function get_mime_type()
    {
        return 'application/pdf';
    }

    /**
     * Get file extension.
     *
     * @return string
     */
    public function get_file_extension()
    {
        return 'pdf';
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
     * Get default options.
     *
     * @return array
     */
    public function get_default_options()
    {
        return array(
            'orientation' => isset($this->plugin_options['pdf_orientation']) ? $this->plugin_options['pdf_orientation'] : 'landscape',
            'paper_size' => isset($this->plugin_options['pdf_paper_size']) ? $this->plugin_options['pdf_paper_size'] : 'A4',
            'title' => '',
            'include_date' => true,
            'include_count' => true,
            'font_size' => 10,
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
                'id' => 'orientation',
                'type' => 'select',
                'label' => __('Page Orientation', 'forminator-export-formats'),
                'options' => array(
                    'portrait' => __('Portrait', 'forminator-export-formats'),
                    'landscape' => __('Landscape', 'forminator-export-formats'),
                ),
                'default' => 'landscape',
            ),
            array(
                'id' => 'paper_size',
                'type' => 'select',
                'label' => __('Paper Size', 'forminator-export-formats'),
                'options' => array(
                    'A4' => 'A4',
                    'A3' => 'A3',
                    'Letter' => 'Letter',
                    'Legal' => 'Legal',
                ),
                'default' => 'A4',
            ),
            array(
                'id' => 'title',
                'type' => 'text',
                'label' => __('Document Title', 'forminator-export-formats'),
                'description' => __('Leave empty to use form name.', 'forminator-export-formats'),
                'default' => '',
            ),
            array(
                'id' => 'include_date',
                'type' => 'checkbox',
                'label' => __('Include Export Date', 'forminator-export-formats'),
                'description' => __('Show export date in the document header.', 'forminator-export-formats'),
                'default' => true,
            ),
        );
    }

    /**
     * Export data to PDF.
     *
     * This implementation creates a styled HTML document and uses
     * a simple PDF generation approach. For production use with
     * many entries, consider using TCPDF or DOMPDF.
     *
     * @param array $headers Headers.
     * @param array $rows    Data rows.
     * @param array $meta    Metadata.
     * @param array $options Options.
     * @return string PDF content.
     */
    public function export(array $headers, array $rows, array $meta, array $options = array())
    {
        $options = $this->merge_options($options);

        // Check if TCPDF is available.
        if ($this->has_tcpdf()) {
            return $this->export_with_tcpdf($headers, $rows, $meta, $options);
        }

        // Fallback to simple HTML-based PDF (browser print-friendly).
        return $this->export_simple_html($headers, $rows, $meta, $options);
    }

    /**
     * Check if TCPDF is available.
     *
     * @return bool
     */
    private function has_tcpdf()
    {
        return class_exists('TCPDF');
    }

    /**
     * Export using TCPDF library.
     *
     * @param array $headers Headers.
     * @param array $rows    Data rows.
     * @param array $meta    Metadata.
     * @param array $options Options.
     * @return string PDF content.
     */
    private function export_with_tcpdf($headers, $rows, $meta, $options)
    {
        // Map orientation.
        $orientation = 'landscape' === $options['orientation'] ? 'L' : 'P';

        // Create PDF.
        $pdf = new \TCPDF($orientation, 'mm', $options['paper_size'], true, 'UTF-8', false);

        // Set document information.
        $title = !empty($options['title']) ? $options['title'] : ($meta['form_name'] ?? 'Export');
        $pdf->SetCreator('Forminator Export Formats');
        $pdf->SetAuthor(get_bloginfo('name'));
        $pdf->SetTitle($title);

        // Remove default header/footer.
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins.
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);

        // Add page.
        $pdf->AddPage();

        // Set font.
        $pdf->SetFont('helvetica', '', $options['font_size']);

        // Generate HTML table.
        $html = $this->generate_table_html($headers, $rows, $meta, $options);

        // Write HTML.
        $pdf->writeHTML($html, true, false, true, false, '');

        // Return PDF content.
        return $pdf->Output('', 'S');
    }

    /**
     * Export as simple HTML (fallback for systems without TCPDF).
     *
     * Note: This returns HTML that is print-friendly and can be saved as PDF
     * using browser's "Print to PDF" feature. For actual PDF binary output,
     * TCPDF or DOMPDF is required.
     *
     * @param array $headers Headers.
     * @param array $rows    Data rows.
     * @param array $meta    Metadata.
     * @param array $options Options.
     * @return string HTML content (styled for print).
     */
    private function export_simple_html($headers, $rows, $meta, $options)
    {
        $title = !empty($options['title']) ? $options['title'] : ($meta['form_name'] ?? 'Export');

        $html = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>' . esc_html($title) . '</title>
	<style>
		@page {
			size: ' . esc_attr($options['paper_size']) . ' ' . esc_attr($options['orientation']) . ';
			margin: 1cm;
		}
		body {
			font-family: Arial, Helvetica, sans-serif;
			font-size: ' . (int) $options['font_size'] . 'pt;
			line-height: 1.4;
			color: #333;
		}
		.header {
			text-align: center;
			margin-bottom: 20px;
			padding-bottom: 10px;
			border-bottom: 2px solid #333;
		}
		.header h1 {
			margin: 0 0 5px 0;
			font-size: 18pt;
		}
		.header .meta {
			font-size: 10pt;
			color: #666;
		}
		table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 10px;
		}
		th, td {
			border: 1px solid #ccc;
			padding: 6px 8px;
			text-align: left;
			vertical-align: top;
		}
		th {
			background-color: #f5f5f5;
			font-weight: bold;
		}
		tr:nth-child(even) {
			background-color: #fafafa;
		}
		@media print {
			body { -webkit-print-color-adjust: exact; }
			thead { display: table-header-group; }
			tr { page-break-inside: avoid; }
		}
	</style>
</head>
<body>
	<div class="header">
		<h1>' . esc_html($title) . '</h1>';

        if ($options['include_date'] || $options['include_count']) {
            $html .= '<div class="meta">';
            $parts = array();

            if ($options['include_date']) {
                $parts[] = sprintf(
                    /* translators: %s: Export date */
                    __('Exported: %s', 'forminator-export-formats'),
                    wp_date(get_option('date_format') . ' ' . get_option('time_format'))
                );
            }

            if ($options['include_count']) {
                $parts[] = sprintf(
                    /* translators: %d: Number of entries */
                    _n('%d entry', '%d entries', count($rows), 'forminator-export-formats'),
                    count($rows)
                );
            }

            $html .= esc_html(implode(' | ', $parts));
            $html .= '</div>';
        }

        $html .= '</div>';

        // Table.
        $html .= '<table>';
        $html .= '<thead><tr>';

        foreach ($headers as $header) {
            $html .= '<th>' . esc_html($header) . '</th>';
        }

        $html .= '</tr></thead>';
        $html .= '<tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . esc_html($this->sanitize_value($cell)) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Generate HTML table for TCPDF.
     *
     * @param array $headers Headers.
     * @param array $rows    Data rows.
     * @param array $meta    Metadata.
     * @param array $options Options.
     * @return string HTML table.
     */
    private function generate_table_html($headers, $rows, $meta, $options)
    {
        $title = !empty($options['title']) ? $options['title'] : ($meta['form_name'] ?? 'Export');

        $html = '<h2 style="text-align:center;">' . esc_html($title) . '</h2>';

        if ($options['include_date']) {
            $html .= '<p style="text-align:center;font-size:10px;color:#666;">';
            $html .= sprintf(
                /* translators: %s: Export date */
                esc_html__('Exported: %s', 'forminator-export-formats'),
                wp_date(get_option('date_format') . ' ' . get_option('time_format'))
            );
            $html .= '</p>';
        }

        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%;border-collapse:collapse;">';
        $html .= '<thead><tr style="background-color:#f0f0f0;">';

        foreach ($headers as $header) {
            $html .= '<th style="font-weight:bold;text-align:left;">' . esc_html($header) . '</th>';
        }

        $html .= '</tr></thead>';
        $html .= '<tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . esc_html($this->sanitize_value($cell)) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }
}
