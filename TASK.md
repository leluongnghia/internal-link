# 📋 TASK LIST — AI Internal Linker

**Cập nhật:** 2026-03-10
**Tham khảo:** Link Whisper Premium Analysis + Frontend Design Skill (ui-ux-pro-max, frontend-design)

> **Hướng dẫn độ ưu tiên:**
> - 🔴 **CRITICAL** — Cần làm ngay, ảnh hưởng core functionality
> - 🟠 **HIGH** — Tính năng quan trọng, lên kế hoạch sprint tiếp theo
> - 🟡 **MEDIUM** — Cải tiến chất lượng, có thể hoãn
> - 🟢 **LOW** — Nice-to-have, để dành sau

---

## SPRINT 0: AI BATCH PROCESSING — TỰ ĐỘNG HOÁ INTERNAL LINK

> **Mục tiêu cốt lõi:** Tự động hoá 100% việc inject internal links vào TẤT CẢ bài viết cũ mà không cần admin thao tác thủ công.

### 🧠 Phân Tích Kỹ: Cách Link Whisper Làm Batch Processing

Qua phân tích `Wpil_Keyword.php` và `Wpil_AI.php`, LW dùng **2 luồng song song**:

**Luồng 1: Regex-based Autolink (Keyword → URL, không cần AI)**
- Schedule: `wpil_autolink_insert_cron` — **mỗi 5 phút** (không phải daily!)
- Offset-based: Lưu `wpil_autolink_cron_offset` vào DB để tiếp tục từ điểm dừng
- Batch toàn bộ post IDs vào Transient 6 giờ: `wpil_autolinking_cron_post_batch`
- Time limit guard: `Wpil_Base::overTimeLimit(10, 60)` — thoát khi gần timeout
- Memory guard: `memory_get_usage() > $memory_break_point`
- Sau khi hết posts → chuyển sang terms rồi quay lại posts (vòng lặp vô tận)

**Luồng 2: AI Embedding + Link Injection**
- Schedule: `wpil_ai_batch_process_cron` — **mỗi giờ**
- Lock mechanism: `set_transient('wpil_doing_ai_data_download', time(), 3*MINUTE)` — tránh chạy đồng thời
- Multi-step pipeline trong 1 lần chạy: embeddings → scoring → sitemap → post save
- Stop guard: `Wpil_Base::overTimeLimit(5, 20)` — thoát khi còn 5s trước 20s deadline

---

### TASK-000 🔴 [CORE] Redesign AIL_Sweeper — Intelligent Batch Processor
- **File sửa:** `includes/class-ail-sweeper.php`
- **File sửa:** `includes/class-ail-activator.php` (thêm options, re-schedule cron)
- **Mô tả:** Thay thế Sweeper đơn giản hiện tại bằng engine batch processing thông minh, có thể tự động hóa 100% internal linking cho site lớn

**Kiến trúc mới — 3 Cron Jobs:**

```
┌─────────────────────────────────────────────────────────┐
│  CronJob 1: ail_batch_index_cron (every 6 hours)        │
│  → Quét toàn site, build danh sách post IDs cần xử lý  │
│  → Lưu vào wp_options: ail_batch_queue (JSON array)     │
│  → Reset offset về 0                                    │
└─────────────────────────────────────────────────────────┘
            ↓ feeds data to
┌─────────────────────────────────────────────────────────┐
│  CronJob 2: ail_batch_process_cron (every 5 minutes)    │
│  → Đọc offset từ ail_batch_queue_pointer               │
│  → Lấy batch N posts từ queue                           │
│  → Gọi AI + inject links                                │
│  → Update pointer đến post tiếp theo                   │
│  → Time guard: thoát khi > 45 giây                     │
└─────────────────────────────────────────────────────────┘
            ↓ fallback
┌─────────────────────────────────────────────────────────┐
│  CronJob 3: ail_regex_sweep_cron (every 30 minutes)     │
│  → Chỉ dùng PHP Regex (KHÔNG gọi AI)                   │
│  → Inject Manual Links vào unprocessed posts            │
│  → Nhanh hơn, xử lý nhiều bài hơn mỗi lần             │
└─────────────────────────────────────────────────────────┘
```

**Việc cần làm — Chi Tiết:**

**A. Tạo Batch Queue Builder:**
```php
private function build_batch_queue() {
    global $wpdb;
    
    // Lấy ALL published post IDs trừ những bài đã xử lý gần đây
    $post_ids = $wpdb->get_col("
        SELECT p.ID 
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm 
            ON p.ID = pm.post_id AND pm.meta_key = '_ail_last_processed'
        WHERE p.post_status = 'publish'
        AND p.post_type IN ('post', 'page')
        AND (
            pm.meta_value IS NULL 
            OR pm.meta_value < DATE_SUB(NOW(), INTERVAL 7 DAY)
        )
        ORDER BY p.post_date DESC
    ");
    
    update_option('ail_batch_queue', $post_ids);
    update_option('ail_batch_queue_pointer', 0);
    update_option('ail_batch_queue_built_at', time());
}
```

**B. Time Limit Guard (học từ LW `Wpil_Base::overTimeLimit`):**
```php
private static $start_time = 0;

public static function start_timer() {
    self::$start_time = microtime(true);
}

public static function over_time_limit($buffer_seconds = 5, $max_seconds = 45) {
    $elapsed = microtime(true) - self::$start_time;
    return $elapsed > ($max_seconds - $buffer_seconds);
}
```

**C. AI Batch Process với Offset:**
```php
public function run_ai_batch_process() {
    // Tránh chạy concurrent
    $lock = get_transient('ail_batch_processing_lock');
    if ($lock && ($lock + 180) > time()) return; // locked for 3 mins
    set_transient('ail_batch_processing_lock', time(), 180);
    
    self::start_timer();
    
    $queue = get_option('ail_batch_queue', []);
    $pointer = (int) get_option('ail_batch_queue_pointer', 0);
    $batch_size = (int) get_option('ail_batch_size', 5); // 5 posts/run với AI
    
    if (empty($queue)) {
        $this->build_batch_queue();
        return; // Xử lý lần sau
    }
    
    $batch = array_slice($queue, $pointer, $batch_size);
    
    if (empty($batch)) {
        // Hết queue → rebuild
        $this->build_batch_queue();
        return;
    }
    
    $injector = new AIL_Injector();
    $processed = 0;
    
    foreach ($batch as $post_id) {
        if (self::over_time_limit(5, 45)) break;
        
        $post = get_post($post_id);
        if (!$post) { $processed++; continue; }
        
        // Check Silo Gate trước khi inject
        $allowed_ids = AIL_Silo::get_allowed_targets($post_id);
        
        $new_content = $injector->inject_links($post->post_content, $post_id);
        
        if ($new_content !== $post->post_content) {
            wp_update_post(['ID' => $post_id, 'post_content' => $new_content]);
            update_post_meta($post_id, '_ail_last_processed', current_time('mysql'));
            $this->log_action($post_id, 1, 'batch-ai');
        }
        
        $processed++;
    }
    
    // Advance pointer
    update_option('ail_batch_queue_pointer', $pointer + $processed);
    
    delete_transient('ail_batch_processing_lock');
}
```

**D. Regex-only Fast Sweep (không AI, nhanh hơn 10x):**
```php
public function run_regex_sweep() {
    self::start_timer();
    
    $manual_links = get_option('ail_manual_links', []);
    if (empty($manual_links)) return;
    
    $batch_size = (int) get_option('ail_sweep_batch_size', 30); // 30 posts/run
    $max_repeat = (int) get_option('ail_max_anchor_repeat', 3);
    
    global $wpdb;
    foreach ($manual_links as $link) {
        if (self::over_time_limit(5, 55)) break;
        
        $phrase = $link['phrase'];
        $url = $link['url'];
        
        // Skip nếu anchor đã quá giới hạn
        if ($this->has_exceeded_anchor_limit($phrase, $url, $max_repeat)) continue;
        
        // Tìm bài có keyword nhưng chưa có link, chưa quét trong 24h
        $posts = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_content
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm 
                ON p.ID = pm.post_id AND pm.meta_key = '_ail_last_swept'
            WHERE p.post_status = 'publish'
            AND p.post_content LIKE %s
            AND p.post_content NOT LIKE %s
            AND (pm.meta_value IS NULL OR pm.meta_value < %s)
            ORDER BY p.post_date DESC
            LIMIT %d
        ",
            '%' . $wpdb->esc_like($phrase) . '%',
            '%' . $wpdb->esc_like($url) . '%',
            date('Y-m-d H:i:s', time() - DAY_IN_SECONDS),
            $batch_size
        ));
        
        foreach ($posts as $post) {
            if (self::over_time_limit(5, 55)) break 2;
            
            // Silo Gate check
            $allowed_ids = AIL_Silo::get_allowed_targets($post->ID);
            if ($allowed_ids !== false) {
                $target_id = url_to_postid($url);
                if ($target_id && !in_array($target_id, $allowed_ids)) continue;
            }
            
            $new_content = AIL_HTMLParser::replace_phrase($post->post_content, $phrase, $url, [
                'link_once' => $link['link_once'] ?? false
            ]);
            
            if ($new_content !== $post->post_content) {
                wp_update_post(['ID' => $post->ID, 'post_content' => $new_content]);
                update_post_meta($post->ID, '_ail_last_swept', current_time('mysql'));
                $this->log_anchor_usage($phrase, $url);
                $this->log_action($post->ID, 1, 'regex-sweep');
            }
        }
    }
}
```

**E. Admin UI — Batch Progress Monitor:**
- Dashboard widget hiển thị: Queue size | Pointer hiện tại | % Hoàn thành
- Estimated time: `(queue_size - pointer) / batch_size * 5 minutes`
- Nút "Pause Batch" — set flag `ail_batch_paused = 1`
- Nút "Force Run Now" — `wp_schedule_single_event(time(), 'ail_batch_process_cron')`
- Nút "Reset Queue" — xóa queue, rebuild từ đầu
- Log stream: 10 hành động gần nhất của batch

**F. Settings mới trong Admin:**
```
[ Batch Processing ]
✓ Enable AI Batch Processing
  Batch Size (AI): [5] posts per run (gợi ý: 3-10 tùy token limit)
  Batch Size (Regex): [30] posts per run  
  Cron Frequency: [Every 5 min ▼] (5min / 15min / 30min / Hourly)
  Re-process posts after: [7] days
  Skip posts processed in last: [24] hours
```

**G. Tự Động Set Priority — Smart Queue Ordering:**
- Posts có **ít inbound links nhất** được xử lý trước → tối ưu SEO impact
- Query: `ORDER BY inbound_count ASC` từ `ail_link_report` table
- Fallback: `ORDER BY post_date DESC` nếu chưa có report data

---

## SPRINT 1: SỬA BUG + REFACTOR NỀN TẢNG

### TASK-001 🔴 [BUG] Fix Sweeper bỏ qua Silo Gate
- **File:** `includes/class-ail-sweeper.php`
- **Vấn đề:** `process_phrase()` inject vào ALL published posts mà không kiểm tra Silo Gate → Vi phạm Pillar/Cluster rules
- **Việc cần làm:**
  - Load `AIL_Silo::get_allowed_targets($post->ID)` trước mỗi inject
  - Nếu post thuộc Silo và target URL không nằm trong `$allowed_ids` → `continue` bỏ qua
  - Nếu post không thuộc Silo → inject bình thường (fallback recent posts)
- **Test:** Tạo 2 Silo khác nhau, chạy Sweeper, xác nhận không có cross-silo links

---

### TASK-002 🔴 [BUG] Fix Velocity Control — Thêm Per-Post Timestamp
- **File:** `includes/class-ail-sweeper.php`
- **Vấn đề:** `$posts_per_run = 20` hardcode, không track bài nào đã quét gần đây → Có thể quét lại bài vừa xong
- **Việc cần làm:**
  - Thêm check `get_post_meta($post->ID, '_ail_last_swept', true)` trước khi xử lý
  - Nếu `last_swept < 24h` → skip bài đó
  - Sau khi inject thành công → `update_post_meta($post->ID, '_ail_last_swept', current_time('mysql'))`
  - Đọc `posts_per_run` từ option `ail_sweep_batch_size` thay vì hardcode (default: 20)
  - Thêm option `ail_sweep_batch_size` vào Settings UI

---

### TASK-003 🟡 [REFACTOR] Tách HTMLParser thành Utility Class
- **File mới:** `includes/class-ail-html-parser.php`
- **Vấn đề:** Hàm `replace_phrase_safely()` bị copy-paste ở cả `AIL_Injector` và `AIL_Sweeper`
- **Việc cần làm:**
  - Tạo `class AIL_HTMLParser` với phương thức `static replace_phrase($content, $phrase, $url)`
  - Chứa toàn bộ logic split HTML, skip `<a>` và heading tags, regex Unicode
  - Cập nhật `AIL_Injector` và `AIL_Sweeper` sử dụng `AIL_HTMLParser::replace_phrase()`
  - Thêm method `static extract_text_nodes($html)` để lấy pure text nodes (dùng cho Phase 2 fallback)

---

### TASK-004 🟡 [IMPROVEMENT] Tách Link Budget 2 Phase Rõ Ràng
- **File:** `includes/class-ail-injector.php`
- **Vấn đề:** Phase 1 (AI) và Phase 2 (Regex fallback) chạy lẫn lộn trong 1 vòng foreach, không rõ ngân sách còn lại
- **Việc cần làm:**
  - Tách `inject_links()` thành 2 step rõ ràng:
    - `$ai_count = $this->apply_ai_phase($content, $candidates, $budget)` → trả về số link đã chèn
    - `$budget_remaining = $max_links - $ai_count`
    - `$this->apply_regex_phase($content, $manual_rules, $budget_remaining)` chỉ chạy khi `$budget_remaining > 0`
  - Log rõ Phase nào đã inject bao nhiêu link trong `ail_logs`

---

### TASK-005 🟡 [IMPROVEMENT] Cập nhật Danh Sách Model AI
- **File:** `admin/partials/ail-admin-display.php` + `includes/class-ail-injector.php`
- **Vấn đề:** Model list lỗi thời (thiếu Gemini 2.0/2.5, GPT-4.1, Grok 3)
- **Việc cần làm:**
  - Gemini: Thêm `gemini-2.0-flash`, `gemini-2.5-pro-preview`
  - OpenAI: Thêm `gpt-4.1`, `gpt-4o-mini`, o3-mini
  - Grok: Thêm `grok-3`, `grok-3-fast`
  - Option `response_format: json_object` chỉ áp dụng cho OpenAI/Grok, không áp dụng Gemini

---

## SPRINT 2: MANUAL LINKS UI + SILO MANAGER

### TASK-006 🔴 [FEATURE] Manual Links UI — Quản Lý Keyword→URL
- **File mới:** `admin/partials/ail-admin-manual-links.php`
- **File sửa:** `admin/class-ail-admin.php`, `admin/js/ail-admin.js`
- **Mô tả:** Giao diện để admin nhập danh sách cặp `Keyword → Target URL` (nguồn data cho Phase 2 Regex + Sweeper)
- **Việc cần làm:**
  - Thêm submenu "Manual Links" vào `add_plugin_admin_menu()`
  - UI dạng bảng có cột: Keyword | Target URL | Priority | Link Once (checkbox) | Delete
  - Nút "Add New" thêm row mới (giống LW Keyword table)
  - Nút "Import CSV" (format: `keyword,url` — học từ `autolink-import-sample.csv` của LW)
  - Nút "Export CSV" xuất toàn bộ keywords
  - Lưu bằng `wp_options` key `ail_manual_links` (array)
  - Thêm option per-keyword: `link_once` (chỉ link 1 lần per post), `case_sensitive`
  - AJAX save/delete từng row không reload page
- **DB schema cho option:**
  ```json
  [{"phrase": "SEO tips", "url": "https://...", "link_once": true, "case_sensitive": false, "priority": 10}]
  ```

---

### TASK-007 🔴 [FEATURE] Silo Manager UI — Quản Lý Pillar/Cluster
- **File mới:** `admin/partials/ail-admin-silo-manager.php`
- **File sửa:** `admin/class-ail-admin.php`
- **Mô tả:** Giao diện để tạo/sửa/xóa Silo, assign Pillar URL và danh sách Cluster URLs
- **Việc cần làm:**
  - Thêm submenu "Silo Manager" vào admin menu
  - Hiển thị danh sách Silos dạng card (mỗi card = 1 Silo)
  - Mỗi card có: Pillar Post (autocomplete search), danh sách Cluster Posts (multi-select)
  - Nút "Add New Silo" mở form thêm mới
  - Nút "Delete Silo" xóa Silo (không xóa bài viết)
  - Dùng WP Post Search Select (ajax) để search post title khi assign Pillar/Clusters
  - AJAX save vào bảng `ail_silos`
  - Hiển thị thống kê: "Silo này có X clusters, Y links đã inject"

---

## SPRINT 3: CLICK TRACKER (Học Từ Link Whisper)

### TASK-008 🟠 [FEATURE] Click Tracker — Theo Dõi Click Link Nội Bộ
- **File mới:** `includes/class-ail-click-tracker.php`
- **File mới:** `admin/partials/ail-admin-click-report.php`
- **File mới:** `admin/js/ail-click-tracker.js`
- **File sửa:** `includes/class-ail-activator.php` (thêm table), `admin/class-ail-admin.php`
- **Mô tả:** Track từng lần user click vào internal link được plugin inject, lưu vào DB
- **Việc cần làm:**

  **Backend (`class-ail-click-tracker.php`):**
  - Tạo bảng `wp_ail_click_log` (id, link_url, source_post_id, target_post_id, anchor_text, visitor_ip, clicked_at)
  - AJAX handler `wp_ajax_nopriv_ail_track_click` lưu click data
  - Hàm `get_click_stats($post_id, $date_range)` để lấy thống kê
  - WP-Cron hàng tuần: Xóa click data cũ hơn 30 ngày (configurable)

  **Frontend (`ail-click-tracker.js`):**
  - Script enqueue trên frontend (public pages)
  - Bắt sự kiện `click` trên các `<a>` có `data-ail-tracked="1"` attribute
  - AJAX POST lên `/wp-admin/admin-ajax.php?action=ail_track_click`
  - Non-blocking, không làm chậm trang (fire-and-forget)

  **Injector:** Thêm `data-ail-tracked="1"` vào các link được inject

  **Report UI (`ail-admin-click-report.php`):**
  - Bảng click log với filter: Date Range, Source Post, Target Post
  - Cột: Thời gian | Anchor Text | Bài nguồn | Bài đích | IP (hash)
  - Chart tổng clicks theo ngày (dùng Chart.js đơn giản)
  - Tổng clicks mỗi bài

---

## SPRINT 4: LINK REPORT PER-POST

### TASK-009 🟠 [FEATURE] Link Report per-Post — Inbound/Outbound Summary
- **File mới:** `includes/class-ail-link-report.php`
- **File sửa:** `includes/class-ail-activator.php`, `admin/class-ail-admin.php`, `admin/partials/ail-admin-reports.php`
- **Mô tả:** Học từ `Wpil_Report.php` của LW — Xây bảng tổng hợp inbound/outbound links per post
- **Việc cần làm:**
  - Tạo bảng `wp_ail_link_report` (post_id, inbound_count, outbound_count, last_scanned)
  - Hàm `scan_post_links($post_id)`:
    - Parse `post_content` tìm tất cả `<a href="...">` → đây là outbound links
    - Query DB ngược lại: bài nào có link trỏ về post này → inbound count
  - WP-Cron hàng ngày: Refresh stats cho tất cả published posts
  - Hook vào `save_post` để refresh stats ngay khi lưu bài
  - **Reports UI cải tiến:**
    - Thêm cột "Inbound Links" và "Outbound Links" vào bảng reports
    - Highlight bài có 0 inbound links (cần link thêm)
    - Sort theo số lượng links

---

## SPRINT 5: GSC INTEGRATION (Auto-Pilot)

### TASK-010 🟠 [FEATURE] Google Search Console OAuth2 + Keyword Discovery
- **File mới:** `includes/class-ail-gsc.php`
- **File mới:** `admin/partials/ail-admin-gsc.php`
- **File sửa:** `includes/class-ail-activator.php`, `admin/class-ail-admin.php`
- **Mô tả:** Kết nối GSC, pull keyword data, tự động phát hiện Opportunity Keywords
- **Việc cần làm:**

  **OAuth2 Flow:**
  - Người dùng nhập Google Client ID + Client Secret trong Settings
  - Nút "Connect Google Search Console" → Redirect OAuth2
  - Callback URL xử lý code → đổi lấy access_token + refresh_token
  - Lưu token mã hóa trong `wp_options` (học từ `Wpil_Toolbox::encrypt()`)
  - Nút "Disconnect" thu hồi token

  **Data Pull (WP-Cron):**
  - Schedule `ail_gsc_daily_pull` — chạy mỗi ngày lúc 3 giờ sáng
  - Query GSC API: `searchanalytics/query` với dimensions=[query, page]
  - Lưu kết quả vào `wp_ail_gsc_keywords` table
  - Xử lý incremental theo ngày (tránh timeout), học pattern từ `Wpil_TargetKeyword::incremental_query_gsc_data()`

  **Opportunity Detection:**
  - Lọc keywords: `impressions > 500 AND ctr < 0.01` (configurable)
  - Danh sách keywords được phát hiện → Đề xuất thêm vào Manual Links
  - Hoặc tự động thêm vào `ail_manual_links` nếu bật "Auto-Pilot Mode"
  - UI: Hiển thị Opportunity Keywords table với cột: Keyword | Page | Impressions | CTR | Status

---

## SPRINT 6: TÍNH NĂNG NÂNG CAO

### TASK-011 🟡 [FEATURE] URL Changer — Cập Nhật Link Hàng Loạt Khi Đổi URL
- **File mới:** `includes/class-ail-url-changer.php`
- **Mô tả:** Khi URL bài viết thay đổi, tự động tìm và update tất cả posts trỏ về URL cũ
- **Việc cần làm:**
  - Hook vào `post_updated` — khi slug/permalink thay đổi
  - So sánh `$old_url` vs `$new_url` của post
  - Query wildcard SQL tìm tất cả posts có `<a href="$old_url">`
  - Replace `$old_url` → `$new_url` trong `post_content`
  - Log thay đổi vào `ail_logs`
  - UI trong Reports: Hiển thị lịch sử URL changes

---

### TASK-012 🟡 [FEATURE] Import/Export Keywords CSV
- **File sửa:** `admin/partials/ail-admin-manual-links.php`, `admin/class-ail-admin.php`
- **Mô tả:** Cho phép import/export danh sách keywords từ file CSV
- **Việc cần làm:**
  - Export: Button "Export CSV" → download file format `keyword,url,link_once,case_sensitive`
  - Import: Upload form nhận `.csv` file, validate, merge vào `ail_manual_links` option
  - Sample CSV file tại `autolink-import-sample.csv` (giống LW)
  - Preview trước khi import: Hiển thị X rows sẽ được thêm

---

### TASK-013 🟢 [FEATURE] nofollow / Target Options cho Link
- **File sửa:** `includes/class-ail-html-parser.php`, `admin/partials/ail-admin-display.php`
- **Mô tả:** Tùy chọn thêm `rel="nofollow"` hoặc `target="_blank"` vào links được inject
- **Việc cần làm:**
  - Thêm 2 checkbox trong Settings:
    - "Add nofollow to injected links" → `get_option('ail_nofollow')`
    - "Open links in new tab" → `get_option('ail_target_blank')`
  - Sửa `AIL_HTMLParser::replace_phrase()` để thêm attributes dựa trên options
  - Có thể set per-keyword trong Manual Links UI

---

### TASK-014 🟢 [SEO] Thêm Stop Words Tiếng Việt
- **File mới:** `includes/stop-words-vi.php`
- **File sửa:** `includes/class-ail-injector.php`, `includes/class-ail-sweeper.php`
- **Mô tả:** Học từ `includes/ignore_word_lists/` của LW — Tránh wrap link vào các từ chung chung
- **Việc cần làm:**
  - Tạo danh sách stop words tiếng Việt: "là", "và", "của", "với", "trong", "những", "một", "có", "được", "này", "đó", "như", "từ", ...
  - Trước khi inject, check nếu `phrase` là stop word → bỏ qua
  - Cũng check minimum phrase length (VD: < 3 ký tự → bỏ qua)

---

## SPRINT 7: CẢI TIẾN UX / REPORTS

### TASK-015 🟡 [UX] Interactive Scanner — Thêm Nút "Apply" Suggestions
- **File sửa:** `admin/class-ail-admin.php`, `admin/js/ail-admin.js`
- **Vấn đề:** Hiện tại Scanner chỉ hiển thị suggestions, user phải tự copy-paste
- **Việc cần làm:**
  - Mỗi suggestion item thêm nút "Apply" (1 link) và checkbox để chọn nhiều
  - Nút "Apply Selected" ở cuối list
  - Khi click Apply → AJAX gọi `/wp-admin/admin-ajax.php?action=ail_apply_suggestion`
  - Server-side: Lấy post content, inject link cụ thể đó, update post
  - Refresh meta box sau khi apply thành công

---

### TASK-016 🟡 [UI] Cải Tiến Reports Dashboard
- **File sửa:** `admin/partials/ail-admin-reports.php`
- **Mô tả:** Reports hiện tại quá đơn giản, thiếu filter/sort/chart
- **Việc cần làm:**
  - Thêm Date Range Filter: "Hôm nay", "7 ngày", "30 ngày", "Tùy chỉnh"
  - Thêm Summary Cards: Tổng links đã inject | Tổng bài đã xử lý | Links hôm nay
  - Chart.js bar chart: Links injected per ngày (30 ngày gần nhất)
  - Filter theo Provider (OpenAI/Gemini/Grok/Sweeper/Manual)
  - Pagination cho log table (hiện đang giới hạn 50 rows cứng)
  - Nút "Clear Logs" để xóa logs cũ

---

### TASK-017 🟢 [QOL] Thêm Plugin Health Check Dashboard
- **File mới:** hoặc thêm section vào Settings
- **Mô tả:** Giúp user diagnose vấn đề nhanh
- **Việc cần làm:**
  - Check API key còn valid không (test call)
  - Check DB tables đã tạo đủ chưa
  - Check WP-Cron có đang chạy không (`wp_next_scheduled`)
  - Hiển thị: tổng posts chưa xử lý, Silo count, Manual keywords count
  - Nút "Force Run Sweep Now" để trigger sweep ngay lập tức (thay vì chờ cron)

---

---

## SPRINT UI: THIẾT KẾ LẠI GIAO DIỆN (Design "Dark Intelligence")

> **Aesthetic Direction:** *Industrial Utilitarian + Minimal Data-Driven*  
> **DFII Score: 16/16 — Excellent ✅**  
> **Design Anchor:** WordPress plugin nhưng cảm giác như Linear.app / Vercel Dashboard

### Design System
```css
/* Fonts: Space Grotesk (display) + DM Sans (body) + JetBrains Mono (code/URL) */
:root {
    --ail-bg-canvas:     #0D1117;   /* Deep Navy — không phải plain black */
    --ail-bg-surface:    #161B22;   /* Card, panel */
    --ail-bg-elevated:   #21262D;   /* Hover, input focus */
    --ail-border:        #30363D;   /* Border tinh tế */
    --ail-accent:        #00D4AA;   /* Electric Teal — không phải WP blue */
    --ail-accent-glow:   rgba(0,212,170,0.15);
    --ail-success:       #2EA043;   --ail-warning: #D29922;
    --ail-danger:        #F85149;   --ail-info: #388BFD;
    --ail-text-primary:  #E6EDF3;   --ail-text-secondary: #8B949E;
}
```

### TASK-UI-001 🟠 [UI] Plugin Shell — Layout Hệ Thống Riêng
- **File mới:** `admin/css/ail-admin.css`
- **File sửa:** `admin/class-ail-admin.php`, `admin/partials/ail-admin-display.php`
- **Việc cần làm:**
  - CSS global cho toàn bộ plugin: load Google Fonts Space Grotesk + DM Sans
  - Top status bar cố định: `� Gemini Connected | Queue: 1,247 posts | Today: +84 links`
  - Sidebar nav riêng (dùng WP submenu nhưng style lại hoàn toàn)
  - Card component system với border teal + glow effect khi hover
  - Toggle switches CSS-only thay thế `<input type="checkbox">`
  - Loading state: skeleton shimmer animation thay vì spinner

---

### TASK-UI-002 🟡 [UI] Settings Page — Tabs + Card Provider Selection
- **File sửa:** `admin/partials/ail-admin-display.php`, `admin/js/ail-admin.js`
- **Việc cần làm:**
  - Tab navigation: General | AI Provider | Batch | Silo | Advanced
  - AI Provider: 3 cards ngang (OpenAI / Gemini / Grok) thay vì `<select>`
    - Mỗi card có logo SVG, model dropdown, API key field, "Test" button
    - Active card có border teal + glow
  - Batch tab: hiển thị Batch Processing controls (từ TASK-000)
  - Toggle switches cho: Auto-Link on Save, Background Mode, Batch Enabled
  - Inline API key validation: test connection ngay sau khi blur field

---

### TASK-UI-003 🟡 [UI] Batch Progress Dashboard Widget
- **File mới:** `admin/partials/ail-admin-batch-monitor.php`
- **Việc cần làm:**
  - Progress ring (SVG circle) hiển thị % queue đã xử lý
  - Live counters (AJAX poll mỗi 10 giây): Queue | Processed | Remaining
  - Estimated time to completion: `X hours Y minutes`
  - Activity log: last 10 actions với fade-in animation
  - Pause / Resume / Force Run / Reset Queue buttons
  - Chart.js mini sparkline: links injected per hour (24h)

---

### TASK-UI-004 🟡 [UI] Manual Links Manager — Inline Editing Table
- **File mới:** `admin/partials/ail-admin-manual-links.php`
- **Việc cần làm:**
  - Keyword table với inline editing (click text → `contenteditable`)
  - Per-row toggles: Link Once / Case Sensitive / Active
  - Priority drag handle (cursor: grab)
  - Search/filter bar với debounce 300ms
  - Import CSV: drag-drop zone với preview
  - "Add Row" button thêm empty row ngay vào đầu bảng
  - Batch delete: checkbox + "Delete Selected"
  - Status badges: `Active` (teal) / `Limit Reached` (orange) / `Disabled` (grey)

---

### TASK-UI-005 🟡 [UI] Silo Manager — Visual Hierarchy Cards
- **File mới:** `admin/partials/ail-admin-silo-manager.php`
- **Việc cần làm:**
  - Mỗi Silo = 1 card lớn có header màu unique (random từ palette cố định)
  - Pillar post hiển thị với badge đặc biệt (Crown icon + teal border)
  - Clusters hiển thị dạng chip tags bên dưới Pillar
  - "+ Add Cluster" mở WP post search select (autocomplete AJAX)
  - Thống kê per-silo: N clusters | M links injected | Last updated
  - Visual: SVG lines nối Pillar → Clusters (optional, progressive enhancement)

---

### TASK-UI-006 🟡 [UI] Reports Dashboard — Charts + Filters
- **File sửa:** `admin/partials/ail-admin-reports.php`
- **Việc cần làm:**
  - 4 Summary cards row: Total Links | Posts Processed | Today | Avg/Post
  - Chart.js line chart: "Links per Day" (30 ngày) với teal gradient fill
  - Filter bar: Date Range (Today/7d/30d/Custom) + Provider
  - Provider breakdown: donut chart nhỏ bên phải
  - Activity log có pagination + fade-in animation per row
  - Export button (CSV)

---

### TASK-UI-007 🟢 [UI] Interactive Scanner — Apply Button trong Metabox
- **File sửa:** `admin/class-ail-admin.php`, `admin/js/ail-admin.js`
- **Việc cần làm:**
  - Redesign metabox: header teal, icon scan
  - Suggestions dạng cards thay vì `<li>` list
  - Mỗi card: phrase snippet | arrow | target URL | `✓ Apply` button
  - "Apply All" button floating ở bottom
  - Scan status: progress bar khi đang scan
  - After apply: card chuyển màu success + check icon

---

## �📊 TỔNG KẾT ROADMAP

| Sprint | Tasks | Độ ưu tiên | Ước lượng |
|---|---|---|---|
| **Sprint 0** | **TASK-000** (Batch Engine) | 🔴 **CRITICAL** | **2-3 ngày** |
| Sprint 1 | TASK-001, 002, 003, 004, 005 | 🔴 CRITICAL | 1-2 ngày |
| Sprint 2 | TASK-006, 007 | 🔴 CRITICAL | 2-3 ngày |
| Sprint 3 | TASK-008 | 🟠 HIGH | 2 ngày |
| Sprint 4 | TASK-009 | 🟠 HIGH | 1-2 ngày |
| Sprint 5 | TASK-010 | 🟠 HIGH | 3-4 ngày |
| Sprint 6 | TASK-011, 012, 013, 014 | 🟡 MEDIUM | 2-3 ngày |
| Sprint 7 | TASK-015, 016, 017 | 🟡-🟢 LOW | 2 ngày |
| **Sprint UI** | **TASK-UI-001~007** | 🟡 **MEDIUM** | **3-4 ngày** |

**Tổng ước lượng:** ~15-20 ngày làm việc

---

## 🔄 ĐIỂM KHÁC BIỆT VỚI LINK WHISPER

| Tính năng | Link Whisper | AI Internal Linker | Lợi thế |
|---|---|---|---|
| AI Provider | OpenAI (own API) | OpenAI + Gemini + Grok | ✅ Linh hoạt hơn |
| Silo System | ❌ Không có | ✅ Pillar/Cluster rules | ✅ Lợi thế |
| Vietnamese Support | ❌ Word stemmer chỉ EN | ✅ Unicode regex từ đầu | ✅ Lợi thế |
| Click Tracker | ✅ Đầy đủ | 🚧 Cần làm (TASK-008) | LW hơn |
| GSC Integration | ✅ OAuth2 đầy đủ | 🚧 Cần làm (TASK-010) | LW hơn |
| URL Changer | ✅ Có | 🚧 Cần làm (TASK-011) | LW hơn |
| AI Scoring | ✅ Chấm điểm suggestion | ❌ Không có | LW hơn |
| Multi-site | ✅ Hỗ trợ | ❌ Chưa xét | LW hơn |
| Price | $ (subscription) | Free / Self-hosted | ✅ Lợi thế |
