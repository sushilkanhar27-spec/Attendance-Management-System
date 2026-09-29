<?php
session_start();
include "db_connect.php";

if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit;
}

$role = isset($_POST['role']) ? trim($_POST['role']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$id = isset($_POST['id']) ? trim($_POST['id']) : '';
$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
$department = isset($_POST['department']) ? trim($_POST['department']) : '';
$semester = isset($_POST['semester']) ? trim($_POST['semester']) : '';
$reg_code = isset($_POST['regCode']) ? trim($_POST['regCode']) : (isset($_POST['reg_code']) ? trim($_POST['reg_code']) : '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

if ($password === '' || $confirmPassword === '') {
    echo json_encode(["success" => false, "message" => "Please enter both password fields"]);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(["success" => false, "message" => "Passwords do not match"]);
    exit;
}

if ($role === 'teacher' || $role === 'admin') {
    if ($reg_code === '') {
        echo json_encode(["success" => false, "message" => "Registration code is required"]);
        exit;
    }

    $codeCheck = $conn->prepare("SELECT 1 FROM registration_codes WHERE role = ? AND reg_code = ? AND status = 'active' LIMIT 1");
    if (!$codeCheck) {
        echo json_encode(["success" => false, "message" => "Unable to validate registration code"]);
        exit;
    }

    $codeCheck->bind_param("ss", $role, $reg_code);
    $codeCheck->execute();
    $codeCheck->store_result();

    if ($codeCheck->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Invalid registration code"]);
        $codeCheck->close();
        exit;
    }

    $codeCheck->close();
}

// Department and semester required only for students
if (
    $role === '' ||
    $name === '' ||
    $id === '' ||
    $mobile === '' ||
    ($role === 'student' && ($department === '' || $semester === '')) ||
    ($role === 'teacher' && $department === '')
) {
    echo json_encode([
        "success" => false,
        "message" => "Please fill all required fields"
    ]);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    // ensure password column large enough
    $conn->query("ALTER TABLE students MODIFY password VARCHAR(255) NOT NULL");
    $conn->query("ALTER TABLE teacher MODIFY password VARCHAR(255) NOT NULL");
    $conn->query("ALTER TABLE admin MODIFY password VARCHAR(255) NOT NULL");

    // check for duplicate ID
    if ($role === "student") {
        $check = $conn->prepare("SELECT 1 FROM students WHERE student_id = ? LIMIT 1");
        $check->bind_param("s", $id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Student ID already exists"]);
            exit;
        }
        $check->close();

        $sql = "INSERT INTO students (student_name, student_id, mobile, branch_name, semester, password) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $name, $id, $mobile, $department, $semester, $passwordHash);
    } elseif ($role === "teacher") {
        $check = $conn->prepare("SELECT 1 FROM teacher WHERE teacher_id = ? LIMIT 1");
        $check->bind_param("s", $id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Teacher ID already exists"]);
            exit;
        }
        $check->close();

        $sql = "INSERT INTO teacher (teacher_name, teacher_id, mobile, branch_name, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $id, $mobile, $department, $passwordHash);
    } elseif ($role === "admin") {

        // Check duplicate Admin ID
        $check = $conn->prepare("SELECT 1 FROM admin WHERE admin_id = ? LIMIT 1");
        $check->bind_param("s", $id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo json_encode([
                "success" => false,
                "message" => "Admin ID already exists"
            ]);
            exit;
        }

        $check->close();

        // Insert admin
        $sql = "INSERT INTO admin (admin_name, admin_id, mobile, password)
            VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssss",
            $name,
            $id,
            $mobile,
            $passwordHash
        );
    } else {
        echo json_encode(["success" => false, "message" => "Invalid role"]);
        exit;
    }

    $stmt->execute();
    echo json_encode(["success" => true, "message" => "Registration successful"]);
} catch (mysqli_sql_exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    mysqli_close($conn);
}
