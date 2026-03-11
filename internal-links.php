<?php
/**
 * Plugin Name: AI Internal Linker
 * Plugin URI:  https://github.com/yourusername/ai-internal-linker
 * Description: Automatically builds internal links using AI. Scans your content, finds relevant internal posts, and naturally weaves them into your articles.
 * Version:     1.0.18
 * Author:      Lê Lương Nghĩa
 * Author URI:  https://yourwebsite.com
 * License:     GPL-2.0+
 * Text Domain: ai-internal-linker
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Current plugin version.
 */
define('AIL_VERSION', '1.0.18');

/**
 * The code that runs during plugin activation.
 */
function activate_ai_internal_linker()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-ail-activator.php';
	AIL_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_ai_internal_linker()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-ail-deactivator.php';
	AIL_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_ai_internal_linker');
register_deactivation_hook(__FILE__, 'deactivate_ai_internal_linker');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-ail-loader.php';

/**
 * Add custom cron schedules
 */
function ail_add_cron_schedules($schedules)
{
	if (!isset($schedules['ail_five_minutes'])) {
		$schedules['ail_five_minutes'] = array('interval' => 300, 'display' => 'Every 5 Minutes (AIL)');
	}
	if (!isset($schedules['ail_half_hourly'])) {
		$schedules['ail_half_hourly'] = array('interval' => 1800, 'display' => 'Every 30 Minutes (AIL)');
	}
	if (!isset($schedules['ail_six_hourly'])) {
		$schedules['ail_six_hourly'] = array('interval' => 21600, 'display' => 'Every 6 Hours (AIL)');
	}
	return $schedules;
}
add_filter('cron_schedules', 'ail_add_cron_schedules');

/**
 * Begins execution of the plugin.
 */
function run_ai_internal_linker()
{
	$plugin = new AIL_Loader();
	$plugin->run();

	// Initialize Sweeper
	if (!class_exists('AIL_Sweeper')) {
		require_once plugin_dir_path(__FILE__) . 'includes/class-ail-sweeper.php';
	}
	new AIL_Sweeper();

	// Initialize Link Indexer
	if (!class_exists('AIL_Link_Indexer')) {
		require_once plugin_dir_path(__FILE__) . 'includes/class-ail-link-indexer.php';
	}
	new AIL_Link_Indexer();

	// Check GitHub Updater Settings
	$github_url = get_option('ail_github_updater_url', '');
	$github_token = get_option('ail_github_updater_token', '');

	if (!empty($github_url)) {
		// Parse GitHub URL: https://github.com/AzEvent/plugin-internal-links -> username = AzEvent, repo = plugin-internal-links
		$parsed_url = parse_url($github_url, PHP_URL_PATH);
		$path_parts = explode('/', trim((string) $parsed_url, '/'));

		if (count($path_parts) >= 2) {
			$username = $path_parts[0];
			$repo = $path_parts[1];

			if (!class_exists('AIL_GitHub_Updater')) {
				require_once plugin_dir_path(__FILE__) . 'includes/updater/class-ail-github-updater.php';
			}

			if (is_admin()) {
				$updater = new AIL_GitHub_Updater(__FILE__);
				$updater->set_username($username);
				$updater->set_repository($repo);
				if (!empty($github_token)) {
					$updater->authorize($github_token);
				}
				$updater->initialize();
			}
		}
	}
}
run_ai_internal_linker();

/**
 * Global API function to inject links into content.
 * Can be called by other plugins.
 *
 * @param string $content HTML content to process.
 * @param int    $post_id Context post ID (optional).
 * @return string Content with injected links.
 */
function ail_process_content($content, $post_id = 0)
{
	// If no post ID provided, try global post
	if (!$post_id) {
		$post_id = get_the_ID();
	}

	if (!class_exists('AIL_Injector')) {
		require_once plugin_dir_path(__FILE__) . 'includes/class-ail-injector.php';
	}

	$injector = new AIL_Injector();
	return $injector->inject_links($content, $post_id);
}
