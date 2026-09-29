const statusData = {

    /* =====================
       200 OK
    ===================== */

    200: {
        title: "Operation Successful",
        message: "The request was completed successfully.",
        color: "#22c55e",

        animation: `
            <div class="success-circle">
                <div class="success-check"></div>
            </div>
        `
    },


    /* =====================
       301 MOVED
    ===================== */

    301: {
        title: "Moved Permanently",
        message: "The requested resource has been permanently moved.",

        color: "#3b82f6",

        animation: `
            <div class="redirect-arrow"></div>
        `
    },


    /* =====================
       302 FOUND
    ===================== */

    302: {
        title: "Redirecting",
        message: "You are being redirected to another page.",

        color: "#06b6d4",

        animation: `
            <div class="redirect-arrow"></div>
        `
    },


    /* =====================
       400 BAD REQUEST
    ===================== */

    400: {
        title: "Bad Request",
        message: "The request contains invalid or incomplete information.",

        color: "#f59e0b",

        animation: `
            <div class="warning"></div>
        `
    },


    /* =====================
       401 UNAUTHORIZED
    ===================== */

    401: {
        title: "Unauthorized",
        message: "Please login to access this resource.",

        color: "#ef4444",

        animation: `
            <div class="lock"></div>
        `
    },


    /* =====================
       403 FORBIDDEN
    ===================== */

    403: {
        title: "Access Forbidden",
        message: "You are not authorized to access this resource.",

        color: "#ef4444",

        animation: `
            <div class="forbidden"></div>
        `
    },


    /* =====================
       404 NOT FOUND
    ===================== */

    404: {
        title: "Page Not Found",
        message: "The requested resource could not be found.",

        color: "#64748b",

        animation: `
            <div class="search-icon"></div>
        `
    },


    /* =====================
       500 SERVER ERROR
    ===================== */

    500: {
        title: "Internal Server Error",
        message: "Something went wrong on the server.",

        color: "#ef4444",

        animation: `
            <div class="server"></div>
        `
    },


    /* =====================
       502 BAD GATEWAY
    ===================== */

    502: {
        title: "Bad Gateway",
        message: "Unable to communicate with the server.",

        color: "#ef4444",

        animation: `
            <div class="connection">

                <div class="device"></div>

                <div class="connection-line"></div>

                <div class="server-small"></div>

            </div>
        `
    },


    /* =====================
       503 SERVICE UNAVAILABLE
    ===================== */

    503: {
        title: "Service Unavailable",
        message: "The service is temporarily unavailable. Please try again later.",

        color: "#f59e0b",

        animation: `
            <div class="gear">⚙</div>
        `
    }

};


/* =====================================
   SHOW HTTP STATUS
===================================== */

function showHttpStatus(code) {

    const data = statusData[code];

    if (!data) {

        console.error("HTTP status not configured:", code);

        return;
    }


    const container =
        document.getElementById("httpStatus");

    const animationArea =
        document.getElementById("animationArea");

    const statusCode =
        document.getElementById("statusCode");

    const statusTitle =
        document.getElementById("statusTitle");

    const statusMessage =
        document.getElementById("statusMessage");


    /* Set animation */

    animationArea.innerHTML =
        data.animation;


    /* Set text */

    statusCode.textContent =
        code;

    statusTitle.textContent =
        data.title;

    statusMessage.textContent =
        data.message;


    /* Set status color */

    statusCode.style.color =
        data.color;


    /* Show */

    container.classList.add("show");
}


/* =====================================
   CLOSE
===================================== */

function closeStatus() {

    const container =
        document.getElementById("httpStatus");

    container.classList.remove("show");
}


/* =====================================
   ESC KEY
===================================== */

document.addEventListener("keydown", function (event) {

    if (event.key === "Escape") {

        closeStatus();

    }

});