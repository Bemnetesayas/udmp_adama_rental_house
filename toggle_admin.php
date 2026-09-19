<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

// Security check
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1){
    die("Access Denied");
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    csrf_validate();
    $user_id = (int)$_POST['user_id'];
    $current_status = (int)$_POST['current_status'];

    // If they were 0 (user), make them 1 (admin). If they were 1, make them 0.
    $new_status = ($current_status == 1) ? 0 : 1;

    $sql = "UPDATE users SET is_admin = $new_status WHERE id = $user_id";
    
    if(mysqli_query($conn, $sql)){
        header("Location: admin_manage_users.php?msg=RoleUpdated");
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}