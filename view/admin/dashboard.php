<?php
session_start();

switch ($_SESSION['role']) {
    case 'administrator':
        header('Location: admindashboard.php');
        exit();
    case 'customer':
        header('Location: customerdashboard.php');
        exit();
    case 'sales':
        header('Location: salesdashboard.php');
        exit();
    case 'inventory':
        header('Location: salesdashboard.php');
        exit();
    default:
        $errorMsg = "Invalid user role.";
}

?>