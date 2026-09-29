<?php
$allowedErrorCodes = [400, 401, 403, 404, 500, 502, 503];
$errorCode = filter_input(INPUT_GET, 'code', FILTER_VALIDATE_INT);
if (!in_array($errorCode, $allowedErrorCodes, true)) {
    $errorCode = 404;
}
$errorContent = [
    400 => ['title' => 'Bad Request', 'message' => 'The request contains invalid or incomplete information.'],
    401 => ['title' => 'Unauthorized', 'message' => 'Please log in to access this resource.'],
    403 => ['title' => 'Access Forbidden', 'message' => 'You are not authorized to access this resource.'],
    404 => ['title' => 'Page Not Found', 'message' => 'The requested page could not be found.'],
    500 => ['title' => 'Internal Server Error', 'message' => 'Something went wrong on the server.'],
    502 => ['title' => 'Bad Gateway', 'message' => 'Unable to communicate with the server.'],
    503 => ['title' => 'Service Unavailable', 'message' => 'The service is temporarily unavailable. Please try again later.'],
];
$errorTitle = $errorContent[$errorCode]['title'];
$errorMessage = $errorContent[$errorCode]['message'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management System | Error <?php echo (int) $errorCode; ?></title>
    <link rel="stylesheet" href="ErrorCode.css">
</head>
<body class="error-page">
    <main id="httpStatus" class="status-container show"
          data-status-code="<?php echo (int) $errorCode; ?>"
          data-status-title="<?php echo htmlspecialchars($errorTitle, ENT_QUOTES, 'UTF-8'); ?>"
          data-status-message="<?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>"
          role="alert" aria-live="assertive">
        <section class="status-card" aria-labelledby="statusTitle">
            <div id="animationArea" class="animation-area" aria-hidden="true"></div>
            <div id="statusCode" class="status-code"><?php echo (int) $errorCode; ?></div>
            <h1 id="statusTitle"><?php echo htmlspecialchars($errorTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p id="statusMessage"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
            <a class="status-button" href="index.php">Back to Login</a>
        </section>
    </main>
    <script src="ErrorCode.js"></script>
</body>
</html>