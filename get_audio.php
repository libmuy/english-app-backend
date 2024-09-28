<?php
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';
require 'vendor/autoload.php';

// Configuration: enable or disable compression
$enableCompression = true; // Change this to false to disable compression

// Check parameters
$data = ENSURE_TOKEN_METHOD_ARGUMENT(['episode_id']);
$episodeId = $data['episode_id'];

// Get Path of audio
$query = "SELECT path FROM episode_master WHERE id = ?";
[$stmt, $result] = execQuery($query, "s", $episodeId);
$row = $result->fetch_assoc();
$filePath = __DIR__ . '/resources/' . trim($row['path']) . '.mp3';
$stmt->close();

error_log("fetch resource: " . $filePath);

// File exist check
if ((!file_exists($filePath)) || (!is_file($filePath))) {
    http_response_code(404);
    echo json_encode(array("message" => "File not found: " . $filePath));
    exit();
}

$mimeType = mime_content_type($filePath);
header('Content-Type: ' . $mimeType);

$content = file_get_contents($filePath);

if ($content === false) {
    error_log("Failed to read file: " . $filePath);
    http_response_code(500);
    echo json_encode(array("message" => "Internal Server Error: Unable to read file."));
    exit();
}

// Check if compression is enabled and the client supports gzip encoding
if ($enableCompression && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
    // Compress the content
    $compressedContent = gzencode($content, 9);

    if ($compressedContent === false) {
        error_log("Failed to compress content.");
        http_response_code(500);
        echo json_encode(array("message" => "Internal Server Error: Unable to compress file."));
        return;
    }

    // Set headers for compressed content
    header('Content-Encoding: gzip');
    header('Vary: Accept-Encoding');
    header('Content-Length: ' . strlen($compressedContent));

    // Output the compressed content
    echo $compressedContent;
} else {
    // Set headers for uncompressed content
    header('Content-Length: ' . strlen($content));

    // Output the uncompressed content
    echo $content;
}


?>
