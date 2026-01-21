<?php
/**
 * Plugin Update Checker.
 *
 * Handles self-hosted plugin updates from GitHub releases or custom server.
 *
 * @package Forminator_Export_Formats
 */

namespace Forminator_Export_Formats;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Plugin_Updater
 *
 * Checks for plugin updates from a remote server or GitHub and enables
 * updates through the WordPress dashboard.
 *
 * @since 1.0.0
 */
class Plugin_Updater
{

    /**
     * Plugin slug.
     *
     * @var string
     */
    private $plugin_slug;

    /**
     * Plugin file path.
     *
     * @var string
     */
    private $plugin_file;

    /**
     * Current version.
     *
     * @var string
     */
    private $version;

    /**
     * Update server URL.
     *
     * @var string
     */
    private $update_url;

    /**
     * GitHub repository (owner/repo format).
     *
     * @var string
     */
    private $github_repo;

    /**
     * Cache key for update data.
     *
     * @var string
     */
    private $cache_key = 'fef_update_check';

    /**
     * Cache duration in seconds.
     *
     * @var int
     */
    private $cache_duration = 43200; // 12 hours

    /**
     * Constructor.
     *
     * @param string $plugin_file Path to main plugin file.
     * @param string $version     Current plugin version.
     * @param array  $args        Optional configuration arguments.
     */
    public function __construct($plugin_file, $version, $args = array())
    {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->version = $version;

        // Configuration.
        $this->update_url = isset($args['update_url']) ? $args['update_url'] : '';
        $this->github_repo = isset($args['github_repo']) ? $args['github_repo'] : '';

        $this->init();
    }

    /**
     * Initialize hooks.
     *
     * @return void
     */
    private function init()
    {
        // Hook into WordPress update system.
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_action('upgrader_process_complete', array($this, 'purge_cache'), 10, 2);

        // Add "Check for updates" link on plugins page.
        add_filter('plugin_row_meta', array($this, 'add_check_update_link'), 10, 2);
        add_action('admin_init', array($this, 'handle_manual_check'));
    }

    /**
     * Check for plugin updates.
     *
     * @param object $transient Update transient data.
     * @return object Modified transient.
     */
    public function check_for_update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote_data = $this->get_remote_data();

        if ($remote_data && version_compare($this->version, $remote_data['version'], '<')) {
            $plugin_data = array(
                'id' => $this->plugin_slug,
                'slug' => dirname($this->plugin_slug),
                'plugin' => $this->plugin_slug,
                'new_version' => $remote_data['version'],
                'url' => $remote_data['homepage'],
                'package' => $remote_data['download_url'],
                'icons' => array(),
                'banners' => array(),
                'banners_rtl' => array(),
                'tested' => $remote_data['tested'] ?? '',
                'requires_php' => $remote_data['requires_php'] ?? '7.4',
                'compatibility' => new \stdClass(),
            );

            $transient->response[$this->plugin_slug] = (object) $plugin_data;
        } else {
            // No update available, but add to no_update to prevent re-checking.
            $transient->no_update[$this->plugin_slug] = (object) array(
                'id' => $this->plugin_slug,
                'slug' => dirname($this->plugin_slug),
                'plugin' => $this->plugin_slug,
                'new_version' => $this->version,
                'url' => '',
                'package' => '',
            );
        }

        return $transient;
    }

    /**
     * Plugin information for the WordPress plugin details popup.
     *
     * @param false|object|array $result The result object or array.
     * @param string             $action The type of information being requested.
     * @param object             $args   Plugin API arguments.
     * @return false|object
     */
    public function plugin_info($result, $action, $args)
    {
        if ('plugin_information' !== $action) {
            return $result;
        }

        if (dirname($this->plugin_slug) !== $args->slug) {
            return $result;
        }

        $remote_data = $this->get_remote_data();

        if (!$remote_data) {
            return $result;
        }

        $plugin_info = array(
            'name' => $remote_data['name'] ?? 'Forminator Export Formats',
            'slug' => dirname($this->plugin_slug),
            'version' => $remote_data['version'],
            'author' => $remote_data['author'] ?? '',
            'author_profile' => $remote_data['author_profile'] ?? '',
            'requires' => $remote_data['requires'] ?? '5.8',
            'tested' => $remote_data['tested'] ?? '',
            'requires_php' => $remote_data['requires_php'] ?? '7.4',
            'sections' => array(
                'description' => $remote_data['description'] ?? '',
                'changelog' => $remote_data['changelog'] ?? '',
            ),
            'download_link' => $remote_data['download_url'],
            'homepage' => $remote_data['homepage'] ?? '',
            'last_updated' => $remote_data['last_updated'] ?? '',
        );

        return (object) $plugin_info;
    }

    /**
     * Get remote update data.
     *
     * @param bool $force_check Force a fresh check, ignoring cache.
     * @return array|false Update data or false on failure.
     */
    private function get_remote_data($force_check = false)
    {
        // Check cache first.
        if (!$force_check) {
            $cached = get_transient($this->cache_key);
            if (false !== $cached) {
                return $cached;
            }
        }

        $remote_data = false;

        // Try GitHub first if configured.
        if (!empty($this->github_repo)) {
            $remote_data = $this->get_github_release_data();
        }

        // Fall back to custom update URL.
        if (!$remote_data && !empty($this->update_url)) {
            $remote_data = $this->get_update_server_data();
        }

        // Cache the result.
        if ($remote_data) {
            set_transient($this->cache_key, $remote_data, $this->cache_duration);
        }

        return $remote_data;
    }

    /**
     * Get update data from GitHub releases.
     *
     * @return array|false
     */
    private function get_github_release_data()
    {
        $api_url = sprintf(
            'https://api.github.com/repos/%s/releases/latest',
            $this->github_repo
        );

        $response = wp_remote_get(
            $api_url,
            array(
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version'),
                ),
                'timeout' => 15,
            )
        );

        if (is_wp_error($response)) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if (200 !== $status_code) {
            return false;
        }

        $release = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($release['tag_name'])) {
            return false;
        }

        // Find the ZIP asset.
        $download_url = '';
        if (!empty($release['assets'])) {
            foreach ($release['assets'] as $asset) {
                if (
                    'application/zip' === $asset['content_type'] ||
                    str_ends_with($asset['name'], '.zip')
                ) {
                    $download_url = $asset['browser_download_url'];
                    break;
                }
            }
        }

        // Fallback to zipball URL.
        if (empty($download_url) && !empty($release['zipball_url'])) {
            $download_url = $release['zipball_url'];
        }

        if (empty($download_url)) {
            return false;
        }

        // Parse version from tag (remove 'v' prefix if present).
        $version = ltrim($release['tag_name'], 'v');

        return array(
            'name' => 'Forminator Export Formats',
            'version' => $version,
            'download_url' => $download_url,
            'homepage' => $release['html_url'] ?? '',
            'changelog' => $this->parse_changelog($release['body'] ?? ''),
            'description' => __('Extend Forminator with multiple export formats: CSV, Excel, JSON, XML, PDF, and HTML.', 'forminator-export-formats'),
            'last_updated' => $release['published_at'] ?? '',
            'requires' => '5.8',
            'tested' => '6.7',
            'requires_php' => '7.4',
            'author' => $release['author']['login'] ?? '',
        );
    }

    /**
     * Get update data from custom update server.
     *
     * @return array|false
     */
    private function get_update_server_data()
    {
        $response = wp_remote_get(
            $this->update_url,
            array(
                'timeout' => 15,
                'headers' => array(
                    'Accept' => 'application/json',
                ),
            )
        );

        if (is_wp_error($response)) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if (200 !== $status_code) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($data['version']) || empty($data['download_url'])) {
            return false;
        }

        return $data;
    }

    /**
     * Parse changelog from GitHub release body.
     *
     * @param string $body Release body content.
     * @return string HTML formatted changelog.
     */
    private function parse_changelog($body)
    {
        if (empty($body)) {
            return '';
        }

        // Convert markdown-ish content to HTML.
        $changelog = esc_html($body);
        $changelog = nl2br($changelog);
        $changelog = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $changelog);
        $changelog = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $changelog);
        $changelog = preg_replace('/^- /m', '• ', $changelog);

        return '<div class="changelog">' . $changelog . '</div>';
    }

    /**
     * Purge update cache after plugin update.
     *
     * @param \WP_Upgrader $upgrader Upgrader instance.
     * @param array        $options  Update options.
     * @return void
     */
    public function purge_cache($upgrader, $options)
    {
        if ('update' === $options['action'] && 'plugin' === $options['type']) {
            delete_transient($this->cache_key);
        }
    }

    /**
     * Add "Check for updates" link on plugins page.
     *
     * @param array  $links Plugin row meta links.
     * @param string $file  Plugin file.
     * @return array Modified links.
     */
    public function add_check_update_link($links, $file)
    {
        if ($this->plugin_slug !== $file) {
            return $links;
        }

        $check_url = wp_nonce_url(
            add_query_arg(
                array(
                    'fef_check_update' => '1',
                ),
                admin_url('plugins.php')
            ),
            'fef_check_update'
        );

        $links[] = sprintf(
            '<a href="%s">%s</a>',
            esc_url($check_url),
            esc_html__('Check for updates', 'forminator-export-formats')
        );

        return $links;
    }

    /**
     * Handle manual update check.
     *
     * @return void
     */
    public function handle_manual_check()
    {
        if (!isset($_GET['fef_check_update'])) {
            return;
        }

        if (!current_user_can('update_plugins')) {
            return;
        }

        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'fef_check_update')) {
            return;
        }

        // Force a fresh check.
        delete_transient($this->cache_key);
        delete_site_transient('update_plugins');

        // Redirect back to plugins page with a message.
        wp_safe_redirect(
            add_query_arg(
                array(
                    'fef_update_checked' => '1',
                ),
                admin_url('plugins.php')
            )
        );
        exit;
    }
}
