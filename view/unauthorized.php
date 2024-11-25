<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="unauthorized-container">
        <i class="fas fa-exclamation-triangle"></i>
        <h1>Unauthorized Access</h1>
        <p>You do not have permission to access this page.</p>
        <a href="javascript:history.back()" class="back-btn">Go Back</a>
    </div>
</body>
</html>