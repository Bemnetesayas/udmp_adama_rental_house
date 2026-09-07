<?php
include('session_config.php');
session_start();
include('db.php');

// Only super admin can delete users
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 2){
    header("Location: login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $target_id = (int)($_POST['user_id'] ?? 0);
    $my_id = (int)$_SESSION['user_id'];

    // Cannot delete yourself
    if($target_id === $my_id){
        header("Location: admin_manage_users.php?msg=no_self_delete");
        exit();
    }

    // Load the target user
    $res = mysqli_query($conn, "SELECT * FROM users WHERE id=$target_id");
    if($res && ($target = mysqli_fetch_assoc($res))){

        // Cannot delete another super admin (rank 2)
        if((int)$target['is_admin'] >= 2){
            header("Location: admin_manage_users.php?msg=no_super_delete");
            exit();
        }

        // Delete their house image files
        $houses = mysqli_query($conn, "SELECT image, video_file FROM houses WHERE user_id=$target_id");
        if($houses){
            while($h = mysqli_fetch_assoc($houses)){
                if(!empty($h['image']) && file_exists("uploads/" . $h['image'])){
                    @unlink("uploads/" . $h['image']);
                }
                if(!empty($h['video_file']) && file_exists("uploads/" . $h['video_file'])){
                    @unlink("uploads/" . $h['video_file']);
                }
            }
        }

        // Delete their houses and requests (cleanup)
        mysqli_query($conn, "DELETE FROM requests WHERE user_id=$target_id");
        mysqli_query($conn, "DELETE FROM requests WHERE house_id IN (SELECT id FROM houses WHERE user_id=$target_id)");
        mysqli_query($conn, "DELETE FROM rental_requests WHERE house_id IN (SELECT id FROM houses WHERE user_id=$target_id)");
        mysqli_query($conn, "DELETE FROM rental_requests WHERE user_id=$target_id");
        mysqli_query($conn, "DELETE FROM houses WHERE user_id=$target_id");
        mysqli_query($conn, "DELETE FROM admin_invites WHERE user_id=$target_id");
        mysqli_query($conn, "DELETE FROM users WHERE id=$target_id");

        header("Location: admin_manage_users.php?msg=deleted");
        exit();
    }
}

// Fallback
header("Location: admin_manage_users.php");
exit();
