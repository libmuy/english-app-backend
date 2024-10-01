<?php
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';

$data = ensure_token_method_argument(['course_id']);
$courseId = $data['course_id'];

$query = "SELECT id, name, description, episode_count FROM course_master WHERE id = ?";
[$stmt, $result] = exec_query($query, "i", $courseId);
$row = $result->fetch_assoc();
$course = [
    'id' => $row['id'],
    'name' => $row['name'],
    'desc' => $row['description'],
    'episode_count' => $row['episode_count'],
];
$stmt->close();

$query = "SELECT id, name, audio_length_sec, sentence_count FROM episode_master WHERE course_id = ?";
[$stmt, $result] = exec_query($query, "i", $courseId);
$episodes = [];
while ($row = $result->fetch_assoc()) {
    $episodes[] = $row;
}
$stmt->close();

$course['episodes'] = $episodes;
header('Content-Type: application/json');
echo json_encode( $course);


