<?php
require 'user/db_config.php';
require 'user/token.php';

try {
    validateToken();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['user_id']) || !isset($data['course'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

$userName = $data['user_id'];
$course = $data['course'];

$stmt = $conn->prepare("SELECT mode, last_index FROM recent WHERE user_id = ? AND course = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare statement']);
    exit();
}

$stmt->bind_param("ss", $userName, $course);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to execute statement']);
    $stmt->close();
    exit();
}

// Fetch the result into a variable
$result = $stmt->get_result();
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get result']);
    $stmt->close();
    exit();
}

$last_index = [];
while ($row = $result->fetch_assoc()) {
    $last_index[] = [
        'mode' => $row['mode'],
        'last_index' => $row['last_index']
    ];
}

$stmt->close();

header('Content-Type: application/json');
echo json_encode(['result' => $last_index]);
?>
