<?php
// Authentication Helper Functions
session_start();

require_once __DIR__ . '/auth_config.php';

function isLoggedIn()
{
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }

    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        logout();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

function requireAuth()
{
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function login($username, $password)
{
    if ($username === AUTH_USERNAME && password_verify($password, AUTH_PASSWORD_HASH)) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['last_activity'] = time();
        return true;
    }
    return false;
}

function logout()
{
    $_SESSION = [];
    session_destroy();
}

function getUsername()
{
    return $_SESSION['username'] ?? 'Unknown';
}
?>