<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1){
    header("Location: login.php");
    exit();
}

if(empty($_POST['action'])){
    header("Location: admin_manage_requests.php");
    exit();
}
csrf_validate();

$action = $_POST['action'];
$id = (int)($_POST['id'] ?? 0);

function notifyOwner($conn, $house_id, $title, $message){
    $owner = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id, kebele FROM houses WHERE id=$house_id"));
    if(!$owner) return;
    $msg = mysqli_real_escape_string($conn, $message);
    mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, link) VALUES ('{$owner['user_id']}', 'listing', '$title', '$msg', 'manage_houses.php')");
}

switch($action){
    case 'approve':
        $hq = mysqli_fetch_assoc(mysqli_query($conn, "SELECT house_id FROM requests WHERE id=$id"));
        $house_id = $hq ? (int)$hq['house_id'] : 0;
        if($house_id){
            mysqli_query($conn, "UPDATE houses SET status='Available', is_approved=1 WHERE id=$house_id");
            notifyOwner($conn, $house_id, 'Listing approved', 'Your listing was approved and is now live on the marketplace.');
        }
        mysqli_query($conn, "UPDATE requests SET status=1 WHERE id=$id");
        header("Location: admin_manage_requests.php?msg=approved");
        break;

    case 'reject':
        $hq = mysqli_fetch_assoc(mysqli_query($conn, "SELECT house_id FROM requests WHERE id=$id"));
        $house_id = $hq ? (int)$hq['house_id'] : 0;
        if($house_id){
            mysqli_query($conn, "UPDATE houses SET status='Rejected' WHERE id=$house_id");
            notifyOwner($conn, $house_id, 'Listing rejected', 'Your listing was rejected. Please review and resubmit.');
        }
        mysqli_query($conn, "UPDATE requests SET status=2 WHERE id=$id");
        header("Location: admin_manage_requests.php?msg=rejected");
        break;

    case 'approve_house':
        mysqli_query($conn, "UPDATE houses SET status='Available', is_approved=1 WHERE id=$id");
        notifyOwner($conn, $id, 'Listing approved', 'Your listing was approved and is now live on the marketplace.');
        header("Location: admin_manage_requests.php?msg=approved");
        break;

    case 'reject_house':
        mysqli_query($conn, "UPDATE houses SET status='Rejected', is_approved=0 WHERE id=$id");
        notifyOwner($conn, $id, 'Listing rejected', 'Your listing was rejected. Please review and resubmit.');
        header("Location: admin_manage_requests.php?msg=rejected");
        break;

    default:
        header("Location: admin_manage_requests.php");
}
exit();