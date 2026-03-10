# AI Internal Linker Plugin - Deployment Guide

Em đã hoàn thành việc xây dựng plugin **AI Internal Linker** tại thư mục:
`c:\Users\leluongnghia\Desktop\Plugin az\Internal-Links`

## 1. Installation (Cài đặt)
1.  Truy cập trang Plugins trong WordPress Admin.
2.  Nếu anh đang phát triển local, plugin có thể đã hiện sẵn trong danh sách (nếu thư mục nằm trong `wp-content/plugins`). Nếu không, hãy copy thư mục `Internal-Links` vào folder `plugins` của WordPress.
3.  Activate plugin **"AI Internal Linker"**.

## 2. Configuration (Cấu hình)
1.  Vào menu **Internal Links** mới xuất hiện.
2.  **API Provider**: Chọn OpenAI (GPT-4o) hoặc Google Gemini.
3.  **API Key**: Nhập key tương ứng.
4.  **Strategy**:
    -   *Same Category*: Chỉ link đến bài cùng chuyên mục (Khuyên dùng).
    -   *Same Tag*: Chỉ link đến bài cùng thẻ.
    -   *All Content*: Quét toàn bộ web (cẩn thận chậm nếu web lớn).
5.  **Auto-Link on Save**: Bật nếu muốn plugin tự động chèn link mỗi khi anh bấm Save/Publish bài viết.

## 3. Usage (Sử dụng)
Có 2 cách sử dụng:
- **Tự động**: Nếu bật "Auto-Link on Save", cứ viết bài bình thường, khi save lại AI sẽ tự sửa nội dung.
- **Tích hợp**: Các plugin khác (ví dụ "AI Product Review Generator") có thể gọi hàm sau để nhờ chèn link:
    ```php
    if (function_exists('ail_process_content')) {
        $content = ail_process_content($original_content, $post_id);
    }
    ```

## 4. How it works (Cơ chế)
- Plugin quét 50 bài viết liên quan nhất (theo Title & Excerpt).
- Gửi nội dung + danh sách bài liên quan này cho AI.
- AI sẽ đọc hiểu và viết lại các câu văn một cách tự nhiên để chèn link vào (Natural Language Processing), thay vì chỉ tìm và thay thế từ khóa cứng nhắc.
