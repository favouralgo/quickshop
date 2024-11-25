<?php
session_start();

function checkUserRole($allowed_roles) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: ../login.php");
        exit();
    }

    // Check if user's role is allowed
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: ../unauthorized.php");
        exit();
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function canManageUsers() {
    return $_SESSION['role'] === 'administrator';
}

function canManageProducts() {
    return in_array($_SESSION['role'], ['administrator', 'inventory']);
}

function canViewProducts() {
    return in_array($_SESSION['role'], ['administrator', 'inventory', 'sales', 'customer']);
}

function canManageOrders() {
    return in_array($_SESSION['role'], ['administrator', 'sales']);
}

function canViewOrders() {
    return in_array($_SESSION['role'], ['administrator', 'sales', 'inventory']);
}

function canViewOwnOrders() {
    return $_SESSION['role'] === 'customer';
}
?>