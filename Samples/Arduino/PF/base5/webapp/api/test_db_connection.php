<?php
// Database connection test and history table verification
header('Content-Type: application/json');
include '../includes/db_config.php';

$tests = [];

// Test 1: Database connection
$tests['db_connection'] = [
    'name' => 'Database Connection',
    'status' => $conn ? 'PASS' : 'FAIL',
    'details' => $conn ? 'Connected successfully' : 'Failed to connect'
];

// Test 2: Check if history table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'history'");
$tests['history_table_exists'] = [
    'name' => 'History Table Exists',
    'status' => ($tableCheck && mysqli_num_rows($tableCheck) > 0) ? 'PASS' : 'FAIL',
    'details' => ($tableCheck && mysqli_num_rows($tableCheck) > 0) ? 'Table found' : 'Table not found'
];

// Test 3: Check history table structure
$structureCheck = mysqli_query($conn, "DESCRIBE history");
$columns = [];
if ($structureCheck) {
    while ($row = mysqli_fetch_assoc($structureCheck)) {
        $columns[] = $row['Field'];
    }
}
$tests['history_table_structure'] = [
    'name' => 'History Table Structure',
    'status' => (count($columns) > 0) ? 'PASS' : 'FAIL',
    'details' => 'Columns: ' . implode(', ', $columns)
];

// Test 4: Count existing history records
$countCheck = mysqli_query($conn, "SELECT COUNT(*) as count FROM history");
$count = 0;
if ($countCheck) {
    $row = mysqli_fetch_assoc($countCheck);
    $count = $row['count'];
}
$tests['history_record_count'] = [
    'name' => 'History Record Count',
    'status' => 'INFO',
    'details' => "$count records found"
];

// Test 5: Try inserting a test record
$testDate = date('Y-m-d');
$testTime = date('H:i:s');
$testSql = "INSERT INTO history (feed_date, feed_time, rounds, type, status) 
            VALUES ('$testDate', '$testTime', 1, 'Test', 'Success')";
$insertTest = mysqli_query($conn, $testSql);
$insertId = null;

if ($insertTest) {
    $insertId = mysqli_insert_id($conn);
    // Clean up test record
    mysqli_query($conn, "DELETE FROM history WHERE id = $insertId");
}

$tests['insert_test'] = [
    'name' => 'Insert Test',
    'status' => $insertTest ? 'PASS' : 'FAIL',
    'details' => $insertTest ? "Successfully inserted and deleted test record (ID: $insertId)" : 'Insert failed: ' . mysqli_error($conn)
];

// Test 6: Check recent history records
$recentCheck = mysqli_query($conn, "SELECT * FROM history ORDER BY created_at DESC LIMIT 5");
$recentRecords = [];
if ($recentCheck) {
    while ($row = mysqli_fetch_assoc($recentCheck)) {
        $recentRecords[] = [
            'id' => $row['id'],
            'date' => $row['feed_date'],
            'time' => $row['feed_time'],
            'rounds' => $row['rounds'],
            'type' => $row['type'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
}

$tests['recent_records'] = [
    'name' => 'Recent Records (Last 5)',
    'status' => 'INFO',
    'details' => count($recentRecords) . ' records retrieved',
    'records' => $recentRecords
];

// Overall status
$allPass = true;
foreach ($tests as $test) {
    if ($test['status'] === 'FAIL') {
        $allPass = false;
        break;
    }
}

echo json_encode([
    'success' => $allPass,
    'message' => $allPass ? 'All tests passed' : 'Some tests failed',
    'tests' => $tests,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);

mysqli_close($conn);
?>
