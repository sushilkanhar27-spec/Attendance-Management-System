# Attendance-Management-System
Major Project in Diploma
# Attendance Management System

A web-based **Attendance Management System** designed to simplify and automate student attendance management for colleges and educational institutions. The system provides separate access for **Admin, Teacher, and Student**, supports department/semester-wise attendance, biometric attendance, attendance reports, and Excel downloads.

---

## 📌 Project Overview

The Attendance Management System replaces traditional manual attendance registers with a centralized digital system.

The system allows:

* Admin to manage students, teachers, departments, semesters, holidays, and attendance settings.
* Teachers to mark and manage attendance for students belonging to their department.
* Students to view their own attendance records and attendance percentage.
* Biometric devices to identify students and record attendance automatically.
* Attendance reports to be generated and downloaded in Excel format.

---

## 🎯 Objectives

The main objectives of this project are:

1. To reduce manual attendance work.
2. To provide accurate attendance records.
3. To prevent unauthorized attendance marking.
4. To allow department-wise and semester-wise attendance management.
5. To calculate attendance percentage automatically.
6. To provide students with access to their own attendance records.
7. To integrate biometric fingerprint-based attendance.
8. To generate downloadable attendance reports.
9. To manage holidays and class dates from the admin panel.
10. To store attendance data securely in MySQL.

---

## 🚀 Main Features

### 👨‍💼 Admin Module

The administrator can:

* Login securely.
* Manage students.
* Manage teachers.
* Manage departments.
* Manage semesters.
* Manage registration codes.
* Configure semester start and end dates.
* Add class dates.
* Manage holidays.
* Manage attendance settings.
* View attendance records.
* Search attendance by date.
* View student reports.
* Generate attendance reports.
* Download attendance data in Excel.
* Manage biometric attendance settings.

---

### 👨‍🏫 Teacher Module

Teachers can:

* Login using Teacher ID and mobile number.
* Automatically view students belonging to their department.
* Select semester/class information where applicable.
* Mark students as **Present** or **Absent**.
* Save attendance.
* View attendance records.
* Use biometric attendance when enabled by the system.
* Access attendance only for their authorized department.
* Prevent future dates from being marked.

> **Important:** Attendance marking is controlled according to the teacher's department and the configured attendance date.

---

### 👨‍🎓 Student Module

Students can:

* Login using Student ID and mobile number.
* View their personal information.
* View attendance calendar.
* View Present and Absent records.
* View holidays and Sundays.
* View total class days.
* View attendance percentage.
* Download their own attendance data.

The student page displays only the logged-in student's information.

---

## 🖐️ Biometric Attendance

The system also supports fingerprint-based attendance using biometric hardware.

### Supported/Used Hardware

* AS608 fingerprint sensor
* R307S fingerprint sensor
* Arduino UNO R4
* ESP8266 NodeMCU
* OLED display
* Buzzer
* Wi-Fi connectivity

### Biometric Workflow

```text
Student
   ↓
Fingerprint Sensor
   ↓
ESP8266 / Arduino
   ↓
Wi-Fi
   ↓
PHP API
   ↓
MySQL Database
   ↓
Attendance Record
   ↓
Student / Teacher / Admin Dashboard
```

When a registered fingerprint is detected:

1. The fingerprint is verified.
2. The corresponding student is identified.
3. The student's department is checked.
4. The biometric attendance permission is checked.
5. Attendance is recorded in the database.
6. The student's name/status can be displayed on the OLED.

Example messages include:

```text
Welcome
Student Name
Attendance Marked
```

For an unregistered fingerprint:

```text
Finger not registered
```

If biometric attendance is not allowed for the student's department:

```text
Your department not allow to biometric attendance
```

---

## 🏗️ System Architecture

```text
                    ┌─────────────────────┐
                    │       Admin         │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │   Web Application   │
                    │   HTML/CSS/JS/PHP   │
                    └──────────┬──────────┘
                               │
             ┌─────────────────┼─────────────────┐
             │                 │                 │
             ▼                 ▼                 ▼
        ┌─────────┐       ┌─────────┐      ┌─────────┐
        │ Teacher │       │ Student │      │Biometric│
        │ Module  │       │ Module  │      │ Module  │
        └────┬────┘       └────┬────┘      └────┬────┘
             │                 │                │
             └─────────────────┼────────────────┘
                               ▼
                    ┌─────────────────────┐
                    │    MySQL Database   │
                    └─────────────────────┘
```

---

## 💻 Technologies Used

### Frontend

* HTML5
* CSS3
* JavaScript
* Responsive UI

### Backend

* PHP
* PHP MySQLi

### Database

* MySQL
* phpMyAdmin

### Development Environment

* XAMPP
* Apache
* MySQL
* Visual Studio Code

### Hardware

* ESP8266 NodeMCU
* Arduino UNO R4
* AS608 / R307S fingerprint sensor
* OLED display
* Buzzer

---

## 🗄️ Database

The system uses **MySQL** for storing application data.

The database contains tables for areas such as:

* Students
* Teachers
* Admins
* Departments
* Attendance records
* Semester settings
* Holidays
* Registration codes
* Biometric control
* Fingerprint/student mapping
* Attendance configuration

The database structure is designed to connect students, teachers, departments, semesters, and attendance records.

---

## 📅 Attendance Calculation

Attendance is calculated using the configured class days.

Basic formula:

```text
Attendance Percentage =
(Present Days / Total Class Days) × 100
```

For example:

```text
Present Days = 81
Total Class Days = 108

Attendance Percentage =
(81 / 108) × 100

= 75%
```

The system can exclude:

* Sundays
* Admin-defined holidays
* Dates outside the configured semester
* Dates outside the configured attendance period

---

## 📆 Semester Management

Semester settings control the attendance calendar.

The administrator can configure:

```text
Semester
    ↓
Start Date
    ↓
End Date
    ↓
Class Dates
    ↓
Holidays
    ↓
Total Class Days
```

The student attendance calendar automatically uses the configured semester dates.

---

## 🔐 Security

The system includes security-related features such as:

* Login authentication
* Session-based access control
* Department-based teacher authorization
* Password hashing
* Database-backed authentication
* Restricted student attendance downloads
* Restricted future attendance dates
* Biometric verification
* Registration code management

Passwords should be stored using secure PHP password hashing functions such as:

```php
password_hash()
```

and verified using:

```php
password_verify()
```

---

## 📊 Attendance Reports

The system provides attendance reporting features.

Reports can include:

* Student ID
* Student Name
* Mobile Number
* Department
* Semester
* Present Days
* Absent Days
* Total Class Days
* Attendance Percentage

Reports can be filtered using dates and other available criteria.

---

## 📥 Excel Download

The system supports attendance data export.

Users can download attendance information in Excel-compatible format.

Download restrictions are applied so that:

* Empty search results are not downloaded.
* Missing date ranges can show a validation message.
* Students can download only their own attendance data.
* Admin/teacher reports can be generated according to their permissions.

Example validation messages:

```text
Please select From Date and To Date.
```

or:

```text
Data is empty.
```

---

## 🔄 Attendance Workflow

### Manual Attendance

```text
Teacher Login
      ↓
Department Verification
      ↓
Student List
      ↓
Mark Present / Absent
      ↓
Save Attendance
      ↓
MySQL Database
      ↓
Student Attendance Calendar
```

### Biometric Attendance

```text
Fingerprint
     ↓
Fingerprint Sensor
     ↓
Student Identification
     ↓
Department Verification
     ↓
Attendance Permission
     ↓
Attendance Database
     ↓
Attendance Recorded
```

---

## ⚠️ Attendance Rules

The system follows rules such as:

* Future dates cannot be marked.
* Sundays are treated separately.
* Admin-defined holidays are excluded from normal class attendance.
* Teachers can manage attendance for their authorized department.
* A student's attendance is linked to the correct student ID.
* Attendance is stored in MySQL.
* Biometric attendance depends on the configured biometric permission.
* Attendance percentage is calculated from configured class days.

---

## 📁 Suggested Project Structure

```text
Attendance-Management-System/
│
├── index.php
├── login.php
├── logout.php
│
├── admin/
│   ├── admin_dashboard.php
│   ├── manage_students.php
│   ├── manage_departments.php
│   ├── admin_manage_attendance.php
│   ├── admin_view_attendance.php
│   ├── admin_download_attendance.php
│   └── reports.php
│
├── teacher/
│   ├── teacher_dashboard.php
│   ├── mark_attendance.php
│   └── teacher_attendance.php
│
├── student/
│   ├── student_dashboard.php
│   ├── attendance_calendar.php
│   └── download_attendance.php
│
├── biometric/
│   ├── biometric_get_request.php
│   ├── biometric_mark_attendance.php
│   └── biometric_control.php
│
├── css/
│   └── *.css
│
├── js/
│   └── *.js
│
├── database/
│   └── attendance_management.sql
│
└── README.md
```

> File names can be adjusted according to the actual project structure.

---

## ⚙️ Installation

### Step 1 — Install XAMPP

Install XAMPP with:

* Apache
* MySQL
* phpMyAdmin

---

### Step 2 — Copy Project

Copy the project folder into:

```text
C:\xampp\htdocs\
```

For example:

```text
C:\xampp\htdocs\AttendanceManagementSystem\
```

---

### Step 3 — Start XAMPP

Open XAMPP Control Panel and start:

```text
Apache
MySQL
```

If MySQL is configured on another port, use the configured port in the database connection file.

---

### Step 4 — Create Database

Open:

```text
http://localhost/phpmyadmin
```

Create the required database.

Example:

```sql
CREATE DATABASE attendance_management;
```

---

### Step 5 — Import Database

Import the project SQL file:

```text
database/attendance_management.sql
```

through phpMyAdmin.

---

### Step 6 — Configure Database Connection

Update the database connection file with your MySQL configuration.

Example:

```php
<?php

$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "attendance_management",
    3306
);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
```

Change the port if your MySQL server uses a different port.

---

## ▶️ Running the Project

After starting Apache and MySQL, open:

```text
http://localhost/AttendanceManagementSystem/
```

The login page will provide access to the appropriate system modules.

---

## 🔑 User Roles

| Role             | Main Responsibilities                        |
| ---------------- | -------------------------------------------- |
| Admin            | Manage complete system                       |
| Teacher          | Mark and manage department attendance        |
| Student          | View own attendance                          |
| Biometric Device | Automatically identify and record attendance |

---

## 🧩 Advantages

* Reduces manual paperwork.
* Saves teacher time.
* Provides centralized attendance records.
* Supports biometric attendance.
* Provides automatic percentage calculation.
* Supports department and semester management.
* Provides attendance reports.
* Allows Excel export.
* Provides students with direct access to attendance.
* Improves attendance record organization.

---

## 🔮 Future Enhancements

Possible future improvements include:

* Email notifications for low attendance.
* SMS notifications.
* Mobile application.
* Cloud-based deployment.
* Multiple biometric devices.
* Advanced analytics dashboard.
* Attendance notifications to parents.
* QR-code attendance.
* Face-recognition attendance.
* Automatic backup and restore.
* Role-based permission management.
* Real-time biometric device monitoring.

---

## 🛠️ Project Status

**Project:** Attendance Management System

**Status:** Development / Functional Prototype

**Platform:** Web Application

**Backend:** PHP + MySQL

**Frontend:** HTML + CSS + JavaScript

**Biometric:** ESP8266/Arduino + Fingerprint Sensor

**Development Environment:** XAMPP

---

## 👨‍💻 Developed For

**Educational Project**

**Course:** Computer Science and Engineering

**Project Type:** Web-Based Attendance Management System

---

## 📜 License

This project is developed for educational and academic purposes.

You may modify and extend the project according to your institution's requirements.
