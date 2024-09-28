<?php
require __DIR__ . '/../cors_header.php';

$host = 'mysql';
$db = 'english_app_new';
$user = 'mquser';
$pass = 'mqpass';

// echo "Connecting to database...\n";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
