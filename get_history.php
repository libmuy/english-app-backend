<?php
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';

$data = ENSURE_TOKEN_METHOD_ARGUMENT(['user_id']);
$userName = $data['user_id'];

$query = "SELECT course_id, episode_id, favorite_list_id, audio_length_sec, sentence_count, title, last_sentence_id, learned_date
    FROM history 
    WHERE user_id = ? 
    ORDER BY learned_date";

[$stmt, $result] = execQuery($query, "s", $userName);

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = [
        'src' => [
            'course_id' => $row['course_id'],
            'episode_id' => $row['episode_id'],
            'favorite_list_id' => $row['favorite_list_id'],
        ],
        'audio_length_sec' => $row['audio_length_sec'],
        'sentence_count' => $row['sentence_count'],
        'title' => $row['title'],
        'last_sentence_id' => $row['last_sentence_id'],
        'learned_date' => $row['learned_date'],
    ];
}

$stmt->close();

header('Content-Type: application/json');
echo json_encode( $categories);
?>
