<?php
require 'db_config.php';
require '../vendor/autoload.php';
require 'validation.php';
require 'token.php';


function checkUserExist($userName)
{
    [$stmt, $result] = exec_query(
        "SELECT * FROM user WHERE name = ?",
        "s",
        $userName
    );
    $user_exists = $result->num_rows > 0;
    $stmt->close();
    // User exist check
    if ($user_exists) {
        http_response_code(400);
        echo json_encode(['error' => 'User already exists']);
        exit();
    }
}


function insertDefaultFavoriteList($userId)
{
    [$stmt, $affected_rows] = exec_query(
        "INSERT INTO favorite_list_master (user_id, id, name) VALUES (?, ?, ?)",
        "iis",
        $userId,
        0,
        'default favorite list',
    );
    $stmt->close();
    // insert rows
    if ($affected_rows <= 0) {
        http_response_code(500);
        echo json_encode(['error' => 'Faild to register user into database']);
        exit();
    }
}



function insertUser($userName, $password, $email)
{
    $hashedPassword = hash('sha256', $password);
    [$stmt, $affected_rows] = exec_query(
        "INSERT INTO user (name, password, email) VALUES (?, ?, ?)",
        "sss",
        $userName,
        $hashedPassword,
        $email
    );
    $stmt->close();
    // insert rows
    if ($affected_rows <= 0) {
        http_response_code(500);
        echo json_encode(['error' => 'Faild to register user into database']);
        exit();
    }
}

function getUserId($userName)
{
    [$stmt, $result] = exec_query(
        "SELECT * FROM user WHERE name = ?",
        "s",
        $userName
    );
    $user = $result->fetch_assoc();
    $stmt->close();

    return $user['id'];
}

function genResp($userId, $userName, $email)
{
    $jwt = generateToken($userName);
    echo json_encode(["token" => $jwt, "user_name" => $userName, "user_id" => (int) $userId, "email" => $email]);
}

$data = ensure_token_method_argument(['user_name', 'password', 'email']);
$userName = $data['user_name'];
$password = $data['password'];
$email = $data['email'];

if (!validate_user_name($userName) || !validate_password($password) || !validate_email($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

checkUserExist($userName);

// start a transaction, exit before commit will cause a auto rollback
$conn->begin_transaction();
insertUser($userName, $password, $email);
$userId = getUserId($userName);
insertDefaultFavoriteList($userId);
$conn->commit();

genResp($userId, $userName, $email);

