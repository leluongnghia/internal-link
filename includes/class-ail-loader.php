<?php

/**
 * Register all actions and filters for the plugin
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/includes
 */

class AIL_Loader
{

	/**
	 * The array of actions registered with WordPress.
	 */
	protected $actions;

	/**
	 * The array of filters registered with WordPress.
	 */
	protected $filters;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 */
	public function __construct()
	{
		$this->actions = array();
		$this->filters = array();
	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 */
	public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1)
	{
		$this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 */
	public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1)
	{
		$this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
	}

	/**
	 * A utility function that is used to register the actions and hooks into a single
	 * collection.
	 */
	private function add($hooks, $hook, $component, $callback, $priority, $accepted_args)
	{
		$hooks[] = array(
			'hook' => $hook,
			'component' => $component,
			'callback' => $callback,
			'priority' => $priority,
			'accepted_args' => $accepted_args
		);
		return $hooks;
	}

	/**
	 * Register the filters and actions with WordPress.
	 */
	public function run()
	{

		// Define Admin hooks
		$this->define_admin_hooks();
		$this->define_public_hooks();

		foreach ($this->filters as $hook) {
			add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
		}

		foreach ($this->actions as $hook) {
			add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
		}
	}

	/**
	 * Define Admin Hooks
	 */
	private function define_admin_hooks()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-ail-admin.php';
		$plugin_admin = new AIL_Admin();

		$this->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
		$this->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->add_action('admin_init', $plugin_admin, 'register_settings');

		// Manual Run Hooks
		$this->add_filter('post_row_actions', $plugin_admin, 'add_manual_run_action', 10, 2);
		$this->add_action('wp_ajax_ail_manual_run', $plugin_admin, 'ajax_handle_manual_run');

		// Force Batch Process Hook
		$this->add_action('wp_ajax_ail_force_batch_process', $plugin_admin, 'ajax_force_batch_process');
	}

	/**
	 * Define Public Hooks
	 */
	private function define_public_hooks()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ail-public.php';
		$plugin_public = new AIL_Public();

		$this->add_action('save_post', $plugin_public, 'auto_inject_on_save');
		$this->add_action('ail_async_process_start', $plugin_public, 'process_background_event');
	}


}
