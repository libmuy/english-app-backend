<?php
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';
require 'learning_data_common.php';

$data = ensure_token_method_argument();
$userId = $data['user_id'];
$currentDate = convert2learn_date(new DateTime());
$query = "
    SELECT 
        SUM(CASE WHEN learned_date + interval_days <= ? THEN 1 ELSE 0 END) as need_to_review_count,
        SUM(CASE WHEN learned_date = ? THEN 1 ELSE 0 END) as today_learned_count
    FROM learning_data
    WHERE user_id = ?";

[$stmt, $result] = exec_query($query, "iii", $currentDate, $currentDate, $userId);
if (!$result) {
    send_error_response(500, "Failed to get sentence count");
}
$row = $result->fetch_assoc();
$row['need_to_review_count'] = (int)$row['need_to_review_count'];
$row['today_learned_count'] = (int)$row['today_learned_count'];
header('Content-Type: application/json');
echo json_encode($row);
