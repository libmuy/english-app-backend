<?php
function validate_user_name($userId)
{
    return preg_match('/^[a-zA-Z0-9_-]{3,32}$/', $userId);
}

function validate_password($password)
{
    return preg_match('/^[a-zA-Z0-9-><?!"#$%&\'()~]{4,16}$/', $password);
}

function validate_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 64;
}

// Utility function to send error responses
function send_error_response($statusCode, $message) {
    http_response_code($statusCode);
    echo json_encode(["error" => $message]);
    exit();
}


function ensure_token_method_argument($args, $validate = true)
{
    ensure_token_method();
    
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON format.']);
        exit;
    }
    
    if (count($args) > 0) {
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input data. Expected a JSON object.']);
            exit;
        }
    
        foreach ($args as $arg) {
            if (isset($data[$arg]))
                continue;
            send_error_response(400, "Missing param: $arg");
        }
    }
    
    return $data;
}





function ensure_token_method()
{
    try {
        // validateToken();
    } catch (Exception $e) {
        send_error_response(401, 'Invalid token');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        send_error_response(401, 'Not Permitted Method: Get');
    }
}

function exec_query($query, $paramType="", ...$params)
{
    global $conn;

    try {
        // Prepare the statement
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            send_error_response(500, 'Database query failed');
        }

        // Bind parameters
        if (strlen($paramType)> 0) {
            $stmt->bind_param($paramType, ...$params);
        }

        // Execute the query
        $succeed = $stmt->execute();
        if (!$succeed) {
            $stmt->close();
            send_error_response(500, 'Failed to execute query');
        }

        if (is_select_query($query)) return [$stmt, $stmt->get_result()];
        
        return [$stmt, $stmt->affected_rows];
    } catch (mysqli_sql_exception $e) {
        // Log the exception
        log_error("MySQLi Error: " . $e->getMessage(), $query);
        throw $e;
    } catch (Exception $e) {
        // Log the exception
        log_error("General Error: " . $e->getMessage(), $query);
        throw $e;
    }
}


function is_select_query($query)
{
    // Trim any leading whitespace
    $query = trim($query);

    // Check if the query starts with "SELECT" (case-insensitive)
    return strcasecmp(substr($query, 0, 6), "SELECT") === 0;
}


function is_development()
{
    // Define your development environment identifier
    // This can be an environment variable, configuration setting, etc.
    // For simplicity, we'll use a constant. In production, consider using environment variables.
    return defined('ENVIRONMENT') && ENVIRONMENT === 'development';
}

function log_error($errorMessage, $query)
{
    // Define the path to your log file
    $logFile = __DIR__ . '/../error_log.txt';

    // Prepare the log entry with a timestamp
    $logEntry = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $errorMessage . " | QUERY: " . $query . PHP_EOL;

    // Append the log entry to the log file
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}