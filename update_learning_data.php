<?php
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';
require 'learning_data_common.php';

$keys = ['user_id', 'sentence_id', 'interval_days', 'learned_date', 
         'ease_factor', 'is_graduated', 'is_skipped'];
$data = ensure_token_method_argument($keys);

if (!set_learning_data($data)) {
    send_error_response(500, 'Update learning data failed');
}
