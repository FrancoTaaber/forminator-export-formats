<?php
/**
 * Settings page template.
 *
 * Uses WordPress native admin styles for compatibility.
 *
 * @package Forminator_Export_Formats
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

$options = get_option('forminator_export_formats_options', array());

// Default values.
$defaults = array(
    'default_format' => 'csv',
    'enabled_formats' => array('csv', 'excel', 'json', 'xml', 'pdf', 'html'),
    'include_entry_id' => false,
    'csv_delimiter' => ',',
    'csv_bom' => true,
    'json_pretty' => true,
    'xml_root' => 'entries',
    'xml_row' => 'entry',
    'pdf_orientation' => 'landscape',
    'pdf_paper_size' => 'A4',
    'html_theme' => 'light',
);

$options = wp_parse_args($options, $defaults);

$all_formats = array(
    'csv' => __('CSV', 'forminator-export-formats'),
    'excel' => __('Excel (.xlsx)', 'forminator-export-formats'),
    'json' => __('JSON', 'forminator-export-formats'),
    'xml' => __('XML', 'forminator-export-formats'),
    'pdf' => __('PDF', 'forminator-export-formats'),
    'html' => __('HTML', 'forminator-export-formats'),
);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if (isset($_GET['settings-updated'])):  // phpcs:ignore ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved successfully.', 'forminator-export-formats'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="options.php">
        <?php settings_fields('forminator_export_formats_options'); ?>

        <!-- General Settings -->
        <div class="card" style="max-width: 800px;">
            <h2><?php esc_html_e('General Settings', 'forminator-export-formats'); ?></h2>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label
                            for="default_format"><?php esc_html_e('Default Export Format', 'forminator-export-formats'); ?></label>
                    </th>
                    <td>
                        <select id="default_format" name="forminator_export_formats_options[default_format]">
                            <?php foreach ($all_formats as $format_id => $format_name): ?>
                                <option value="<?php echo esc_attr($format_id); ?>" <?php selected($options['default_format'], $format_id); ?>>
                                    <?php echo esc_html($format_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('The format that will be pre-selected when exporting.', 'forminator-export-formats'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Enabled Formats', 'forminator-export-formats'); ?></th>
                    <td>
                        <fieldset>
                            <?php foreach ($all_formats as $format_id => $format_name): ?>
                                <label style="display: inline-block; margin-right: 15px; margin-bottom: 5px;">
                                    <input type="checkbox" name="forminator_export_formats_options[enabled_formats][]"
                                        value="<?php echo esc_attr($format_id); ?>" <?php checked(in_array($format_id, $options['enabled_formats'], true)); ?>>
                                    <?php echo esc_html($format_name); ?>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                        <p class="description">
                            <?php esc_html_e('Select which export formats should be available.', 'forminator-export-formats'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Include Entry ID', 'forminator-export-formats'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="forminator_export_formats_options[include_entry_id]" value="1"
                                <?php checked($options['include_entry_id'], true); ?>>
                            <?php esc_html_e('Add Entry ID as first column in exports', 'forminator-export-formats'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When enabled, each export will include the unique submission ID as the first column.', 'forminator-export-formats'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- CSV Settings -->
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2><?php esc_html_e('CSV Settings', 'forminator-export-formats'); ?></h2>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label
                            for="csv_delimiter"><?php esc_html_e('Delimiter', 'forminator-export-formats'); ?></label>
                    </th>
                    <td>
                        <select id="csv_delimiter" name="forminator_export_formats_options[csv_delimiter]">
                            <option value="," <?php selected($options['csv_delimiter'], ','); ?>>
                                <?php esc_html_e('Comma (,)', 'forminator-export-formats'); ?>
                            </option>
                            <option value=";" <?php selected($options['csv_delimiter'], ';'); ?>>
                                <?php esc_html_e('Semicolon (;)', 'forminator-export-formats'); ?>
                            </option>
                            <option value="	" <?php selected($options['csv_delimiter'], "\t"); ?>>
                                <?php esc_html_e('Tab', 'forminator-export-formats'); ?>
                            </option>
                            <option value="|" <?php selected($options['csv_delimiter'], '|'); ?>>
                                <?php esc_html_e('Pipe (|)', 'forminator-export-formats'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Excel Compatibility', 'forminator-export-formats'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="forminator_export_formats_options[csv_bom]" value="1" <?php checked($options['csv_bom']); ?>>
                            <?php esc_html_e('Add BOM for Excel compatibility', 'forminator-export-formats'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Adds UTF-8 Byte Order Mark for better Excel compatibility.', 'forminator-export-formats'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- JSON Settings -->
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2><?php esc_html_e('JSON Settings', 'forminator-export-formats'); ?></h2>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Formatting', 'forminator-export-formats'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="forminator_export_formats_options[json_pretty]" value="1" <?php checked($options['json_pretty']); ?>>
                            <?php esc_html_e('Pretty print (formatted with indentation)', 'forminator-export-formats'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <!-- XML Settings -->
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2><?php esc_html_e('XML Settings', 'forminator-export-formats'); ?></h2>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label
                            for="xml_root"><?php esc_html_e('Root Element Name', 'forminator-export-formats'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="xml_root" name="forminator_export_formats_options[xml_root]"
                            value="<?php echo esc_attr($options['xml_root']); ?>" class="regular-text"
                            placeholder="entries">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label
                            for="xml_row"><?php esc_html_e('Entry Element Name', 'forminator-export-formats'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="xml_row" name="forminator_export_formats_options[xml_row]"
                            value="<?php echo esc_attr($options['xml_row']); ?>" class="regular-text"
                            placeholder="entry">
                    </td>
                </tr>
            </table>
        </div>

        <!-- PDF Settings -->
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2><?php esc_html_e('PDF Settings', 'forminator-export-formats'); ?></h2>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label
                            for="pdf_orientation"><?php esc_html_e('Page Orientation', 'forminator-export-formats'); ?></label>
                    </th>
                    <td>
                        <select id="pdf_orientation" name="forminator_export_formats_options[pdf_orientation]">
                            <option value="portrait" <?php selected($options['pdf_orientation'], 'portrait'); ?>>
                                <?php esc_html_e('Portrait', 'forminator-export-formats'); ?>
                            </option>
                            <option value="landscape" <?php selected($options['pdf_orientation'], 'landscape'); ?>>
                                <?php esc_html_e('Landscape', 'forminator-export-formats'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label
                            for="pdf_paper_size"><?php esc_html_e('Paper Size', 'forminator-export-formats'); ?></label>
                    </th>
                    <td>
                        <select id="pdf_paper_size" name="forminator_export_formats_options[pdf_paper_size]">
                            <option value="A4" <?php selected($options['pdf_paper_size'], 'A4'); ?>>A4</option>
                            <option value="A3" <?php selected($options['pdf_paper_size'], 'A3'); ?>>A3</option>
                            <option value="Letter" <?php selected($options['pdf_paper_size'], 'Letter'); ?>>Letter
                            </option>
                            <option value="Legal" <?php selected($options['pdf_paper_size'], 'Legal'); ?>>Legal
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- HTML Settings -->
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2><?php esc_html_e('HTML Settings', 'forminator-export-formats'); ?></h2>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label
                            for="html_theme"><?php esc_html_e('Default Theme', 'forminator-export-formats'); ?></label>
                    </th>
                    <td>
                        <select id="html_theme" name="forminator_export_formats_options[html_theme]">
                            <option value="light" <?php selected($options['html_theme'], 'light'); ?>>
                                <?php esc_html_e('Light', 'forminator-export-formats'); ?>
                            </option>
                            <option value="dark" <?php selected($options['html_theme'], 'dark'); ?>>
                                <?php esc_html_e('Dark', 'forminator-export-formats'); ?>
                            </option>
                            <option value="minimal" <?php selected($options['html_theme'], 'minimal'); ?>>
                                <?php esc_html_e('Minimal', 'forminator-export-formats'); ?>
                            </option>
                            <option value="bordered" <?php selected($options['html_theme'], 'bordered'); ?>>
                                <?php esc_html_e('Bordered', 'forminator-export-formats'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Save Button -->
        <p class="submit" style="margin-top: 20px;">
            <?php submit_button(__('Save Settings', 'forminator-export-formats'), 'primary', 'submit', false); ?>
        </p>

    </form>
</div>