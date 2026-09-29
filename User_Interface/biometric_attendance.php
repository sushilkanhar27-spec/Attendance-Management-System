<?php
/*
|--------------------------------------------------------------------------
| BIOMETRIC ATTENDANCE API - STEP 3
|--------------------------------------------------------------------------
| Called by ESP8266:
|
| biometric_attendance.php?device_id=GATE_01&fingerprint_id=12
|
| Logic:
| 1. Validate device.
| 2. Find student from fingerprint_id.
| 3. Read active biometric_attendance_control row.
| 4. Check attendance permission and allowed department.
| 5. Prevent duplicate attendance for the same day.
| 6. Insert Present into attendance_record.
| 7. Return student information + OLED-friendly message.
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db_connect.php';

mysqli_set_charset($conn, 'utf8mb4');
date_default_timezone_set('Asia/Kolkata');

/*
|--------------------------------------------------------------------------
| INPUT
|--------------------------------------------------------------------------
*/

$device_id = trim((string)($_GET['device_id'] ?? ''));
$fingerprint_id = isset($_GET['fingerprint_id'])
    ? (int)$_GET['fingerprint_id']
    : 0;

if ($device_id === '' || $fingerprint_id <= 0) {
    echo json_encode([
        'success' => false,
        'attendance_marked' => false,
        'biometric_allowed' => false,
        'message' => 'Invalid device ID or fingerprint ID'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| 1. VALIDATE DEVICE
|--------------------------------------------------------------------------
| The ESP8266 must be the device configured in the control table.
|--------------------------------------------------------------------------
*/

$deviceStmt = mysqli_prepare(
    $conn,
    "SELECT device_id, device_name, status
     FROM biometric_devices
     WHERE device_id = ?
     LIMIT 1"
);

if (!$deviceStmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'attendance_marked' => false,
        'biometric_allowed' => false,
        'message' => 'Device query preparation failed'
    ]);
    exit;
}

mysqli_stmt_bind_param($deviceStmt, 's', $device_id);
mysqli_stmt_execute($deviceStmt);

$deviceResult = mysqli_stmt_get_result($deviceStmt);
$device = $deviceResult ? mysqli_fetch_assoc($deviceResult) : null;

mysqli_stmt_close($deviceStmt);

if (!$device) {
    echo json_encode([
        'success' => false,
        'attendance_marked' => false,
        'biometric_allowed' => false,
        'message' => 'Biometric device is not registered',
        'device_id' => $device_id
    ]);
    exit;
}

if ($device['status'] !== 'Active') {
    echo json_encode([
        'success' => false,
        'attendance_marked' => false,
        'biometric_allowed' => false,
        'message' => 'Biometric device is inactive',
        'device_id' => $device_id
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| 2. FIND STUDENT FROM FINGERPRINT
|--------------------------------------------------------------------------
| student_biometrics maps fingerprint_id -> student_id.
|--------------------------------------------------------------------------
*/

$studentStmt = mysqli_prepare(
    $conn,
    "SELECT
        sb.student_id,
        sb.fingerprint_id,
        sb.status AS biometric_status,
        s.student_name,
        s.branch_name,
        s.semester,
        d.branch_id AS student_department_id,
        d.branch_name AS student_department_name
     FROM student_biometrics sb
     INNER JOIN students s
        ON s.student_id = sb.student_id
     LEFT JOIN departments d
        ON d.branch_name = s.branch_name
     WHERE sb.fingerprint_id = ?
       AND sb.status = 'Active'
     LIMIT 1"
);

if (!$studentStmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'attendance_marked' => false,
        'biometric_allowed' => false,
        'message' => 'Student query preparation failed'
    ]);
    exit;
}

mysqli_stmt_bind_param($studentStmt, 'i', $fingerprint_id);
mysqli_stmt_execute($studentStmt);

$studentResult = mysqli_stmt_get_result($studentStmt);
$student = $studentResult ? mysqli_fetch_assoc($studentResult) : null;

mysqli_stmt_close($studentStmt);

/*
|--------------------------------------------------------------------------
| FINGERPRINT NOT REGISTERED
|--------------------------------------------------------------------------
*/

if (!$student) {
    echo json_encode([
        'success' => false,
        'attendance_marked' => false,
        'biometric_allowed' => false,
        'fingerprint_id' => $fingerprint_id,
        'message' => 'Finger not registered'
    ]);
    exit;
}

$student_id = trim((string)$student['student_id']);
$student_name = trim((string)$student['student_name']);
$student_branch = trim((string)$student['branch_name']);
$student_semester = $student['semester'];

$studentData = [
    'student_id' => $student_id,
    'student_name' => $student_name,
    'branch_name' => $student_branch,
    'semester' => $student_semester,
    'department_id' => $student['student_department_id'] !== null
        ? (int)$student['student_department_id']
        : null,
    'department_name' => $student['student_department_name']
];

/*
|--------------------------------------------------------------------------
| 3. READ CURRENT BIOMETRIC ATTENDANCE CONTROL
|--------------------------------------------------------------------------
|
| This is the important new logic.
|
| enabled       = whether teacher has allowed biometric attendance
| teacher_id    = teacher who currently controls the device
| device_id     = device currently assigned
| department_id = department allowed to use biometric attendance
|--------------------------------------------------------------------------
*/

$controlStmt = mysqli_prepare(
    $conn,
    "SELECT
        bac.enabled,
        bac.teacher_id,
        bac.device_id,
        bac.department_id,
        d.branch_name AS allowed_department_name,
        t.teacher_name
     FROM biometric_attendance_control bac
     LEFT JOIN departments d
        ON d.branch_id = bac.department_id
     LEFT JOIN teacher t
        ON t.teacher_id = bac.teacher_id
     WHERE bac.id = 1
     LIMIT 1"
);

if (!$controlStmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'attendance_marked' => false,
        'biometric_allowed' => false,
        'fingerprint_id' => $fingerprint_id,
        'student' => $studentData,
        'message' => 'Attendance control query preparation failed'
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
        'attendance_marked' => false,
        'biometric_allowed' => false,
        'fingerprint_id' => $fingerprint_id,
        'student' => $studentData,
        'message' => 'Biometric attendance control is not configured'
    ]);
    exit;
}

$enabled = ((int)$control['enabled'] === 1);
$active_teacher_id = trim((string)($control['teacher_id'] ?? ''));
$control_device_id = trim((string)($control['device_id'] ?? ''));
$allowed_department_id = $control['department_id'] !== null
    ? (int)$control['department_id']
    : 0;
$allowed_department_name = trim((string)($control['allowed_department_name'] ?? ''));
$teacher_name = trim((string)($control['teacher_name'] ?? ''));

/*
|--------------------------------------------------------------------------
| 4. DEVICE MUST MATCH CONTROL TABLE
|--------------------------------------------------------------------------
*/

if ($control_device_id === '' || $control_device_id !== $device_id) {
    echo json_encode([
        'success' => false,
        'attendance_marked' => false,
        'biometric_allowed' => false,
        'permission_enabled' => $enabled,
        'fingerprint_id' => $fingerprint_id,
        'device_id' => $device_id,
        'configured_device_id' => $control_device_id,
        'student' => $studentData,
        'message' => 'This biometric device is not currently assigned'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| 5. BIOMETRIC ATTENDANCE MUST BE ENABLED
|--------------------------------------------------------------------------
*/

if (!$enabled || $active_teacher_id === '' || $allowed_department_id <= 0) {
    echo json_encode([
        'success' => false,
        'attendance_marked' => false,
        'biometric_allowed' => false,
        'permission_enabled' => false,
        'fingerprint_id' => $fingerprint_id,
        'teacher_id' => $active_teacher_id !== '' ? $active_teacher_id : null,
        'device_id' => $device_id,
        'allowed_department_id' => $allowed_department_id > 0
            ? $allowed_department_id
            : null,
        'allowed_department_name' => $allowed_department_name !== ''
            ? $allowed_department_name
            : null,
        'student' => $studentData,
        'message' => 'Your department is not allowed for biometric attendance'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| 6. CHECK STUDENT DEPARTMENT
|--------------------------------------------------------------------------
|
| We compare department IDs, not department text.
|
| Example:
| control department_id = 2 (Computer Science)
| student department_id = 2 -> ALLOWED
| student department_id = 1 -> NOT ALLOWED
|--------------------------------------------------------------------------
*/

$student_department_id = $student['student_department_id'] !== null
    ? (int)$student['student_department_id']
    : 0;

if (
    $student_department_id <= 0 ||
    $student_department_id !== $allowed_department_id
) {
    echo json_encode([
        'success' => false,
        'attendance_marked' => false,
        'biometric_allowed' => false,
        'permission_enabled' => true,
        'fingerprint_id' => $fingerprint_id,
        'teacher' => [
            'teacher_id' => $active_teacher_id,
            'teacher_name' => $teacher_name
        ],
        'allowed_department' => [
            'department_id' => $allowed_department_id,
            'department_name' => $allowed_department_name
        ],
        'student' => $studentData,
        'message' => 'Your department is not allowed for biometric attendance'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| 7. CHECK TODAY'S ATTENDANCE
|--------------------------------------------------------------------------
*/

$checkStmt = mysqli_prepare(
    $conn,
    "SELECT
        attendance_id,
        attendance_date,
        status,
        created_at
     FROM attendance_record
     WHERE student_id = ?
       AND attendance_date = CURDATE()
     LIMIT 1"
);

if (!$checkStmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'attendance_marked' => false,
        'biometric_allowed' => true,
        'fingerprint_id' => $fingerprint_id,
        'student' => $studentData,
        'message' => 'Attendance check failed'
    ]);
    exit;
}

mysqli_stmt_bind_param($checkStmt, 's', $student_id);
mysqli_stmt_execute($checkStmt);

$checkResult = mysqli_stmt_get_result($checkStmt);
$existing = $checkResult ? mysqli_fetch_assoc($checkResult) : null;

mysqli_stmt_close($checkStmt);

if ($existing) {
    echo json_encode([
        'success' => true,
        'attendance_marked' => false,
        'already_marked' => true,
        'biometric_allowed' => true,
        'permission_enabled' => true,
        'fingerprint_id' => $fingerprint_id,
        'teacher_id' => $active_teacher_id,
        'device_id' => $device_id,
        'department_id' => $allowed_department_id,
        'department_name' => $allowed_department_name,
        'student' => $studentData,
        'message' => 'Attendance already marked today'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| 8. INSERT PRESENT ATTENDANCE
|--------------------------------------------------------------------------
|
| attendance_record currently contains:
| student_id
| teacher_id
| attendance_date
| status
| branch_name
| attendance_method
|--------------------------------------------------------------------------
*/

$insertStmt = mysqli_prepare(
    $conn,
    "INSERT INTO attendance_record
     (
        student_id,
        teacher_id,
        attendance_date,
        status,
        branch_name,
        attendance_method
     )
     VALUES
     (
        ?,
        ?,
        CURDATE(),
        'Present',
        ?,
        'Biometric'
     )"
);

if (!$insertStmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'attendance_marked' => false,
        'biometric_allowed' => true,
        'fingerprint_id' => $fingerprint_id,
        'student' => $studentData,
        'message' => 'Attendance insert preparation failed'
    ]);
    exit;
}

mysqli_stmt_bind_param(
    $insertStmt,
    'sss',
    $student_id,
    $active_teacher_id,
    $student_branch
);

$inserted = mysqli_stmt_execute($insertStmt);
$insertError = mysqli_stmt_error($insertStmt);
$insertedRows = mysqli_stmt_affected_rows($insertStmt);

mysqli_stmt_close($insertStmt);

/*
|--------------------------------------------------------------------------
| 9. FINAL RESPONSE
|--------------------------------------------------------------------------
*/

if ($inserted && $insertedRows === 1) {

    echo json_encode([
        'success' => true,
        'attendance_marked' => true,
        'already_marked' => false,
        'biometric_allowed' => true,
        'permission_enabled' => true,
        'fingerprint_id' => $fingerprint_id,
        'teacher_id' => $active_teacher_id,
        'teacher_name' => $teacher_name,
        'device_id' => $device_id,
        'department_id' => $allowed_department_id,
        'department_name' => $allowed_department_name,
        'student' => $studentData,
        'attendance_date' => date('Y-m-d'),
        'status' => 'Present',
        'attendance_method' => 'Biometric',
        'message' => 'Attendance marked successfully'
    ]);

} else {

    /*
     * If another request inserted the same student/date between
     * our SELECT and INSERT, treat it as already marked.
     */
    if (
        strpos(strtolower($insertError), 'duplicate') !== false ||
        strpos(strtolower($insertError), 'unique') !== false
    ) {
        echo json_encode([
            'success' => true,
            'attendance_marked' => false,
            'already_marked' => true,
            'biometric_allowed' => true,
            'permission_enabled' => true,
            'fingerprint_id' => $fingerprint_id,
            'teacher_id' => $active_teacher_id,
            'device_id' => $device_id,
            'department_id' => $allowed_department_id,
            'department_name' => $allowed_department_name,
            'student' => $studentData,
            'message' => 'Attendance already marked today'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'attendance_marked' => false,
            'biometric_allowed' => true,
            'fingerprint_id' => $fingerprint_id,
            'student' => $studentData,
            'message' => 'Failed to insert attendance',
            'error' => $insertError
        ]);
    }
}

mysqli_close($conn);
exit;
?>
