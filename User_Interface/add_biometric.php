<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

$student_id = $_GET['student_id'] ?? '';

if ($student_id === '') {
    die("Student ID is missing.");
}

/*
|--------------------------------------------------------------------------
| Determine logged-in user
|--------------------------------------------------------------------------
*/

// Admin takes precedence if an older session still contains both role keys.
$is_admin = isset($_SESSION['admin_id']);
$is_teacher = !$is_admin && isset($_SESSION['teacher_id']);

$teacher_branch = null;

/*
|--------------------------------------------------------------------------
| Teacher department
|--------------------------------------------------------------------------
*/

if ($is_teacher) {

    $teacher_id = $_SESSION['teacher_id'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT branch_name
         FROM teacher
         WHERE teacher_id = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "s", $teacher_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $teacher = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$teacher) {
        die("Teacher information not found.");
    }

    $teacher_branch = $teacher['branch_name'];
}

/*
|--------------------------------------------------------------------------
| Get student
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        student_id,
        student_name,
        branch_name,
        semester,
        mobile
     FROM students
     WHERE student_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "s", $student_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$student) {
    die("Student not found.");
}

/*
|--------------------------------------------------------------------------
| Teacher security
|--------------------------------------------------------------------------
|
| Teacher can enroll only students from his/her department.
|--------------------------------------------------------------------------
*/

if ($is_teacher && $student['branch_name'] !== $teacher_branch) {
    http_response_code(403);
    die("Access denied. This student does not belong to your department.");
}

/*
|--------------------------------------------------------------------------
| Check existing biometric
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT fingerprint_id, status
     FROM student_biometrics
     WHERE student_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "s", $student_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$biometric = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (
    isset($_GET['action']) &&
    $_GET['action'] === 'status'
) {

    header("Content-Type: application/json; charset=UTF-8");

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            request_id,
            status,
            fingerprint_id,
            message,
            completed_at
         FROM biometric_enrollment_requests
         WHERE student_id = ?
         ORDER BY request_id DESC
         LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $student_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $request_status = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    /*
     * Check student biometric directly
     */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            fingerprint_id,
            status
         FROM student_biometrics
         WHERE student_id = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $student_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $biometric_status = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    echo json_encode([
        "success" => true,

        "request_id" =>
            $request_status['request_id'] ?? null,

        "status" =>
            $request_status['status'] ?? null,

        "fingerprint_id" =>
            $request_status['fingerprint_id'] ?? null,

        "message" =>
            $request_status['message'] ?? null,

        "completed_at" =>
            $request_status['completed_at'] ?? null,

        "completed" =>
            (
                isset($biometric_status['status']) &&
                $biometric_status['status'] === 'Active'
            )
    ]);

    exit;
}

$message = '';
$message_type = '';

/*
|--------------------------------------------------------------------------
| Start Enrollment
|--------------------------------------------------------------------------
*/

$existing_request = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /*
     * Handle cancel request
     */
    if ($action === 'cancel_request') {

        $request_id = $_POST['request_id'] ?? '';

        if ($request_id !== '') {

            $stmt = mysqli_prepare(
                $conn,
                "DELETE FROM biometric_enrollment_requests
                 WHERE request_id = ?
                 AND student_id = ?
                 AND status IN ('Pending', 'Processing')"
            );

            mysqli_stmt_bind_param($stmt, "ss", $request_id, $student_id);

            if (mysqli_stmt_execute($stmt)) {

                $message = "Enrollment request has been cancelled.";
                $message_type = "success";
                $existing_request = null;

            } else {

                $message = "Unable to cancel the enrollment request.";
                $message_type = "error";
            }

            mysqli_stmt_close($stmt);
        }

    } else {

        /*
         * Prevent duplicate active enrollment
         */
        $stmt = mysqli_prepare(
            $conn,
            "SELECT request_id
             FROM biometric_enrollment_requests
             WHERE student_id = ?
             AND status IN ('Pending', 'Processing')
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $student_id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $existing_request = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if ($existing_request) {

            $message = "An enrollment request is already waiting for this student.";
            $message_type = "warning";

        } else {

            /*
             * If an old biometric exists, do not overwrite it accidentally.
             */
            if ($biometric && $biometric['status'] === 'Active') {

                $message =
                    "This student already has an active biometric fingerprint.";

                $message_type = "warning";

            } else {

                /*
                 * Create new enrollment request
                 */
                $device_id = null;

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO biometric_enrollment_requests
                    (student_id, device_id, status, message)
                    VALUES (?, ?, 'Pending', 'Waiting for biometric device')"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "ss",
                    $student_id,
                    $device_id
                );

                if (mysqli_stmt_execute($stmt)) {

                    $message =
                        "Enrollment request created. Please place the student's finger on the biometric device.";

                    $message_type = "success";
                    $existing_request = [
                        'request_id' => mysqli_insert_id($conn)
                    ];

                } else {

                    $message =
                        "Unable to create enrollment request.";

                    $message_type = "error";
                }

                mysqli_stmt_close($stmt);
            }
        }
    }

} else {

    /*
     * Check for existing pending request on page load
     */
    $stmt = mysqli_prepare(
        $conn,
        "SELECT request_id
         FROM biometric_enrollment_requests
         WHERE student_id = ?
         AND status IN ('Pending', 'Processing')
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "s", $student_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $existing_request = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Biometric Enrollment</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
        }

        .container {
            width: min(700px, 94%);
            margin: 40px auto;
        }

        .card {
            background: white;
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,.08);
        }

        h1 {
            margin-top: 0;
            color: #0d47a1;
        }

        .student-info {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .row:last-child {
            border-bottom: none;
        }

        .label {
            color: #64748b;
        }

        .value {
            font-weight: bold;
        }

        .fingerprint {
            text-align: center;
            font-size: 70px;
            margin: 25px 0;
        }

        .instruction {
            text-align: center;
            color: #475569;
            margin-bottom: 25px;
        }

        .start-btn {
            width: 100%;
            border: none;
            background: linear-gradient(135deg,#059669,#10b981);
            color: white;
            padding: 15px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .start-btn:hover {
            transform: translateY(-1px);
        }

        .cancel-btn {
            width: 100%;
            border: none;
            background: linear-gradient(135deg,#dc2626,#ef4444);
            color: white;
            padding: 15px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }

        .cancel-btn:hover {
            transform: translateY(-1px);
        }

        .message {
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .warning {
            background: #fef3c7;
            color: #92400e;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .back {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #1565c0;
            font-weight: bold;
        }

    </style>

    <link rel="stylesheet" href="Load.css">
</head>

<body>

<div class="container">

    <div class="card">

        <h1>🖐️ Biometric Enrollment</h1>

        <?php if ($message !== ''): ?>

            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <div class="student-info">

            <div class="row">
                <span class="label">Student ID</span>
                <span class="value">
                    <?php echo htmlspecialchars($student['student_id']); ?>
                </span>
            </div>

            <div class="row">
                <span class="label">Student Name</span>
                <span class="value">
                    <?php echo htmlspecialchars($student['student_name']); ?>
                </span>
            </div>

            <div class="row">
                <span class="label">Department</span>
                <span class="value">
                    <?php echo htmlspecialchars($student['branch_name']); ?>
                </span>
            </div>

            <div class="row">
                <span class="label">Semester</span>
                <span class="value">
                    <?php echo htmlspecialchars($student['semester']); ?>
                </span>
            </div>

            <div class="row">
                <span class="label">Mobile</span>
                <span class="value">
                    <?php echo htmlspecialchars($student['mobile']); ?>
                </span>
            </div>

        </div>

        <div class="fingerprint">
            🖐️
        </div>

        <div class="instruction">

            <strong>Ready to enroll?</strong>

            <br><br>

            Click the button below and then place the student's
            finger on the AS608 fingerprint sensor.

        </div>

        <?php if ($existing_request): ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="action"
                    value="cancel_request"
                >

                <input
                    type="hidden"
                    name="request_id"
                    value="<?php echo htmlspecialchars($existing_request['request_id']); ?>"
                >

                <button
                    type="submit"
                    class="cancel-btn"
                >
                    ✕ Cancel Enrollment Request
                </button>

            </form>

        <?php elseif (!$biometric || $biometric['status'] !== 'Active'): ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="action"
                    value="start_enrollment"
                >

                <button
                    type="submit"
                    class="start-btn"
                >
                    🖐️ Start Biometric Enrollment
                </button>

            </form>

        <?php endif; ?>

        <?php if ($existing_request): ?>

<script>

const statusUrl = <?php echo json_encode(
    'add_biometric.php?student_id=' .
    rawurlencode($student_id) .
    '&action=status'
); ?>;

const enrollmentPoll = setInterval(async () => {

    try {

        const response =
            await fetch(statusUrl + '&_=' + Date.now(), {
                cache: 'no-store'
            });

        if (!response.ok) {
            return;
        }

        const status =
            await response.json();

        console.log("Biometric status:", status);

        /*
         * Enrollment successful
         */
        if (
            status.completed === true ||
            status.status === "Completed"
        ) {

            clearInterval(enrollmentPoll);

            window.location.reload();
        }

        /*
         * Enrollment failed
         */
        if (status.status === "Failed") {

            clearInterval(enrollmentPoll);

            window.location.reload();
        }

    } catch (error) {

        console.log(
            "Waiting for biometric device...",
            error
        );
    }

}, 2000);

</script>

<?php endif; ?>

        <?php if ($biometric && $biometric['status'] === 'Active'): ?>

            <div class="message success">
                This student already has an active biometric fingerprint.
            </div>

        <?php endif; ?>

    <?php require_once __DIR__ . '/loader.php'; ?>
</body>
</html>