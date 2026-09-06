<?php
include('session_config.php');
session_start();
include('db.php');

// Only super admin can revoke admin roles
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 2){
    header("Location: login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $target_id = (int)($_POST['user_id'] ?? 0);
    $my_id = (int)$_SESSION['user_id'];

    // Cannot revoke yourself
    if($target_id === $my_id){
        header("Location: admin_manage_users.php?msg=no_self_revoke");
        exit();
    }

    // Load the target user
    $res = mysqli_query($conn, "SELECT * FROM users WHERE id=$target_id");
    if($res && ($target = mysqli_fetch_assoc($res))){

        // Cannot revoke another super admin (rank 2), only rank 1 admins
        if((int)$target['is_admin'] != 1){
            header("Location: admin_manage_users.php?msg=no_revoke_super");
            exit();
        }

        // Revoke: demote from admin (1) back to landlord (0)
        mysqli_query($conn, "UPDATE users SET is_admin = 0 WHERE id=$target_id");

        header("Location: admin_manage_users.php?msg=revoked");
        exit();
    }
}

// Fallback
header("Location: admin_manage_users.php");
exit();