<?php
function validateUserName($userId)
{
    return preg_match('/^[a-zA-Z0-9_-]{3,32}$/', $userId);
}

function validatePassword($password)
{
    return preg_match('/^[a-zA-Z0-9-><?!"#$%&\'()~]{4,16}$/', $password);
}

function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 64;
}



function ENSURE_TOKEN_METHOD_ARGUMENT($args, $validate = true)
{
    ENSURE_TOKEN_METHOD();
    
    $data = json_decode(file_get_contents('php://input'), true);
    foreach ($args as $arg) {
        if (isset($data[$arg]))
            continue;

        http_response_code(400);
        echo json_encode(['error' => "Missing param: $arg"]);
        exit();
    }

    return $data;
}





function ENSURE_TOKEN_METHOD()
{
    try {
        // validateToken();
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        http_response_code(401);
        echo json_encode(['error' => 'Not Permitted Method: Get']);
        exit();
    }
}

function execQuery($query, $paramType="", ...$params)
{
    global $conn;

    // Prepare the statement
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        http_response_code(500);
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['error' => 'Database query failed']);
        exit();
    }

    // Bind parameters
    if (strlen($paramType)> 0) {
        $stmt->bind_param($paramType, ...$params);
    }

    // Execute the query
    $succeed = $stmt->execute();
    if (!$succeed) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to execute query']);
        $stmt->close();
        exit();
    }

    if (isSelectQuery($query)) return [$stmt, $stmt->get_result()];
    
    return [$stmt, $stmt->affected_rows];
}


function isSelectQuery($query)
{
    // Trim any leading whitespace
    $query = trim($query);

    // Check if the query starts with "SELECT" (case-insensitive)
    return strcasecmp(substr($query, 0, 6), "SELECT") === 0;
}



?>