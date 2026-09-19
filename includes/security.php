<?php
// CSRF protection helpers — must be included AFTER session_start().
// Use csrf_field() in every state-changing form, and csrf_validate()
// at the top of every state-changing handler.

function csrf_token(){
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(){
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

// Validate a POSTed CSRF token. On failure prints a message and dies.
function csrf_validate(){
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string)$_POST['csrf_token'])) {
        http_response_code(403);
        die("Invalid or expired form token. Please go back, reload the page, and try again.");
    }
}