<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$me = (int)$_SESSION['user_id'];
$house_id = (int)($_POST['id'] ?? 0);

if($house_id <= 0){
    header("Location: manage_houses.php");
    exit();
}

csrf_validate();

// Load the house, ensuring it belongs to the current user
$row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status, is_approved FROM houses WHERE id=$house_id AND user_id=$me"));

if(!$row){
    header("Location: manage_houses.php");
    exit();
}

$current = $row['status'];

// Keep this a strict Available <-> Rented toggle. Pending/Rejected listings
// cannot be manually marked available; they must pass through admin approval.
if($current === 'Available'){
    $new_status = 'Rented';
} elseif(strcasecmp($current, 'Rented') === 0){
    $new_status = 'Available';
} else {
    header("Location: manage_houses.php");
    exit();
}

if($new_status === 'Available'){
    // Close any accepted rental agreement for this house so it can be re-listed cleanly
    $active = mysqli_query($conn, "SELECT rr.id, rr.user_id, h.kebele
                                   FROM rental_requests rr
                                   JOIN houses h ON rr.house_id = h.id
                                   WHERE rr.house_id=$house_id AND rr.status='accepted'");
    if($active){
        while($a = mysqli_fetch_assoc($active)){
            $tenant_id = (int)$a['user_id'];
            mysqli_query($conn, "UPDATE rental_requests SET status='completed' WHERE id={$a['id']}");
            $msg = mysqli_real_escape_string($conn, "The property in Kebele {$a['kebele']} has been marked available again by the owner.");
            mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, link)
                                 VALUES ($tenant_id, 'info', 'Rental ended', '$msg', 'index.php')");
        }
    }
    mysqli_query($conn, "UPDATE houses SET status='Available' WHERE id=$house_id AND user_id=$me");
} else {
    mysqli_query($conn, "UPDATE houses SET status='Rented' WHERE id=$house_id AND user_id=$me");
}

header("Location: manage_houses.php?msg=status_saved");
exit();