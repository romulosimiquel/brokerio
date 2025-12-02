<?php

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

try {
    $propertyId = $_GET['id'] ?? null;
    
    if (!$propertyId || !is_numeric($propertyId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing property ID']);
        exit;
    }
    
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();
    
    if (!$property) {
        http_response_code(404);
        echo json_encode(['error' => 'Property not found']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE property_id = ? ORDER BY created_at DESC");
    $stmt->execute([$propertyId]);
    $notes = $stmt->fetchAll();
    
    echo json_encode([
        'property' => $property,
        'notes' => $notes
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}

