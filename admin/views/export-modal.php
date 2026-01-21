<?php
/**
 * Export modal template.
 *
 * @package Forminator_Export_Formats
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Get form info from request.
$form_id = filter_input(INPUT_GET, 'form_id', FILTER_VALIDATE_INT);
$form_type = get_option('forminator_submissions_form_type', 'forminator_forms');

// Get plugin options.
$options = get_option('forminator_export_formats_options', array());
$default_format = isset($options['default_format']) ? $options['default_format'] : 'csv';
$enabled_formats = isset($options['enabled_formats']) ? $options['enabled_formats'] : array('csv', 'excel', 'json', 'xml', 'pdf', 'html');

// Format info.
$format_info = array(
    'csv' => array(
        'name' => __('CSV', 'forminator-export-formats'),
        'desc' => __('Excel, Google Sheets', 'forminator-export-formats'),
        'icon' => 'dashicons-media-spreadsheet',
    ),
    'excel' => array(
        'name' => __('Excel', 'forminator-export-formats'),
        'desc' => __('Native .xlsx format', 'forminator-export-formats'),
        'icon' => 'dashicons-media-spreadsheet',
    ),
    'json' => array(
        'name' => __('JSON', 'forminator-export-formats'),
        'desc' => __('APIs, developers', 'forminator-export-formats'),
        'icon' => 'dashicons-editor-code',
    ),
    'xml' => array(
        'name' => __('XML', 'forminator-export-formats'),
        'desc' => __('System integration', 'forminator-export-formats'),
        'icon' => 'dashicons-editor-code',
    ),
    'pdf' => array(
        'name' => __('PDF', 'forminator-export-formats'),
        'desc' => __('Print, share', 'forminator-export-formats'),
        'icon' => 'dashicons-media-document',
    ),
    'html' => array(
        'name' => __('HTML', 'forminator-export-formats'),
        'desc' => __('View in browser', 'forminator-export-formats'),
        'icon' => 'dashicons-admin-site',
    ),
);
?>

<!-- Export Formats Modal Overlay -->
<div id="forminator-export-formats-modal" class="fef-modal-overlay" style="display: none;">
    <div class="fef-modal-container">
        <div class="fef-modal-box">
            <!-- Header -->
            <div class="fef-modal-header">
                <h2><?php esc_html_e('Export Submissions', 'forminator-export-formats'); ?></h2>
                <button type="button" class="fef-close-modal"
                    aria-label="<?php esc_attr_e('Close', 'forminator-export-formats'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>

            <!-- Body -->
            <div class="fef-modal-body">
                <form id="fef-export-form" method="post">
                    <?php wp_nonce_field('forminator_export_formats', '_forminator_export_formats_nonce'); ?>
                    <input type="hidden" name="forminator_export_formats_action" value="download">
                    <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
                    <input type="hidden" name="form_type" value="<?php echo esc_attr($form_type); ?>">

                    <!-- Format Selection -->
                    <p class="fef-label"><?php esc_html_e('Select Export Format', 'forminator-export-formats'); ?></p>
                    <div class="fef-format-grid">
                        <?php foreach ($format_info as $format_id => $info): ?>
                            <?php if (in_array($format_id, $enabled_formats, true)): ?>
                                <label
                                    class="fef-format-option <?php echo $format_id === $default_format ? 'selected' : ''; ?>">
                                    <input type="radio" name="export_format" value="<?php echo esc_attr($format_id); ?>" <?php checked($format_id, $default_format); ?>>
                                    <span class="fef-format-card">
                                        <span class="dashicons <?php echo esc_attr($info['icon']); ?>"></span>
                                        <span class="fef-format-name"><?php echo esc_html($info['name']); ?></span>
                                        <span class="fef-format-desc"><?php echo esc_html($info['desc']); ?></span>
                                    </span>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- Format Options Container -->
                    <div id="fef-format-options" style="display: none;">
                        <p class="fef-label"><?php esc_html_e('Format Options', 'forminator-export-formats'); ?></p>
                        <div id="fef-format-options-content"></div>
                    </div>

                    <!-- Use Current Filters -->
                    <div class="fef-filter-option">
                        <label>
                            <input type="checkbox" name="submission-filter" value="1">
                            <?php esc_html_e('Apply current submission filters', 'forminator-export-formats'); ?>
                        </label>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="fef-modal-footer">
                <button type="button" class="button fef-close-modal">
                    <?php esc_html_e('Cancel', 'forminator-export-formats'); ?>
                </button>
                <button type="submit" form="fef-export-form" class="button button-primary" id="fef-download-btn">
                    <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
                    <?php esc_html_e('Download', 'forminator-export-formats'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Modal Overlay */
    .fef-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 100000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .fef-modal-container {
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow: auto;
    }

    .fef-modal-box {
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
    }

    /* Header */
    .fef-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid #ddd;
    }

    .fef-modal-header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .fef-close-modal {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        color: #666;
    }

    .fef-close-modal:hover {
        color: #333;
    }

    /* Body */
    .fef-modal-body {
        padding: 24px;
    }

    .fef-label {
        font-weight: 600;
        margin-bottom: 12px;
        color: #333;
    }

    /* Format Grid */
    .fef-format-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-bottom: 20px;
    }

    .fef-format-option {
        cursor: pointer;
    }

    .fef-format-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .fef-format-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 15px 10px;
        border: 2px solid #ddd;
        border-radius: 4px;
        text-align: center;
        transition: all 0.2s;
        background: #fff;
    }

    .fef-format-option:hover .fef-format-card {
        border-color: #2271b1;
    }

    .fef-format-option.selected .fef-format-card,
    .fef-format-option input:checked+.fef-format-card {
        border-color: #2271b1;
        background: #f0f7fc;
    }

    .fef-format-card .dashicons {
        font-size: 28px;
        width: 28px;
        height: 28px;
        color: #666;
        margin-bottom: 8px;
    }

    .fef-format-option.selected .fef-format-card .dashicons,
    .fef-format-option input:checked+.fef-format-card .dashicons {
        color: #2271b1;
    }

    .fef-format-name {
        font-weight: 600;
        font-size: 13px;
        color: #333;
    }

    .fef-format-desc {
        font-size: 11px;
        color: #888;
        margin-top: 2px;
    }

    /* Format Options */
    #fef-format-options {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    /* Filter Option */
    .fef-filter-option {
        margin-top: 15px;
    }

    .fef-filter-option label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    /* Footer */
    .fef-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 16px 24px;
        border-top: 1px solid #ddd;
        background: #f9f9f9;
    }

    /* Responsive */
    @media (max-width: 600px) {
        .fef-format-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>