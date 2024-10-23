<?php
require __DIR__ . '/../vendor/autoload.php';

// function getTuple() {
//     $value1 = "apple";
//     $value2 = 42;
//     $value3 = true;

//     return [$value1, $value2, $value3];  // Return an array as a tuple-like structure
// }

// // Usage
// [$fruit, $number, $flag] = getTuple();
// echo $fruit;   // Outputs: apple
// echo $number;  // Outputs: 42
// echo $flag;    // Outputs: 1 (true)


$redis = new Redis();
$redis->connect("redis", 6379);

// Get all keys from Redis
$allKeys = $redis->keys('*');

$allData = [];
foreach ($allKeys as $key) {
    $allData[$key] = json_decode($redis->get($key), true);
}

header('Content-Type: application/json');
echo json_encode($allData);


exit();



use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

// Load environment variables from /tmpfs
$dotenv = Dotenv::createImmutable('/tmpfs');
$dotenv->load();

// Debug: List all environment variables
echo "<pre>";
print_r($_ENV);
echo "</pre>";

$currentVersion = $_ENV['JWT_CURRENT_VERSION'] ?? null;
$key = $_ENV["JWT_SECRET_KEY_$currentVersion"] ?? null;

// Debug: Print current version and key
var_dump($currentVersion);
var_dump($key);

if (!$key) {
    throw new Exception('Secret key not set');
}
