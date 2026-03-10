# Plugin AI Internal Linker - Kế Hoạch Triển Khai

**Vị trí Đích**: `c:\Users\leluongnghia\Desktop\Plugin az\Internal-Links`
**Mục tiêu**: Tạo một plugin WordPress độc lập sử dụng AI để tự động quét nội dung trang web và chèn các liên kết nội bộ phù hợp theo ngữ cảnh.

## Yêu Cầu Người Dùng Xem Xét
> [!IMPORTANT]
> Đây là một **Plugin Mới**. Nó sẽ yêu cầu cấu hình API Key riêng (OpenAI/Gemini/Grok) trừ khi chúng ta quyết định tự động phát hiện key từ plugin khác (không khuyến nghị cho bản phân phối độc lập).

## Cấu Trúc Plugin
```text
internal-links/
├── internal-links.php          # File chính của plugin
├── admin/
│   ├── class-ail-admin.php     # Giao diện Admin & Cài đặt
│   └── partials/               # Các file View (HTML)
├── includes/
│   ├── class-ail-loader.php
│   ├── class-ail-retriever.php # Lấy bài viết ứng viên (Lọc thông minh)
│   └── class-ail-injector.php  # Xử lý giao tiếp AI & thay thế nội dung
└── assets/                     # CSS/JS
```

## Các Tính Năng Đề Xuất

### 1. Cài Đặt Admin
- **Menu Cấp Cao**: "Internal Links".
- **Tab Chung**:
    - **AI Provider**: Dropdown (OpenAI, Gemini, Grok).
    - **API Keys**: Các trường nhập riêng cho từng provider (Chỉ hiện trường tương ứng với provider đang chọn).
    - **Lựa Chọn Model**: Dropdown list tương ứng với từng provider (OpenAI: gpt-4o..., Gemini: 1.5-pro..., Grok).
    - "Auto-Link on Save": Tùy chọn tự động xử lý bài viết khi lưu.
    - **Optimization Skill**: Checkbox list. Mặc định chọn tất cả nếu user không chọn gì.
- **Tab Chiến Lược**:
    - **Nguồn Link**: "Cùng Chuyên Mục" (Same Category), "Cùng Thẻ" (Same Tag), hoặc "Toàn Bộ Nội Dung" (All Content).
    - **Số Link Tối Đa mỗi Bài**: Mặc định 5.

### 2. Logic Cốt Lõi (`class-ail-injector.php`)
- **Hàm**: `inject_links($content, $post_id)`
- **Logic**:
    1.  **Lấy Cấu Hình**: Lấy API Key và Model dựa trên Provider đang chọn.
    2.  **Lấy Ứng Viên**: Gọi `AIL_Retriever`.
    3.  **Xây Dựng Prompt**: Tích hợp Optimization Skill (nếu có).
    4.  **Gọi AI**: Gửi prompt.
    5.  **Xử Lý Đầu Ra**: Thay thế nội dung.
    6.  **Ghi Log**: Lưu thông tin vào bảng `wp_ail_logs`.

### 3. Hệ Thống Báo Cáo
- Tạo bảng `wp_ail_logs` khi activate plugin.
- Thêm trang submenu "Reports" hiển thị danh sách các lần AI chèn link (Post, Provider, Model, Time).

### 4. API Sử Dụng Bên Ngoài
- Cung cấp một hàm toàn cục: `ail_process_content($content, $post_id_context)`

### 5. Tích Hợp SEO Kỹ Thuật
- Đảm bảo tất cả link là **dofollow**.
- Chỉ chèn link vào nội dung thân bài (body content).
