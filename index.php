<?php
require_once __DIR__ . '/api/db.php';

$error = '';
$success = false;
$propertyData = null;

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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        input[type="text"] {
            width: 100%;
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        button:active {
            transform: translateY(0);
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }
        .success {
            background: #efe;
            color: #3c3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3c3;
        }
        .property-details {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .property-details h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #666;
            width: 150px;
            flex-shrink: 0;
        }
        .detail-value {
            color: #333;
            flex: 1;
        }
        .map-link {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.3s;
        }
        .map-link:hover {
            background: #5568d3;
        }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
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
</body>
</html>
