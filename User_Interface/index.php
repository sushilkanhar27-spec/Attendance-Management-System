<?php
session_start();
include "db_connect.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = trim($_POST['role'] ?? 'student');
    $user_id = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($user_id === '' || $password === '') {
        $error = 'Please enter both ID and password.';
    } else {
        $user_id = mysqli_real_escape_string($conn, $user_id);

        if ($role === 'teacher') {
            $sql = "SELECT * FROM teacher WHERE teacher_id='$user_id'";
            $result = mysqli_query($conn, $sql);

            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);

                if (password_verify($password, $row['password'])) {
                    unset($_SESSION['student_id'], $_SESSION['admin_id']);
                    $_SESSION['teacher_id'] = $row['teacher_id'];
                    header("Location: Teacher.php");
                    exit();
                } else {
                    $error = "Wrong password.";
                }
            } else {
                $error = "Teacher not found.";
            }
        } elseif ($role === 'admin') {
            $sql = "SELECT * FROM admin WHERE admin_id='$user_id'";
            $result = mysqli_query($conn, $sql);

            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);

                if (password_verify($password, $row['password'])) {
                    unset($_SESSION['student_id'], $_SESSION['teacher_id']);
                    $_SESSION['admin_id'] = $row['admin_id'];
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    $error = "Wrong password.";
                }
            } else {
                $error = "Admin not found.";
            }
        } else {
            $sql = "SELECT * FROM students WHERE student_id='$user_id'";
            $result = mysqli_query($conn, $sql);

            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);

                if (password_verify($password, $row['password'])) {
                    unset($_SESSION['teacher_id'], $_SESSION['admin_id']);
                    $_SESSION['student_id'] = $row['student_id'];
                    header("Location: Student.php");
                    exit();
                } else {
                    $error = "Wrong password.";
                }
            } else {
                $error = "Student not found.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2563eb">
    <title>Attendance Management System | Login</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="Load.css">
    <?php if ($error !== ''): ?>
    <link rel="stylesheet" href="ErrorCode.css">
    <script src="ErrorCode.js" defer></script>
    <?php endif; ?>
</head>

<body>
    <main class="container">
        <div class="logo" aria-hidden="true">🎓</div>

        <h2>Attendance System</h2>
        <p class="subtitle">Secure login to your attendance portal</p>

        <div class="role" aria-label="Select account type">
            <button type="button" id="studentBtn" class="active">Student</button>
            <button type="button" id="teacherBtn">Teacher</button>
            <button type="button" id="adminBtn">Admin</button>
        </div>

        <form id="loginForm" method="post" action="">
            <input type="hidden" name="role" id="roleInput" value="student">

            <div class="input-group">
                <label id="idLabel" for="userId">Student ID</label>
                <input
                    type="text"
                    id="userId"
                    name="user_id"
                    placeholder="Enter Student ID"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter Password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button class="login-btn" type="submit">Login Securely</button>

            <a href="forget_password.php" class="reset-btn">Forget Password?</a>
            <a href="Registration.php" class="create_account">Create Account</a>
        </form>

        <?php if ($error !== ''): ?>
            <div class="error" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="footer">
            Government Polytechnic Kandhamal &nbsp;•&nbsp; Attendance Management System
        </div>
    </main>

    <script>
        const studentBtn = document.getElementById("studentBtn");
        const teacherBtn = document.getElementById("teacherBtn");
        const adminBtn = document.getElementById("adminBtn");
        const roleInput = document.getElementById("roleInput");
        const idLabel = document.getElementById("idLabel");
        const userId = document.getElementById("userId");

        function selectRole(role, button, label) {
            roleInput.value = role;

            [studentBtn, teacherBtn, adminBtn].forEach(btn => {
                btn.classList.remove("active");
            });

            button.classList.add("active");
            idLabel.textContent = label + " ID";
            userId.placeholder = "Enter " + label + " ID";
            userId.focus();
        }

        studentBtn.addEventListener("click", () =>
            selectRole("student", studentBtn, "Student")
        );

        teacherBtn.addEventListener("click", () =>
            selectRole("teacher", teacherBtn, "Teacher")
        );

        adminBtn.addEventListener("click", () =>
            selectRole("admin", adminBtn, "Admin")
        );
    </script>
    <?php if ($error !== '') { require __DIR__ . '/login-modal.php'; } ?>
    <?php require_once __DIR__ . '/loader.php'; ?>
</body>
</html>
