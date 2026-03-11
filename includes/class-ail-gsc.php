<?php

/**
 * Handles Google Search Console data processing and opportunity identification.
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/includes
 */

class AIL_GSC
{
    /**
     * Register actions and filters
     */
    public function __construct()
    {
        // Hooks goes here
        add_action('wp_ajax_ail_upload_gsc_csv', array($this, 'ajax_upload_gsc_csv'));
    }

    /**
     * Import GSC Keyword Data from CSV
     * Expects standard GSC export format: Top queries, Clicks, Impressions, CTR, Position
     */
    public function upload_csv_file($file_path)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ail_gsc_keywords';

        if (!file_exists($file_path) || !is_readable($file_path)) {
            return false;
        }

        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return false;
        }

        $header = fgetcsv($handle, 1000, ',');
        if (!$header) {
            return false;
        }

        $imported_count = 0;
        $current_date = current_time('mysql');

        // Map indices based on strict headers or assumptions
        // Assuming standard columns: Top queries, Clicks, Impressions, CTR, Position
        // We might need to ask user to map or assume standard english export

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            if (count($data) < 5) {
                continue;
            }

            // Simple mapping assumption based on Google Search Console Query report
            $keyword = sanitize_text_field($data[0]);
            $clicks = intval(str_replace(',', '', $data[1]));
            $impressions = intval(str_replace(',', '', $data[2]));
            $ctr = floatval(rtrim(str_replace('%', '', $data[3]), '%'));
            $position = floatval($data[4]);

            if (empty($keyword))
                continue;

            // Store the record
            $wpdb->insert(
                $table_name,
                array(
                    'keyword' => $keyword,
                    'page_url' => '', // GSC pure query export doesn't have page URL without specific dimensions, could be improved
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'ctr' => $ctr,
                    'position' => $position,
                    'data_date' => date('Y-m-d'),
                    'processed' => 0,
                    'created_at' => $current_date
                ),
                array('%s', '%s', '%d', '%d', '%f', '%f', '%s', '%d', '%s')
            );

            $imported_count++;
        }

        fclose($handle);

        return $imported_count;
    }

    /**
     * AJAX handler for CSV upload
     */
    public function ajax_upload_gsc_csv()
    {
        check_ajax_referer('ail_gsc_upload', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        if (empty($_FILES['gsc_csv']['tmp_name'])) {
            wp_send_json_error('No file uploaded');
        }

        $file = $_FILES['gsc_csv']['tmp_name'];

        $count = $this->upload_csv_file($file);

        if ($count !== false) {
            wp_send_json_success(array('message' => sprintf('Successfully imported %d keywords.', $count)));
        } else {
            wp_send_json_error('Failed to import CSV.');
        }
    }

    /**
     * Get "Opportunity" keywords based on Link Whisper like algorithm
     * High impressions, low clicks, ranking on page 2 (Position 11-20)
     */
    public static function get_opportunity_keywords($limit = 50)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ail_gsc_keywords';

        // Identify opportunities: Good impressions but low ranking/clicks
        $query = $wpdb->prepare("
			SELECT * FROM {$table_name}
			WHERE impressions > 50 
			AND position > 10 
			AND position < 30
			ORDER BY impressions DESC
			LIMIT %d
		", $limit);

        return $wpdb->get_results($query);
    }
}
