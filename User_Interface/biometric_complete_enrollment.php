<?php

header("Content-Type: application/json; charset=UTF-8");

include "db_connect.php";

mysqli_set_charset($conn, "utf8mb4");

/*
|--------------------------------------------------------------------------
| Receive data from ESP8266
|--------------------------------------------------------------------------
*/

$request_id = isset($_POST['request_id'])
    ? intval($_POST['request_id'])
    : 0;

$fingerprint_id = isset($_POST['fingerprint_id'])
    ? intval($_POST['fingerprint_id'])
    : 0;

$device_id = isset($_POST['device_id'])
    ? trim($_POST['device_id'])
    : '';

$success = isset($_POST['success'])
    ? strtolower(trim($_POST['success']))
    : 'false';

$message = isset($_POST['message'])
    ? trim($_POST['message'])
    : '';

/*
|--------------------------------------------------------------------------
| Validate request
|--------------------------------------------------------------------------
*/

if ($request_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request ID"
    ]);

    exit;
}

if ($success === "true" && $fingerprint_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid fingerprint ID"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Find enrollment request
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        request_id,
        student_id,
        device_id,
        status
     FROM biometric_enrollment_requests
     WHERE request_id = ?
     LIMIT 1"
);

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Database prepare error",
        "error" => mysqli_error($conn)
    ]);

    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $request_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$request = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$request) {

    echo json_encode([
        "success" => false,
        "message" => "Enrollment request not found",
        "request_id" => $request_id
    ]);

    exit;
}

$student_id = $request['student_id'];

/*
|--------------------------------------------------------------------------
| SUCCESSFUL ENROLLMENT
|--------------------------------------------------------------------------
*/

if ($success === "true") {

    /*
    |----------------------------------------------------------------------
    | Check whether fingerprint ID is already assigned
    |----------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT student_id
         FROM student_biometrics
         WHERE fingerprint_id = ?
         LIMIT 1"
    );

    if (!$stmt) {

        echo json_encode([
            "success" => false,
            "message" => "Unable to check fingerprint ID",
            "error" => mysqli_error($conn)
        ]);

        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $fingerprint_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $existingFingerprint = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    /*
    |----------------------------------------------------------------------
    | Fingerprint already belongs to another student
    |----------------------------------------------------------------------
    */

    if (
        $existingFingerprint &&
        $existingFingerprint['student_id'] !== $student_id
    ) {

        $failedMessage =
            "Fingerprint ID already belongs to another student";

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE biometric_enrollment_requests
             SET
                status = 'Failed',
                fingerprint_id = ?,
                message = ?,
                completed_at = NOW()
             WHERE request_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "isi",
            $fingerprint_id,
            $failedMessage,
            $request_id
        );

        mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);

        echo json_encode([
            "success" => false,
            "message" => $failedMessage,
            "request_id" => $request_id
        ]);

        exit;
    }

    /*
    |----------------------------------------------------------------------
    | Check existing biometric for this student
    |----------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id
         FROM student_biometrics
         WHERE student_id = ?
         LIMIT 1"
    );

    if (!$stmt) {

        echo json_encode([
            "success" => false,
            "message" => "Unable to check student biometric",
            "error" => mysqli_error($conn)
        ]);

        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $student_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $existingStudent = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    /*
    |----------------------------------------------------------------------
    | Update existing biometric
    |----------------------------------------------------------------------
    */

    if ($existingStudent) {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE student_biometrics
             SET
                fingerprint_id = ?,
                enrolled_at = NOW(),
                status = 'Active'
             WHERE student_id = ?"
        );

        if (!$stmt) {

            echo json_encode([
                "success" => false,
                "message" => "Unable to prepare biometric update",
                "error" => mysqli_error($conn)
            ]);

            exit;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "is",
            $fingerprint_id,
            $student_id
        );

    }

    /*
    |----------------------------------------------------------------------
    | Insert new biometric
    |----------------------------------------------------------------------
    */

    else {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO student_biometrics
            (
                student_id,
                fingerprint_id,
                enrolled_at,
                status
            )
            VALUES
            (
                ?,
                ?,
                NOW(),
                'Active'
            )"
        );

        if (!$stmt) {

            echo json_encode([
                "success" => false,
                "message" => "Unable to prepare biometric insert",
                "error" => mysqli_error($conn)
            ]);

            exit;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $student_id,
            $fingerprint_id
        );
    }

    /*
    |----------------------------------------------------------------------
    | Execute INSERT / UPDATE
    |----------------------------------------------------------------------
    */

    if (!mysqli_stmt_execute($stmt)) {

        $dbError = mysqli_stmt_error($stmt);

        mysqli_stmt_close($stmt);

        echo json_encode([
            "success" => false,
            "message" => "Unable to save biometric information",
            "error" => $dbError,
            "student_id" => $student_id,
            "fingerprint_id" => $fingerprint_id
        ]);

        exit;
    }

    mysqli_stmt_close($stmt);

    /*
    |----------------------------------------------------------------------
    | Update enrollment request
    |----------------------------------------------------------------------
    */

    $successMessage =
        "Biometric enrollment successful";

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE biometric_enrollment_requests
         SET
            status = 'Completed',
            fingerprint_id = ?,
            message = ?,
            completed_at = NOW()
         WHERE request_id = ?"
    );

    if (!$stmt) {

        echo json_encode([
            "success" => false,
            "message" => "Biometric saved but request update failed",
            "error" => mysqli_error($conn),
            "student_id" => $student_id,
            "fingerprint_id" => $fingerprint_id
        ]);

        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isi",
        $fingerprint_id,
        $successMessage,
        $request_id
    );

    if (!mysqli_stmt_execute($stmt)) {

        $dbError = mysqli_stmt_error($stmt);

        mysqli_stmt_close($stmt);

        echo json_encode([
            "success" => false,
            "message" => "Biometric saved but request status update failed",
            "error" => $dbError
        ]);

        exit;
    }

    mysqli_stmt_close($stmt);

    /*
    |----------------------------------------------------------------------
    | Get student name
    |----------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT student_name
         FROM students
         WHERE student_id = ?
         LIMIT 1"
    );

    $student_name = $student_id;

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $student_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $student = mysqli_fetch_assoc($result);

        if ($student) {
            $student_name = $student['student_name'];
        }

        mysqli_stmt_close($stmt);
    }

    /*
    |--------------------------------------------------------------------------
    | Final response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Biometric enrollment completed successfully",
        "request_id" => $request_id,
        "student_id" => $student_id,
        "student_name" => $student_name,
        "fingerprint_id" => $fingerprint_id,
        "device_id" => $device_id
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| ENROLLMENT FAILED
|--------------------------------------------------------------------------
*/

if ($message === '') {
    $message = "Fingerprint enrollment failed";
}

$stmt = mysqli_prepare(
    $conn,
    "UPDATE biometric_enrollment_requests
     SET
        status = 'Failed',
        fingerprint_id = NULL,
        message = ?,
        completed_at = NOW()
     WHERE request_id = ?"
);

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to record enrollment failure",
        "error" => mysqli_error($conn)
    ]);

    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "si",
    $message,
    $request_id
);

mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);

echo json_encode([
    "success" => true,
    "message" => "Enrollment failure recorded",
    "request_id" => $request_id
]);

exit;

?>