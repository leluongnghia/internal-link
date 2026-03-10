# 🚀 AI Internal Linker — Kế Hoạch Triển Khai Toàn Diện

**Plugin:** AI Internal Linker (`internal-links`)
**Phiên bản hiện tại:** 1.0.6
**Cập nhật kế hoạch:** 2026-03-10
**Tham khảo cạnh tranh:** Link Whisper Premium (đã phân tích)

---

## 📊 HIỆN TRẠNG PLUGIN (v1.0.6)

### ✅ Đã Hoàn Thành
| Module | Class / File | Trạng thái |
|---|---|---|
| DB Tables: `ail_logs`, `ail_silos`, `ail_anchor_log` | `class-ail-activator.php` | ✅ Ổn định |
| AI Phase 1 — Gọi AI tìm cụm từ | `class-ail-injector.php` | ✅ Có, cần cải tiến |
| PHP Phase 2 — Regex Unicode inject | `class-ail-injector.php` | ✅ Hoạt động tốt |
| Silo Gate — Pillar/Cluster rules | `class-ail-silo.php` | ✅ Logic đúng |
| Background Sweep (WP-Cron) | `class-ail-sweeper.php` | ✅ Cơ bản |
| Anchor Usage Tracker | `ail_anchor_log` table | ✅ Hoạt động |
| Interactive Scanner (meta box) | `class-ail-admin.php` | ✅ Có |
| Multi-provider AI (OpenAI/Gemini/Grok) | `class-ail-injector.php` | ✅ Đủ 3 provider |
| Reports Dashboard | `ail-admin-reports.php` | ✅ Cơ bản |

### ❌ Còn Thiếu (theo Roadmap + học từ Link Whisper)
| Tính năng | Độ ưu tiên | Ghi chú |
|---|---|---|
| **Manual Links UI** (Nhập Keyword→URL tay) | 🔴 CRITICAL | Sweeper cần nguồn data này |
| **Silo Manager UI** | 🔴 CRITICAL | DB có, thiếu giao diện |
| **GSC Integration** (Opportunity Keywords) | 🟠 HIGH | LW có `SearchConsole.php` + `TargetKeyword.php` |
| **Click Tracker** (Theo dõi click link) | 🟠 HIGH | LW có `ClickTracker.php` — tính năng killer |
| **Link Budget tách 2 Phase rõ ràng** | 🟡 MEDIUM | Fix logic hiện tại |
| **Velocity Control tinh vi** (Sweep per-post timestamp) | 🟡 MEDIUM | LW có cron batch offset |
| **Sweeper + Silo Gate check** | 🔴 BUG | Sweeper hiện bỏ qua Silo rules |
| **URL Changer** (Đổi URL hàng loạt) | 🟡 MEDIUM | LW có `URLChanger.php` |
| **nofollow / target="_blank"** options | 🟢 LOW | Tùy chọn link attributes |
| **Link Report per-post** (Inbound/Outbound) | 🟠 HIGH | LW có `Report.php` với inbound/outbound |
| **Broken Link Checker** | 🟢 LOW | LW có trong `Maintenance.php` |
| **Import/Export Keywords** CSV | 🟡 MEDIUM | LW có `Excel.php` + `autolink-import-sample.csv` |
| **Utility Class** tách `replace_phrase_safely()` | 🟡 MEDIUM | Tránh trùng code |

---

## 🏗 KIẾN TRÚC FILE MỤC TIÊU

```text
Internal-Links/
├── internal-links.php
├── includes/
│   ├── class-ail-activator.php          ✅ Cập nhật thêm tables mới
│   ├── class-ail-loader.php             ✅ Đăng ký thêm hooks mới
│   ├── class-ail-injector.php           ✅ Cải tiến Link Budget 2 Phase
│   ├── class-ail-retriever.php          ✅ Tối ưu cache
│   ├── class-ail-silo.php               ✅ Giữ nguyên
│   ├── class-ail-sweeper.php            ✅ Fix Silo Gate + Velocity
│   ├── class-ail-html-parser.php        🆕 Utility: tách replace_phrase_safely()
│   ├── class-ail-gsc.php                🆕 Google Search Console OAuth + API
│   ├── class-ail-click-tracker.php      🆕 Click Tracking (học từ LW)
│   └── class-ail-link-report.php        🆕 Per-post Inbound/Outbound Report
├── admin/
│   ├── class-ail-admin.php              ✅ Thêm menu items mới
│   ├── js/
│   │   ├── ail-admin.js                 ✅ Thêm JS cho Manual Links UI + Silo UI
│   │   └── ail-click-tracker.js         🆕 Frontend click tracking
│   └── partials/
│       ├── ail-admin-display.php        ✅ Cập nhật Settings
│       ├── ail-admin-reports.php        ✅ Cải tiến với filters
│       ├── ail-admin-manual-links.php   🆕 Manual Keyword→URL UI
│       ├── ail-admin-silo-manager.php   🆕 Silo Pillar/Cluster UI
│       ├── ail-admin-gsc.php            🆕 GSC Integration UI
│       └── ail-admin-click-report.php   🆕 Click stats UI
└── README.md
```

---

## 📋 CÁC TÍNH NĂNG HỌC TỪ LINK WHISPER PREMIUM

### 1. GSC Integration (`SearchConsole.php` + `TargetKeyword.php`)
LW xây dựng OAuth2 đầy đủ với Google Search Console, lấy dữ liệu keyword theo ngày, lưu vào DB riêng, xử lý incremental (từng ngày một để tránh timeout). Keyword từ GSC được merge với Yoast/RankMath SEO plugin keywords. Chúng ta sẽ áp dụng để phát hiện **Opportunity Keywords** (impressions > 500, CTR < 1%).

### 2. Click Tracker (`ClickTracker.php`)
LW theo dõi từng click vào internal link: lưu IP, thời gian, bài nguồn, bài đích. Có giao diện report chi tiết với date range filter. Cực kỳ giá trị để biết link nào đang hoạt động. **Đây là killer feature cần thêm vào.**

### 3. Link Report per-post (`Report.php`)
LW scan toàn bộ site, build bảng `link_report` với inbound/outbound count per post. Report có sort/filter/search. Chúng ta học cách tổ chức data này.

### 4. Batch Processing thông minh (`AI.php`)
LW xây WP-Cron batch với offset tracking, retry logic, và batch log. Không chỉ `posts_per_run = 20` cứng như chúng ta mà còn có `get_autolinking_cron_batch($offset, $type)` để tiếp tục từ điểm dừng.

### 5. Import/Export CSV (`Excel.php` + `autolink-import-sample.csv`)
LW cho phép import keywords từ CSV với format chuẩn. Rất tiện cho người dùng có sẵn keyword list.

### 6. URL Changer (`URLChanger.php`)
Khi URL bài viết thay đổi, LW tự động cập nhật tất cả internal links trỏ về URL cũ. Tính năng này rất quan trọng cho site lớn.

### 7. Word Stemmer + Ignore Lists (`includes/word_stemmers/`, `ignore_word_lists/`)
LW có 57 file word stemmer theo ngôn ngữ và 25 file ignore word list. Giúp matching chính xác hơn, tránh link vào stop words. **Cần bổ sung danh sách stop words tiếng Việt.**

---

## 📐 THIẾT KẾ DB MỚI CẦN THÊM

```sql
-- Click Tracker Table (NEW)
CREATE TABLE wp_ail_click_log (
    id         mediumint(9)  NOT NULL AUTO_INCREMENT,
    link_url   varchar(500)  NOT NULL,
    source_post_id bigint(20) NOT NULL,
    target_post_id bigint(20) DEFAULT 0,
    anchor_text varchar(255)  NOT NULL,
    visitor_ip  varchar(100)  NOT NULL,
    clicked_at  datetime      NOT NULL,
    PRIMARY KEY (id),
    KEY source_post_id (source_post_id),
    KEY clicked_at (clicked_at)
);

-- GSC Opportunity Keywords Table (NEW)
CREATE TABLE wp_ail_gsc_keywords (
    id          mediumint(9)  NOT NULL AUTO_INCREMENT,
    keyword     varchar(500)  NOT NULL,
    page_url    varchar(500)  NOT NULL,
    impressions int(11)       DEFAULT 0,
    clicks      int(11)       DEFAULT 0,
    ctr         float         DEFAULT 0,
    position    float         DEFAULT 0,
    data_date   date          NOT NULL,
    processed   tinyint(1)    DEFAULT 0,
    created_at  datetime      DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY keyword (keyword(191)),
    KEY processed (processed)
);

-- Link Report Table (NEW) — Inbound/Outbound per post
CREATE TABLE wp_ail_link_report (
    id              mediumint(9)  NOT NULL AUTO_INCREMENT,
    post_id         bigint(20)    NOT NULL,
    inbound_count   int(11)       DEFAULT 0,
    outbound_count  int(11)       DEFAULT 0,
    last_scanned    datetime      DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY post_id (post_id)
);
```

---

## ⚙️ CẢI TIẾN SWEEPER (Fix Bugs)

**Bug 1: Sweeper bỏ qua Silo Gate**
```php
// Thêm vào process_phrase() trước khi inject:
$allowed_ids = AIL_Silo::get_allowed_targets($post->ID);
if ($allowed_ids !== false) {
    // Chỉ inject nếu target URL thuộc allowed targets
    $target_post_id = url_to_postid($url);
    if ($target_post_id && !in_array($target_post_id, $allowed_ids)) {
        continue; // Bỏ qua, vi phạm Silo Gate
    }
}
```

**Bug 2: Velocity Control thiếu per-post timestamp**
```php
// Thêm post_meta tracking:
$last_swept = get_post_meta($post->ID, '_ail_last_swept', true);
if ($last_swept && (time() - strtotime($last_swept)) < DAY_IN_SECONDS) {
    continue; // Bỏ qua bài vừa quét trong 24h
}
update_post_meta($post->ID, '_ail_last_swept', current_time('mysql'));
```

**Bug 3: Sweeper hardcode `posts_per_run = 20`**
```php
// Đọc từ option thay vì hardcode:
$posts_per_run = intval(get_option('ail_sweep_batch_size', 20));
```

---

## 🎯 LINK BUDGET — TÁCH 2 PHASE RÕ RÀNG

```php
public function inject_links($content, $post_id) {
    $max_links = intval(get_option('ail_max_links', 5));
    $links_budget = $max_links;

    // === PHASE 1: AI Linking ===
    $ai_mappings = $this->get_ai_suggestions($content, $candidates, $links_budget);
    $content = $this->apply_mappings($content, $ai_mappings, $links_budget, $applied_count);
    $links_budget -= $applied_count; // Cập nhật ngân sách còn lại

    // === PHASE 2: PHP Regex Fallback (chỉ chạy nếu còn budget) ===
    if ($links_budget > 0) {
        $manual_rules = get_option('ail_manual_links', []);
        $content = $this->apply_manual_rules($content, $manual_rules, $links_budget);
    }

    return $content;
}
```
