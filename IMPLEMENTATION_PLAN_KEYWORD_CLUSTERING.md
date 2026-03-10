# Keyword Grouping Feature Proposal for Ai-Internal-Links

Sau khi phân tích file `Best Speakers.xlsx`, tôi nhận thấy đây là cấu trúc dữ liệu xuất ra từ các công cụ SEO (như Semrush/Ahrefs) bao gồm các cột: `Keyword`, `Intent`, `Volume`, `Keyword Difficulty`, `CPC (USD)`, `Competitive Density`, `SERP Features`.

Dựa vào bộ dữ liệu này, để tạo tính năng **Phân tích và Gom nhóm từ khóa (Keyword Grouping / Clustering)** chính xác và ứng dụng hiệu quả cho Internal Link, tôi đề xuất giải pháp sau:

## 1. Thuật toán Gom nhóm Từ khóa (Clustering Algorithm)

### Bước 1: Phân loại theo Intent (Ý định tìm kiếm)
Internal link có độ hiệu quả cao nhất khi điều hướng người dùng đúng phễu (Funnel).
- **Informational (Thông tin):** Giai đoạn tìm hiểu (VD: "how to clean speakers").
- **Commercial / Transactional (Thương mại/Giao dịch):** Giai đoạn mua hàng (VD: "best bluetooth speakers", "buy onn speaker").

### Bước 2: Gom nhóm theo Ngữ nghĩa (Semantic Grouping)
Sử dụng thuật toán so khớp chuỗi (như NLP, Levenshtein distance, hoặc chung Root Word) để tạo các **Topic Clusters (Cụm chủ đề)**.
- VD Cụm "Bluetooth Speaker": *best bluetooth speaker, waterproof bluetooth speaker, cheap bluetooth speaker...*
- Tư duy "Hub & Spoke" (Trục bánh xe): Trong một Cluster, chọn ra từ khóa có **Volume cao nhất** làm "Pillar / Hub Keyword" (Từ khóa chính), các từ khóa còn lại là "Supporting Keywords" (Từ khóa phụ/Long-tail).

## 2. Ứng dụng vào Plugin Ai-Internal-Links

Dựa vào kết quả Keyword Grouping trên, Ai-Internal-Links có thể xây dựng các tính năng sau:

### Tự động thiết lập cấu trúc Hub & Spoke (Topic Cluster)
Kéo file Excel vào Plugin, Plugin sẽ tự động quét các bài viết hiện có trên web.
- **Bài viết Pillar (Hub):** Ứng với từ khóa chính có Volume cao.
- **Bài viết Spoke (Sub-pages):** Ứng với các từ khóa phụ (Long-tail).
**Cơ chế đi link tự động:** Điều hướng Plugin tự động **chỉ cho phép** các bài Spoke link về bài Pillar và các bài Spoke link chéo nhau trong cùng 1 Cluster. Ngăn chặn việc link lan man sang các Cluster không liên quan làm loãng sức mạnh SEO.

### Tự động chọn Anchor Text chất lượng, đa dạng
Thay vì chỉ dùng 1 anchor text lặp đi lặp lại rất dễ bị Google phạt (Over-optimization), dựa vào tập từ khóa phụ trong file Excel:
Khi 1 bài Spoke link về bài Pillar, Plugin sẽ lấy ngẫu nhiên (hoặc xoay vòng) các "Supporting keywords" làm Anchor Text giúp Internal Link tự nhiên và tăng thứ hạng cho nhiều từ khóa phụ cùng lúc.

### Silo Structure Rule (Luật Silo theo Intent)
- Cho phép thiết lập quy tắc: Bài viết có Intent là **Informational** phải luôn có ít nhất 1 Internal Link điều hướng sang bài viết có intent **Commercial / Transactional** cùng cụm chủ đề để tăng tỷ lệ chuyển đổi.

### Báo cáo Keyword Content Gap
Từ file Excel đã gom nhóm, Plugin đối chiếu với dữ liệu bài viết trên Website. Nếu một Cluster còn thiếu nhiều "Supporting keywords" chưa có bài viết tương ứng, Plugin sẽ hiển thị cảnh báo (Gaps) để bạn viết thêm bài lấp đầy bộ từ khóa, tạo thành một Topic Cluster hoàn hảo.

## Kế hoạch phát triển dự kiến
Nếu bạn đồng ý với hướng đi này, chúng ta có thể tiến hành code tính năng Import dữ liệu Excel -> Chạy thuật toán xử lý phân nhóm (Clustering) hiển thị lên giao diện quản trị của Plugin.
