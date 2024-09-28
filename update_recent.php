<?php
require 'cors_header.php';
require 'vendor/autoload.php';
require 'user/token.php';
require 'user/db_config.php';

// Validate the token
validateToken();

function updateRecentAccess($conn, $userId, $course, $mode, $last_index) {
    $stmt = $conn->prepare("INSERT INTO recent (user_id, course, mode, last_index, access_time) 
                            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
                            ON DUPLICATE KEY UPDATE last_index = VALUES(last_index), access_time = CURRENT_TIMESTAMP");
    if (!$stmt) {
        throw new Exception('Database prepare statement failed');
    }

    // Use "i" for the last_index parameter to indicate it's an integer
    $stmt->bind_param("sssi", $userId, $course, $mode, $last_index);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Failed to update recent access or access not found');
    }

    $stmt->close();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['user_id']) || !isset($data['course']) || !isset($data['mode']) || !isset($data['last_index'])) {
        throw new Exception('Invalid input data');
    }

    $userName = $data['user_id'];
    $course = $data['course'];
    $mode = $data['mode'];
    $last_index = $data['last_index'];
    error_log('course: ' . $course . ', mode: ' . $mode . ', index: ' . $last_index);
    // Update recent access
    updateRecentAccess($conn, $userName, $course, $mode, $last_index);

    echo json_encode(['message' => 'Recent access updated successfully']);
} catch (Exception $e) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => $e->getMessage()]);
}
?>
