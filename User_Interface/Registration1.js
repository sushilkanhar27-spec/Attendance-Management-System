console.log('Registration1.js loaded');

function getFormData() {
    const regCodeInput = document.querySelector('input[name="reg_code"]');

    return {
        role: document.getElementById("role").value,
        name: document.getElementById("name").value.trim(),
        id: document.getElementById("id").value.trim(),
        mobile: document.getElementById("mobile").value.trim(),
        department: document.getElementById("department").value,
        semester: document.getElementById("semester").value,
        regCode: regCodeInput ? regCodeInput.value.trim() : ""
    };
}

function showPasswordBox() {

    const data = getFormData();

    if (data.role === "student") {
        if (!data.name || !data.id || !data.mobile || !data.department || !data.semester) {
            alert("Please fill all fields.");
            return;
        }
    }
    else if (data.role === "teacher") {
        if (!data.name || !data.id || !data.mobile || !data.department) {
            alert("Please fill all fields.");
            return;
        }
    }
    else if (data.role === "admin") {
        if (!data.name || !data.id || !data.mobile) {
            alert("Please fill all fields.");
            return;
        }
    }

    // Show Password Section
    document.getElementById("passwordSection").style.display = "block";

    // Show Registration Code only for Teacher/Admin
    if (data.role === "teacher" || data.role === "admin") {
        document.getElementById("codeDiv").style.display = "block";
    } else {
        document.getElementById("codeDiv").style.display = "none";
    }

    // Hide Register Button
    document.getElementById("registerBtn").style.display = "none";
}
function completeRegistration() {

    const data = getFormData();

    const regCode = document.querySelector('input[name="reg_code"]').value.trim();

    if ((data.role === "teacher" || data.role === "admin") && regCode === "") {
        alert("Please enter the Registration Code.");
        return;
    }

    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    const formBody = new URLSearchParams({
        role: data.role,
        name: data.name,
        id: data.id,
        mobile: data.mobile,
        department: data.department,
        semester: data.semester,
        regCode: data.regCode,
        password: password,
        confirmPassword: confirmPassword
    });

    fetch("register_process.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: formBody.toString()
    })
        .then(async response => {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (error) {
                return { success: false, message: text || "Unknown server error" };
            }
        })
        .then(result => {
            if (result.success) {
                alert(result.message || "Registration Successful");
                location.reload();
            } else {
                alert("Error: " + (result.message || "Registration failed"));
            }
        })
        .catch(error => {
            alert("Server Error");
            console.error(error);
        });
}

function switchRole(role) {

    document.getElementById("role").value = role;

    document.getElementById("studentBtn").classList.toggle("active", role === "student");
    document.getElementById("teacherBtn").classList.toggle("active", role === "teacher");
    document.getElementById("adminBtn").classList.toggle("active", role === "admin");

    if (role === "student") {
        document.getElementById("title").innerText = "Student Registration";
        document.getElementById("idLabel").innerText = "Student ID";
        document.getElementById("departmentBox").style.display = "block";
        document.getElementById("semesterBox").style.display = "block";
        document.getElementById("codeDiv").style.display = "none";
    }

    else if (role === "teacher") {
        document.getElementById("title").innerText = "Teacher Registration";
        document.getElementById("idLabel").innerText = "Teacher ID";
        document.getElementById("departmentBox").style.display = "block";
        document.getElementById("semesterBox").style.display = "none";
        document.getElementById("semester").value = "";
        document.getElementById("codeDiv").style.display = "block";
    }

    else if (role === "admin") {
        document.getElementById("title").innerText = "Admin Registration";
        document.getElementById("idLabel").innerText = "Admin ID";
        document.getElementById("departmentBox").style.display = "none";
        document.getElementById("semesterBox").style.display = "none";
        document.getElementById("department").value = "";
        document.getElementById("semester").value = "";
        document.getElementById("codeDiv").style.display = "block";
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const studentBtn = document.getElementById("studentBtn");
    const teacherBtn = document.getElementById("teacherBtn");
    const adminBtn = document.getElementById("adminBtn");

    if (studentBtn) studentBtn.addEventListener("click", () => switchRole("student"));
    if (teacherBtn) teacherBtn.addEventListener("click", () => switchRole("teacher"));
    if (adminBtn) adminBtn.addEventListener("click", () => switchRole("admin"));

    switchRole("student");
});

// const roleInput = document.getElementById("role");
// const codeDiv = document.getElementById("codeDiv");

// if (roleInput && codeDiv) {
//     roleInput.addEventListener("change", function () {
//         codeDiv.style.display = (this.value === "teacher" || this.value === "admin") ? "block" : "none";
//     });
// }