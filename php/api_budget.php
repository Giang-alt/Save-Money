<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'connect.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Get database connection
$pdo = getConnection();

try {
    switch($method) {
        case 'GET':
            // Lấy danh sách ngân sách
            $user_id = $_GET['user_id'] ?? null;
            if (!$user_id) {
                throw new Exception('user_id is required');
            }
            
            // Check if budgets table exists
            $checkTable = $pdo->query("SHOW TABLES LIKE 'budgets'");
            if ($checkTable->rowCount() == 0) {
                echo json_encode([
                    'success' => true,
                    'data' => [],
                    'message' => 'Budgets table does not exist'
                ]);
                break;
            }
            
            // Check if user wants to see all budgets (including inactive)
            $show_all = isset($_GET['show_all']) && $_GET['show_all'] === 'true';
            
            if ($show_all) {
                $stmt = $pdo->prepare("
                    SELECT * FROM budgets 
                    WHERE user_id = ? 
                    ORDER BY is_active DESC, created_at DESC
                ");
            } else {
                $stmt = $pdo->prepare("
                    SELECT * FROM budgets 
                    WHERE user_id = ? AND is_active = 1 
                    ORDER BY created_at DESC
                ");
            }
            $stmt->execute([$user_id]);
            $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $budgets,
                'count' => count($budgets)
            ]);
            break;
            
        case 'POST':
            // Tạo ngân sách mới
            if (!$input) {
                throw new Exception('Invalid input data');
            }
            
            $required_fields = ['user_id', 'category', 'budget_amount', 'period_type', 'start_date', 'end_date'];
            foreach ($required_fields as $field) {
                if (!isset($input[$field])) {
                    throw new Exception("Field $field is required");
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO budgets (user_id, category, budget_amount, period_type, start_date, end_date, is_active, alert_percentage, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                $input['user_id'],
                $input['category'],
                $input['budget_amount'],
                $input['period_type'],
                $input['start_date'],
                $input['end_date'],
                $input['is_active'] ?? 1,
                $input['alert_percentage'] ?? 80
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Budget created successfully',
                    'budget_id' => $pdo->lastInsertId()
                ]);
            } else {
                throw new Exception('Failed to create budget');
            }
            break;
            
        case 'DELETE':
            // Xóa ngân sách
            $budget_id = $_GET['id'] ?? null;
            if (!$budget_id) {
                throw new Exception('Budget ID is required');
            }
            
            $stmt = $pdo->prepare("UPDATE budgets SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$budget_id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Budget deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete budget');
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
