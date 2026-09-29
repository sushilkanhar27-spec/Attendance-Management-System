<?php
session_start();
include "db_connect.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $role = $_POST['role'] ?? 'student';
    $user_id = trim($_POST['user_id'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $allowed_roles = ['student', 'teacher', 'admin'];

    if (!in_array($role, $allowed_roles, true)) {
        $error = "Invalid account type.";
    } elseif ($user_id === '' || $new_password === '' || $confirm_password === '') {
        $error = "Please fill all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {

        $table = '';
        $id_column = '';

        if ($role === 'student') {
            $table = 'students';
            $id_column = 'student_id';
        } elseif ($role === 'teacher') {
            $table = 'teacher';
            $id_column = 'teacher_id';
        } else {
            $table = 'admin';
            $id_column = 'admin_id';
        }

        $stmt = $conn->prepare("SELECT $id_column FROM $table WHERE $id_column = ? LIMIT 1");

        if (!$stmt) {
            $error = "Unable to process the password reset.";
        } else {
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->close();

                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $update = $conn->prepare("UPDATE $table SET password = ? WHERE $id_column = ?");

                if ($update) {
                    $update->bind_param("ss", $hashed_password, $user_id);

                    if ($update->execute()) {
                        $message = "Password reset successfully. You can now sign in.";
                    } else {
                        $error = "Unable to update the password.";
                    }

                    $update->close();
                } else {
                    $error = "Unable to process the password reset.";
                }
            } else {
                $stmt->close();

                $label = $role === 'student' ? 'Student ID' :
                         ($role === 'teacher' ? 'Teacher ID' : 'Admin ID');

                $error = $label . " not found.";
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
    <title>Reset Password | Attendance Management System</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --cyan: #06b6d4;
            --green: #16a34a;
            --red: #dc2626;
            --text: #172033;
            --muted: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px 15px;
            font-family: 'Inter', Arial, sans-serif;
            color: var(--text);
            background:
                linear-gradient(135deg, rgba(7, 22, 48, .84), rgba(14, 116, 144, .57)),
                url('GPK1.webp');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .page {
            width: min(100%, 440px);
        }

        .container {
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, .55);
            border-radius: 26px;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 30px 75px rgba(0, 0, 0, .32);
            backdrop-filter: blur(18px);
            animation: cardIn .5s ease-out;
        }

        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(24px) scale(.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .brand-icon {
            width: 52px;
            height: 52px;
            display: grid;
            place-items: center;
            border-radius: 15px;
            font-size: 27px;
            background: linear-gradient(135deg, var(--primary), var(--cyan));
            box-shadow: 0 10px 22px rgba(37, 99, 235, .25);
        }

        .brand-name {
            display: block;
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
        }

        .brand-subtitle {
            display: block;
            margin-top: 3px;
            font-size: 10px;
            color: var(--muted);
        }

        .heading {
            margin-bottom: 22px;
        }

        .eyebrow {
            display: block;
            margin-bottom: 7px;
            color: var(--primary);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
        }

        h2 {
            color: #0f172a;
            font-size: 27px;
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: -.7px;
        }

        .heading p {
            margin-top: 7px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .role {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 7px;
            margin-bottom: 22px;
            padding: 5px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #f1f5f9;
        }

        .role button {
            min-height: 44px;
            border: 0;
            border-radius: 10px;
            cursor: pointer;
            background: transparent;
            color: #64748b;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            transition: .25s ease;
        }

        .role button:hover {
            color: var(--primary);
            background: #e8f0ff;
        }

        .role button.active {
            color: #fff;
            background: linear-gradient(135deg, var(--primary), #3b82f6);
            box-shadow: 0 6px 14px rgba(37, 99, 235, .25);
        }

        .input-group {
            margin-bottom: 16px;
        }

        .input-group label {
            display: block;
            margin-bottom: 7px;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
        }

        .input-group input {
            width: 100%;
            height: 49px;
            padding: 0 14px;
            border: 1px solid #cbd5e1;
            border-radius: 11px;
            outline: none;
            background: #f8fafc;
            color: #0f172a;
            font-family: inherit;
            font-size: 13px;
            transition: .2s ease;
        }

        .input-group input::placeholder {
            color: #94a3b8;
        }

        .input-group input:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
        }

        .password-hint {
            margin-top: -8px;
            margin-bottom: 16px;
            color: #94a3b8;
            font-size: 10px;
        }

        .submit-btn {
            width: 100%;
            min-height: 50px;
            border: 0;
            border-radius: 12px;
            cursor: pointer;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            box-shadow: 0 10px 20px rgba(37, 99, 235, .24);
            font-family: inherit;
            font-size: 13px;
            font-weight: 800;
            transition: .2s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 25px rgba(37, 99, 235, .30);
        }

        .back {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 45px;
            margin-top: 10px;
            border: 1px solid #bfdbfe;
            border-radius: 11px;
            background: #eff6ff;
            color: #1e40af;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            transition: .2s ease;
        }

        .back:hover {
            background: #dbeafe;
            transform: translateY(-1px);
        }

        .success,
        .error {
            margin-top: 16px;
            padding: 12px 13px;
            border-radius: 11px;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.45;
        }

        .success {
            border: 1px solid #bbf7d0;
            color: #166534;
            background: #f0fdf4;
        }

        .error {
            border: 1px solid #fecaca;
            color: var(--red);
            background: #fef2f2;
        }

        .security-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 18px;
            color: #64748b;
            font-size: 10px;
        }

        .footer {
            margin-top: 15px;
            padding-top: 14px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 600;
        }

        @media (max-width: 520px) {
            body {
                padding: 15px 10px;
                background-attachment: scroll;
            }

            .container {
                padding: 24px 18px;
                border-radius: 21px;
            }

            h2 {
                font-size: 23px;
            }

            .role button {
                font-size: 11px;
            }
        }
    </style>
    <link rel="stylesheet" href="Load.css">
</head>

<body>
    <main class="page">
        <section class="container">

            <div class="brand">
                <div class="brand-icon">🎓</div>
                <div>
                    <span class="brand-name">GP Kandhamal</span>
                    <span class="brand-subtitle">Attendance Management System</span>
                </div>
            </div>

            <div class="heading">
                <span class="eyebrow">ACCOUNT SECURITY</span>
                <h2>Reset Password</h2>
                <p>Choose your account type and create a new secure password.</p>
            </div>

            <div class="role" aria-label="Select account type">
                <button type="button" id="studentBtn" class="active">Student</button>
                <button type="button" id="teacherBtn">Teacher</button>
                <button type="button" id="adminBtn">Admin</button>
            </div>

            <form method="post" id="resetForm">
                <input type="hidden" name="role" id="role" value="student">

                <div class="input-group">
                    <label id="idLabel" for="userId">Student ID</label>
                    <input
                        type="text"
                        name="user_id"
                        id="userId"
                        placeholder="Enter Student ID"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="input-group">
                    <label for="newPassword">New Password</label>
                    <input
                        type="password"
                        name="new_password"
                        id="newPassword"
                        placeholder="Enter new password"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="password-hint">Use at least 6 characters for better security.</div>

                <div class="input-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input
                        type="password"
                        name="confirm_password"
                        id="confirmPassword"
                        placeholder="Re-enter new password"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button class="submit-btn" type="submit">Reset Password</button>

                <a href="index.php" class="back">← Back to Login</a>
            </form>

            <?php if ($message !== ""): ?>
                <div class="success" role="status">
                    ✓ <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ""): ?>
                <div class="error" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="security-note">
                🔒 Your password is securely encrypted before storage.
            </div>

            <div class="footer">
                Government Polytechnic Kandhamal
            </div>

        </section>
    </main>

    <script>
        const studentBtn = document.getElementById("studentBtn");
        const teacherBtn = document.getElementById("teacherBtn");
        const adminBtn = document.getElementById("adminBtn");
        const roleInput = document.getElementById("role");
        const idLabel = document.getElementById("idLabel");
        const userId = document.getElementById("userId");

        function switchRole(role, button, label) {
            roleInput.value = role;

            [studentBtn, teacherBtn, adminBtn].forEach(btn => {
                btn.classList.remove("active");
            });

            button.classList.add("active");
            idLabel.textContent = label + " ID";
            userId.placeholder = "Enter " + label + " ID";
            userId.value = "";
            userId.focus();
        }

        studentBtn.addEventListener("click", () =>
            switchRole("student", studentBtn, "Student")
        );

        teacherBtn.addEventListener("click", () =>
            switchRole("teacher", teacherBtn, "Teacher")
        );

        adminBtn.addEventListener("click", () =>
            switchRole("admin", adminBtn, "Admin")
        );
    </script>
    <?php require_once __DIR__ . '/loader.php'; ?>
</body>
</html>
