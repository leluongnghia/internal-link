<?php

/**
 * AI Internal Link Injector
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/includes
 */

class AIL_Injector
{

    private $api_key;
    private $provider;
    private $model;
    private $skills;

    public function __construct()
    {
        $this->provider = get_option('ail_api_provider', 'openai');

        // Load key and model based on provider
        if ('openai' === $this->provider) {
            $this->api_key = get_option('ail_openai_key');
            $this->model = get_option('ail_openai_model', 'gpt-4o');
        } elseif ('gemini' === $this->provider) {
            $this->api_key = get_option('ail_gemini_key');
            $this->model = get_option('ail_gemini_model', 'gemini-1.5-pro');
        } elseif ('grok' === $this->provider) {
            $this->api_key = get_option('ail_grok_key');
            $this->model = get_option('ail_grok_model', 'grok-2-1212');
        }

        // Get selected skills (array)
        $this->skills = get_option('ail_selected_skill', array());
    }

    /**
     * Main function to inject links into content.
     *
     * @param string $content Post content.
     * @param int $post_id Post ID.
     * @return string Content with links.
     */
    public function inject_links($content, $post_id)
    {
        if (empty($this->api_key)) {
            return $content; // No key, return original
        }

        require_once plugin_dir_path(__FILE__) . 'class-ail-retriever.php';
        $retriever = new AIL_Retriever();
        $candidates = $retriever->get_candidate_posts($post_id);

        if (empty($candidates)) {
            return $content; // No candidates
        }

        // Get limits
        $max_links = intval(get_option('ail_max_links', 5));
        $max_repeat = intval(get_option('ail_max_anchor_repeat', 3));

        // Prepare Prompt to get JSON map
        $prompt = $this->build_prompt($content, $candidates, $max_links);

        // Call AI
        $ai_response = $this->call_ai_api($prompt);

        if (!$ai_response || is_wp_error($ai_response)) {
            return $content; // Fail safe
        }

        $link_mappings = $this->parse_ai_json($ai_response);
        if (empty($link_mappings)) {
            return $content; // AI didn't find good matches
        }

        // Stage 2: Strict PHP Replacement
        // Thêm fallback an toàn: kiểm tra hallucination (chỉ chap nhận URL có trong candidate list)
        $valid_urls = array_map(function ($c) {
            return $c['url'];
        }, $candidates);

        if (!class_exists('AIL_HTMLParser')) {
            require_once plugin_dir_path(__FILE__) . 'class-ail-html-parser.php';
        }

        $modified_content = $content;
        $links_injected = 0;

        foreach ($link_mappings as $mapping) {
            if ($links_injected >= $max_links)
                break;

            if (!isset($mapping['exact_phrase']) || !isset($mapping['target_url'])) {
                continue;
            }

            $phrase = trim($mapping['exact_phrase']);
            $url = esc_url_raw($mapping['target_url']);

            if (empty($phrase) || empty($url))
                continue;

            // Chống Hallucination: Nêu URL AI chế ra ko có trong DS cho phép -> Skip
            if (!in_array($url, $valid_urls)) {
                continue;
            }

            // Check Anchor Limit Rule
            if ($this->has_exceeded_anchor_limit($phrase, $url, $max_repeat)) {
                continue; // Skip this one, used too many times
            }

            // Attempt to inject
            $new_content = AIL_HTMLParser::replace_phrase($modified_content, $phrase, $url);

            if ($new_content !== $modified_content) {
                // Success
                $modified_content = $new_content;
                $links_injected++;
                $this->log_anchor_usage($phrase, $url);
            }
        }

        if ($links_injected > 0) {
            $this->log_action($post_id, $links_injected);
        }

        return $modified_content;
    }

    /**
     * Parse AI output to extract JSON block
     */
    private function parse_ai_json($response)
    {
        // Try to find JSON block
        if (preg_match('/```(?:json)?(.*?)```/is', $response, $matches)) {
            $json_str = trim($matches[1]);
        } else {
            // Assume the whole response is JSON
            $json_str = trim($response);
        }

        $data = json_decode($json_str, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Check if anchor has been used too many times for a target
     */
    private function has_exceeded_anchor_limit($phrase, $url, $limit)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ail_anchor_log';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT usage_count FROM $table WHERE exact_phrase = %s AND target_url = %s",
            $phrase,
            $url
        ));

        return intval($count) >= $limit;
    }

    /**
     * Log anchor usage
     */
    public function log_anchor_usage($phrase, $url)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ail_anchor_log';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE exact_phrase = %s AND target_url = %s",
            $phrase,
            $url
        ));

        if ($exists) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET usage_count = usage_count + 1 WHERE id = %d",
                $exists
            ));
        } else {
            $wpdb->insert($table, array(
                'exact_phrase' => $phrase,
                'target_url' => $url,
                'usage_count' => 1
            ));
        }
    }

    /**
     * Log action to database
     */
    public function log_action($post_id, $links_count, $provider = null, $model = null)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ail_logs';

        $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'provider' => $provider !== null ? $provider : $this->provider,
                'model' => $model !== null ? $model : $this->model,
                'created_at' => current_time('mysql'),
                'links_created' => $links_count
            )
        );
    }

    /**
     * Build the AI Prompt
     */
    private function build_prompt($content, $candidates, $max_links)
    {
        $candidate_list = "";
        foreach ($candidates as $c) {
            $candidate_list .= "- URL: {$c['url']} | Summary: {$c['summary']}\n";
        }

        $prompt = "You are an expert SEO editor. Your task is to identify phrases in the HTML content below to turn into internal links.\n\n";
        $prompt .= "### CANDIDATE LINKS:\n";
        $prompt .= $candidate_list . "\n\n";
        $prompt .= "### INSTRUCTIONS:\n";
        $prompt .= "1. Read the provided TEXT CONTENT. Identify up to {$max_links} opportunities to link to the candidate URLs.\n";
        $prompt .= "2. STRICT RULE: the anchor text MUST BE an EXACT case-sensitive substring from the text content. Do NOT make up phrases not currently in the text.\n";
        $prompt .= "3. DO NOT wrap phrases that are already inside <a> tags or headings.\n";
        $prompt .= "4. ONLY RETURN VALID JSON in the following array format:\n";
        $prompt .= "[\n  {\"exact_phrase\": \"string from text\", \"target_url\": \"URL from candidates\"}\n]\n";
        $prompt .= "5. Return nothing else but the JSON array.\n";

        // Append Skills
        if (!empty($this->skills) && is_array($this->skills)) {
            $upload_dir = wp_upload_dir();
            $skills_dir = trailingslashit($upload_dir['basedir']) . 'aprg-skills/';

            foreach ($this->skills as $skill_file) {
                $file_path = $skills_dir . $skill_file . '.md';
                if (file_exists($file_path)) {
                    $skill_content = file_get_contents($file_path);
                    $prompt .= "\n### ADDITIONAL SKILL INSTRUCTION ({$skill_file}):\n";
                    $prompt .= $skill_content . "\n";
                }
            }
        }

        // Just send stripped text to help AI focus and save tokens, 
        // because we use PHP regex to inject back into HTML anyway.
        $clean_text = wp_strip_all_tags($content);

        $prompt .= "\n### TEXT CONTENT:\n";
        $prompt .= $clean_text;

        return $prompt;
    }

    /**
     * Call AI API
     */
    private function call_ai_api($prompt)
    {
        if ('openai' === $this->provider) {
            return $this->call_openai($prompt);
        } elseif ('gemini' === $this->provider) {
            return $this->call_gemini($prompt);
        } elseif ('grok' === $this->provider) {
            return $this->call_grok($prompt);
        }
        return false;
    }

    private function call_openai($prompt)
    {
        $model = !empty($this->model) ? $this->model : 'gpt-4o';
        $url = 'https://api.openai.com/v1/chat/completions';
        $body = array(
            'model' => $model,
            'messages' => array(
                array('role' => 'system', 'content' => 'You are an API that only returns JSON.'),
                array('role' => 'user', 'content' => $prompt)
            ),
            'temperature' => 0.1,
            'response_format' => array('type' => 'json_object')
        );

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 60
        ));

        if (is_wp_error($response))
            return $response;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['choices'][0]['message']['content'] ?? false;
    }

    private function call_gemini($prompt)
    {
        // Gemini URL format: models/{model}:generateContent
        $model = !empty($this->model) ? $this->model : 'gemini-1.5-pro';
        if (strpos($model, 'models/') === false) {
            $model = 'models/' . $model;
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/' . $model . ':generateContent?key=' . $this->api_key;
        $body = array(
            'contents' => array(
                array('parts' => array(array('text' => $prompt)))
            )
        );

        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($body),
            'timeout' => 60
        ));

        if (is_wp_error($response))
            return $response;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['candidates'][0]['content']['parts'][0]['text'] ?? false;
    }

    private function call_grok($prompt)
    {
        // Grok uses OpenAI-compatible API at api.x.ai
        $model = !empty($this->model) ? $this->model : 'grok-2-1212';
        $url = 'https://api.x.ai/v1/chat/completions';

        $body = array(
            'model' => $model,
            'messages' => array(
                array('role' => 'system', 'content' => 'You are an API that only returns JSON.'),
                array('role' => 'user', 'content' => $prompt)
            ),
            'temperature' => 0.1,
            'response_format' => array('type' => 'json_object')
        );

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 60
        ));

        if (is_wp_error($response))
            return $response;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['choices'][0]['message']['content'] ?? false;
    }

    /**
     * Interactive Scanner: Suggest links for a given content block without modifying it.
     */
    public function suggest_links($content, $post_id)
    {
        if (empty($this->api_key)) {
            return false;
        }

        require_once plugin_dir_path(__FILE__) . 'class-ail-retriever.php';
        $retriever = new AIL_Retriever();
        $candidates = $retriever->get_candidate_posts($post_id);

        if (empty($candidates)) {
            return false;
        }

        $max_links = 10; // Provide more suggestions for interactive use

        // Try to utilize the same prompt building method
        $prompt = $this->build_prompt($content, $candidates, $max_links);

        // Call AI
        $ai_response = $this->call_ai_api($prompt);

        if (!$ai_response || is_wp_error($ai_response)) {
            return false;
        }

        return $this->parse_ai_json($ai_response);
    }

}
