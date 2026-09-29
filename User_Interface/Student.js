(() => {
    'use strict';

    const data = window.STUDENT_PAGE_DATA || {};

    const semester = data.semester || {};
    const months = Array.isArray(data.months) ? data.months : [];
    const attendance = data.attendance || {};
    const holidays = data.holidays || {};
    let today = data.today || '';

    const calendarContainer =
        document.getElementById('calendarContainer');

    const totalClassDaysEl =
        document.getElementById('totalClassDays');

    const presentDaysEl =
        document.getElementById('presentDays');

    const absentDaysEl =
        document.getElementById('absentDays');

    const percentageEl =
        document.getElementById('attendancePercentage');

    const downloadBtn =
        document.getElementById('downloadExcelBtn');

    const downloadMessage =
        document.getElementById('downloadMessage');


    const MONTH_NAMES = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December'
    ];

    const DAY_NAMES = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday'
    ];


    /* ==========================================
       DATE FUNCTIONS
    ========================================== */

    function parseDate(dateString) {

        if (!dateString) {
            return null;
        }

        const parts = dateString.split('-').map(Number);

        return new Date(
            parts[0],
            parts[1] - 1,
            parts[2]
        );
    }


    function formatDate(date) {

        const year = date.getFullYear();

        const month = String(
            date.getMonth() + 1
        ).padStart(2, '0');

        const day = String(
            date.getDate()
        ).padStart(2, '0');

        return `${year}-${month}-${day}`;
    }

    // Prefer the client's local date to avoid server timezone mismatches
    // which can cause the page to show the next day as "upcoming".
    if (!today) {
        today = formatDate(new Date());
    } else {
        const clientToday = formatDate(new Date());
        if (today !== clientToday) {
            today = clientToday;
        }
    }


    function isSunday(date) {

        return date.getDay() === 0;
    }


    function isHoliday(dateString) {

        return Object.prototype.hasOwnProperty.call(
            holidays,
            dateString
        );
    }


    function isInsideSemester(dateString) {

        return (
            dateString >= semester.start_date &&
            dateString <= semester.end_date
        );
    }


    /* ==========================================
       GET DATE STATUS
    ========================================== */

    function getStatus(dateString) {

        const date = parseDate(dateString);

        if (!date) {
            return 'not-marked';
        }


        /* Sunday has priority */

        if (isSunday(date)) {
            return 'sunday';
        }


        /* Holiday */

        if (isHoliday(dateString)) {
            return 'holiday';
        }


        /* Future date */

        if (dateString > today) {
            return 'upcoming';
        }


        /* Attendance from database */

        const status = String(
            attendance[dateString] || ''
        )
            .trim()
            .toLowerCase();


        if (status === 'present') {
            return 'present';
        }


        if (status === 'absent') {
            return 'absent';
        }


        return 'not-marked';
    }


    /* ==========================================
       STATUS LABEL
    ========================================== */

    function statusLabel(status, dateString) {

        switch (status) {

            case 'present':
                return 'Present';

            case 'absent':
                return 'Absent';

            case 'sunday':
                return 'Sunday';

            case 'holiday':
                return holidays[dateString] || 'Holiday';

            case 'upcoming':
                return 'Upcoming';

            default:
                return 'Not Marked';
        }
    }


    /* ==========================================
       CREATE DAY
    ========================================== */

    function buildDayCell(dateString) {

        const date = parseDate(dateString);

        const status = getStatus(dateString);

        const cell = document.createElement('div');

        cell.className = `day-cell ${status}`;


        /* Highlight today */

        if (dateString === today) {
            cell.classList.add('today');
        }


        /* Date number */

        const dateNumber =
            document.createElement('div');

        dateNumber.className = 'date-number';

        dateNumber.textContent =
            date.getDate();


        cell.appendChild(dateNumber);


        /* Tooltip */

        cell.title =
            `${dateString} - ${statusLabel(
                status,
                dateString
            )}`;


        return cell;
    }


    /* ==========================================
       CREATE MONTH
    ========================================== */

    function createMonth(monthInfo) {

        const year = Number(monthInfo.year);

        const month = Number(monthInfo.month);


        const monthCard =
            document.createElement('section');

        monthCard.className = 'month-card';


        /* Month heading */

        const header =
            document.createElement('div');

        header.className = 'month-header';


        const title =
            document.createElement('h2');

        title.textContent =
            `${MONTH_NAMES[month - 1]} ${year}`;


        header.appendChild(title);

        monthCard.appendChild(header);


        /* Week names */

        const weekHeader =
            document.createElement('div');

        weekHeader.className =
            'week-header';


        [
            'Sun',
            'Mon',
            'Tue',
            'Wed',
            'Thu',
            'Fri',
            'Sat'
        ].forEach(day => {

            const element =
                document.createElement('div');

            element.textContent = day;

            weekHeader.appendChild(element);
        });


        monthCard.appendChild(weekHeader);


        /* Calendar grid */

        const grid =
            document.createElement('div');

        grid.className =
            'calendar-grid';


        const firstDate =
            new Date(year, month - 1, 1);

        const lastDate =
            new Date(year, month, 0);


        const firstWeekday =
            firstDate.getDay();


        /* Empty cells before month */

        for (
            let i = 0;
            i < firstWeekday;
            i++
        ) {

            const blank =
                document.createElement('div');

            blank.className =
                'day-cell empty';

            grid.appendChild(blank);
        }


        /* Days */

        for (
            let day = 1;
            day <= lastDate.getDate();
            day++
        ) {

            const date =
                new Date(
                    year,
                    month - 1,
                    day
                );


            const dateString =
                formatDate(date);


            /*
             * Do not show dates outside
             * administrator semester.
             */

            if (!isInsideSemester(dateString)) {

                const empty =
                    document.createElement('div');

                empty.className =
                    'day-cell empty';

                grid.appendChild(empty);

                continue;
            }


            grid.appendChild(
                buildDayCell(dateString)
            );
        }


        monthCard.appendChild(grid);

        return monthCard;
    }


    /* ==========================================
       CREATE CALENDAR
    ========================================== */

    function createCalendar() {

        calendarContainer.innerHTML = '';


        if (
            !semester.start_date ||
            !semester.end_date
        ) {

            calendarContainer.innerHTML =
                `<div class="empty-state">
                    Semester dates are not available.
                </div>`;

            return;
        }


        if (!months.length) {

            calendarContainer.innerHTML =
                `<div class="empty-state">
                    No semester months found.
                </div>`;

            return;
        }


        months.forEach(monthInfo => {

            calendarContainer.appendChild(
                createMonth(monthInfo)
            );

        });
    }


    /* ==========================================
       ATTENDANCE SUMMARY
    ========================================== */

    function recalculateSummary() {

        let total = 0;
        let present = 0;
        let absent = 0;


        if (
            !semester.start_date ||
            !semester.end_date
        ) {
            return;
        }


        const start =
            parseDate(semester.start_date);

        const end =
            parseDate(semester.end_date);


        /*
         * ==========================================
         * TOTAL CLASS DAYS
         *
         * Full semester:
         * Start Date → End Date
         *
         * Exclude:
         * - Sunday
         * - Holiday
         * ==========================================
         */

        for (
            let cursor = new Date(start);
            cursor <= end;
            cursor.setDate(
                cursor.getDate() + 1
            )
        ) {

            const dateString =
                formatDate(cursor);


            /*
             * Sunday is not a class day
             */
            if (isSunday(cursor)) {
                continue;
            }


            /*
             * Holiday is not a class day
             */
            if (isHoliday(dateString)) {
                continue;
            }


            total++;
        }


        /*
         * ==========================================
         * PRESENT / ABSENT
         *
         * Count only attendance already saved.
         * Future dates are ignored.
         * ==========================================
         */

        Object.keys(attendance).forEach(dateString => {

            /*
             * Ignore future attendance
             */
            if (dateString > today) {
                return;
            }


            /*
             * Ignore dates outside semester
             */
            if (!isInsideSemester(dateString)) {
                return;
            }


            const date =
                parseDate(dateString);


            /*
             * Ignore Sunday
             */
            if (isSunday(date)) {
                return;
            }


            /*
             * Ignore holiday
             */
            if (isHoliday(dateString)) {
                return;
            }


            const status =
                String(
                    attendance[dateString] || ''
                )
                    .trim()
                    .toLowerCase();


            if (status === 'present') {
                present++;
            }


            if (status === 'absent') {
                absent++;
            }

        });


        /*
         * ==========================================
         * ATTENDANCE %
         *
         * Present ÷ Total Semester Class Days
         * ==========================================
         */

        const percentage =
            total > 0
                ? (present / total) * 100
                : 0;


        /*
         * Update page
         */

        totalClassDaysEl.textContent =
            total;

        presentDaysEl.textContent =
            present;

        absentDaysEl.textContent =
            absent;

        percentageEl.textContent =
            `${percentage.toFixed(2)}%`;
    }

    /* ==========================================
       EXCEL
    ========================================== */

    function excelEscape(value) {

        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }


    function downloadExcel() {

        const student =
            data.student || {};

        const rows = [];


        rows.push([
            'Sl. No.',
            'Student ID',
            'Student Name',
            'Department',
            'Semester',
            'Academic Year',
            'Attendance Date',
            'Day',
            'Status'
        ]);


        const start =
            parseDate(semester.start_date);

        const end =
            parseDate(semester.end_date);


        let serial = 1;


        for (
            let cursor = new Date(start);
            cursor <= end;
            cursor.setDate(
                cursor.getDate() + 1
            )
        ) {

            const dateString =
                formatDate(cursor);


            const status =
                getStatus(dateString);


            rows.push([
                serial++,
                student.student_id || '',
                student.student_name || '',
                student.branch_name || '',
                student.semester || '',
                semester.academic_year || '',
                dateString,
                DAY_NAMES[cursor.getDay()],
                statusLabel(
                    status,
                    dateString
                )
            ]);
        }


        const htmlRows =
            rows.map(row => {

                const cells =
                    row.map(value =>
                        `<td>${excelEscape(value)}</td>`
                    ).join('');

                return `<tr>${cells}</tr>`;

            }).join('');


        const table = `
            <html>
            <head>
                <meta charset="UTF-8">
            </head>
            <body>
                <table border="1">
                    ${htmlRows}
                </table>
            </body>
            </html>
        `;


        const blob =
            new Blob(
                [table],
                {
                    type:
                        'application/vnd.ms-excel;charset=utf-8;'
                }
            );


        const url =
            URL.createObjectURL(blob);


        const link =
            document.createElement('a');

        link.href = url;

        link.download =
            `Student_Attendance_${student.student_id || 'Report'
            }.xls`;


        document.body.appendChild(link);

        link.click();

        link.remove();

        URL.revokeObjectURL(url);


        if (downloadMessage) {

            downloadMessage.textContent =
                'Excel downloaded successfully.';

            setTimeout(() => {

                downloadMessage.textContent = '';

            }, 3000);
        }
    }

    /* ==========================================
       DOWNLOAD BUTTON
    ========================================== */

    if (downloadBtn) {

        downloadBtn.addEventListener(
            'click',
            downloadExcel
        );
    }


    /* ==========================================
       START
    ========================================== */

    createCalendar();

    recalculateSummary();

})();