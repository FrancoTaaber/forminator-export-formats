<?php
/**
 * Main plugin class.
 *
 * @package Forminator_Export_Formats
 */

namespace Forminator_Export_Formats;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Plugin
 *
 * Main plugin class that initializes all components.
 *
 * @since 1.0.0
 */
final class Plugin
{

    /**
     * Plugin instance.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Export Manager instance.
     *
     * @var Export_Manager|null
     */
    private $export_manager = null;

    /**
     * Admin instance.
     *
     * @var Admin|null
     */
    private $admin = null;

    /**
     * Format Registry instance.
     *
     * @var Format_Registry|null
     */
    private $format_registry = null;

    /**
     * Plugin options.
     *
     * @var array
     */
    private $options = array();

    /**
     * Get plugin instance.
     *
     * @return Plugin
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->load_options();
        $this->load_dependencies();
        $this->init_components();
        $this->register_hooks();
    }

    /**
     * Prevent cloning.
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization.
     *
     * @throws \Exception Always throws exception.
     */
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    /**
     * Load plugin options.
     *
     * @return void
     */
    private function load_options()
    {
        $defaults = array(
            'default_format' => 'csv',
            'enabled_formats' => array('csv', 'excel', 'json', 'xml', 'pdf', 'html'),
            'include_entry_id' => false,
            'csv_delimiter' => ',',
            'csv_enclosure' => '"',
            'csv_bom' => true,
            'excel_autowidth' => true,
            'json_pretty' => true,
            'xml_root' => 'entries',
            'xml_row' => 'entry',
            'pdf_orientation' => 'landscape',
            'pdf_paper_size' => 'A4',
            'html_theme' => 'light',
        );

        $saved_options = get_option('forminator_export_formats_options', array());
        $this->options = wp_parse_args($saved_options, $defaults);
    }

    /**
     * Load required dependencies.
     *
     * @return void
     */
    private function load_dependencies()
    {
        // Load interfaces and abstract classes first.
        require_once FORMINATOR_EXPORT_FORMATS_DIR . 'includes/exporters/interface-exporter.php';
        require_once FORMINATOR_EXPORT_FORMATS_DIR . 'includes/exporters/abstract-exporter.php';

        // Load core classes.
        require_once FORMINATOR_EXPORT_FORMATS_DIR . 'includes/class-format-registry.php';
        require_once FORMINATOR_EXPORT_FORMATS_DIR . 'includes/class-export-manager.php';
        require_once FORMINATOR_EXPORT_FORMATS_DIR . 'includes/class-admin.php';
        require_once FORMINATOR_EXPORT_FORMATS_DIR . 'includes/class-plugin-updater.php';
        require_once FORMINATOR_EXPORT_FORMATS_DIR . 'includes/class-logger.php';

        // Load exporters.
        require_once FORMINATOR_EXPORT_FORMATS_DIR . 'includes/exporters/class-csv-exporter.php';
        require_once FORMINATOR_EXPORT_FORMATS_DIR . 'includes/exporters/class-excel-exporter.php';
        require_once FORMINATOR_EXPORT_FORMATS_DIR . 'includes/exporters/class-json-exporter.php';
        require_once FORMINATOR_EXPORT_FORMATS_DIR . 'includes/exporters/class-xml-exporter.php';
        require_once FORMINATOR_EXPORT_FORMATS_DIR . 'includes/exporters/class-pdf-exporter.php';
        require_once FORMINATOR_EXPORT_FORMATS_DIR . 'includes/exporters/class-html-exporter.php';
    }

    /**
     * Initialize components.
     *
     * @return void
     */
    private function init_components()
    {
        // Initialize format registry and register exporters.
        $this->format_registry = new Format_Registry();
        $this->register_exporters();

        // Initialize export manager.
        $this->export_manager = new Export_Manager($this->format_registry, $this->options);

        // Initialize admin.
        if (is_admin()) {
            $this->admin = new Admin($this->format_registry, $this->options);
        }

        // Initialize plugin updater for auto-updates from GitHub.
        new Plugin_Updater(
            FORMINATOR_EXPORT_FORMATS_FILE,
            FORMINATOR_EXPORT_FORMATS_VERSION,
            array(
                'github_repo' => 'FrancoTaaber/forminator-export-formats',
            )
        );
    }

    /**
     * Register all exporters.
     *
     * @return void
     */
    private function register_exporters()
    {
        $enabled_formats = $this->options['enabled_formats'];

        if (in_array('csv', $enabled_formats, true)) {
            $this->format_registry->register(new Exporters\CSV_Exporter($this->options));
        }

        if (in_array('excel', $enabled_formats, true)) {
            $this->format_registry->register(new Exporters\Excel_Exporter($this->options));
        }

        if (in_array('json', $enabled_formats, true)) {
            $this->format_registry->register(new Exporters\JSON_Exporter($this->options));
        }

        if (in_array('xml', $enabled_formats, true)) {
            $this->format_registry->register(new Exporters\XML_Exporter($this->options));
        }

        if (in_array('pdf', $enabled_formats, true)) {
            $this->format_registry->register(new Exporters\PDF_Exporter($this->options));
        }

        if (in_array('html', $enabled_formats, true)) {
            $this->format_registry->register(new Exporters\HTML_Exporter($this->options));
        }

        /**
         * Allow third-party plugins to register custom exporters.
         *
         * @param Format_Registry $format_registry The format registry instance.
         * @param array           $options         Plugin options.
         */
        do_action('forminator_export_formats_register_exporters', $this->format_registry, $this->options);
    }

    /**
     * Register hooks.
     *
     * @return void
     */
    private function register_hooks()
    {
        // Nothing needed here for now - components register their own hooks.
    }

    /**
     * Get plugin options.
     *
     * @return array
     */
    public function get_options()
    {
        return $this->options;
    }

    /**
     * Get a specific option value.
     *
     * @param string $key     Option key.
     * @param mixed  $default Default value.
     * @return mixed
     */
    public function get_option($key, $default = null)
    {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    /**
     * Get the format registry.
     *
     * @return Format_Registry
     */
    public function get_format_registry()
    {
        return $this->format_registry;
    }

    /**
     * Get the export manager.
     *
     * @return Export_Manager
     */
    public function get_export_manager()
    {
        return $this->export_manager;
    }
}
