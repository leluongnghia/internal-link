<?php
/**
 * Plugin Name: AI Internal Linker
 * Plugin URI:  https://github.com/yourusername/ai-internal-linker
 * Description: Automatically builds internal links using AI. Scans your content, finds relevant internal posts, and naturally weaves them into your articles.
 * Version:     1.0.6
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
define('AIL_VERSION', '1.0.0');

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
 * Begins execution of the plugin.
 */
function run_ai_internal_linker()
{
	$plugin = new AIL_Loader();
	$plugin->run();
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
