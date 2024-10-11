<?php
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';

$data = ensure_token_method_argument(['user_id']);

$userName = $data['user_id'];
$userId = $data['course_id'] ?? null;
$episodeId = $data['episode_id'] ?? null;
$favoriteListId = $data['favorite_list_id'] ?? null;
$pageSize = $data['page_size'] ?? null;
$offset = $data['offset'] ?? null;


function returnCompressed($data) {
    $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
    $canCompress = strpos($acceptEncoding, 'gzip') !== false;
    
    if ($canCompress) {
        $compressedContent = gzencode($data, 9);

        if ($compressedContent === false) {
            error_log("Failed to compress content");
            send_error_response(500, "Internal Server Error: Unable to compress file.");
        }
    
        header('Content-Encoding: gzip');
        header('Vary: Accept-Encoding');
        header('Content-Length: ' . strlen($compressedContent));
    
        echo $compressedContent;
    } else {
        echo $data;
    }
    exit();
}

function queryRecordCount($query, $paramType, ...$params) {
    [$stmt, $result] = exec_query($query, $paramType, ...$params);
    $count = $result->fetch_row()[0];
    $stmt->close();

    return $count;
}

function querySentences($query, $paramType, ...$params) {
    [$stmt, $result] = exec_query($query, $paramType, ...$params);

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
        IF(fs.sentence_id IS NOT NULL, 1, 0) AS is_fav, sm.has_description
        FROM sentence_master sm
        LEFT JOIN favorite_sentence fs ON fs.sentence_id = sm.id AND fs.user_id = ?
        WHERE sm.episode_id = ?";

    $sentences = querySentences($query, "ii", $userId, $episodeId);

    return [
        'total_count' => count($sentences),
        'offset' => 0,
        'sentences' => $sentences
    ];
}



function getFavoriteSentencesByEpisode($favoriteListId, $episodeId) {
    global $conn;

    $baseQuery = "SELECT sm.id, sm.episode_id, sm.sentence_idx, sm.start_time, sm.end_time, sm.english, sm.chinese,
                1 AS is_fav, sm.has_description
                FROM favorite_sentence fs
                INNER JOIN sentence_master sm ON fs.sentence_id = sm.id";
    if ($favoriteListId == -1) {
        $query = "$baseQuery WHERE sm.episode_id = ?";
        $sentences = querySentences($query, "i", $episodeId);
    } else {
        $query = "$baseQuery WHERE fs.list_id = ? AND sm.episode_id = ?";
        $sentences = querySentences($query, "ii", $favoriteListId, $episodeId);
    }

    return [
        'total_count' => count($sentences),
        'offset' => 0,
        'sentences' => $sentences
    ];
}

function getFavoriteSentencesByCourse($favoriteListId, $courseId, $pageSize, $offset) {
    global $conn;

    $baseQuery = "FROM favorite_sentence fs
                    INNER JOIN sentence_master sm ON fs.sentence_id = sm.id
                    INNER JOIN episode_master em ON sm.episode_id = em.id";
    if ($favoriteListId == -1) {
        $countQuery = "SELECT COUNT(*) as total $baseQuery WHERE em.course_id = ?";
        $totalCount = queryRecordCount($countQuery, "i", $courseId);
        $query = "SELECT sm.id, sm.episode_id, sm.sentence_idx, sm.start_time, sm.end_time, sm.english, sm.chinese,
                  1 AS is_fav, sm.has_description
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
                  1 AS is_fav, sm.has_description
                  $baseQuery
                  WHERE fs.list_id = ? AND em.course_id = ?
                  LIMIT ? OFFSET ?";
        $sentences = querySentences($query, "iiii", $favoriteListId, $courseId, $pageSize, $offset);
    }

    return [
        'total_count' => $totalCount,
        'offset' => $offset,
        'sentences' => $sentences
    ];
}

function getFavoriteSentencesWithoutId($favoriteListId, $pageSize, $offset) {
    $baseQuery = "SELECT sm.id, sm.episode_id, sm.sentence_idx, sm.start_time, sm.end_time, sm.english, sm.chinese,
                  1 AS is_fav, sm.has_description
                  FROM favorite_sentence fs
                  INNER JOIN sentence_master sm ON fs.sentence_id = sm.id";

    if ($favoriteListId == -1) {
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
        'total_count' => $totalCount,
        'offset' => $offset,
        'sentences' => $sentences
    ];
}

# Function to retrieve favorite sentences based on favorite list ID, course ID, and episode ID
function getFavoriteSentences($favoriteListId, $courseId, $episodeId, $offset, $pageSize) {
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
    $sentences = getFavoriteSentences($favoriteListId, $userId, $episodeId, $offset, $pageSize);
} elseif ($episodeId !== null) {
    $sentences = getSentencesByEpisode($userId, $episodeId);
} elseif ($userId !== null) {
    http_response_code(400);
    echo json_encode(['error' => 'fetch all sentence of a course is not permited']);
    exit();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing episode_id, course_id, or favorite_list_id']);
    exit();
}

header('Content-Type: application/json');
returnCompressed(json_encode($sentences));
