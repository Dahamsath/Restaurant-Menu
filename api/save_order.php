<?php
header('Content-Type: application/json');
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_number']) || !isset($data['items']) || !isset($data['total'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        INSERT INTO orders (order_number, customer_name, customer_email, total_amount, payment_status, order_status) 
        VALUES (?, ?, ?, ?, 'completed', 'pending')
    ");
    $stmt->execute([
        $data['order_number'],
        $data['customer_name'] ?? 'Guest',
        $data['customer_email'] ?? '',
        $data['total']
    ]);
    
    $order_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, price) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($data['items'] as $item) {
        $stmt->execute([
            $order_id,
            $item['id'] ?? null,
            $item['name'],
            $item['quantity'] ?? 1,
            $item['price']
        ]);
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>