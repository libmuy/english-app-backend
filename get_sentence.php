<?php
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';

$data = ENSURE_TOKEN_METHOD_ARGUMENT(['user_id']);

$userName = $data['user_id'];
$courseId = $data['course_id'] ?? null;
$episodeId = $data['episode_id'] ?? null;
$favoriteListId = $data['favorite_list_id'] ?? null;
$pageSize = $data['page_size'] ?? null;
$pageNumber = $data['page_number'] ?? null;


function queryRecordCount($query, $paramType, ...$params) {
    [$stmt, $result] = execQuery($query, $paramType, ...$params);
    $count = $result->fetch_row()[0];
    $stmt->close();

    return $count;
}

function querySentences($query, $paramType, ...$params) {
    [$stmt, $result] = execQuery($query, $paramType, ...$params);

    $sentences = [];
    while ($row = $result->fetch_assoc()) {
        $sentences[] = $row;
    }

    $stmt->close();

    return $sentences;
}


function getSentencesByEpisode($userId, $episodeId) {
    $query = "
        SELECT sm.id, sm.episode_id, sm.sentence_idx, sm.start_time, sm.end_time, sm.english, sm.chinese,
        IF(fs.sentence_id IS NOT NULL, 1, 0) AS is_fav, sm.is_have_desc
        FROM sentence_master sm
        LEFT JOIN favorite_sentence fs ON fs.sentence_id = sm.id AND fs.user_id = ?
        WHERE sm.episode_id = ?";

    return querySentences($query, "ii", $userId, $episodeId);
}



function getFavoriteSentencesByEpisode($favoriteListId, $episodeId) {
    global $conn;

    $baseQuery = "SELECT sm.id, sm.episode_id, sm.sentence_idx, sm.start_time, sm.end_time, sm.english, sm.chinese,
                1 AS is_fav, sm.is_have_desc
                FROM favorite_sentence fs
                INNER JOIN sentence_master sm ON fs.sentence_id = sm.id";
    if ($favoriteListId == 0) {
        $query = "$baseQuery WHERE sm.episode_id = ?";
        $sentences = querySentences($query, "i", $episodeId);
    } else {
        $query = "$baseQuery WHERE fs.list_id = ? AND sm.episode_id = ?";
        $sentences = querySentences($query, "ii", $favoriteListId, $episodeId);
    }

    return [
        'total' => count($sentences),
        'sentences' => $sentences
    ];
}

function getFavoriteSentencesByCourse($favoriteListId, $courseId, $pageSize, $offset) {
    global $conn;

    $baseQuery = "FROM favorite_sentence fs
                    INNER JOIN sentence_master sm ON fs.sentence_id = sm.id
                    INNER JOIN episode_master em ON sm.episode_id = em.id";
    if ($favoriteListId == 0) {
        $countQuery = "SELECT COUNT(*) as total $baseQuery WHERE em.course_id = ?";
        $totalCount = queryRecordCount($countQuery, "i", $courseId);
        $query = "SELECT sm.id, sm.episode_id, sm.sentence_idx, sm.start_time, sm.end_time, sm.english, sm.chinese,
                  1 AS is_fav, sm.is_have_desc
                  $baseQuery
                  WHERE em.course_id = ?
                  LIMIT ? OFFSET ?";
        $sentences = querySentences($query, "iii", $courseId, $pageSize, $offset);
    } else {
        $countQuery = "SELECT COUNT(*) as total 
                        $baseQuery 
                        WHERE fs.list_id = ? AND em.course_id = ?";
        $totalCount = queryRecordCount($countQuery, "ii", $favoriteListId, $courseId);
        $query = "SELECT sm.id, sm.episode_id, sm.sentence_idx, sm.start_time, sm.end_time, sm.english, sm.chinese,
                  1 AS is_fav, sm.is_have_desc
                  $baseQuery
                  WHERE fs.list_id = ? AND em.course_id = ?
                  LIMIT ? OFFSET ?";
        $sentences = querySentences($query, "iiii", $favoriteListId, $courseId, $pageSize, $offset);
    }

    return [
        'total' => $totalCount,
        'sentences' => $sentences
    ];
}

function getFavoriteSentencesWithoutId($favoriteListId, $pageSize, $offset) {
    global $conn;

    $baseQuery = "SELECT sm.id, sm.episode_id, sm.sentence_idx, sm.start_time, sm.end_time, sm.english, sm.chinese,
                  1 AS is_fav, sm.is_have_desc
                  FROM favorite_sentence fs
                  INNER JOIN sentence_master sm ON fs.sentence_id = sm.id";

    if ($favoriteListId == 0) {
        $countQuery = "SELECT COUNT(*) as total FROM favorite_sentence";
        $totalCount = queryRecordCount($countQuery, "");
        $query = "$baseQuery LIMIT ? OFFSET ?";
        $sentences = querySentences($query, "ii", $pageSize, $offset);
    } else {
        $countQuery = "SELECT COUNT(*) as total FROM favorite_sentence WHERE list_id = ?";
        $totalCount = queryRecordCount($countQuery, "i", $favoriteListId);
        $query = "$baseQuery WHERE fs.list_id = ? LIMIT ? OFFSET ?";
        $sentences = querySentences($query, "iii", $favoriteListId, $pageSize, $offset);
    }

    return [
        'total' => $totalCount,
        'sentences' => $sentences
    ];
}

# Function to retrieve favorite sentences based on favorite list ID, course ID, and episode ID
function getFavoriteSentences($db, $favoriteListId, $courseId, $episodeId, $pageNumber = 1, $pageSize = 10) {
    $offset = ($pageNumber - 1) * $pageSize;

    # episod is specified, get all favorite sentences of this episod
    if ($episodeId !== null) {
        return getFavoriteSentencesByEpisode($favoriteListId, $episodeId);
    # episode is null, get all favorite sentence of whole course
    } elseif ($courseId !== null) {
        return getFavoriteSentencesByCourse($favoriteListId, $courseId, $pageSize, $offset);
    # get sepcified favorite list
    } else {
        return getFavoriteSentencesWithoutId($favoriteListId, $pageSize, $offset);
    }
}

// var_dump($favoriteListId);
// var_dump($episodeId);

if ($favoriteListId !== null) {
    $favorites = getFavoriteSentences($db, $favoriteListId, $courseId, $episodeId, $pageNumber, $pageSize);
} elseif ($episodeId !== null) {
    $favorites = getSentencesByEpisode($db, $episodeId);
} elseif ($courseId !== null) {
    http_response_code(400);
    echo json_encode(['error' => 'fetch all sentence of a course is not permited']);
    exit();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing episode_id, course_id, or favorite_list_id']);
    exit();
}

header('Content-Type: application/json');
echo json_encode($favorites);
?>
