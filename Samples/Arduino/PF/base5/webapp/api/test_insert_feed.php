<?php
// Direct test to insert a feed record into history table
header('Content-Type: application/json');
include '../includes/db_config.php';

// Test 1: Insert a test feed record
$testDate = date('Y-m-d');
$testTime = date('H:i:s');
$testRounds = 3;
$testType = 'Quick';
$testStatus = 'Success';

echo "<h2>Testing Feed History Insert</h2>";
echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
echo "<p><strong>Server:</strong> " . DB_SERVER . "</p>";

// Check connection
if (!$conn) {
    echo "<p style='color:red;'>❌ Database connection FAILED: " . mysqli_connect_error() . "</p>";
    exit;
}
echo "<p style='color:green;'>✓ Database connected</p>";

// Check if table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'history'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo "<p style='color:red;'>❌ History table does NOT exist!</p>";
    echo "<p>Please run the database setup SQL script first.</p>";
    exit;
}
echo "<p style='color:green;'>✓ History table exists</p>";

// Show current record count
$countQuery = mysqli_query($conn, "SELECT COUNT(*) as count FROM history");
$countRow = mysqli_fetch_assoc($countQuery);
$beforeCount = $countRow['count'];
echo "<p>Current history records: <strong>$beforeCount</strong></p>";

// Try to insert
$sql = "INSERT INTO history (feed_date, feed_time, rounds, type, status) 
        VALUES ('$testDate', '$testTime', $testRounds, '$testType', '$testStatus')";

echo "<p><strong>SQL Query:</strong><br><code>$sql</code></p>";

if (mysqli_query($conn, $sql)) {
    $insertId = mysqli_insert_id($conn);
    echo "<p style='color:green;'>✅ <strong>SUCCESS!</strong> Inserted test feed record (ID: $insertId)</p>";
    
    // Verify it was inserted
    $verifyQuery = mysqli_query($conn, "SELECT * FROM history WHERE id = $insertId");
    if ($verifyQuery && mysqli_num_rows($verifyQuery) > 0) {
        $row = mysqli_fetch_assoc($verifyQuery);
        echo "<p style='color:green;'>✓ Verified: Record exists in database</p>";
        echo "<pre>" . print_r($row, true) . "</pre>";
    }
    
    // Count again
    $countQuery2 = mysqli_query($conn, "SELECT COUNT(*) as count FROM history");
    $countRow2 = mysqli_fetch_assoc($countQuery2);
    $afterCount = $countRow2['count'];
    echo "<p>History records after insert: <strong>$afterCount</strong></p>";
    
    // Show recent records
    echo "<h3>Recent Feed History (Last 5):</h3>";
    $recentQuery = mysqli_query($conn, "SELECT * FROM history ORDER BY created_at DESC LIMIT 5");
    if ($recentQuery) {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Date</th><th>Time</th><th>Rounds</th><th>Type</th><th>Status</th><th>Created At</th></tr>";
        while ($record = mysqli_fetch_assoc($recentQuery)) {
            echo "<tr>";
            echo "<td>{$record['id']}</td>";
            echo "<td>{$record['feed_date']}</td>";
            echo "<td>{$record['feed_time']}</td>";
            echo "<td>{$record['rounds']}</td>";
            echo "<td>{$record['type']}</td>";
            echo "<td>{$record['status']}</td>";
            echo "<td>{$record['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} else {
    echo "<p style='color:red;'>❌ <strong>FAILED!</strong> Could not insert record</p>";
    echo "<p style='color:red;'><strong>Error:</strong> " . mysqli_error($conn) . "</p>";
    echo "<p style='color:red;'><strong>Error Code:</strong> " . mysqli_errno($conn) . "</p>";
}

// Test get_history.php
echo "<h3>Testing get_history.php API:</h3>";
$historyApiUrl = '../api/get_history.php';
if (file_exists($historyApiUrl)) {
    echo "<p>Fetching from get_history.php...</p>";
    // We'll need to make a request to it
    $historyData = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/get_history.php');
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($historyData) . "</pre>";
}

mysqli_close($conn);

echo "<hr>";
echo "<p><a href='test_insert_feed.php'>Run Test Again</a> | <a href='../pages/dashboard.php'>Go to Dashboard</a></p>";
?>
