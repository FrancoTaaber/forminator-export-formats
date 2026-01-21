<?php
/**
 * Plugin Name: Forminator Export Formats
 * Plugin URI: https://github.com/FrancoTaaber/forminator-export-formats
 * Description: Extend Forminator with multiple export formats: CSV, Excel, JSON, XML, PDF, and HTML.
 * Version: 1.1.1
 * Requires at least: 5.8
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * Author: Franco Taaber
 * Author URI: https://francotaaber.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: forminator-export-formats
 * Domain Path: /languages
 *
 * @package Forminator_Export_Formats
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
	exit;
}

// Plugin version.
define('FORMINATOR_EXPORT_FORMATS_VERSION', '1.1.1');

// Plugin file path.
define('FORMINATOR_EXPORT_FORMATS_FILE', __FILE__);

// Plugin directory path.
define('FORMINATOR_EXPORT_FORMATS_DIR', plugin_dir_path(__FILE__));

// Plugin directory URL.
define('FORMINATOR_EXPORT_FORMATS_URL', plugin_dir_url(__FILE__));

// Minimum Forminator version required.
define('FORMINATOR_EXPORT_FORMATS_MIN_FORMINATOR', '1.20.0');

// Minimum PHP version required.
define('FORMINATOR_EXPORT_FORMATS_MIN_PHP', '7.4');

// Load Composer autoloader if available (for TCPDF and other dependencies).
$composer_autoload = FORMINATOR_EXPORT_FORMATS_DIR . 'vendor/autoload.php';
if (file_exists($composer_autoload)) {
	require_once $composer_autoload;
}

/**
 * Check if PHP version meets minimum requirements.
 *
 * @return bool
 */
function forminator_export_formats_php_version_check()
{
	return version_compare(PHP_VERSION, FORMINATOR_EXPORT_FORMATS_MIN_PHP, '>=');
}

/**
 * Check if Forminator is active and meets minimum version.
 *
 * @return bool
 */
function forminator_export_formats_forminator_check()
{
	if (!class_exists('Forminator')) {
		return false;
	}

	if (!defined('FORMINATOR_VERSION')) {
		return false;
	}

	return version_compare(FORMINATOR_VERSION, FORMINATOR_EXPORT_FORMATS_MIN_FORMINATOR, '>=');
}

/**
 * Display admin notice if PHP version is too low.
 *
 * @return void
 */
function forminator_export_formats_php_notice()
{
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required PHP version, 2: Current PHP version */
				esc_html__('Forminator Export Formats requires PHP %1$s or higher. Your current PHP version is %2$s. Please upgrade PHP to use this plugin.', 'forminator-export-formats'),
				esc_html(FORMINATOR_EXPORT_FORMATS_MIN_PHP),
				esc_html(PHP_VERSION)
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Display admin notice if Forminator is not active or version is too low.
 *
 * @return void
 */
function forminator_export_formats_forminator_notice()
{
	?>
	<div class="notice notice-error">
		<p>
			<?php
			if (!class_exists('Forminator')) {
				esc_html_e('Forminator Export Formats requires Forminator plugin to be installed and activated.', 'forminator-export-formats');
			} else {
				printf(
					/* translators: 1: Required Forminator version, 2: Current Forminator version */
					esc_html__('Forminator Export Formats requires Forminator %1$s or higher. Your current version is %2$s. Please update Forminator.', 'forminator-export-formats'),
					esc_html(FORMINATOR_EXPORT_FORMATS_MIN_FORMINATOR),
					esc_html(FORMINATOR_VERSION)
				);
			}
			?>
		</p>
	</div>
	<?php
}

/**
 * Initialize the plugin.
 *
 * @return void
 */
function forminator_export_formats_init()
{
	// Check PHP version.
	if (!forminator_export_formats_php_version_check()) {
		add_action('admin_notices', 'forminator_export_formats_php_notice');
		return;
	}

	// Check Forminator.
	if (!forminator_export_formats_forminator_check()) {
		add_action('admin_notices', 'forminator_export_formats_forminator_notice');
		return;
	}

	// Load plugin text domain.
	load_plugin_textdomain(
		'forminator-export-formats',
		false,
		dirname(plugin_basename(__FILE__)) . '/languages'
	);

	// Load the main plugin class.
	require_once FORMINATOR_EXPORT_FORMATS_DIR . 'includes/class-plugin.php';

	// Initialize the plugin.
	Forminator_Export_Formats\Plugin::get_instance();
}
add_action('plugins_loaded', 'forminator_export_formats_init', 20);

/**
 * Plugin activation hook.
 *
 * @return void
 */
function forminator_export_formats_activate()
{
	// Check PHP version.
	if (!forminator_export_formats_php_version_check()) {
		deactivate_plugins(plugin_basename(__FILE__));
		wp_die(
			sprintf(
				/* translators: %s: Required PHP version */
				esc_html__('Forminator Export Formats requires PHP %s or higher.', 'forminator-export-formats'),
				esc_html(FORMINATOR_EXPORT_FORMATS_MIN_PHP)
			)
		);
	}

	// Set default options.
	$default_options = array(
		'default_format' => 'csv',
		'enabled_formats' => array('csv', 'excel', 'json', 'xml', 'pdf', 'html'),
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

	// Only set defaults if not already set.
	if (false === get_option('forminator_export_formats_options')) {
		add_option('forminator_export_formats_options', $default_options);
	}

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'forminator_export_formats_activate');

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function forminator_export_formats_deactivate()
{
	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'forminator_export_formats_deactivate');

/**
 * Add settings link on plugins page.
 *
 * @param array $links Plugin action links.
 * @return array
 */
function forminator_export_formats_plugin_links($links)
{
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		admin_url('admin.php?page=forminator-export-formats'),
		esc_html__('Settings', 'forminator-export-formats')
	);
	array_unshift($links, $settings_link);
	return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'forminator_export_formats_plugin_links');
