# Phân Tích Lỗi OpenRouter Solar-Pro (Status 200, Finish Reason: Unknown)

## Vấn đề
Mô hình `upstage/solar-pro-3:free` trên OpenRouter thỉnh thoảng trả về mã lỗi:
`"OpenRouter Error (upstage/solar-pro-3:free): API returned empty content. Status: 200, Finish Reason: unknown."`

## Phân tích nguyên nhân
Mặc dù thông số danh nghĩa trên trang chủ OpenRouter ghi Context Window và Max Output của mô hình này đều là 128K, việc API trả về lỗi như trên khi yêu cầu một output 8.192 tokens xuất phát từ 3 đặc thù thực tế khi làm việc với các cổng API trung gian dạng free tier:

1. **Giới hạn Timeout nội bộ (Timeout drops):**
   * Theo thông số thực tế, Throughput của model này khá chậm (khoảng 20tps). 
   * Để xuất ra 8.192 tokens, model phải chạy liên tục trong khoảng ~410 giây (gần 7 phút).
   * Đa số các cổng API miễn phí sẽ tự động drop (ngắt kết nối) nếu request kéo dài quá 1-2 phút.
   * Khi ngắt do timeout, server không trả về lỗi 504 hay 429 mà đẩy ra một file JSON rỗng với `finish_reason: unknown`.

2. **Cơ chế ngắt tải ngầm (Silent Load Shedding):**
   * OpenRouter sẽ `routes requests` (định tuyến) tới các provider.
   * Tại cổng `:free`, khi có nhiều traffic đồng thời mà lại gặp một request đòi lượng output quá lớn (vd: 8.192), provider có xu hướng "im lặng bỏ qua" thay vì báo lỗi hết quota, dẫn đến HTTP Status 200 nhưng Content lại rỗng.

3. **Ngưỡng Max-Tokens nội bộ (Internal Threshold):**
   * Dù kiến trúc mô hình hỗ trợ lý thuyết tới 128K, nhà cung cấp (Upstage) chạy bản host miễn phí thường đặt giới hạn cứng ở ngưỡng thấp hơn (vd: 2000-4000) đối với các request cá lẻ để giảm chi phí tài nguyên máy chủ.

## Giải pháp (Đã triển khai trong v1.6.26)
Hạ mức giới hạn `max_tokens` mặc định thành mức thực tế an toàn hơn (trong đoạn mã `generate_content` tại `includes/ai/class-aprg-openrouter-provider.php`):

```php
$max_tokens = isset($options['max_tokens']) ? $options['max_tokens'] : 8192;

// Safety caps for certain OpenRouter models
// Solar-pro features a 4096 context window (effective). Setting max_tokens too high causes it to fail silently.
if (strpos($model, 'solar-pro') !== false) {
    $max_tokens = min(2500, $max_tokens); 
} elseif (strpos($model, 'free') !== false) {
    $max_tokens = min(4000, $max_tokens);
}
```

* Giải pháp này sẽ giúp các model hoạt động nhanh hơn, tránh timeout, vượt qua giới hạn của server miễn phí.
* Việc chia lô sản phẩm ra (tối đa 2 SP/batch) rồi xử lý dần cũng hỗ trợ tốt cho giới hạn token ngắn này.
