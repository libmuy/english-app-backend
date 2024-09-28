<?php
require 'cors_header.php';
require 'user/token.php';

// Validate the token
validateToken();

header('Content-Type: application/json');

define('CACHE_FILE', 'coursesListCache.json');
define('CACHE_TTL', 3600); // Cache Time-To-Live in seconds

function logError($message) {
    error_log($message);
    // Optionally, write to a custom log file
    // file_put_contents('error_log.txt', $message . PHP_EOL, FILE_APPEND);
}

function readDirectory($dir) {
    $result = [];
    $items = scandir($dir);

    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            $infoFilePath = $path . DIRECTORY_SEPARATOR . 'info.json';

            if (file_exists($infoFilePath)) {
                $infoContent = file_get_contents($infoFilePath);
                $info = json_decode($infoContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    logError("Invalid JSON in $infoFilePath: " . json_last_error_msg());
                    continue;
                }

                if (strpos($item, 'category-') === 0) {
                    $info['name'] = $item;
                    if (file_exists($path . DIRECTORY_SEPARATOR . 'icon.jpg')) {
                        $info['icon'] = true;
                    }
                    $info['courses'] = readCourses($path);
                    $info['categories'] = readDirectory($path);
                    $result[] = $info;
                }
            } else {
                logError("Missing info.json in $path");
            }
        }
    }

    return $result;
}

function readCourses($dir) {
    $courses = [];
    $items = scandir($dir);

    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path) && strpos($item, 'course-') === 0) {
            $infoFilePath = $path . DIRECTORY_SEPARATOR . 'info.json';

            if (file_exists($infoFilePath)) {
                $infoContent = file_get_contents($infoFilePath);
                $info = json_decode($infoContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    logError("Invalid JSON in $infoFilePath: " . json_last_error_msg());
                    continue;
                }

                $info['name'] = $item;
                if (file_exists($path . DIRECTORY_SEPARATOR . 'icon.jpg')) {
                    $info['icon'] = true;
                }

                if (isset($info['episodes']) && is_array($info['episodes'])) {
                    foreach ($info['episodes'] as &$ep) { // Using reference here to modify the array directly
                        if (file_exists($path . DIRECTORY_SEPARATOR . $ep['name'] . '-icon.jpg')) {
                            $ep['icon'] = true;
                        }
                    }
                }

                $courses[] = $info;
            } else {
                logError("Missing info.json in $path");
            }
        }
    }

    return $courses;
}

function generateCoursesList($baseDir) {
    $coursesList = readDirectory($baseDir);
    return json_encode(['categories' => $coursesList], JSON_PRETTY_PRINT);
}

function getCachedCoursesList() {
    if (file_exists(CACHE_FILE) && (time() - filemtime(CACHE_FILE)) < CACHE_TTL) {
        return file_get_contents(CACHE_FILE);
    }
    return false;
}

function setCachedCoursesList($data) {
    return file_put_contents(CACHE_FILE, $data);
}

// Configuration
$baseDir = __DIR__ . '/resources';

// Check for cached data
$coursesList = getCachedCoursesList();

if ($coursesList === false) {
    // Generate the courses list
    $coursesList = generateCoursesList($baseDir);

    // Cache the generated data
    if (setCachedCoursesList($coursesList) === false) {
        logError('Failed to write cache file');
    } else {
        error_log('Cache file has been written successfully.');
    }
} else {
    error_log('Data loaded from cache.');
}

// Output the result
echo $coursesList;

?>
