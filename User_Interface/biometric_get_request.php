<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

/*
|--------------------------------------------------------------------------
| Database connection
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/db_connect.php';

if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Get biometric device ID
|--------------------------------------------------------------------------
*/

$device_id = isset($_GET['device_id'])
    ? trim($_GET['device_id'])
    : '';

if ($device_id === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Device ID missing'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Find the oldest Pending enrollment request
|--------------------------------------------------------------------------
|
| The request can either:
| 1. Already belong to this device
| 2. Have no device assigned yet
|
*/

$sql = "
    SELECT
        request_id,
        student_id,
        device_id,
        status,
        fingerprint_id,
        message,
        created_at,
        completed_at
    FROM biometric_enrollment_requests
    WHERE status = 'Pending'
      AND (device_id IS NULL OR device_id = ?)
    ORDER BY request_id ASC
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare enrollment request query',
        'error' => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $device_id);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to execute enrollment request query',
        'error' => mysqli_stmt_error($stmt)
    ]);

    mysqli_stmt_close($stmt);
    exit;
}

$result = mysqli_stmt_get_result($stmt);

$request = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| No pending request
|--------------------------------------------------------------------------
*/

if (!$request) {
    echo json_encode([
        'success' => true,
        'request_available' => false,
        'message' => 'No pending enrollment request'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Get student information
|--------------------------------------------------------------------------
*/

$student_sql = "
    SELECT
        student_id,
        student_name,
        branch_name,
        semester
    FROM students
    WHERE student_id = ?
    LIMIT 1
";

$student_stmt = mysqli_prepare($conn, $student_sql);

if (!$student_stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare student query',
        'error' => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param(
    $student_stmt,
    's',
    $request['student_id']
);

if (!mysqli_stmt_execute($student_stmt)) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to execute student query',
        'error' => mysqli_stmt_error($student_stmt)
    ]);

    mysqli_stmt_close($student_stmt);
    exit;
}

$student_result = mysqli_stmt_get_result($student_stmt);

$student = mysqli_fetch_assoc($student_result);

mysqli_stmt_close($student_stmt);

/*
|--------------------------------------------------------------------------
| Student not found
|--------------------------------------------------------------------------
*/

if (!$student) {
    echo json_encode([
        'success' => false,
        'message' => 'Student not found',
        'student_id' => $request['student_id']
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Mark request as Processing
|--------------------------------------------------------------------------
*/

$update_sql = "
    UPDATE biometric_enrollment_requests
    SET
        status = 'Processing',
        device_id = ?,
        message = 'Biometric device is processing enrollment'
    WHERE request_id = ?
      AND status = 'Pending'
";

$update_stmt = mysqli_prepare($conn, $update_sql);

if (!$update_stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare request update',
        'error' => mysqli_error($conn)
    ]);
    exit;
}

$request_id = (int)$request['request_id'];

mysqli_stmt_bind_param(
    $update_stmt,
    'si',
    $device_id,
    $request_id
);

if (!mysqli_stmt_execute($update_stmt)) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update enrollment request',
        'error' => mysqli_stmt_error($update_stmt)
    ]);

    mysqli_stmt_close($update_stmt);
    exit;
}

$updated_rows = mysqli_stmt_affected_rows($update_stmt);

mysqli_stmt_close($update_stmt);

if ($updated_rows <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Enrollment request was already processed'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Return data to ESP8266
|--------------------------------------------------------------------------
*/

$response = [
    'success' => true,

    'request_available' => true,

    'request_id' => $request_id,

    'device_id' => $device_id,

    'student' => [
        'student_id' => $student['student_id'],
        'student_name' => $student['student_name'],
        'branch_name' => $student['branch_name'],
        'semester' => $student['semester']
    ]
];

echo json_encode(
    $response,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

exit;

?>