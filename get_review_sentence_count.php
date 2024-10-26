<?php
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';
require 'learning_data_common.php';

$data = ensure_token_method_argument();
$userId = $data['user_id'];
$currentDate = convert2learn_date(new DateTime());
$query = "
    SELECT COUNT(*) as count FROM learning_data
    WHERE user_id = ? AND learned_date + interval_days < ?";

[$stmt, $result] = exec_query($query, "ii", $userId, $currentDate);
if (!$result) {
    send_error_response(500, "Failed to get sentence count");
}
$row = $result->fetch_assoc();
header('Content-Type: application/json');
echo json_encode($row);
