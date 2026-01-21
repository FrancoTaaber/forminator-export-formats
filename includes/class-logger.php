<?php
/**
 * Logger class for error handling and debugging.
 *
 * @package Forminator_Export_Formats
 */

namespace Forminator_Export_Formats;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Logger
 *
 * Handles logging of errors, warnings, and debug information.
 * Integrates with WP_DEBUG and provides admin notices for critical errors.
 *
 * @since 1.0.0
 */
class Logger
{

    /**
     * Log levels.
     */
    const LEVEL_ERROR = 'error';
    const LEVEL_WARNING = 'warning';
    const LEVEL_INFO = 'info';
    const LEVEL_DEBUG = 'debug';

    /**
     * Option key for storing admin notices.
     *
     * @var string
     */
    private static $notices_option = 'forminator_export_formats_notices';

    /**
     * Log a message.
     *
     * @param string $message Log message.
     * @param string $level   Log level (error, warning, info, debug).
     * @param array  $context Additional context data.
     * @return void
     */
    public static function log($message, $level = self::LEVEL_INFO, $context = array())
    {
        // Only log if WP_DEBUG is enabled.
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            // Always log errors regardless of WP_DEBUG.
            if (self::LEVEL_ERROR !== $level) {
                return;
            }
        }

        // Format the log message.
        $formatted = self::format_message($message, $level, $context);

        // Log to PHP error log.
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log($formatted);

        // Store critical errors for admin display.
        if (self::LEVEL_ERROR === $level) {
            self::store_admin_notice($message, $level);
        }
    }

    /**
     * Log an error.
     *
     * @param string $message Error message.
     * @param array  $context Additional context.
     * @return void
     */
    public static function error($message, $context = array())
    {
        self::log($message, self::LEVEL_ERROR, $context);
    }

    /**
     * Log a warning.
     *
     * @param string $message Warning message.
     * @param array  $context Additional context.
     * @return void
     */
    public static function warning($message, $context = array())
    {
        self::log($message, self::LEVEL_WARNING, $context);
    }

    /**
     * Log an info message.
     *
     * @param string $message Info message.
     * @param array  $context Additional context.
     * @return void
     */
    public static function info($message, $context = array())
    {
        self::log($message, self::LEVEL_INFO, $context);
    }

    /**
     * Log a debug message.
     *
     * @param string $message Debug message.
     * @param array  $context Additional context.
     * @return void
     */
    public static function debug($message, $context = array())
    {
        self::log($message, self::LEVEL_DEBUG, $context);
    }

    /**
     * Format a log message.
     *
     * @param string $message Log message.
     * @param string $level   Log level.
     * @param array  $context Additional context.
     * @return string Formatted message.
     */
    private static function format_message($message, $level, $context)
    {
        $timestamp = wp_date('Y-m-d H:i:s');
        $level_label = strtoupper($level);

        $formatted = sprintf(
            '[%s] [Forminator Export Formats] [%s] %s',
            $timestamp,
            $level_label,
            $message
        );

        // Add context if available.
        if (!empty($context)) {
            $formatted .= ' | Context: ' . wp_json_encode($context);
        }

        return $formatted;
    }

    /**
     * Store an admin notice for display.
     *
     * @param string $message Notice message.
     * @param string $level   Notice level.
     * @return void
     */
    private static function store_admin_notice($message, $level)
    {
        $notices = get_option(self::$notices_option, array());

        // Limit to last 5 notices.
        if (count($notices) >= 5) {
            array_shift($notices);
        }

        $notices[] = array(
            'message' => $message,
            'level' => $level,
            'timestamp' => time(),
        );

        update_option(self::$notices_option, $notices);
    }

    /**
     * Display admin notices.
     *
     * @return void
     */
    public static function display_admin_notices()
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        $notices = get_option(self::$notices_option, array());

        if (empty($notices)) {
            return;
        }

        foreach ($notices as $notice) {
            // Only show notices from the last 24 hours.
            if ((time() - $notice['timestamp']) > DAY_IN_SECONDS) {
                continue;
            }

            $class = 'error' === $notice['level'] ? 'notice-error' : 'notice-warning';

            printf(
                '<div class="notice %s is-dismissible"><p><strong>%s:</strong> %s</p></div>',
                esc_attr($class),
                esc_html__('Forminator Export Formats', 'forminator-export-formats'),
                esc_html($notice['message'])
            );
        }

        // Clear notices after display.
        delete_option(self::$notices_option);
    }

    /**
     * Clear all stored notices.
     *
     * @return void
     */
    public static function clear_notices()
    {
        delete_option(self::$notices_option);
    }

    /**
     * Initialize logger hooks.
     *
     * @return void
     */
    public static function init()
    {
        add_action('admin_notices', array(__CLASS__, 'display_admin_notices'));
    }
}
