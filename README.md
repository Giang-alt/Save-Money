# Save Money - Ứng dụng Quản lý Tiết kiệm Tiền

Ứng dụng web đơn giản để theo dõi thu chi và quản lý tiết kiệm tiền.

## 🚀 Cài đặt

### Yêu cầu hệ thống
- XAMPP (Apache + MySQL + PHP)
- PHP 7.4 trở lên
- MySQL 5.7 trở lên

### Bước 1: Clone repository
```bash
git clone <your-repository-url>
cd save_money_backup
```

### Bước 2: Cấu hình Database

1. **Tạo database mới** trong phpMyAdmin:
   - Tên database: `savemoney_db`
   - Charset: `utf8mb4_unicode_ci`

2. **Cấu hình kết nối database**:
   ```bash
   # Copy file mẫu
   cp php/config.example.php php/config.php
   ```

3. **Chỉnh sửa file `php/config.php`**:
   ```php
   define('DB_HOST', 'localhost');           // Địa chỉ database server
   define('DB_NAME', 'savemoney_db');        // Tên database
   define('DB_USER', 'root');                // Username database
   define('DB_PASS', '');                    // Password database
   ```

### Bước 3: Khởi chạy ứng dụng

1. **Copy toàn bộ thư mục** vào `htdocs` của XAMPP
2. **Khởi động XAMPP** (Apache + MySQL)
3. **Truy cập**: `http://localhost/save_money_backup`

## 📁 Cấu trúc thư mục

```
save_money_backup/
├── index.html              # Trang chủ
├── php/
│   ├── config.example.php  # File mẫu cấu hình
│   ├── config.php          # File cấu hình thực tế (không up GitHub)
│   ├── connect.php         # Kết nối database
│   ├── init.php           # Khởi tạo
│   ├── login.php          # Xử lý đăng nhập
│   ├── register.php       # Xử lý đăng ký
│   └── api_*.php          # Các API xử lý dữ liệu
└── save_money_api/
    ├── connect.php        # Kết nối database
    ├── login.php          # API đăng nhập
    └── register.php       # API đăng ký
```

## 🔧 Tính năng

- ✅ Đăng ký/Đăng nhập người dùng
- ✅ Thêm giao dịch thu/chi
- ✅ Xem danh sách giao dịch
- ✅ Thống kê thu chi theo thời gian
- ✅ Phân loại danh mục chi tiêu

## 📝 Lưu ý

- **KHÔNG up file `config.php`** lên GitHub (đã có trong .gitignore)
- Luôn sử dụng `config.example.php` làm mẫu
- Backup database thường xuyên

## 🤝 Đóng góp

1. Fork repository
2. Tạo branch mới (`git checkout -b feature/AmazingFeature`)
3. Commit thay đổi (`git commit -m 'Add some AmazingFeature'`)
4. Push lên branch (`git push origin feature/AmazingFeature`)
5. Tạo Pull Request

## 📄 License

MIT License - xem file [LICENSE](LICENSE) để biết thêm chi tiết.
