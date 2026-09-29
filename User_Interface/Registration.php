<?php
include "db_connect.php";

$role = isset($_POST['role']) ? trim($_POST['role']) : 'student';
$regCodeError = '';

if ($role === 'teacher' || $role === 'admin') {
    $reg_code = isset($_POST['reg_code']) ? trim($_POST['reg_code']) : '';

    if ($reg_code === '') {
        $regCodeError = 'Registration code is required.';
    } else {
        $check = $conn->prepare("SELECT 1 FROM registration_codes WHERE role = ? AND reg_code = ? AND status = 'active' LIMIT 1");
        if ($check) {
            $check->bind_param("ss", $role, $reg_code);
            $check->execute();
            $check->store_result();
            if ($check->num_rows === 0) {
                $regCodeError = 'Invalid registration code.';
            }
            $check->close();
        } else {
            $regCodeError = 'Unable to validate registration code.';
        }
    }
}

$departments = [];

$result = mysqli_query($conn, "SELECT branch_name FROM departments ORDER BY branch_name ASC");

while ($row = mysqli_fetch_assoc($result)) {
    $departments[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>

    <link rel="stylesheet" href="Register1.css">
    <link rel="stylesheet" href="Load.css">
</head>

<body>
    <form method="POST">

        <!-- Hidden Role -->
        <input type="hidden" id="role" name="role" value="student">

        <div class="container">
            <div class="topbar">
                <div class="brand">
                    <div class="brand-icon">🎓</div>
                    <div>
                        <span class="brand-name">GP Kandhamal</span>
                        <span class="brand-subtitle">Attendance Management System</span>
                    </div>
                </div>

                <div class="back-button">
                    <a href="index.php">← Back to Login</a>
                </div>
            </div>

            <div class="heading">
                <span class="eyebrow">ACCOUNT SETUP</span>
                <h2 id="title">Student Registration</h2>
                <p>Create your secure attendance portal account</p>
            </div>

            <div class="tabs">
                <button type="button" id="studentBtn" class="active">
                    Student
                </button>

                <button type="button" id="teacherBtn">
                    Teacher
                </button>

                <button type="button" id="adminBtn">
                    Admin
                </button>
            </div>

            <div class="input-group">
                <label id="nameLabel" for="name">Full Name</label>
                <input type="text" id="name" name="name" autocomplete="name" placeholder="Enter your full name">
            </div>

            <div class="input-group">
                <label id="idLabel" for="id">Student ID</label>
                <input type="text" id="id" name="id" autocomplete="username" placeholder="Enter your ID">
            </div>

            <div class="input-group">
                <label for="mobile">Mobile Number</label>
                <input type="tel" id="mobile" name="mobile" maxlength="10" autocomplete="tel" placeholder="Enter 10-digit mobile number">
            </div>

            <div class="input-group" id="departmentBox">
                <label for="department">Department</label>
                <select id="department" name="department" required>
                    <option value="">Select Department</option>

                    <?php foreach ($departments as $dept) { ?>
                        <option value="<?php echo htmlspecialchars($dept['branch_name']); ?>">
                            <?php echo htmlspecialchars($dept['branch_name']); ?>
                        </option>
                    <?php } ?>

                </select>
            </div>

            <div class="input-group" id="semesterBox">
                <label for="semester">Semester</label>
                <select id="semester" name="semester">
                    <option value="">Select Semester</option>
                    <option>1st Semester</option>
                    <option>2nd Semester</option>
                    <option>3rd Semester</option>
                    <option>4th Semester</option>
                    <option>5th Semester</option>
                    <option>6th Semester</option>
                </select>
            </div>



            <!-- Password Box (Hidden Initially) -->
            <div id="passwordSection" style="display:none;">

                <div id="codeDiv" style="display:none;">
                    <label for="reg_code">Registration Code</label>
                    <input type="text" id="reg_code" name="reg_code" placeholder="Enter registration code">
                    <?php if ($regCodeError !== '') { ?>
                        <div style="color:#dc3545; font-size:13px; margin-top:6px;">
                            <?php echo htmlspecialchars($regCodeError); ?>
                        </div>
                    <?php } ?>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" autocomplete="new-password">
                </div>

                <div class="input-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Re-enter your password" autocomplete="new-password">
                </div>

                <button type="button" class="register" onclick="completeRegistration()">
                    Complete Registration
                </button>

            </div>

            <button type="button" id="registerBtn" class="register" onclick="showPasswordBox()">
                Register
            </button>

            <div class="security-note">
                <span class="security-icon">🔒</span>
                <span>Your account information is handled securely.</span>
            </div>

            <div class="footer">
                Government Polytechnic Kandhamal
            </div>
        </div>
    </form>

    <script src="Registration1.js"></script>
    <?php require_once __DIR__ . '/loader.php'; ?>
</body>

</html>