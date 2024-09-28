<?php
require 'user/db_config.php';
require 'user/token.php';

try {
    validateToken();

    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['user_id']) || !isset($data['course'])) {
        throw new Exception('Invalid input data');
    }

    $userName = $data['user_id'];
    $course = $data['course'];

    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND course = ?");
    if (!$stmt) {
        throw new Exception('Database prepare statement failed');
    }

    $stmt->bind_param("ss", $userName, $course);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Failed to remove favorite or favorite not found');
    }

    $stmt->close();

    echo json_encode(['message' => 'Favorite removed successfully']);
} catch (Exception $e) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => $e->getMessage()]);
}
?>
