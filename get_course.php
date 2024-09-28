<?php
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';

$data = ENSURE_TOKEN_METHOD_ARGUMENT(['course_id']);
$courseId = $data['course_id'];

$query = "SELECT id, name, 'desc', episode_count FROM course_master WHERE id = ?";
[$stmt, $result] = execQuery($query, "i", $courseId);
$course = $result->fetch_assoc();
$stmt->close();

$query = "SELECT id, name, audio_length_sec, sentence_count FROM episode_master WHERE course_id = ?";
[$stmt, $result] = execQuery($query, "i", $courseId);
$episodes = [];
while ($row = $result->fetch_assoc()) {
    $episodes[] = $row;
}
$stmt->close();

$course['episodes'] = $episodes;
header('Content-Type: application/json');
echo json_encode( $course);


