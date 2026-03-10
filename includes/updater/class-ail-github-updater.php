<?php

/**
 * Handles checking for updates from GitHub
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/includes/updater
 */

class AIL_GitHub_Updater
{

    private $file;
    private $plugin;
    private $basename;
    private $active;
    private $username;
    private $repository;
    private $authorize_token;
    private $github_response;

    public function __construct($file)
    {
        $this->file = $file;
        $this->add_plugin_hooks();
    }

    public function set_username($username)
    {
        $this->username = $username;
    }

    public function set_repository($repository)
    {
        $this->repository = $repository;
    }

    public function authorize($token)
    {
        $this->authorize_token = $token;
    }

    private function get_repository_info()
    {
        if (is_null($this->github_response)) { // Do we have a response?
            $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases', $this->username, $this->repository);

            $args = array();
            if ($this->authorize_token) { // Is there an access token?
                $args['headers']['Authorization'] = "bearer {$this->authorize_token}";
            }

            $response = json_decode(wp_remote_retrieve_body(wp_remote_get($request_uri, $args)), true);

            if (is_array($response)) { // If it is an array
                $response = current($response); // Get the first item
            }

            if ($this->authorize_token) { // Is there an access token?
                $response['zipball_url'] = add_query_arg('access_token', $this->authorize_token, $response['zipball_url']);
            }

            $this->github_response = $response; // Set it to our property
        }
    }

    public function initialize()
    {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'modify_transient'), 10, 1);
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
    }

    private function add_plugin_hooks()
    {
        $this->plugin = plugin_basename($this->file);
        $this->basename = plugin_basename(dirname($this->file));
        $this->active = is_plugin_active($this->plugin);
    }

    public function modify_transient($transient)
    {
        if (property_exists($transient, 'checked')) {
            if ($checked = $transient->checked) {
                $this->get_repository_info(); // Get the repo info

                if (empty($this->github_response['tag_name'])) {
                    return $transient; // No valid tag
                }

                $out_of_date = version_compare($this->github_response['tag_name'], $checked[$this->plugin], 'gt'); // Check if we're out of date

                if ($out_of_date) {
                    $new_files = $this->github_response['zipball_url']; // Get the ZIP
                    $slug = current(explode('/', $this->plugin)); // Create valid slug

                    $plugin = array( // setup our plugin info
                        'url' => $this->plugin,
                        'slug' => $slug,
                        'package' => $new_files,
                        'new_version' => $this->github_response['tag_name']
                    );

                    $transient->response[$this->plugin] = (object) $plugin; // Return it in response
                }
            }
        }

        return $transient; // Return filtered transient
    }

    public function plugin_popup($result, $action, $args)
    {
        if (!empty($args->slug)) {
            if ($args->slug == current(explode('/', $this->plugin))) {
                $this->get_repository_info(); // Get our repo info

                $plugin = array(
                    'name' => $this->github_response['name'],
                    'slug' => $this->basename,
                    'requires' => '5.0',
                    'tested' => '6.4.2',
                    'rating' => '100.0',
                    'num_ratings' => '1',
                    'downloaded' => '1',
                    'added' => '2024-01-01',
                    'version' => $this->github_response['tag_name'],
                    'author' => $this->github_response['author']['login'],
                    'author_profile' => $this->github_response['author']['html_url'],
                    'last_updated' => $this->github_response['published_at'],
                    'homepage' => $this->github_response['html_url'],
                    'short_description' => $this->github_response['body'],
                    'sections' => array(
                        'Updates' => $this->github_response['body'],
                    ),
                    'download_link' => $this->github_response['zipball_url']
                );

                return (object) $plugin;
            }
        }
        return $result;
    }

    public function after_install($response, $hook_extra, $result)
    {
        global $wp_filesystem;

        $install_directory = plugin_dir_path($this->file); // Our plugin directory
        $wp_filesystem->move($result['destination'], $install_directory); // Move files to the plugin dir
        $result['destination'] = $install_directory; // Set the destination for the rest of the stack

        if ($this->active) { // If it was active
            activate_plugin($this->basename); // Reactivate
        }

        return $result;
    }
}
