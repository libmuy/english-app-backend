<?php
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';

// category_id == NULL is acceptable.
ENSURE_TOKEN_METHOD();

$data = json_decode(file_get_contents('php://input'), true);
$categoryId = $data['category_id'];

if (is_null($categoryId)) {
    $name = "Root Category";
    $desc = "";
    $condition = "IS NULL";

    $query = "SELECT id, name, 'desc' FROM category_master WHERE parent_id IS NULL";
    [$categoryStmt, $categoryResult] = execQuery($query);
    $query = "SELECT id, name, 'desc', episode_count FROM course_master WHERE category_id IS NULL";
    [$courseStmt, $courseResult] = execQuery($query);
} else {
    $query = "SELECT * FROM category_master WHERE id = ?";
    [$stmt, $result] = execQuery($query, "s", $categoryId);
    $row = $result->fetch_assoc();

    $name = $row['name'];
    $desc = $row['desc'];
    $stmt->close();

    $query = "SELECT id, name, 'desc' FROM category_master WHERE parent_id = ?";
    [$categoryStmt, $categoryResult] = execQuery($query, "i", $categoryId);
    $query = "SELECT id, name, 'desc', episode_count FROM course_master WHERE category_id = ?";
    [$courseStmt, $courseResult] = execQuery($query, "i", $categoryId);
}

$subcategories = [];
while ($row = $categoryResult->fetch_assoc()) {
    $subcategories[] = $row;
}
$categoryStmt->close();

$courses = [];
while ($row = $courseResult->fetch_assoc()) {
    $courses[] = $row;
}
$courseStmt->close();


header('Content-Type: application/json');
echo json_encode( [
    "name" => $name,
    "id" => $categoryId,
    "desc" => $desc,
    "subcategories" => $subcategories,
    "courses" => $courses
]);
?>
