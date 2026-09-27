let holidays = [];

fetch("get_holidays.php")
    .then(res => res.json())
    .then(data => {
        holidays = data;

        // Refresh calendar after holidays are loaded
        if (typeof createCalendar === "function") {
            createCalendar();
        }
    });