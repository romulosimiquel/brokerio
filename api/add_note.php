<?php
/**
 * Add Note API
 * POST /api/add_note.php
 * 
 * Expected JSON payload:
 * {
 *   "property_id": 1,
 *   "note": "This is a note about the property"
 * }
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    $propertyId = $input['property_id'] ?? null;
    $note = trim($input['note'] ?? '');
    
    // Validation
    if (!$propertyId || !is_numeric($propertyId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing property_id']);
        exit;
    }
    
    if (empty($note)) {
        http_response_code(400);
        echo json_encode(['error' => 'Note cannot be empty']);
        exit;
    }
    
    $pdo = getDbConnection();
    
    // Verify property exists
    $stmt = $pdo->prepare("SELECT id FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Property not found']);
        exit;
    }
    
    // Insert note
    $stmt = $pdo->prepare("INSERT INTO notes (property_id, note) VALUES (?, ?)");
    $stmt->execute([$propertyId, $note]);
    
    $noteId = $pdo->lastInsertId();
    
    // Return success with created note
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'note' => [
            'id' => $noteId,
            'property_id' => $propertyId,
            'note' => $note,
            'created_at' => date('Y-m-d H:i:s')
        ]
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

