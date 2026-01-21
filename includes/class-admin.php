<?php
/**
 * Admin class.
 *
 * @package Forminator_Export_Formats
 */

namespace Forminator_Export_Formats;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Admin
 *
 * Handles all admin functionality including settings page and UI integration.
 *
 * @since 1.0.0
 */
class Admin
{

    /**
     * Format registry.
     *
     * @var Format_Registry
     */
    private $format_registry;

    /**
     * Plugin options.
     *
     * @var array
     */
    private $options;

    /**
     * Constructor.
     *
     * @param Format_Registry $format_registry Format registry instance.
     * @param array           $options         Plugin options.
     */
    public function __construct(Format_Registry $format_registry, array $options)
    {
        $this->format_registry = $format_registry;
        $this->options = $options;

        $this->register_hooks();
    }

    /**
     * Register hooks.
     *
     * @return void
     */
    private function register_hooks()
    {
        // Add settings page under Forminator menu.
        add_action('admin_menu', array($this, 'add_settings_page'), 99);

        // Register settings.
        add_action('admin_init', array($this, 'register_settings'));

        // Enqueue admin scripts and styles.
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        // Add export format selector to Forminator entries page.
        add_action('admin_footer', array($this, 'render_export_modal'));

        // Modify Forminator's export button.
        add_action('admin_footer', array($this, 'inject_export_button_script'));

        // AJAX handler for getting format options.
        add_action('wp_ajax_forminator_export_formats_get_options', array($this, 'ajax_get_format_options'));
    }

    /**
     * Add settings page.
     *
     * @return void
     */
    public function add_settings_page()
    {
        add_submenu_page(
            'forminator',
            __('Export Formats', 'forminator-export-formats'),
            __('Export Formats', 'forminator-export-formats'),
            'manage_options',
            'forminator-export-formats',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings.
     *
     * @return void
     */
    public function register_settings()
    {
        register_setting(
            'forminator_export_formats_options',
            'forminator_export_formats_options',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_options'),
            )
        );
    }

    /**
     * Sanitize options.
     *
     * @param array $input Input options.
     * @return array Sanitized options.
     */
    public function sanitize_options($input)
    {
        $sanitized = array();

        // Default format.
        $sanitized['default_format'] = isset($input['default_format']) ?
            sanitize_text_field($input['default_format']) : 'csv';

        // Enabled formats.
        $all_formats = array('csv', 'excel', 'json', 'xml', 'pdf', 'html');
        $sanitized['enabled_formats'] = isset($input['enabled_formats']) && is_array($input['enabled_formats']) ?
            array_intersect($input['enabled_formats'], $all_formats) : $all_formats;

        // CSV options.
        $sanitized['csv_delimiter'] = isset($input['csv_delimiter']) ?
            sanitize_text_field($input['csv_delimiter']) : ',';
        $sanitized['csv_enclosure'] = isset($input['csv_enclosure']) ?
            sanitize_text_field($input['csv_enclosure']) : '"';
        $sanitized['csv_bom'] = isset($input['csv_bom']);

        // Excel options.
        $sanitized['excel_autowidth'] = isset($input['excel_autowidth']);

        // JSON options.
        $sanitized['json_pretty'] = isset($input['json_pretty']);

        // XML options.
        $sanitized['xml_root'] = isset($input['xml_root']) ?
            sanitize_text_field($input['xml_root']) : 'entries';
        $sanitized['xml_row'] = isset($input['xml_row']) ?
            sanitize_text_field($input['xml_row']) : 'entry';

        // PDF options.
        $sanitized['pdf_orientation'] = isset($input['pdf_orientation']) &&
            in_array($input['pdf_orientation'], array('portrait', 'landscape'), true) ?
            $input['pdf_orientation'] : 'landscape';
        $sanitized['pdf_paper_size'] = isset($input['pdf_paper_size']) ?
            sanitize_text_field($input['pdf_paper_size']) : 'A4';

        // HTML options.
        $sanitized['html_theme'] = isset($input['html_theme']) &&
            in_array($input['html_theme'], array('light', 'dark', 'minimal', 'bordered'), true) ?
            $input['html_theme'] : 'light';

        return $sanitized;
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_assets($hook)
    {
        // Load on our settings page.
        $is_settings_page = ('forminator_page_forminator-export-formats' === $hook);

        // Load on Forminator entries pages.
        $is_forminator_page = (false !== strpos($hook, 'forminator'));

        if (!$is_settings_page && !$is_forminator_page) {
            return;
        }

        // Try to load Forminator's shared-ui styles if available.
        if (defined('FORMINATOR_PLUGIN_URL')) {
            $sui_version = defined('FORMINATOR_SUI_VERSION') ? FORMINATOR_SUI_VERSION : '2.12.24';
            wp_enqueue_style(
                'forminator-sui',
                FORMINATOR_PLUGIN_URL . 'assets/css/shared-ui.min.css',
                array(),
                $sui_version
            );
        }

        // Enqueue our styles.
        wp_enqueue_style(
            'forminator-export-formats-admin',
            FORMINATOR_EXPORT_FORMATS_URL . 'admin/css/admin.css',
            array(),
            FORMINATOR_EXPORT_FORMATS_VERSION
        );

        // Enqueue scripts.
        wp_enqueue_script(
            'forminator-export-formats-admin',
            FORMINATOR_EXPORT_FORMATS_URL . 'admin/js/admin.js',
            array('jquery'),
            FORMINATOR_EXPORT_FORMATS_VERSION,
            true
        );

        // Localize script.
        wp_localize_script(
            'forminator-export-formats-admin',
            'forminatorExportFormats',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('forminator_export_formats'),
                'formats' => $this->format_registry->get_formats_info(),
                'defaultFormat' => $this->options['default_format'],
                'i18n' => array(
                    'exportTitle' => __('Export Submissions', 'forminator-export-formats'),
                    'selectFormat' => __('Select Format', 'forminator-export-formats'),
                    'formatOptions' => __('Format Options', 'forminator-export-formats'),
                    'download' => __('Download', 'forminator-export-formats'),
                    'cancel' => __('Cancel', 'forminator-export-formats'),
                    'exporting' => __('Exporting...', 'forminator-export-formats'),
                    'useFilters' => __('Use current filters', 'forminator-export-formats'),
                ),
            )
        );
    }

    /**
     * Render settings page.
     *
     * @return void
     */
    public function render_settings_page()
    {
        // Check permissions.
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'forminator-export-formats'));
        }

        include FORMINATOR_EXPORT_FORMATS_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Render export modal.
     *
     * @return void
     */
    public function render_export_modal()
    {
        $screen = get_current_screen();

        // Only on Forminator entries page.
        if (!$screen || false === strpos($screen->id, 'forminator-entries')) {
            return;
        }

        include FORMINATOR_EXPORT_FORMATS_DIR . 'admin/views/export-modal.php';
    }

    /**
     * Inject script to modify Forminator's export button.
     * 
     * Note: The actual hijacking is now done in admin/js/admin.js
     * This method is kept for potential future use.
     *
     * @return void
     */
    public function inject_export_button_script()
    {
        // All export button handling is now done in admin/js/admin.js
        // This hook is kept for future use if needed.
    }

    /**
     * AJAX handler to get format options form.
     *
     * @return void
     */
    public function ajax_get_format_options()
    {
        check_ajax_referer('forminator_export_formats', 'nonce');

        $format = isset($_POST['format']) ? sanitize_text_field(wp_unslash($_POST['format'])) : '';

        if (empty($format)) {
            wp_send_json_error(array('message' => __('Invalid format.', 'forminator-export-formats')));
        }

        $exporter = $this->format_registry->get($format);

        if (!$exporter) {
            wp_send_json_error(array('message' => __('Format not found.', 'forminator-export-formats')));
        }

        $fields = $exporter->get_options_fields();
        $defaults = $exporter->get_default_options();

        ob_start();
        $this->render_options_form($format, $fields, $defaults);
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Render options form for a format.
     *
     * @param string $format   Format ID.
     * @param array  $fields   Field definitions.
     * @param array  $defaults Default values.
     * @return void
     */
    private function render_options_form($format, $fields, $defaults)
    {
        if (empty($fields)) {
            echo '<p class="sui-description">' . esc_html__('No additional options available for this format.', 'forminator-export-formats') . '</p>';
            return;
        }

        foreach ($fields as $field) {
            $field_id = 'export_option_' . $format . '_' . $field['id'];
            $field_name = $field_id;
            $field_value = isset($defaults[$field['id']]) ? $defaults[$field['id']] : '';

            echo '<div class="sui-form-field">';

            switch ($field['type']) {
                case 'select':
                    echo '<label for="' . esc_attr($field_id) . '" class="sui-label">' . esc_html($field['label']) . '</label>';
                    echo '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" class="sui-select">';
                    foreach ($field['options'] as $value => $label) {
                        $selected = ($value === $field_value) ? ' selected' : '';
                        echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
                    }
                    echo '</select>';
                    break;

                case 'checkbox':
                    echo '<label class="sui-toggle">';
                    echo '<input type="checkbox" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="1"' . checked($field_value, true, false) . '>';
                    echo '<span class="sui-toggle-slider" aria-hidden="true"></span>';
                    echo '<span class="sui-toggle-label">' . esc_html($field['label']) . '</span>';
                    echo '</label>';
                    if (isset($field['description'])) {
                        echo '<span class="sui-description">' . esc_html($field['description']) . '</span>';
                    }
                    break;

                case 'text':
                default:
                    echo '<label for="' . esc_attr($field_id) . '" class="sui-label">' . esc_html($field['label']) . '</label>';
                    echo '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($field_value) . '" class="sui-form-control">';
                    if (isset($field['description'])) {
                        echo '<span class="sui-description">' . esc_html($field['description']) . '</span>';
                    }
                    break;
            }

            echo '</div>';
        }
    }
}
