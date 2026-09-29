<?php

// ============================================
// Get Active Semester
// ============================================

function getActiveSemester($conn)
{
    $query = mysqli_query($conn, "
        SELECT *
        FROM semester_settings
        WHERE status='Active'
        LIMIT 1
    ");

    if (!$query || mysqli_num_rows($query) == 0) {
        return false;
    }

    return mysqli_fetch_assoc($query);
}


// ============================================
// Check Holiday
// ============================================

function isHoliday($conn, $date)
{
    $date = mysqli_real_escape_string($conn, $date);

    $query = mysqli_query($conn, "
        SELECT id
        FROM holidays
        WHERE holiday_date='$date'
        LIMIT 1
    ");

    return mysqli_num_rows($query) > 0;
}


// ============================================
// Check Sunday
// ============================================

function isSunday($date)
{
    return date("w", strtotime($date)) == 0;
}


// ============================================
// Attendance Allowed ?
// ============================================

function isAttendanceAllowed($conn, $date)
{

    $semester = getActiveSemester($conn);

    if (!$semester) {
        return false;
    }

    if ($date < $semester['start_date']) {
        return false;
    }

    if ($date > $semester['end_date']) {
        return false;
    }

    if (isSunday($date)) {
        return false;
    }

    if (isHoliday($conn, $date)) {
        return false;
    }

    return true;
}


// ============================================
// Total Working Days
// ============================================

function getWorkingDays($conn)
{

    $semester = getActiveSemester($conn);

    if (!$semester) {
        return 0;
    }

    $start = strtotime($semester['start_date']);

    $end = strtotime($semester['end_date']);

    $workingDays = 0;

    while ($start <= $end) {

        $date = date("Y-m-d", $start);

        if (
            !isSunday($date)
            &&
            !isHoliday($conn, $date)
        ) {
            $workingDays++;
        }

        $start = strtotime("+1 day", $start);
    }

    return $workingDays;
}


// ============================================
// Completed Working Days (Till Today)
// ============================================

function getCompletedWorkingDays($conn)
{

    $semester = getActiveSemester($conn);

    if (!$semester) {
        return 0;
    }

    $today = date("Y-m-d");

    if ($today > $semester['end_date']) {
        $today = $semester['end_date'];
    }

    $start = strtotime($semester['start_date']);

    $end = strtotime($today);

    $count = 0;

    while ($start <= $end) {

        $date = date("Y-m-d", $start);

        if (
            !isSunday($date)
            &&
            !isHoliday($conn, $date)
        ) {
            $count++;
        }

        $start = strtotime("+1 day", $start);
    }

    return $count;
}


// ============================================
// Semester Closed ?
// ============================================

function isSemesterClosed($conn)
{

    $semester = getActiveSemester($conn);

    if (!$semester) {
        return true;
    }

    return date("Y-m-d") > $semester['end_date'];
}
