Attendance Management System - Student Page

Files:
1. Student.php
2. Student0.js
3. Student.css
4. download_student_excel.php

Required existing files:
- db_connect.php
- attendance_functions.php
- login.php
- logout.php

Existing database tables/columns used:
- students: student_id, student_name, mobile, branch_name, semester
- attendance_record: student_id, teacher_id, attendance_date, status
- semester_settings: semester_name, academic_year, start_date, end_date, status
- holidays: id, holiday_date, holiday_name

Session required:
$_SESSION['student_id'] must contain the logged-in student's ID.

The page automatically reads the ACTIVE semester from semester_settings.
No hard-coded month/year is used.
