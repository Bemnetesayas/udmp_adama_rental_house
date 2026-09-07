<?php
include('session_config.php');
session_start();
include('db.php');

// Security check[cite: 5]
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 2) die("Denied");

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $id = (int)$_POST['id'];
    $action = $_POST['action'];

    switch($action) {
        case 'approve_user':
            mysqli_query($conn, "UPDATE users SET status = 1 WHERE id = $id");
            header("Location: admin_manage_users.php?msg=approved");
            break;
            
        case 'delete_user':
            if($id != $_SESSION['user_id']){
                mysqli_query($conn, "DELETE FROM users WHERE id = $id");
            }
            header("Location: admin_manage_users.php?msg=deleted");
            break;

        case 'approve_house':
            mysqli_query($conn, "UPDATE houses SET status = 1 WHERE id = $id");
            header("Location: admin_manage_houses.php?msg=posted");
            break;

        case 'delete_house':
            mysqli_query($conn, "DELETE FROM requests WHERE house_id = $id");
            mysqli_query($conn, "DELETE FROM rental_requests WHERE house_id = $id");
            mysqli_query($conn, "DELETE FROM houses WHERE id = $id");
            header("Location: admin_manage_houses.php?msg=deleted");
            break;
    }
}
?>