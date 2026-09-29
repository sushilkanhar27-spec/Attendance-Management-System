<?php
session_start();
include "db_connect.php";

header("Content-Type: application/json; charset=UTF-8");
mysqli_set_charset($conn, "utf8mb4");

/*
 * BIOMETRIC ATTENDANCE CONTROL
 *
 * Database structure used by this file:
 *
 * biometric_attendance_control
 *   id
 *   enabled
 *   teacher_id       -> teacher.teacher_id
 *   device_id        -> biometric_devices.device_id
 *   department_id    -> departments.branch_id
 *   updated_at
 *
 * POST:
 *   Logged-in teacher changes biometric attendance ON/OFF.
 *
 *   JSON:
 *   {
 *      "enabled": true,
 *      "device_id": "GATE_01"
 *   }
 *
 * GET ?action=status
 *   Teacher dashboard reads current control state.
 *
 * GET ?device_id=GATE_01
 *   ESP8266 reads the control state for its device.
 */

/* Make sure the single control row exists.
 * The table itself has already been created/migrated in MySQL.
 */
$insert = mysqli_query(
    $conn,
    "INSERT IGNORE INTO biometric_attendance_control (id, enabled)
     VALUES (1, 0)"
);

if (!$insert) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Unable to initialize biometric attendance control."
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

/*
 * ============================================================
 * POST - Teacher enables/disables biometric attendance
 * ============================================================
 */
if ($method === 'POST') {

    if (!isset($_SESSION['teacher_id'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Teacher not logged in."
        ]);
        exit;
    }

    $payload = json_decode(file_get_contents("php://input"), true);

    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON request."
        ]);
        exit;
    }

    $enabled = !empty($payload['enabled']) ? 1 : 0;
    $teacher_id = trim((string)$_SESSION['teacher_id']);

    /*
     * Find the logged-in teacher's department.
     *
     * teacher.branch_name -> departments.branch_name
     * departments.branch_id becomes department_id.
     */
    $teacherStmt = mysqli_prepare(
        $conn,
        "SELECT
            t.teacher_id,
            t.branch_name,
            d.branch_id AS department_id,
            d.branch_name AS department_name
         FROM teacher t
         LEFT JOIN departments d
           ON d.branch_name = t.branch_name
         WHERE t.teacher_id = ?
         LIMIT 1"
    );

    if (!$teacherStmt) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to prepare teacher lookup."
        ]);
        exit;
    }

    mysqli_stmt_bind_param($teacherStmt, "s", $teacher_id);
    mysqli_stmt_execute($teacherStmt);

    $teacherResult = mysqli_stmt_get_result($teacherStmt);
    $teacherRow = mysqli_fetch_assoc($teacherResult);
    mysqli_stmt_close($teacherStmt);

    if (!$teacherRow) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Teacher record not found."
        ]);
        exit;
    }

    if ($teacherRow['department_id'] === null) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Teacher department is not configured in departments table."
        ]);
        exit;
    }

    $department_id = (int)$teacherRow['department_id'];
    $department_name = $teacherRow['department_name'];

    /*
     * Device selection:
     *
     * 1. If dashboard sends device_id, use it.
     * 2. Otherwise use the device already stored in the control row.
     * 3. If there is no stored device yet, use GATE_01.
     *
     * GATE_01 is the ESP8266 biometric device configured in this project.
     */
    $requested_device_id = trim((string)($payload['device_id'] ?? ''));

    if ($requested_device_id !== '') {
        $device_id = $requested_device_id;
    } else {
        $currentStmt = mysqli_prepare(
            $conn,
            "SELECT device_id
             FROM biometric_attendance_control
             WHERE id = 1
             LIMIT 1"
        );

        mysqli_stmt_execute($currentStmt);
        $currentResult = mysqli_stmt_get_result($currentStmt);
        $currentRow = mysqli_fetch_assoc($currentResult);
        mysqli_stmt_close($currentStmt);

        $device_id = trim((string)($currentRow['device_id'] ?? ''));

        if ($device_id === '') {
            $device_id = 'GATE_01';
        }
    }

    /*
     * Validate device_id against biometric_devices.
     * This keeps the foreign-key relationship and prevents an
     * unknown device from being stored.
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
            "success" => false,
            "message" => "Unable to prepare device lookup."
        ]);
        exit;
    }

    mysqli_stmt_bind_param($deviceStmt, "s", $device_id);
    mysqli_stmt_execute($deviceStmt);

    $deviceResult = mysqli_stmt_get_result($deviceStmt);
    $deviceRow = mysqli_fetch_assoc($deviceResult);
    mysqli_stmt_close($deviceStmt);

    if (!$deviceRow) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Biometric device is not registered.",
            "device_id" => $device_id
        ]);
        exit;
    }

    if ($deviceRow['status'] !== 'Active') {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Biometric device is inactive.",
            "device_id" => $device_id
        ]);
        exit;
    }

    /*
     * ============================================================
     * STEP 4B - When teacher switches attendance OFF:
     * finalize today's attendance for the selected department.
     *
     * Students who already have today's record are untouched.
     * Students with no record today are inserted as Absent.
     * Present records are never overwritten.
     * ============================================================
     */
    $finalized_total = 0;
    $finalized_absent = 0;
    $finalize_error = null;

    if ($enabled === 0) {

        $finalizeStmt = mysqli_prepare(
            $conn,
            "SELECT
                s.student_id,
                s.branch_name
             FROM students s
             INNER JOIN departments d
               ON d.branch_name = s.branch_name
             WHERE d.branch_id = ?
             ORDER BY s.student_id"
        );

        $checkTodayStmt = mysqli_prepare(
            $conn,
            "SELECT attendance_id
             FROM attendance_record
             WHERE student_id = ?
               AND attendance_date = CURDATE()
             LIMIT 1"
        );

        $insertAbsentStmt = mysqli_prepare(
            $conn,
            "INSERT IGNORE INTO attendance_record
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
                'Absent',
                ?,
                'Biometric'
             )"
        );

        if (!$finalizeStmt || !$checkTodayStmt || !$insertAbsentStmt) {

            $finalize_error = "Unable to prepare daily attendance finalization.";

            if ($finalizeStmt) {
                mysqli_stmt_close($finalizeStmt);
            }
            if ($checkTodayStmt) {
                mysqli_stmt_close($checkTodayStmt);
            }
            if ($insertAbsentStmt) {
                mysqli_stmt_close($insertAbsentStmt);
            }

        } else {

            mysqli_stmt_bind_param(
                $finalizeStmt,
                "i",
                $department_id
            );

            if (!mysqli_stmt_execute($finalizeStmt)) {

                $finalize_error = "Unable to read department students.";

            } else {

                $studentsResult = mysqli_stmt_get_result($finalizeStmt);

                if (!$studentsResult) {

                    $finalize_error = "Unable to read department students.";

                } else {

                    while ($student = mysqli_fetch_assoc($studentsResult)) {

                        $finalized_total++;

                        $student_id_for_absent =
                            trim((string)$student["student_id"]);

                        $branch_name_for_absent =
                            trim((string)$student["branch_name"]);

                        /*
                         * Do not touch an existing Present or Absent record.
                         */
                        mysqli_stmt_bind_param(
                            $checkTodayStmt,
                            "s",
                            $student_id_for_absent
                        );

                        if (!mysqli_stmt_execute($checkTodayStmt)) {
                            $finalize_error =
                                "Unable to check today's attendance.";
                            break;
                        }

                        $todayResult =
                            mysqli_stmt_get_result($checkTodayStmt);

                        $todayRecord =
                            $todayResult
                                ? mysqli_fetch_assoc($todayResult)
                                : null;

                        if ($todayRecord) {
                            continue;
                        }

                        /*
                         * No record today -> insert Absent.
                         */
                        mysqli_stmt_bind_param(
                            $insertAbsentStmt,
                            "sss",
                            $student_id_for_absent,
                            $teacher_id,
                            $branch_name_for_absent
                        );

                        if (!mysqli_stmt_execute($insertAbsentStmt)) {
                            $finalize_error =
                                "Unable to insert Absent attendance.";
                            break;
                        }

                        if (mysqli_stmt_affected_rows($insertAbsentStmt) === 1) {
                            $finalized_absent++;
                        }
                    }
                }
            }

            mysqli_stmt_close($finalizeStmt);
            mysqli_stmt_close($checkTodayStmt);
            mysqli_stmt_close($insertAbsentStmt);
        }

        /*
         * Do not disable attendance if finalization failed.
         * This prevents a partial attendance session from being
         * silently closed.
         */
        if ($finalize_error !== null) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Attendance was NOT disabled because daily attendance finalization failed.",
                "error" => $finalize_error,
                "department_id" => $department_id,
                "department_name" => $department_name,
                "students_checked" => $finalized_total,
                "absent_inserted" => $finalized_absent
            ]);
            exit;
        }
    }

    /*
     * Update ALL control information together:
     *
     * enabled
     * teacher_id
     * device_id
     * department_id
     * updated_at
     */
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE biometric_attendance_control
         SET
            enabled = ?,
            teacher_id = ?,
            device_id = ?,
            department_id = ?,
            updated_at = NOW()
         WHERE id = 1"
    );

    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to prepare biometric control update."
        ]);
        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "issi",
        $enabled,
        $teacher_id,
        $device_id,
        $department_id
    );

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to update biometric attendance mode.",
            "error" => mysqli_stmt_error($stmt)
        ]);
        mysqli_stmt_close($stmt);
        exit;
    }

    mysqli_stmt_close($stmt);

    echo json_encode([
        "success" => true,
        "enabled" => ($enabled === 1),
        "teacher_id" => $teacher_id,
        "device_id" => $device_id,
        "department_id" => $department_id,
        "department_name" => $department_name,
        "updated_at" => date("Y-m-d H:i:s"),
        "finalized" => ($enabled === 0),
        "students_checked" => $finalized_total,
        "absent_inserted" => $finalized_absent,
        "message" => $enabled
            ? "Biometric attendance enabled."
            : "Biometric attendance disabled and today's attendance finalized."
    ]);
    exit;
}

/*
 * ============================================================
 * GET - Read biometric attendance control
 * ============================================================
 */

$requested_device_id = trim((string)($_GET['device_id'] ?? ''));

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        bac.enabled,
        bac.teacher_id,
        bac.device_id,
        bac.department_id,
        d.branch_name AS department_name,
        bac.updated_at
     FROM biometric_attendance_control bac
     LEFT JOIN departments d
       ON d.branch_id = bac.department_id
     WHERE bac.id = 1
     LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Unable to read biometric attendance control."
    ]);
    exit;
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$row = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$row) {
    echo json_encode([
        "success" => true,
        "enabled" => false,
        "teacher_id" => null,
        "device_id" => $requested_device_id !== '' ? $requested_device_id : null,
        "department_id" => null,
        "department_name" => null,
        "updated_at" => null
    ]);
    exit;
}

/*
 * If an ESP8266 asks for its own device ID, only that configured
 * device is allowed to receive the active attendance permission.
 *
 * Example:
 *   control device = GATE_01
 *   ESP asks       = GATE_01 -> enabled remains true
 *
 *   control device = GATE_01
 *   ESP asks       = OTHER_01 -> enabled becomes false
 */
$effective_enabled = ((int)$row['enabled'] === 1);

if (
    $requested_device_id !== '' &&
    $row['device_id'] !== $requested_device_id
) {
    $effective_enabled = false;
}

echo json_encode([
    "success" => true,
    "enabled" => $effective_enabled,
    "teacher_id" => $row['teacher_id'],
    "device_id" => $row['device_id'],
    "department_id" => $row['department_id'] !== null
        ? (int)$row['department_id']
        : null,
    "department_name" => $row['department_name'],
    "updated_at" => $row['updated_at']
]);
?>
