<?php
require_once __DIR__ . '/api/db.php';

$error = '';
$success = false;
$propertyData = null;

// Fetch all properties for the sidebar
$allProperties = [];
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT id, name FROM properties ORDER BY created_at DESC");
    $allProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching properties: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
   
    if (empty($name) || empty($address)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $geocodeResult = geocodeAddress($address);
            
            if ($geocodeResult && isset($geocodeResult['lat']) && isset($geocodeResult['lon'])) {
                $pdo = getDbConnection();
                $stmt = $pdo->prepare("
                    INSERT INTO properties (name, address, latitude, longitude, extra_field) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $extraField = json_encode([
                    'confidence' => $geocodeResult['confidence'] ?? null,
                    'type' => $geocodeResult['type'] ?? null,
                    'display_name' => $geocodeResult['display_name'] ?? null
                ]);
                
                $stmt->execute([
                    $name,
                    $address,
                    $geocodeResult['lat'],
                    $geocodeResult['lon'],
                    $extraField
                ]);
                
                $propertyId = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
                $stmt->execute([$propertyId]);
                $propertyData = $stmt->fetch();
                
                $success = true;
            } else {
                $error = 'Could not geocode the address. Please check the address and try again.';
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error = 'Database error occurred. Please try again.';
        } catch (Exception $e) {
            error_log("Geocoding error: " . $e->getMessage());
            $error = 'Error processing address. Please try again.';
        }
    }
}

function geocodeAddress($address) {
    $encodedAddress = urlencode($address);
    $url = "https://nominatim.openstreetmap.org/search?q={$encodedAddress}&format=json&limit=1";

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Brokerio/1.0'
            ],
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);

    print_r($response);
    
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (empty($data) || !isset($data[0])) {
        return null;
    }
    
    $result = $data[0];
    
    return [
        'lat' => floatval($result['lat']),
        'lon' => floatval($result['lon']),
        'display_name' => $result['display_name'] ?? null,
        'type' => $result['type'] ?? null,
        'confidence' => isset($result['importance']) ? round($result['importance'] * 10, 2) : null
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brokerio</title>
    <style>
        <?php include 'styles.css'; ?>
    </style>
</head>
<body>
    <div class="main-content">
        <div class="container">
            <div class="header">
                <h1>🏠 Brokerio</h1>
                <p>Add and enrich property information</p>
            </div>
            <div class="content">
            <?php if ($error): ?>
                <div class="error">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success && $propertyData): ?>
                <div class="success">
                    <strong>Success!</strong> Property has been saved successfully.
                </div>
                
                <div class="property-details">
                    <h2>Property Details</h2>
                    <div class="detail-row">
                        <span class="detail-label">ID:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($propertyData['id']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($propertyData['name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Address:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($propertyData['address']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Latitude:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($propertyData['latitude']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Longitude:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($propertyData['longitude']); ?></span>
                    </div>
                    <?php if ($propertyData['extra_field']): 
                        $extra = json_decode($propertyData['extra_field'], true);
                        if ($extra):
                    ?>
                        <div class="detail-row">
                            <span class="detail-label">Confidence Score:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($extra['confidence'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Location Type:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($extra['type'] ?? 'N/A'); ?></span>
                        </div>
                    <?php endif; endif; ?>
                    <div class="detail-row">
                        <span class="detail-label">Created:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($propertyData['created_at']); ?></span>
                    </div>
                    
                    <a href="public/map.html?id=<?php echo htmlspecialchars($propertyData['id']); ?>" class="map-link">
                        🗺️ View on Map
                    </a>
                    <br>
                    <a href="index.php" class="back-link">← Add Another Property</a>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Property Name</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            placeholder="e.g., Downtown Office Building" 
                            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input 
                            type="text" 
                            id="address" 
                            name="address" 
                            placeholder="e.g., 123 Main St, New York, NY 10001" 
                            value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>"
                            required
                        >
                    </div>
                    <button type="submit">Add Property</button>
                </form>
            <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="sidebar">
        <div class="properties-card">
            <div class="properties-card-header">
                My Properties
            </div>
            <div class="properties-list">
                <?php if (empty($allProperties)): ?>
                    <div class="empty-properties">No properties yet</div>
                <?php else: ?>
                    <?php foreach ($allProperties as $property): ?>
                        <div class="property-item">
                            <a href="public/map.html?id=<?php echo htmlspecialchars($property['id']); ?>">
                                <?php echo htmlspecialchars($property['name']); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
