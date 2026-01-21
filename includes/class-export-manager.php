<?php
/**
 * Export Manager class.
 *
 * @package Forminator_Export_Formats
 */

namespace Forminator_Export_Formats;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Export_Manager
 *
 * Handles export requests and delegates to appropriate exporters.
 *
 * @since 1.0.0
 */
class Export_Manager
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
        // Listen for our custom export action.
        add_action('wp_loaded', array($this, 'listen_for_export'), 9);

        // Register AJAX handlers.
        add_action('wp_ajax_forminator_export_formats_download', array($this, 'ajax_download'));
    }

    /**
     * Listen for export requests.
     *
     * @return void
     */
    public function listen_for_export()
    {
        // Check if this is our export request.
        $action = filter_input(INPUT_POST, 'forminator_export_formats_action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if ('download' !== $action) {
            return;
        }

        // Verify user capabilities.
        if (!$this->verify_capabilities()) {
            wp_die(esc_html__('You are not allowed to export submissions.', 'forminator-export-formats'));
        }

        // Verify nonce.
        if (!$this->verify_nonce()) {
            wp_die(esc_html__('Security check failed.', 'forminator-export-formats'));
        }

        // Get export parameters.
        $form_id = filter_input(INPUT_POST, 'form_id', FILTER_VALIDATE_INT);
        $form_type = filter_input(INPUT_POST, 'form_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $format = filter_input(INPUT_POST, 'export_format', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $filter = filter_input(INPUT_POST, 'submission-filter', FILTER_VALIDATE_BOOLEAN);

        if (!$form_id || !$form_type) {
            wp_die(esc_html__('Invalid form parameters.', 'forminator-export-formats'));
        }

        // Default to CSV if format not specified.
        if (empty($format)) {
            $format = $this->options['default_format'];
        }

        // Get exporter.
        $exporter = $this->format_registry->get($format);
        if (!$exporter) {
            wp_die(esc_html__('Invalid export format.', 'forminator-export-formats'));
        }

        // Get format-specific options from POST.
        $format_options = $this->get_format_options_from_request($format);

        // Perform export.
        $this->do_export($form_id, $form_type, $exporter, $format_options, $filter);
    }

    /**
     * Handle AJAX download request.
     *
     * @return void
     */
    public function ajax_download()
    {
        // Verify capabilities.
        if (!$this->verify_capabilities()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'forminator-export-formats')));
        }

        // Verify nonce.
        check_ajax_referer('forminator_export_formats_nonce', 'nonce');

        // Get parameters.
        $form_id = filter_input(INPUT_POST, 'form_id', FILTER_VALIDATE_INT);
        $form_type = filter_input(INPUT_POST, 'form_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $format = filter_input(INPUT_POST, 'format', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (!$form_id || !$form_type || !$format) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'forminator-export-formats')));
        }

        // Get exporter.
        $exporter = $this->format_registry->get($format);
        if (!$exporter) {
            wp_send_json_error(array('message' => __('Invalid format.', 'forminator-export-formats')));
        }

        // Get format options.
        $format_options = $this->get_format_options_from_request($format);

        // Prepare export data.
        $export_data = $this->prepare_export_data($form_id, $form_type);
        if (is_wp_error($export_data)) {
            wp_send_json_error(array('message' => $export_data->get_error_message()));
        }

        // Generate export content.
        $content = $exporter->export(
            $export_data['headers'],
            $export_data['rows'],
            $export_data['meta'],
            $format_options
        );

        // Create temporary file.
        $filename = $this->generate_filename($export_data['meta'], $exporter);
        $temp_dir = $this->get_temp_dir();
        $filepath = $temp_dir . '/' . $filename;

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($filepath, $content);

        // Return download URL.
        $download_url = add_query_arg(
            array(
                'action' => 'forminator_export_formats_serve',
                'file' => basename($filepath),
                'nonce' => wp_create_nonce('forminator_export_formats_serve'),
                'format' => $format,
                'form_id' => $form_id,
            ),
            admin_url('admin-ajax.php')
        );

        wp_send_json_success(array('download_url' => $download_url));
    }

    /**
     * Perform the export and send file to browser.
     *
     * @param int                                  $form_id        Form ID.
     * @param string                               $form_type      Form type.
     * @param Exporters\Exporter_Interface         $exporter       Exporter instance.
     * @param array                                $format_options Format-specific options.
     * @param bool                                 $filter         Whether to use filters.
     * @return void
     */
    private function do_export($form_id, $form_type, $exporter, $format_options, $filter = false)
    {
        // Prepare export data.
        $export_data = $this->prepare_export_data($form_id, $form_type, $filter);
        if (is_wp_error($export_data)) {
            wp_die(esc_html($export_data->get_error_message()));
        }

        // Generate filename.
        $filename = $this->generate_filename($export_data['meta'], $exporter);

        // Log export.
        $this->log_export($form_id, count($export_data['rows']));

        /**
         * Action before export download.
         *
         * @param int    $form_id   Form ID.
         * @param string $form_type Form type.
         * @param string $format    Export format.
         */
        do_action('forminator_export_formats_before_download', $form_id, $form_type, $exporter->get_format_id());

        // Set headers.
        $this->set_download_headers($filename, $exporter->get_mime_type());

        // Output content.
        if ($exporter->supports_streaming()) {
            $exporter->stream(
                $export_data['headers'],
                $export_data['rows'],
                $export_data['meta'],
                $format_options
            );
        } else {
            $content = $exporter->export(
                $export_data['headers'],
                $export_data['rows'],
                $export_data['meta'],
                $format_options
            );
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $content;
        }

        exit;
    }

    /**
     * Prepare export data from Forminator.
     *
     * @param int    $form_id   Form ID.
     * @param string $form_type Form type.
     * @param bool   $filter    Whether to use filters.
     * @return array|\WP_Error Export data or error.
     */
    private function prepare_export_data($form_id, $form_type, $filter = false)
    {
        // Get form model.
        $model = \Forminator_Base_Form_Model::get_model($form_id);
        if (!$model instanceof \Forminator_Base_Form_Model) {
            return new \WP_Error('invalid_form', __('Form not found.', 'forminator-export-formats'));
        }

        // Map form type to internal format.
        $type = $this->map_form_type($form_type);

        // Use Forminator's export class to get data.
        $export_instance = \Forminator_Export::get_instance();

        // Get entries based on form type.
        switch ($type) {
            case 'quiz':
            case 'poll':
            case 'cform':
                $entries = $this->get_entries($form_id, $filter);
                break;
            default:
                return new \WP_Error('invalid_type', __('Invalid form type.', 'forminator-export-formats'));
        }

        // Get field mappers for cform.
        if ('cform' === $type) {
            $data = $this->prepare_cform_data($model, $entries);
        } elseif ('quiz' === $type) {
            $data = $this->prepare_quiz_data($model, $entries);
        } elseif ('poll' === $type) {
            $data = $this->prepare_poll_data($model, $entries);
        } else {
            $data = array(
                'headers' => array(),
                'rows' => array(),
            );
        }

        // Add metadata.
        $data['meta'] = array(
            'form_id' => $form_id,
            'form_name' => $model->name,
            'form_type' => $type,
            'export_date' => current_time('mysql'),
            'entries_count' => count($entries),
        );

        return $data;
    }

    /**
     * Prepare custom form data for export.
     *
     * @param \Forminator_Base_Form_Model $model   Form model.
     * @param array                       $entries Form entries.
     * @return array
     */
    private function prepare_cform_data($model, $entries)
    {
        $headers = array();
        $rows = array();

        // Get field mappers using reflection to access Forminator's method.
        $export = \Forminator_Export::get_instance();

        // Try to use the private method via closure binding.
        $get_mappers = function ($model) {
            return $this->get_custom_form_export_mappers($model);
        };
        $get_mappers = \Closure::bind($get_mappers, $export, \Forminator_Export::class);
        $mappers = $get_mappers($model);

        // Build headers from mappers.
        foreach ($mappers as $mapper) {
            if (!isset($mapper['sub_metas'])) {
                $headers[] = $mapper['label'];
            } else {
                foreach ($mapper['sub_metas'] as $sub_meta) {
                    $headers[] = $sub_meta['label'];
                }
            }
        }

        // Build rows from entries.
        foreach ($entries as $entry) {
            if (empty($entry->meta_data)) {
                continue;
            }

            $row = array();
            foreach ($mappers as $mapper) {
                if (isset($mapper['property'])) {
                    $property = $mapper['property'];
                    $row[] = property_exists($entry, $property) ? (string) $entry->$property : '';
                } elseif (!isset($mapper['sub_metas'])) {
                    $meta_value = $entry->get_meta($mapper['meta_key'], '');
                    $row[] = \Forminator_Form_Entry_Model::meta_value_to_string($mapper['type'], $meta_value);
                } else {
                    $meta_value = $entry->get_meta($mapper['meta_key'], array());
                    foreach ($mapper['sub_metas'] as $sub_meta) {
                        $sub_key = $sub_meta['key'];
                        $value = isset($meta_value[$sub_key]) ? $meta_value[$sub_key] : '';
                        $field_type = $mapper['type'] . '.' . $sub_key;
                        $row[] = \Forminator_Form_Entry_Model::meta_value_to_string($field_type, $value);
                    }
                }
            }

            $rows[] = $row;
        }

        return array(
            'headers' => $headers,
            'rows' => $rows,
        );
    }

    /**
     * Prepare quiz data for export.
     *
     * @param \Forminator_Base_Form_Model $model   Form model.
     * @param array                       $entries Form entries.
     * @return array
     */
    private function prepare_quiz_data($model, $entries)
    {
        $headers = array(
            __('Date', 'forminator-export-formats'),
            __('Question', 'forminator-export-formats'),
            __('Answer', 'forminator-export-formats'),
            __('Result', 'forminator-export-formats'),
        );

        $rows = array();

        foreach ($entries as $entry) {
            if ('nowrong' === $model->quiz_type) {
                $meta = isset($entry->meta_data['entry']['value'][0]['value']) ?
                    $entry->meta_data['entry']['value'][0]['value'] : array();

                if (isset($meta['answers'])) {
                    $first = true;
                    foreach ($meta['answers'] as $answer) {
                        $rows[] = array(
                            $first ? $entry->time_created : '',
                            !empty($answer['question']) ? $answer['question'] : '',
                            $answer['answer'],
                            isset($meta['result']['title']) ? $meta['result']['title'] : '',
                        );
                        $first = false;
                    }
                }
            } elseif ('knowledge' === $model->quiz_type) {
                $meta = isset($entry->meta_data['entry']['value']) ?
                    $entry->meta_data['entry']['value'] : array();

                if (!empty($meta)) {
                    $first = true;
                    foreach ($meta as $answer) {
                        $result = '';
                        if (!empty($answer['answer'])) {
                            $result = $answer['isCorrect'] ?
                                __('Correct', 'forminator-export-formats') :
                                __('Incorrect', 'forminator-export-formats');
                        }
                        $rows[] = array(
                            $first ? $entry->time_created : '',
                            !empty($answer['question']) ? $answer['question'] : '',
                            $answer['answer'],
                            $result,
                        );
                        $first = false;
                    }
                }
            }
        }

        return array(
            'headers' => $headers,
            'rows' => $rows,
        );
    }

    /**
     * Prepare poll data for export.
     *
     * @param \Forminator_Base_Form_Model $model   Form model.
     * @param array                       $entries Form entries.
     * @return array
     */
    private function prepare_poll_data($model, $entries)
    {
        $headers = array(
            __('Date', 'forminator-export-formats'),
            __('Answer', 'forminator-export-formats'),
            __('Extra', 'forminator-export-formats'),
        );

        $rows = array();

        $fields_array = $model->get_fields_as_array();
        $map_entries = \Forminator_Form_Entry_Model::map_polls_entries_for_export($model->id, $fields_array);

        foreach ($map_entries as $map_entry) {
            $entry = new \Forminator_Form_Entry_Model($map_entry['entry_id']);
            $extra = $entry->get_meta('extra', '');

            $rows[] = array(
                $entry->time_created,
                $map_entry['meta_value'],
                $extra,
            );
        }

        return array(
            'headers' => $headers,
            'rows' => $rows,
        );
    }

    /**
     * Get entries for a form.
     *
     * @param int  $form_id Form ID.
     * @param bool $filter  Whether to use filters.
     * @return array
     */
    private function get_entries($form_id, $filter = false)
    {
        if ($filter) {
            $filters = $this->get_request_filters();
            return \Forminator_Form_Entry_Model::get_all_entries($form_id, $filters);
        }

        return \Forminator_Form_Entry_Model::get_all_entries($form_id);
    }

    /**
     * Get request filters.
     *
     * @return array
     */
    private function get_request_filters()
    {
        $filters = array();

        // Get date filters from Forminator's export result class if available.
        if (class_exists('\Forminator_Export_Result')) {
            $export_result = new \Forminator_Export_Result();
            if (method_exists($export_result, 'request_filters')) {
                $filters = $export_result->request_filters();
            }
        }

        return $filters;
    }

    /**
     * Map form type to internal type.
     *
     * @param string $form_type Form type.
     * @return string
     */
    private function map_form_type($form_type)
    {
        $map = array(
            'forminator_forms' => 'cform',
            'forminator_polls' => 'poll',
            'forminator_quizzes' => 'quiz',
            'cform' => 'cform',
            'poll' => 'poll',
            'quiz' => 'quiz',
        );

        return isset($map[$form_type]) ? $map[$form_type] : 'cform';
    }

    /**
     * Generate filename for export.
     *
     * @param array                        $meta     Export metadata.
     * @param Exporters\Exporter_Interface $exporter Exporter instance.
     * @return string
     */
    private function generate_filename($meta, $exporter)
    {
        $parts = array(
            'forminator',
            isset($meta['form_name']) ? sanitize_title($meta['form_name']) : 'export',
            gmdate('ymdHis'),
        );

        $basename = implode('-', array_filter($parts));
        $extension = $exporter->get_file_extension();

        return $basename . '.' . $extension;
    }

    /**
     * Set download headers.
     *
     * @param string $filename  Filename.
     * @param string $mime_type MIME type.
     * @return void
     */
    private function set_download_headers($filename, $mime_type)
    {
        // Clean output buffer.
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
    }

    /**
     * Get format options from request.
     *
     * @param string $format Format ID.
     * @return array
     */
    private function get_format_options_from_request($format)
    {
        $options = array();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in caller.
        $post_data = $_POST;

        foreach ($post_data as $key => $value) {
            if (0 === strpos($key, 'export_option_' . $format . '_')) {
                $option_key = str_replace('export_option_' . $format . '_', '', $key);
                $options[$option_key] = sanitize_text_field($value);
            }
        }

        return $options;
    }

    /**
     * Verify user capabilities.
     *
     * @return bool
     */
    private function verify_capabilities()
    {
        if (function_exists('forminator_is_user_allowed')) {
            return forminator_is_user_allowed('forminator-entries');
        }

        return current_user_can('manage_options');
    }

    /**
     * Verify nonce.
     *
     * @return bool
     */
    private function verify_nonce()
    {
        $nonce = filter_input(INPUT_POST, '_forminator_export_formats_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        return wp_verify_nonce($nonce, 'forminator_export_formats');
    }

    /**
     * Log export.
     *
     * @param int $form_id Form ID.
     * @param int $count   Entries count.
     * @return void
     */
    private function log_export($form_id, $count)
    {
        $logs = get_option('forminator_exporter_log', array());

        if (!isset($logs[$form_id])) {
            $logs[$form_id] = array();
        }

        $logs[$form_id][] = array(
            'time' => current_time('timestamp'),
            'count' => $count,
        );

        update_option('forminator_exporter_log', $logs);
    }

    /**
     * Get temp directory.
     *
     * @return string
     */
    private function get_temp_dir()
    {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/forminator-export-formats-temp';

        if (!is_dir($temp_dir)) {
            wp_mkdir_p($temp_dir);

            // Add index.php for security.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents($temp_dir . '/index.php', '<?php // Silence is golden.');

            // Add .htaccess for Apache.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents($temp_dir . '/.htaccess', 'Deny from all');
        }

        return $temp_dir;
    }
}
