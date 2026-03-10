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
            $this->model = get_option('ail_grok_model', 'grok-beta');
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

        // Prepare Prompt
        $prompt = $this->build_prompt($content, $candidates);

        // Call AI
        $modified_content = $this->call_ai_api($prompt);

        if (!$modified_content || is_wp_error($modified_content)) {
            return $content; // Fail safe
        }

        $modified_content = $this->clean_response($modified_content);

        // Log the action
        $links_count = $this->count_injected_links($modified_content, $candidates);
        $this->log_action($post_id, $links_count);

        return $modified_content;
    }

    /**
     * Count how many candidate links appear in the content
     */
    private function count_injected_links($content, $candidates)
    {
        $count = 0;
        foreach ($candidates as $c) {
            if (strpos($content, $c['url']) !== false) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Log action to database
     */
    private function log_action($post_id, $links_count)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ail_logs';

        $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'provider' => $this->provider,
                'model' => $this->model,
                'created_at' => current_time('mysql'),
                'links_created' => $links_count
            )
        );

    /**
     * Build the AI Prompt
     */
    private function build_prompt($content, $candidates)
    {
        $candidate_list = "";
        foreach ($candidates as $c) {
            $candidate_list .= "- Title: {$c['title']} | URL: {$c['url']} | Summary: {$c['summary']}\n";
        }

        $max_links = get_option('ail_max_links', 5);

        $prompt = "You are an expert SEO editor. Your task is to naturally inject internal links into the provided content.\n\n";
        $prompt .= "### CANDIDATE LINKS (Title | URL | Summary):\n";
        $prompt .= $candidate_list . "\n\n";
        $prompt .= "### INSTRUCTIONS:\n";
        $prompt .= "1. Read the content below and identify 3 to {$max_links} opportunities to link to the candidate articles provided above.\n";
        $prompt .= "2. **Crucial**: Naturally weave the links into the text. You are allowed to slightly rephrase sentences to make the links flow naturally (Search Atlas / Moonlit style).\n";
        $prompt .= "3. **Anchor Text**: Use varied anchor text. Do NOT always use the exact title. Use semantic phrases that fit the context.\n";
        $prompt .= "4. **Placement**: prioritize placing links in the first half of the content if relevant.\n";
        $prompt .= "5. Return the FULL HTML content with the new <a> tags inserted. Do not remove any existing HTML tags.\n";
        $prompt .= "6. Ensure all links are dofollow.\n";
        $prompt .= "7. If no relevant place is found, return the content unchanged.\n";

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

        $prompt .= "\n### CONTENT:\n";
        $prompt .= $content;

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
                array('role' => 'system', 'content' => 'You are a helpful SEO assistant.'),
                array('role' => 'user', 'content' => $prompt)
            ),
            'temperature' => 0.3
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
        $model = !empty($this->model) ? $this->model : 'grok-beta';
        $url = 'https://api.x.ai/v1/chat/completions';

        $body = array(
            'model' => $model,
            'messages' => array(
                array('role' => 'system', 'content' => 'You are a helpful SEO assistant.'),
                array('role' => 'user', 'content' => $prompt)
            ),
            'temperature' => 0.3
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
     * Clean response (remove markdown code blocks if any)
     */
    private function clean_response($content)
    {
        // Remove ```html ... ```
        $content = preg_replace('/^```html/i', '', $content);
        $content = preg_replace('/^```/i', '', $content);
        $content = preg_replace('/```$/', '', $content);
        return trim($content);
    }

}
