<?php
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';

$data = ENSURE_TOKEN_METHOD_ARGUMENT(['user_id']);
$userName = $data['user_id'];
// reduces the number of database queries from 3 to 1, 
// potentially improving performance by minimizing round trips to the database
$query = "
    SELECT cm.id, cm.name, 'desc', NULL AS audio_length_sec, NULL AS sub_count
    FROM favorite_resource fr
    JOIN category_master cm ON fr.id = cm.id
    WHERE fr.user_id = ? AND fr.type = 'category'

    UNION ALL

    SELECT cr.id, cr.name, 'desc', NULL AS audio_length_sec, cr.episode_count AS sub_count
    FROM favorite_resource fr
    JOIN course_master cr ON fr.id = cr.id
    WHERE fr.user_id = ? AND fr.type = 'course'

    UNION ALL

    SELECT em.id, em.name, NULL AS 'desc', em.audio_length_sec, em.sentence_count AS sub_count
    FROM favorite_resource fr
    JOIN episode_master em ON fr.id = em.id
    WHERE fr.user_id = ? AND fr.type = 'episod'
";

[$stmt, $result] = execQuery($query, "sss", $userName, $userName, $userName);

// Initialize empty arrays for categories, courses, and episodes
$categories = [];
$courses = [];
$episodes = [];

while ($row = $result->fetch_assoc()) {
    switch ($row['resource_type']) {
        case 'category':
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'desc' => $row['desc']
            ];
            break;
        case 'course':
            $courses[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'desc' => $row['desc'],
                'episode_count' => $row['sub_count']
            ];
            break;
        case 'episod':
            $episodes[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'audio_length_sec' => $row['audio_length_sec'],
                'sentence_count' => $row['sub_count']
            ];
            break;
    }
}

$stmt->close();

// Return the JSON response
header('Content-Type: application/json');
echo json_encode([
    'category' => $categories,
    'course' => $courses,
    'episode' => $episodes
]);
?>
