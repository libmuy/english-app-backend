<?php
require 'user/db_config.php';
require 'user/token.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    validateToken();

    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['user_id']) || !isset($data['setting_key']) || !isset($data['setting_value'])) {
        throw new Exception('Invalid input data');
    }

    $userName = $data['user_id'];
    $setting_key = $data['setting_key'];
    $setting_value = $data['setting_value'];

    // Check if $conn is properly initialized
    if (!$conn) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Check if the setting already exists
    $check_stmt = $conn->prepare("SELECT setting_value FROM setting WHERE user_id = ? AND setting_key = ?");
    if (!$check_stmt) {
        throw new Exception('Database prepare statement failed: ' . $conn->error);
    }

    $check_stmt->bind_param("ss", $userName, $setting_key);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $check_stmt->bind_result($existing_value);
        $check_stmt->fetch();
        if ($existing_value === $setting_value) {
            throw new Exception('Failed to save setting: No rows affected because the data is identical.');
        }
    }

    $check_stmt->close();

    $stmt = $conn->prepare("INSERT INTO setting (user_id, setting_key, setting_value) VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    if (!$stmt) {
        throw new Exception('Database prepare statement failed: ' . $conn->error);
    }

    $stmt->bind_param("sss", $userName, $setting_key, $setting_value);
    $stmt->execute();

    if ($stmt->error) {
        throw new Exception('Failed to save setting: ' . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception('Failed to save setting: No rows affected.');
    }

    $stmt->close();

    echo json_encode(['message' => 'Setting saved successfully']);
} catch (Exception $e) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => $e->getMessage()]);
}
?>
