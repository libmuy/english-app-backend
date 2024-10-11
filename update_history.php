<?php
// Include necessary files
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';

// Define the UNIQUE key columns
$uniqueKeys = ['user_id', 'course_id', 'episode_id', 'favorite_list_id'];

// Define all possible columns in the history table
$allKeys = [
    'user_id',
    'course_id',
    'episode_id',
    'favorite_list_id',
    'audio_length_sec',
    'sentence_count',
    'title',
    'last_sentence_id',
    // 'last_learned' // Excluded to let MySQL handle it automatically
];

// Assuming 'last_learned' and 'last_sentence_id' are required fields
$data = ensure_token_method_argument(['last_learned', 'last_sentence_id']);

$conn->begin_transaction();

try {
    // Step 4: Prepare the WHERE clause for the UPDATE statement
    list($whereClause, $whereTypes, $whereParams) = prepareWhereClause($uniqueKeys, $data);

    // Step 5: Prepare the UPDATE SQL statement
    // Using COALESCE to only update fields that are provided (non-NULL)
    $updateFields = [
        'audio_length_sec',
        'sentence_count',
        'title',
        'last_sentence_id'
        // 'last_learned' is managed by MySQL automatically
    ];

    $updateSetClauses = [];
    $updateParams = [];
    $updateTypes = '';

    foreach ($updateFields as $field) {
        if (isset($data[$field])) {
            $updateSetClauses[] = "`$field` = COALESCE(?, `$field`)";
            $updateParams[] = $data[$field];
            // Determine the type
            if ($field === 'title') {
                $updateTypes .= 's';
            } else {
                $updateTypes .= 'i';
            }
        }
    }

    // If there are fields to update, construct the SET clause
    if (!empty($updateSetClauses)) {
        $updateSet = implode(', ', $updateSetClauses);
    } else {
        send_error_response(400, 'Not a valid history (no fields specified).');
    }

    $updateQuery = "UPDATE `history` SET $updateSet WHERE $whereClause";

    // Combine types and parameters for the UPDATE statement
    $fullUpdateTypes = $updateTypes . $whereTypes;
    $fullUpdateParams = array_merge($updateParams, $whereParams);

    // Step 6: Execute the UPDATE statement using exec_query()
    [$stmtUpdate, $affectedRows] = exec_query($updateQuery, $fullUpdateTypes, ...$fullUpdateParams);
    $stmtUpdate->close();

    if ($affectedRows === 0) {
        // Step 7: If no rows were updated, perform an INSERT
        // Prepare the INSERT SQL statement
        $insertColumns = [];
        $insertPlaceholders = [];
        $insertParams = [];
        $insertTypes = '';

        foreach ($allKeys as $key) {
            if (isset($data[$key])) {
                $insertColumns[] = "`$key`";
                $insertPlaceholders[] = "?";
                $insertParams[] = $data[$key];
                // Determine the type
                if ($key === 'title') {
                    $insertTypes .= 's';
                } else {
                    $insertTypes .= 'i';
                }
            }
        }

        // Construct the INSERT statement
        $insertColumnsStr = implode(', ', $insertColumns);
        $insertPlaceholdersStr = implode(', ', $insertPlaceholders);
        $insertQuery = "INSERT INTO `history` ($insertColumnsStr) VALUES ($insertPlaceholdersStr)";

        // Execute the INSERT statement using exec_query()
        [$stmtInsert, $insertedRows] = exec_query($insertQuery, $insertTypes, ...$insertParams);
        $stmtInsert->close();

        if ($insertedRows > 0) {
            log2file("Inserted new history record for user_id: {$history->user_id}");
        } else {
            throw new Exception("Failed to insert new history record.");
        }
    } else {
        log2file("Updated existing history record for user_id: {$history->user_id}");
    }

    // Step 8: Commit the transaction
    $conn->commit();

    // Step 9: Respond with success
    echo json_encode(['success' => true, 'message' => 'History upserted successfully.']);

} catch (Exception $e) {
    // Step 10: Rollback the transaction on error
    $conn->rollback();
    log2file("Transaction failed: " . $e->getMessage());
    send_error_response(500, 'Internal Server Error: ' . $e->getMessage());
}
