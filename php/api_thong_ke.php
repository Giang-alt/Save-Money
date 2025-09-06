<?php
// API lấy thống kê tổng quan từ cơ sở dữ liệu
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
    // Sử dụng file connect để kết nối database
    require_once 'connect.php';
    $pdo = getConnection();
    
    // Set UTF-8 charset
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Lấy tham số thời gian (mặc định là tháng hiện tại)
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    $period = isset($_GET['period']) ? $_GET['period'] : 'current_month'; // current_month, last_month, all_time
    
    // Xác định khoảng thời gian để tính toán
    $dateCondition = '';
    $params = [];
    
    switch ($period) {
        case 'current_month':
            $dateCondition = "WHERE MONTH(ngay) = :month AND YEAR(ngay) = :year";
            $params[':month'] = date('n');
            $params[':year'] = date('Y');
            break;
            
        case 'last_month':
            $lastMonth = date('n') - 1;
            $lastYear = date('Y');
            if ($lastMonth == 0) {
                $lastMonth = 12;
                $lastYear--;
            }
            $dateCondition = "WHERE MONTH(ngay) = :month AND YEAR(ngay) = :year";
            $params[':month'] = $lastMonth;
            $params[':year'] = $lastYear;
            break;
            
        case 'custom':
            if ($month && $year) {
                $dateCondition = "WHERE MONTH(ngay) = :month AND YEAR(ngay) = :year";
                $params[':month'] = $month;
                $params[':year'] = $year;
            }
            break;
            
        case 'all_time':
        default:
            $dateCondition = "";
            break;
    }
    
    // Tính tổng thu nhập
    if (!empty($dateCondition)) {
        $incomeQuery = "SELECT COALESCE(SUM(so_tien), 0) as total_income FROM giao_dich $dateCondition AND loai = 'income'";
        $stmt = $pdo->prepare($incomeQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();
    } else {
        $incomeQuery = "SELECT COALESCE(SUM(so_tien), 0) as total_income FROM giao_dich WHERE loai = 'income'";
        $stmt = $pdo->prepare($incomeQuery);
        $stmt->execute();
    }
    $totalIncome = $stmt->fetch(PDO::FETCH_ASSOC)['total_income'];
    
    // Tính tổng chi tiêu
    if (!empty($dateCondition)) {
        $expenseQuery = "SELECT COALESCE(SUM(so_tien), 0) as total_expense FROM giao_dich $dateCondition AND loai = 'expense'";
        $stmt = $pdo->prepare($expenseQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();
    } else {
        $expenseQuery = "SELECT COALESCE(SUM(so_tien), 0) as total_expense FROM giao_dich WHERE loai = 'expense'";
        $stmt = $pdo->prepare($expenseQuery);
        $stmt->execute();
    }
    $totalExpense = $stmt->fetch(PDO::FETCH_ASSOC)['total_expense'];
    
    // Tính số dư hiện tại (tổng thu nhập - tổng chi tiêu của tất cả thời gian)
    $balanceQuery = "SELECT 
                        COALESCE(SUM(CASE WHEN loai = 'income' THEN so_tien ELSE 0 END), 0) as total_income_all,
                        COALESCE(SUM(CASE WHEN loai = 'expense' THEN so_tien ELSE 0 END), 0) as total_expense_all
                     FROM giao_dich";
    
    $stmt = $pdo->prepare($balanceQuery);
    $stmt->execute();
    $balanceData = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentBalance = $balanceData['total_income_all'] - $balanceData['total_expense_all'];
    
    // Tính phần trăm thay đổi so với tháng trước (nếu là tháng hiện tại)
    $incomeChange = 0;
    $expenseChange = 0;
    $balanceChange = 0;
    
    if ($period === 'current_month') {
        // Tính số liệu tháng trước
        $lastMonth = date('n') - 1;
        $lastYear = date('Y');
        if ($lastMonth == 0) {
            $lastMonth = 12;
            $lastYear--;
        }
        
        // Thu nhập tháng trước
        $lastMonthIncomeQuery = "SELECT COALESCE(SUM(so_tien), 0) as total 
                                FROM giao_dich 
                                WHERE MONTH(ngay) = :month AND YEAR(ngay) = :year AND loai = 'income'";
        $stmt = $pdo->prepare($lastMonthIncomeQuery);
        $stmt->bindValue(':month', $lastMonth, PDO::PARAM_INT);
        $stmt->bindValue(':year', $lastYear, PDO::PARAM_INT);
        $stmt->execute();
        $lastMonthIncome = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Chi tiêu tháng trước
        $lastMonthExpenseQuery = "SELECT COALESCE(SUM(so_tien), 0) as total 
                                 FROM giao_dich 
                                 WHERE MONTH(ngay) = :month AND YEAR(ngay) = :year AND loai = 'expense'";
        $stmt = $pdo->prepare($lastMonthExpenseQuery);
        $stmt->bindValue(':month', $lastMonth, PDO::PARAM_INT);
        $stmt->bindValue(':year', $lastYear, PDO::PARAM_INT);
        $stmt->execute();
        $lastMonthExpense = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $lastMonthBalance = $lastMonthIncome - $lastMonthExpense;
        $currentMonthBalance = $totalIncome - $totalExpense;
        
        // Tính phần trăm thay đổi
        if ($lastMonthIncome > 0) {
            $incomeChange = (($totalIncome - $lastMonthIncome) / $lastMonthIncome) * 100;
        } elseif ($totalIncome > 0) {
            $incomeChange = 100; // Tăng 100% nếu tháng trước = 0, tháng này > 0
        }
        
        if ($lastMonthExpense > 0) {
            $expenseChange = (($totalExpense - $lastMonthExpense) / $lastMonthExpense) * 100;
        } elseif ($totalExpense > 0) {
            $expenseChange = 100;
        }
        
        if ($lastMonthBalance != 0) {
            $balanceChange = (($currentMonthBalance - $lastMonthBalance) / abs($lastMonthBalance)) * 100;
        } elseif ($currentMonthBalance > 0) {
            $balanceChange = 100;
        }
    }
    
    // Lấy thống kê bổ sung
    $additionalStats = [];
    
    // Số giao dịch hôm nay
    $todayTransactionsQuery = "SELECT COUNT(*) as count FROM giao_dich WHERE DATE(ngay) = CURDATE()";
    $stmt = $pdo->prepare($todayTransactionsQuery);
    $stmt->execute();
    $todayTransactions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Danh mục chi tiêu nhiều nhất (trong khoảng thời gian được chọn)
    if (!empty($dateCondition)) {
        $topCategoryQuery = "SELECT danh_muc, SUM(so_tien) as total FROM giao_dich $dateCondition AND loai = 'expense' GROUP BY danh_muc ORDER BY total DESC LIMIT 1";
        $stmt = $pdo->prepare($topCategoryQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();
    } else {
        $topCategoryQuery = "SELECT danh_muc, SUM(so_tien) as total FROM giao_dich WHERE loai = 'expense' GROUP BY danh_muc ORDER BY total DESC LIMIT 1";
        $stmt = $pdo->prepare($topCategoryQuery);
        $stmt->execute();
    }
    $topCategory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Tính mục tiêu tiết kiệm (giả sử mục tiêu là tiết kiệm 20% thu nhập)
    $savingsTarget = $totalIncome * 0.2; // 20% thu nhập
    $actualSavings = $totalIncome - $totalExpense;
    $savingsProgress = $savingsTarget > 0 ? ($actualSavings / $savingsTarget) * 100 : 0;
    $savingsProgress = max(0, min(100, $savingsProgress)); // Giới hạn 0-100%
    
    $additionalStats = [
        'today_transactions' => (int)$todayTransactions,
        'top_expense_category' => $topCategory['danh_muc'] ?? 'Chưa có',
        'savings_progress' => round($savingsProgress, 1),
        'savings_target' => (float)$savingsTarget,
        'actual_savings' => (float)$actualSavings
    ];
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'message' => 'Lấy thống kê thành công',
        'data' => [
            'current_balance' => (float)$currentBalance,
            'total_income' => (float)$totalIncome,
            'total_expense' => (float)$totalExpense,
            'net_savings' => (float)($totalIncome - $totalExpense),
            'changes' => [
                'income_change' => round($incomeChange, 1),
                'expense_change' => round($expenseChange, 1),
                'balance_change' => round($balanceChange, 1)
            ],
            'period' => $period,
            'period_info' => [
                'month' => $period === 'current_month' ? date('n') : $month,
                'year' => $period === 'current_month' ? date('Y') : $year,
                'month_name' => $period === 'current_month' ? 
                    'Tháng ' . date('n') . '/' . date('Y') : 
                    'Tháng ' . $month . '/' . $year
            ],
            'additional_stats' => $additionalStats
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
