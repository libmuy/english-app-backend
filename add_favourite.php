<?php
require 'user/db_config.php';
require 'user/token.php';

try {
    validateToken();

    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['user_id']) || !isset($data['course']) || !isset($data['sentence'])) {
        throw new Exception('Invalid input data');
    }

    $userName = $data['user_id'];
    $course = $data['course'];
    $sentence = $data['sentence'];

    $stmt = $conn->prepare("INSERT INTO favorites (user_id, course, sentence) VALUES (?, ?, ?)");
    if (!$stmt) {
        throw new Exception('Database prepare statement failed');
    }

    $stmt->bind_param("sss", $userName, $course, $sentence);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Failed to add favorite');
    }

    $stmt->close();

    echo json_encode(['message' => 'Favorite added successfully']);
} catch (Exception $e) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => $e->getMessage()]);
}
?>
