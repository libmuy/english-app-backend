<?php
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';

$data = ensure_token_method_argument();
$userId = $data['user_id'];

$query = "SELECT * FROM setting WHERE user_id = ?";
[$stmt, $result] = exec_query($query, "i", $userId);

$setting = $result->fetch_assoc();
$stmt->close();

header('Content-Type: application/json');
echo json_encode( $setting ?? new stdClass());
