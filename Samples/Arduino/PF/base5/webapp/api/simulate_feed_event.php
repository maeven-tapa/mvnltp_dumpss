<?php
date_default_timezone_set('Asia/Manila');
// Simulate Arduino sending feed event to hardware_update.php
header('Content-Type: application/json');

echo "<h2>Simulating Arduino Feed Event</h2>";

// Simulate the exact payload the Arduino would send
$testPayload = [
    'dispensed' => 3,
    'type' => 'Quick',
    'status' => 'Success',
    'weight' => 250.50,
    'feed_date' => date('Y-m-d'),
    'feed_time' => date('H:i:s')
];

echo "<h3>Step 1: Preparing Test Payload</h3>";
echo "<pre>" . json_encode($testPayload, JSON_PRETTY_PRINT) . "</pre>";

// Make a POST request to hardware_update.php
$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/hardware_update.php';
echo "<h3>Step 2: Sending to hardware_update.php</h3>";
echo "<p><strong>URL:</strong> $url</p>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-KEY: your_secret_hardware_key_12345'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<h3>Step 3: Response from hardware_update.php</h3>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($curlError) {
    echo "<p style='color:red;'><strong>cURL Error:</strong> $curlError</p>";
}

echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$responseData = json_decode($response, true);
if ($responseData) {
    echo "<p><strong>Parsed Response:</strong></p>";
    echo "<pre>" . print_r($responseData, true) . "</pre>";
    
    if (isset($responseData['success']) && $responseData['success']) {
        echo "<p style='color:green;'>✅ <strong>Server accepted the request</strong></p>";
        if (isset($responseData['updated']) && in_array('feed_event', $responseData['updated'])) {
            echo "<p style='color:green;'>✅ <strong>Feed event was recorded!</strong></p>";
        } elseif (isset($responseData['updated']) && in_array('feed_event_failed', $responseData['updated'])) {
            echo "<p style='color:red;'>❌ <strong>Feed event recording FAILED</strong></p>";
        }
    } else {
        echo "<p style='color:red;'>❌ <strong>Server rejected the request</strong></p>";
    }
}

// Check if record was actually inserted
echo "<h3>Step 4: Verifying Database</h3>";
include '../includes/db_config.php';

$query = "SELECT * FROM history ORDER BY created_at DESC LIMIT 5";
$result = mysqli_query($conn, $query);

if ($result) {
    $count = mysqli_num_rows($result);
    echo "<p>Found <strong>$count</strong> recent records</p>";
    
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Date</th><th>Time</th><th>Rounds</th><th>Type</th><th>Status</th><th>Created At</th></tr>";
    
    $foundTestRecord = false;
    while ($row = mysqli_fetch_assoc($result)) {
        $isNew = (strtotime($row['created_at']) > time() - 10); // Within last 10 seconds
        $style = $isNew ? "background-color: #90EE90;" : "";
        
        echo "<tr style='$style'>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['feed_date']}</td>";
        echo "<td>{$row['feed_time']}</td>";
        echo "<td>{$row['rounds']}</td>";
        echo "<td>{$row['type']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
        
        if ($isNew && $row['type'] == 'Quick' && $row['rounds'] == 3) {
            $foundTestRecord = true;
        }
    }
    echo "</table>";
    
    if ($foundTestRecord) {
        echo "<p style='color:green; font-size:18px; font-weight:bold;'>✅ TEST RECORD FOUND IN DATABASE!</p>";
    } else {
        echo "<p style='color:orange; font-weight:bold;'>⚠ Test record not found (highlighted row should be your test)</p>";
    }
} else {
    echo "<p style='color:red;'>Error querying database: " . mysqli_error($conn) . "</p>";
}

mysqli_close($conn);

echo "<hr>";
echo "<p><a href='simulate_feed_event.php'>Run Test Again</a> | ";
echo "<a href='test_insert_feed.php'>Direct Insert Test</a> | ";
echo "<a href='../pages/dashboard.php'>Go to Dashboard</a></p>";
?>
