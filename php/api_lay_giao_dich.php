<?php
// API lấy danh sách giao dịch từ cơ sở dữ liệu savemoney_db
// Cho phép CORS để frontend có thể gọi API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Chỉ cho phép GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Chỉ hỗ trợ phương thức GET'
    ]);
    exit();
}

try {
    // Sử dụng file config để kết nối database
    require_once 'config.php';
    $pdo = initializeDatabase();
    
    // Lấy tham số từ query string
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $type = isset($_GET['type']) ? trim($_GET['type']) : null;
    $category = isset($_GET['category']) ? trim($_GET['category']) : null;
    $from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : null;
    $to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : null;
    

    
    // Xây dựng câu truy vấn với các điều kiện lọc
    $where_conditions = [];
    $params = [];
    
    if ($type && in_array(strtolower($type), ['income', 'expense'])) {
        $where_conditions[] = "loai = :type";
        $params[':type'] = strtolower($type);
    }
    
    if ($category) {
        $where_conditions[] = "danh_muc = :category";
        $params[':category'] = $category;
    }
    
    if ($from_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date)) {
        $where_conditions[] = "ngay >= :from_date";
        $params[':from_date'] = $from_date;
    }
    
    if ($to_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)) {
        $where_conditions[] = "ngay <= :to_date";
        $params[':to_date'] = $to_date;
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Đếm tổng số bản ghi
    $count_sql = "SELECT COUNT(*) as total FROM giao_dich $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Xây dựng câu truy vấn chính
    $sql = "SELECT id, loai, danh_muc, so_tien, ngay, ghi_chu, ngay_tao, ngay_cap_nhat 
            FROM giao_dich 
            $where_clause 
            ORDER BY ngay DESC, ngay_tao DESC";
    
    // Thêm LIMIT và OFFSET nếu có
    if ($limit && $limit > 0) {
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
    }
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters với đúng type
    foreach ($params as $key => $value) {
        if ($key === ':limit' || $key === ':offset') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    }
    
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dữ liệu để trả về
    $formatted_transactions = [];
    foreach ($transactions as $transaction) {
        $formatted_transactions[] = [
            'id' => (int)$transaction['id'],
            'type' => $transaction['loai'],
            'category' => $transaction['danh_muc'],
            'amount' => (float)$transaction['so_tien'],
            'date' => $transaction['ngay'],
            'note' => $transaction['ghi_chu'],
            'created_at' => $transaction['ngay_tao'],
            'updated_at' => $transaction['ngay_cap_nhat']
        ];
    }
    
    // Tính toán thông tin phân trang
    $has_more = false;
    if ($limit && $limit > 0) {
        $has_more = ($page * $limit) < $total;
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'message' => 'Lấy danh sách giao dịch thành công',
        'data' => [
            'transactions' => $formatted_transactions,
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
            'has_more' => $has_more,
            'filters' => [
                'type' => $type,
                'category' => $category,
                'from_date' => $from_date,
                'to_date' => $to_date
            ]
        ]
    ]);
    
} catch (PDOException $e) {
    // Xử lý lỗi cơ sở dữ liệu
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    // Xử lý các lỗi khác
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
