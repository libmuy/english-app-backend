<?php
require 'db_config.php';
require 'validation.php';

$data = json_decode(file_get_contents('php://input'), true);

$userName = $data['user_name'];
$password = $data['password'];
$email = $data['email'];

if (!validateUserName($userName)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid user id']);
    exit;
}
$set_pass = false;
$set_email = false;

if (strlen($email) > 0) {
    if (!validateEmail($email)) {
        http_response_code(400);
        echo json_encode(['message' => 'Email address is invalid']);
        exit;
    } else {
        $set_email = true;
    }
}

if (strlen($password) > 0) {
    if (!validatePassword($password)) {
        http_response_code(400);
        echo json_encode(['message' => 'Password address is invalid']);
        exit;
    } else {
        $set_pass = true;
    }
}

$userName = $conn->real_escape_string($userName);
$password = $conn->real_escape_string($password);
$email = $conn->real_escape_string($email);

$hashedPassword = hash('sha256', $password); // Hash the password using SHA-256

if ($set_email && $set_pass) {
    $sql = "UPDATE user SET password = '$hashedPassword', email = '$email' WHERE name = '$userName'";
} else if ($set_email && !$set_pass) {
    $sql = "UPDATE user SET email = '$email' WHERE name = '$userName'";
} else if (!$set_email && $set_pass) {
    $sql = "UPDATE user SET password = '$hashedPassword' WHERE name = '$userName'";
} else {
    http_response_code(400);
    echo json_encode(['message' => 'Please specify password or email']);
    exit;
}

if ($conn->query($sql) === TRUE) {
    if ($conn->affected_rows === 1) {
        echo json_encode(['message' => 'Update successful']);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'no record updated!']);
    }
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Error updating user: ' . $conn->error]);
}

$conn->close();
?>
