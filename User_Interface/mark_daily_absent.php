<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db_connect.php';

mysqli_set_charset($conn, 'utf8mb4');
date_default_timezone_set('Asia/Kolkata');

$device_id = trim((string)($_GET['device_id'] ?? ''));

/* Read the active biometric-attendance control. */
$controlStmt = mysqli_prepare(
    $conn,
    "SELECT
        bac.enabled,
        bac.teacher_id,
        bac.device_id,
        bac.department_id,
        d.branch_name AS department_name
     FROM biometric_attendance_control bac
     LEFT JOIN departments d ON d.branch_id = bac.department_id
     WHERE bac.id = 1
     LIMIT 1"
);

if (!$controlStmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare biometric control query.'
    ]);
    exit;
}

mysqli_stmt_execute($controlStmt);
$controlResult = mysqli_stmt_get_result($controlStmt);
$control = $controlResult ? mysqli_fetch_assoc($controlResult) : null;
mysqli_stmt_close($controlStmt);

if (!$control) {
    echo json_encode([
        'success' => false,
        'finalized' => false,
        'message' => 'Biometric attendance control is not configured.'
    ]);
    exit;
}

$enabled = ((int)$control['enabled'] === 1);
$teacher_id = trim((string)($control['teacher_id'] ?? ''));
$control_device_id = trim((string)($control['device_id'] ?? ''));
$department_id = $control['department_id'] !== null
    ? (int)$control['department_id']
    : 0;
$department_name = trim((string)($control['department_name'] ?? ''));

/* If called by ESP/device, it must be the configured device. */
if ($device_id !== '' && $control_device_id !== $device_id) {
    echo json_encode([
        'success' => false,
        'finalized' => false,
        'message' => 'This biometric device is not the active attendance device.',
        'device_id' => $device_id,
        'configured_device_id' => $control_device_id
    ]);
    exit;
}

/* Attendance must have been enabled and a department/teacher selected. */
if (!$enabled || $teacher_id === '' || $department_id <= 0) {
    echo json_encode([
        'success' => false,
        'finalized' => false,
        'message' => 'Biometric attendance is not currently enabled.'
    ]);
    exit;
}

/*
 * Current students table stores the department as branch_name.
 * departments.branch_id is the department ID used by the control table.
 */
$studentsStmt = mysqli_prepare(
    $conn,
    "SELECT
        s.student_id,
        s.student_name,
        s.branch_name,
        s.semester
     FROM students s
     INNER JOIN departments d ON d.branch_name = s.branch_name
     WHERE d.branch_id = ?
     ORDER BY s.student_id"
);

if (!$studentsStmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare student query.'
    ]);
    exit;
}

mysqli_stmt_bind_param($studentsStmt, 'i', $department_id);
mysqli_stmt_execute($studentsStmt);
$studentsResult = mysqli_stmt_get_result($studentsStmt);

$checkStmt = mysqli_prepare(
    $conn,
    "SELECT attendance_id, status
     FROM attendance_record
     WHERE student_id = ?
       AND attendance_date = CURDATE()
     LIMIT 1"
);

$insertStmt = mysqli_prepare(
    $conn,
    "INSERT IGNORE INTO attendance_record
     (student_id, teacher_id, attendance_date, status, branch_name, attendance_method)
     VALUES (?, ?, CURDATE(), 'Absent', ?, 'Biometric')"
);

if (!$studentsResult || !$checkStmt || !$insertStmt) {
    if ($studentsStmt) mysqli_stmt_close($studentsStmt);
    if ($checkStmt) mysqli_stmt_close($checkStmt);
    if ($insertStmt) mysqli_stmt_close($insertStmt);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare daily attendance queries.'
    ]);
    exit;
}

$total_students = 0;
$already_recorded = 0;
$present_count = 0;
$absent_inserted = 0;
$failed = 0;

while ($student = mysqli_fetch_assoc($studentsResult)) {

    $total_students++;

    $student_id = trim((string)$student['student_id']);
    $student_name = trim((string)$student['student_name']);
    $branch_name = trim((string)$student['branch_name']);

    /* Never replace an existing Present/Absent record. */
    mysqli_stmt_bind_param($checkStmt, 's', $student_id);
    mysqli_stmt_execute($checkStmt);

    $checkResult = mysqli_stmt_get_result($checkStmt);
    $existing = $checkResult ? mysqli_fetch_assoc($checkResult) : null;

    if ($existing) {
        $already_recorded++;

        if (($existing['status'] ?? '') === 'Present') {
            $present_count++;
        }

        continue;
    }

    /* No record today -> create Absent. */
    mysqli_stmt_bind_param(
        $insertStmt,
        'sss',
        $student_id,
        $teacher_id,
        $branch_name
    );

    if (mysqli_stmt_execute($insertStmt)) {
        if (mysqli_stmt_affected_rows($insertStmt) === 1) {
            $absent_inserted++;
        }
    } else {
        $failed++;
    }
}

mysqli_stmt_close($insertStmt);
mysqli_stmt_close($checkStmt);
mysqli_stmt_close($studentsStmt);

echo json_encode([
    'success' => ($failed === 0),
    'finalized' => true,
    'attendance_date' => date('Y-m-d'),
    'teacher_id' => $teacher_id,
    'device_id' => $control_device_id,
    'department_id' => $department_id,
    'department_name' => $department_name,
    'total_students' => $total_students,
    'already_recorded' => $already_recorded,
    'present_count' => $present_count,
    'absent_inserted' => $absent_inserted,
    'failed' => $failed,
    'message' => $failed === 0
        ? 'Daily biometric attendance finalized successfully.'
        : 'Daily attendance finalized with some errors.'
]);

mysqli_close($conn);
exit;
?>