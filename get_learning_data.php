<?php
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';
require 'learning_data_common.php';

$data = ensure_token_method_argument(['sentence_id']);
$userId = $data['user_id'];
$sentenceId = $data['sentence_id'];

$row = get_learning_data($userId, $sentenceId);
if (!$row) {
    send_error_response(404, "Not Found");
}
header('Content-Type: application/json');
echo json_encode($row);
