<?php
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';

$data = ENSURE_TOKEN_METHOD_ARGUMENT(['user_id']);
$userName = $data['user_id'];

$query = "SELECT * FROM setting WHERE user_id = ?";
[$stmt, $result] = execQuery($query, "s", $userName);

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

$stmt->close();

header('Content-Type: application/json');
echo json_encode( $categories);
?>
