# Phân tích quá trình viết bài Best (Best Article)

Quá trình sinh bài viết dạng "Best" (Best Article) trong code hiện tại (`class-aprg-best-article.php`) được tổ chức cực kỳ logic, chuyên nghiệp và có thể chia làm 6 bước chính như sau:

## 1. Khởi tạo và Xác định Nguồn ASIN (Initialization)
Trích xuất mọi cài đặt từ hệ thống (Keyword, Year, AI Model, Chế độ chạy, Cài đặt SEO...). Hệ thống sẽ xác định danh sách sản phẩm thông qua 2 chế độ (Processing Mode):

*   **Auto Mode**: Tự động dùng Keyword để cào lên Amazon/API (hàm `search_products`), sau đó giới hạn và lấy ra `$auto_limit` số lượng các sản phẩm tốt nhất (bạn đang set mặc định 10).
*   **Manual Mode**: Người dùng đã duyệt trước và truyền tay một mảng các mã ASIN vào dữ liệu.

## 2. Trích xuất Dữ liệu chi tiết Sản phẩm (Fetching Product Details)
Sau khi có mảng mã ASIN, hàm `get_product_details()` sẽ được kích hoạt. API / Crawler sẽ đi qua từng ASIN và kéo về toàn bộ siêu dữ liệu của mỗi sản phẩm bao gồm: Tên, Hình ảnh, Giá, Rating trung bình, Mô tả kỹ thuật và cả bình luận (Customer Reviews).

Đồng thời plugin cũng tự động chèn Amazon Affiliate Tag của bạn vào link sản phẩm trong bước này.

## 3. Tối ưu SEO Đầu vào (SEO Fundamentals)
Hệ thống chạy các script tự động tạo các giá trị cho nền tảng WordPress:

*   Gen Title (Tiêu đề): Thường sẽ có mẫu "The Best [Keyword] of [Year]".
*   Gen Meta Tags / Description
*   Gen Post Slug (Đường dẫn).
*   (Khâu lưu Schema List sẽ được xử lý ở đoạn sau sau khi nội dung đã có).

## 4. Lõi Generate Nội Dung AI (AI Generation Core)
Đây là đoạn phức tạp nhất, được rẽ làm 2 luồng rõ rệt tuỳ thuộc bạn cấu hình Dual-Model hay không:

### Luồng 1: Dual-Model Mode (Chế độ 2 Model Tối Ưu)

Hệ thống gọi hàm `generate_dual_model_content()`.

*   **Bước 1: Gọi Primary Model để viết "Vỏ Bọc Chiến Lược" (Strategic Content)**
    Đầu tiên, hệ thống sẽ sử dụng Primary Model (mặc định là mô hình thông minh và đắt tiền hơn như `gpt-4o` hoặc `claude-3-5-sonnet`) để viết các thành phần bao quanh bài viết.

    Nó gọi hàm phụ `build_primary_prompt()` để tạo lệnh. Prompt này yêu cầu AI đóng vai một chuyên gia 20+ năm kinh nghiệm để viết các phần:
    0. Quick Answer & Key Takeaways
    1. Quick Summary & Winners
    2. Comparison Table
    3. In-Depth Introduction
    4. Technical Deep Dive
    5. "Best For" Scenarios
    6. Extensive Buying Guide
    7. Final Verdict & Recommendations
    8. FAQs

    Đầu ra của đoạn này thường rất dài và yêu cầu IQ cao để phân tích thị trường, so sánh thông số và đưa ra lời khuyên chọn mua đỉnh cao. Số token tối đa được cấp cho đoạn này là 8192 tokens.

*   **Bước 2: Trích xuất "Top Pick" (Sản phẩm tốt nhất)**
    Hệ thống dùng Regex (`preg_match`) để phân tích nhanh nội dung trả về từ Primary Model để tìm xem AI đã chọn sản phẩm nào là #1 TOP PICK. Thông tin này sẽ được lưu lại (biến `$top_pick`) để bơm làm context (ngữ cảnh) cho Secondary Model ở bước sau, giúp 2 Model hiểu ý nhau (không bị lệch pha kiểu Model 1 khen SP A nhất, nhưng Model 2 lại khen SP B nhất).

*   **Bước 3: Gọi Secondary Model để đánh giá TỪNG sản phẩm (Product Reviews LOOP)**
    Đây là lúc tối ưu chi phí. Việc viết review chi tiết cho từng sản phẩm (500-600 chữ/SP) rất tốn Token. Nếu dùng `gpt-4o` để viết cho 10 sản phẩm thì cực kỳ đắt. Do đó, hệ thống dùng Secondary Model (mặc định là model nhanh, rẻ như `gpt-4o-mini`).

    Và thay vì đưa cả cục 10 sản phẩm cho AI viết cùng lúc (dễ bị AI "lười" và viết ngắn lại), hệ thống sử dụng Vòng lặp (Loop):
    *   Mỗi sản phẩm sẽ được gọi API độc lập một lần thông qua prompt `build_secondary_prompt()`.
    *   Mã lệnh sẽ inject (bơm) sẵn tiêu đề có chứa Rank (Số thứ tự xếp hạng) vào bài đánh giá: `### 1. [Tên Sản Phẩm]`.
    *   Giới hạn token cho mỗi bài đánh giá đơn lẻ này là 4096. Do chỉ viết về 1 sản phẩm nên AI sẽ viết cực kì dài, chi tiết, phân tích chân thực kèm theo Bảng Pros/Cons chuẩn xác.
    *   Cứ mỗi lần xong 1 sản phẩm, hệ thống sẽ tự động ghép nối (`.=`) nội dung bào biến `$full_reviews_content`.
    *   Có hàm `sleep(3)` giữa mỗi lần gọi API để tránh bị Rate Limit (chặn vì gọi máy chủ quá nhanh).

*   **Bước 4: Gộp nội dung (Merge)**
    Sau khi có đủ 2 biến: `$primary_response` (Vỏ bọc) và `$full_reviews_content` (Phần ruột đánh giá chi tiết).
    Hệ thống sẽ gọi hàm `merge_dual_model_content()`. Hàm này dùng một số thuật toán "chia cắt" chuỗi (string split) thông minh để:
    *   Cắt phần Introducing, Summary, Table ở đầu của Primary Content.
    *   Chèn phần Ruột (Toàn bộ bài đánh giá từ Secondary Model) vào chính giữa.
    *   Nối tiếp nửa sau của Primary Content (Buying Guide, FAQs) vào cuối cùng.

    *Kết quả: Bạn có một bài viết 3000-5000 chữ. Phần đầu và cuối phân tích cực sắc sảo (do IQ GPT-4o). Phần giữa đánh giá sản phẩm cực kỳ siêu chi tiết, dài dòng (do nhốt GPT-4o-mini viết từng review một) mà chi phí API lại rẻ bằng 1/10 so với việc dùng GPT-4o viết toàn bộ!*

### Luồng 2: Single-Model Mode + Chế độ Batching (Đơn Model Tối Ưu Dài)

Đây là lối dùng 1 model duy nhất do người dùng chọn (Gemini, ChatGPT,...). Nhưng vì AI hay mắc bệnh "lười" viết ngắn đi khi gặp danh sách quá dài (khoảng >3 sản phẩm), bạn đã viết thuật toán Batching cực hay.
*   Hệ thống băm mảng sản phẩm ra thành các luồng chạy (`$batch_size = 2`).
*   Part 1: Bắt AI viết Intro + Review 2 SP đầu.
*   Part 2...N: Bắt AI "CONTINUE.." viết tiếp từ SP số 3, cấm xuất lại Intro. Delay để tránh rate API limits (nhất là API Google 20s).
*   Cuối cùng nối kết quả. Và thuật toán có 1 cơ chế khử (`strip_duplicate_intro_sections`) nếu nhỡ AI bướng bỉnh lặp lại Intro =)).

## 5. Parse Markdown & Inject Shortcode (Định hình HTML)
Tất cả kết quả thô của AI lúc này là dạng Markdown. Hàm `APRG_Markdown_Parser::parse()` sẽ được kích hoạt:

*   Dịch các thẻ Markdown (#, ##, bảng, in đậm) thành HTML chuẩn.
*   Tìm kiếm tất cả các dòng lệnh `[aprg_card asin="..."]` do AI xuất ra một cách chiến lược, và biến chúng thành các thẻ HTML Product Card siêu đẹp có chứa Link Affiliate và Image của bạn.
*   Cơ chế Safety net: Nhỡ Parser có lỡ cắt nhầm HTML dẫn đến cụt ngủn nội dung, nó có cơ chế fallback lấy lại dữ liệu AI thô `wpautop`.

## 6. Published lên WordPress CMS
*   Gọi `wp_insert_post` hoặc `wp_update_post` với content đầy đủ.
*   Móc hình ảnh Image của sản phẩm #1 làm Featured Image (Ảnh đại diện bài viết) thông qua `APRG_Post_Creator::set_featured_image()`.
*   Tạo JSON-LD Schema Markup và đẩy vào Database.
*   Lưu toàn bộ Meta-data ASIN, tone, log... để phục vụ cho các tính năng Cập nhật/Regenerate sau này.
*   Chuyển trạng thái Queue (hàng chờ) sang Completed.

---
**Tổng kết:** Luồng đi này hiện tại cực kỳ bảo mật (xử lý token an toàn), tiết kiệm chi phí (nhờ dual-model) và đánh giá được mọi góc nhìn hạn chế khả năng AI lười biếng qua Batching. Prompt cũng đã hoàn thiện xuất thẳng shortcode nên hiệu suất tạo bài sẽ ổn định hơn trước rất nhiều!
