<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "attendance_functions.php";

session_start();
include "db_connect.php";

header("Content-Type: application/json");

if (!isset($_SESSION['teacher_id'])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Teacher not logged in."
    ]);
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Get teacher branch
$q = mysqli_prepare($conn, "SELECT branch_name FROM teacher WHERE teacher_id = ? LIMIT 1");
if (!$q) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error: failed to prepare teacher query."]);
    exit;
}

mysqli_stmt_bind_param($q, "s", $teacher_id);
mysqli_stmt_execute($q);
$res = mysqli_stmt_get_result($q);
$teacher = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($q);

if (!$teacher) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid teacher."]);
    exit;
}

$teacher_branch = $teacher['branch_name'] ?? null;

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid request payload."]);
    exit;
}

$attendance_date = trim($data['attendance_date'] ?? '');
$attendance = $data['attendance_record'] ?? [];

if ($attendance_date === '' || !is_array($attendance) || empty($attendance)) {
    echo json_encode(["success" => true, "message" => "No attendance to save.", "saved_records" => 0]);
    exit;
}

$dateObject = DateTime::createFromFormat('!Y-m-d', $attendance_date);
if (
    !$dateObject ||
    $dateObject->format('Y-m-d') !== $attendance_date ||
    $attendance_date > date('Y-m-d')
) {
    echo json_encode(["success" => false, "message" => "Invalid or future attendance date."]);
    exit();
}

if (!isAttendanceAllowed($conn, $attendance_date)) {
    echo json_encode(["success" => false, "message" => "Attendance is not allowed on this date."]);
    exit();
}

// A saved student/date must never be edited by a later request.
$insertSql = "INSERT IGNORE INTO attendance_record (student_id, teacher_id, attendance_date, status)
              VALUES (?, ?, ?, ?)";

$insertStmt = mysqli_prepare($conn, $insertSql);
if (!$insertStmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error: failed to prepare insert statement."]);
    exit;
}

$saved = 0;
mysqli_begin_transaction($conn);
$existingStmt = mysqli_prepare(
    $conn,
    "SELECT 1 FROM attendance_record
     WHERE student_id = ? AND attendance_date = ?
     LIMIT 1"
);

foreach ($attendance as $row) {
    if (!is_array($row)) continue;
    $student_id = trim((string) ($row['student_id'] ?? ''));
    $status = trim((string) ($row['status'] ?? ''));
    if ($student_id === '' || $status === '') continue;

    // Verify student belongs to teacher's branch
    $checkStmt = mysqli_prepare($conn, "SELECT branch_name FROM students WHERE student_id = ? LIMIT 1");
    if (!$checkStmt) continue;
    mysqli_stmt_bind_param($checkStmt, "s", $student_id);
    mysqli_stmt_execute($checkStmt);
    $studRes = mysqli_stmt_get_result($checkStmt);
    $student = $studRes ? mysqli_fetch_assoc($studRes) : null;
    mysqli_stmt_close($checkStmt);
    if (!$student || ($student['branch_name'] ?? null) !== $teacher_branch) continue;

    if (!$existingStmt) continue;
    mysqli_stmt_bind_param($existingStmt, "ss", $student_id, $attendance_date);
    mysqli_stmt_execute($existingStmt);
    $existingResult = mysqli_stmt_get_result($existingStmt);
    if ($existingResult && mysqli_num_rows($existingResult) > 0) continue;

    mysqli_stmt_bind_param($insertStmt, "ssss", $student_id, $teacher_id, $attendance_date, $status);
    if (mysqli_stmt_execute($insertStmt) && mysqli_stmt_affected_rows($insertStmt) === 1) {
        $saved++;
    }
}

mysqli_commit($conn);
if ($existingStmt) {
    mysqli_stmt_close($existingStmt);
}
mysqli_stmt_close($insertStmt);

echo json_encode(["success" => true, "message" => "Attendance saved.", "saved_records" => $saved]);

exit;
