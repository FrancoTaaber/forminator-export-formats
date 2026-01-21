<?php
/**
 * Excel Exporter class.
 *
 * @package Forminator_Export_Formats
 */

namespace Forminator_Export_Formats\Exporters;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Excel_Exporter
 *
 * Exports data to Excel XLSX format.
 * Uses a lightweight XML-based approach without external dependencies.
 *
 * @since 1.0.0
 */
class Excel_Exporter extends Abstract_Exporter
{

    /**
     * Get format ID.
     *
     * @return string
     */
    public function get_format_id()
    {
        return 'excel';
    }

    /**
     * Get format name.
     *
     * @return string
     */
    public function get_format_name()
    {
        return __('Excel', 'forminator-export-formats');
    }

    /**
     * Get format description.
     *
     * @return string
     */
    public function get_format_description()
    {
        return __('Microsoft Excel spreadsheet (.xlsx). Native format for Excel 2007 and later.', 'forminator-export-formats');
    }

    /**
     * Get MIME type.
     *
     * @return string
     */
    public function get_mime_type()
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    /**
     * Get file extension.
     *
     * @return string
     */
    public function get_file_extension()
    {
        return 'xlsx';
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
            'sheet_name' => __('Submissions', 'forminator-export-formats'),
            'freeze_row' => true,
            'auto_width' => isset($this->plugin_options['excel_autowidth']) ? $this->plugin_options['excel_autowidth'] : true,
            'bold_headers' => true,
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
                'id' => 'sheet_name',
                'type' => 'text',
                'label' => __('Sheet Name', 'forminator-export-formats'),
                'default' => __('Submissions', 'forminator-export-formats'),
            ),
            array(
                'id' => 'freeze_row',
                'type' => 'checkbox',
                'label' => __('Freeze Header Row', 'forminator-export-formats'),
                'description' => __('Keep the header row visible when scrolling.', 'forminator-export-formats'),
                'default' => true,
            ),
            array(
                'id' => 'bold_headers',
                'type' => 'checkbox',
                'label' => __('Bold Headers', 'forminator-export-formats'),
                'description' => __('Make header row text bold.', 'forminator-export-formats'),
                'default' => true,
            ),
        );
    }

    /**
     * Export data to Excel.
     *
     * @param array $headers Headers.
     * @param array $rows    Data rows.
     * @param array $meta    Metadata.
     * @param array $options Options.
     * @return string Excel file content.
     */
    public function export(array $headers, array $rows, array $meta, array $options = array())
    {
        $options = $this->merge_options($options);

        // Create temporary directory for XLSX parts.
        $temp_dir = $this->create_temp_dir();
        if (!$temp_dir) {
            return '';
        }

        try {
            // Create XLSX structure.
            $this->create_xlsx_structure($temp_dir, $headers, $rows, $options);

            // Create ZIP archive.
            $xlsx_content = $this->create_xlsx_archive($temp_dir);

            // Cleanup.
            $this->cleanup_temp_dir($temp_dir);

            return $xlsx_content;
        } catch (\Exception $e) {
            $this->cleanup_temp_dir($temp_dir);
            return '';
        }
    }

    /**
     * Create temporary directory.
     *
     * @return string|false Directory path or false on failure.
     */
    private function create_temp_dir()
    {
        $upload_dir = wp_upload_dir();
        $temp_base = $upload_dir['basedir'] . '/forminator-export-formats-temp';

        if (!is_dir($temp_base)) {
            wp_mkdir_p($temp_base);
        }

        $temp_dir = $temp_base . '/xlsx-' . wp_generate_password(12, false);

        if (wp_mkdir_p($temp_dir)) {
            return $temp_dir;
        }

        return false;
    }

    /**
     * Cleanup temporary directory.
     *
     * @param string $dir Directory path.
     * @return void
     */
    private function cleanup_temp_dir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                wp_delete_file($file->getRealPath());
            }
        }

        rmdir($dir);
    }

    /**
     * Create XLSX directory structure and files.
     *
     * @param string $dir     Temp directory.
     * @param array  $headers Headers.
     * @param array  $rows    Data rows.
     * @param array  $options Options.
     * @return void
     */
    private function create_xlsx_structure($dir, $headers, $rows, $options)
    {
        // Create directories.
        wp_mkdir_p($dir . '/_rels');
        wp_mkdir_p($dir . '/docProps');
        wp_mkdir_p($dir . '/xl/_rels');
        wp_mkdir_p($dir . '/xl/worksheets');

        // Create [Content_Types].xml.
        $this->write_content_types($dir);

        // Create _rels/.rels.
        $this->write_rels($dir);

        // Create docProps/app.xml.
        $this->write_app_props($dir);

        // Create docProps/core.xml.
        $this->write_core_props($dir);

        // Create xl/_rels/workbook.xml.rels.
        $this->write_workbook_rels($dir);

        // Create xl/styles.xml.
        $this->write_styles($dir, $options);

        // Create xl/workbook.xml.
        $this->write_workbook($dir, $options);

        // Create xl/sharedStrings.xml and xl/worksheets/sheet1.xml.
        $this->write_sheet_and_strings($dir, $headers, $rows, $options);
    }

    /**
     * Write [Content_Types].xml.
     *
     * @param string $dir Directory.
     * @return void
     */
    private function write_content_types($dir)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
	<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
	<Default Extension="xml" ContentType="application/xml"/>
	<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
	<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
	<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
	<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
	<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
	<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>';

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($dir . '/[Content_Types].xml', $xml);
    }

    /**
     * Write _rels/.rels.
     *
     * @param string $dir Directory.
     * @return void
     */
    private function write_rels($dir)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
	<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
	<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
	<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>';

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($dir . '/_rels/.rels', $xml);
    }

    /**
     * Write docProps/app.xml.
     *
     * @param string $dir Directory.
     * @return void
     */
    private function write_app_props($dir)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">
	<Application>Forminator Export Formats</Application>
	<AppVersion>1.0</AppVersion>
</Properties>';

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($dir . '/docProps/app.xml', $xml);
    }

    /**
     * Write docProps/core.xml.
     *
     * @param string $dir Directory.
     * @return void
     */
    private function write_core_props($dir)
    {
        $date = gmdate('Y-m-d\TH:i:s\Z');
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
	<dc:creator>Forminator</dc:creator>
	<dcterms:created xsi:type="dcterms:W3CDTF">' . $date . '</dcterms:created>
	<dcterms:modified xsi:type="dcterms:W3CDTF">' . $date . '</dcterms:modified>
</cp:coreProperties>';

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($dir . '/docProps/core.xml', $xml);
    }

    /**
     * Write xl/_rels/workbook.xml.rels.
     *
     * @param string $dir Directory.
     * @return void
     */
    private function write_workbook_rels($dir)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
	<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
	<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
	<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($dir . '/xl/_rels/workbook.xml.rels', $xml);
    }

    /**
     * Write xl/styles.xml.
     *
     * @param string $dir     Directory.
     * @param array  $options Options.
     * @return void
     */
    private function write_styles($dir, $options)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
	<fonts count="2">
		<font>
			<sz val="11"/>
			<name val="Calibri"/>
		</font>
		<font>
			<b/>
			<sz val="11"/>
			<name val="Calibri"/>
		</font>
	</fonts>
	<fills count="2">
		<fill><patternFill patternType="none"/></fill>
		<fill><patternFill patternType="gray125"/></fill>
	</fills>
	<borders count="1">
		<border><left/><right/><top/><bottom/><diagonal/></border>
	</borders>
	<cellStyleXfs count="1">
		<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
	</cellStyleXfs>
	<cellXfs count="2">
		<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
		<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
	</cellXfs>
</styleSheet>';

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($dir . '/xl/styles.xml', $xml);
    }

    /**
     * Write xl/workbook.xml.
     *
     * @param string $dir     Directory.
     * @param array  $options Options.
     * @return void
     */
    private function write_workbook($dir, $options)
    {
        $sheet_name = htmlspecialchars($options['sheet_name'], ENT_XML1, 'UTF-8');

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
	<sheets>
		<sheet name="' . $sheet_name . '" sheetId="1" r:id="rId1"/>
	</sheets>
</workbook>';

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($dir . '/xl/workbook.xml', $xml);
    }

    /**
     * Write sheet and shared strings.
     *
     * @param string $dir     Directory.
     * @param array  $headers Headers.
     * @param array  $rows    Data rows.
     * @param array  $options Options.
     * @return void
     */
    private function write_sheet_and_strings($dir, $headers, $rows, $options)
    {
        $shared_strings = array();
        $string_index = 0;
        $string_map = array();

        // Collect all unique strings.
        $all_rows = array_merge(array($headers), $rows);

        foreach ($all_rows as $row) {
            foreach ($row as $cell) {
                $cell = $this->ensure_utf8($this->sanitize_value($cell));
                if (!isset($string_map[$cell])) {
                    $string_map[$cell] = $string_index;
                    $shared_strings[$string_index] = $cell;
                    $string_index++;
                }
            }
        }

        // Write sharedStrings.xml.
        $this->write_shared_strings($dir, $shared_strings);

        // Write sheet1.xml.
        $this->write_worksheet($dir, $headers, $rows, $string_map, $options);
    }

    /**
     * Write xl/sharedStrings.xml.
     *
     * @param string $dir     Directory.
     * @param array  $strings Shared strings.
     * @return void
     */
    private function write_shared_strings($dir, $strings)
    {
        $count = count($strings);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $count . '" uniqueCount="' . $count . '">';

        foreach ($strings as $string) {
            $xml .= '<si><t>' . htmlspecialchars($string, ENT_XML1, 'UTF-8') . '</t></si>';
        }

        $xml .= '</sst>';

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($dir . '/xl/sharedStrings.xml', $xml);
    }

    /**
     * Write xl/worksheets/sheet1.xml.
     *
     * @param string $dir        Directory.
     * @param array  $headers    Headers.
     * @param array  $rows       Data rows.
     * @param array  $string_map String to index map.
     * @param array  $options    Options.
     * @return void
     */
    private function write_worksheet($dir, $headers, $rows, $string_map, $options)
    {
        $col_count = count($headers);
        $row_count = count($rows) + 1;

        // Calculate column reference for dimension.
        $last_col = $this->get_column_letter($col_count);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        // Dimension.
        $xml .= '<dimension ref="A1:' . $last_col . $row_count . '"/>';

        // Freeze pane for header row.
        if ($options['freeze_row']) {
            $xml .= '<sheetViews><sheetView tabSelected="1" workbookViewId="0">';
            $xml .= '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>';
            $xml .= '</sheetView></sheetViews>';
        }

        // Sheet data.
        $xml .= '<sheetData>';

        // Header row.
        $xml .= '<row r="1">';
        $col_num = 1;
        foreach ($headers as $header) {
            $header = $this->ensure_utf8($this->sanitize_value($header));
            $col_ref = $this->get_column_letter($col_num) . '1';
            $str_index = $string_map[$header];
            $style = $options['bold_headers'] ? ' s="1"' : '';
            $xml .= '<c r="' . $col_ref . '" t="s"' . $style . '><v>' . $str_index . '</v></c>';
            $col_num++;
        }
        $xml .= '</row>';

        // Data rows.
        $row_num = 2;
        foreach ($rows as $row) {
            $xml .= '<row r="' . $row_num . '">';
            $col_num = 1;
            foreach ($row as $cell) {
                $cell = $this->ensure_utf8($this->sanitize_value($cell));
                $col_ref = $this->get_column_letter($col_num) . $row_num;
                $str_index = $string_map[$cell];
                $xml .= '<c r="' . $col_ref . '" t="s"><v>' . $str_index . '</v></c>';
                $col_num++;
            }
            $xml .= '</row>';
            $row_num++;
        }

        $xml .= '</sheetData>';
        $xml .= '</worksheet>';

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($dir . '/xl/worksheets/sheet1.xml', $xml);
    }

    /**
     * Get column letter from number.
     *
     * @param int $num Column number (1-based).
     * @return string Column letter (A, B, ..., Z, AA, AB, ...).
     */
    private function get_column_letter($num)
    {
        $letter = '';
        while ($num > 0) {
            $num--;
            $letter = chr(65 + ($num % 26)) . $letter;
            $num = (int) ($num / 26);
        }
        return $letter;
    }

    /**
     * Create XLSX archive from directory.
     *
     * @param string $dir Directory path.
     * @return string ZIP content.
     */
    private function create_xlsx_archive($dir)
    {
        $zip_file = $dir . '.xlsx';

        $zip = new \ZipArchive();
        if (true !== $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            return '';
        }

        // Add all files recursively.
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $file_path = $file->getRealPath();
            $relative_path = substr($file_path, strlen($dir) + 1);
            $zip->addFile($file_path, $relative_path);
        }

        $zip->close();

        // Read content.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $content = file_get_contents($zip_file);

        // Delete zip file.
        wp_delete_file($zip_file);

        return $content;
    }
}
