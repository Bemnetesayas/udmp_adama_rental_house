<?php
include('session_config.php');
session_start();
include('db.php');

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';
$me = (int)$_SESSION['user_id'];

if($id <= 0 || !in_array($action, ['accept', 'reject'], true)){
    header("Location: manage_houses.php");
    exit();
}

// Fetch the request joined with its house; ensure the current user owns the house
$rr = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT rr.*, h.kebele, h.user_id AS house_owner
    FROM rental_requests rr
    JOIN houses h ON rr.house_id = h.id
    WHERE rr.id=$id AND h.user_id=$me
"));

if(!$rr || $rr['status'] !== 'pending'){
    header("Location: manage_houses.php");
    exit();
}

$kebele = mysqli_real_escape_string($conn, $rr['kebele']);
$tenant_id = (int)$rr['user_id'];

if($action === 'accept'){
    mysqli_query($conn, "UPDATE rental_requests SET status='accepted' WHERE id=$id");
    mysqli_query($conn, "UPDATE houses SET status='Rented' WHERE id={$rr['house_id']}");
    mysqli_query($conn, "UPDATE rental_requests SET status='rejected' WHERE house_id={$rr['house_id']} AND id<>$id AND status='pending'");
    $msg = mysqli_real_escape_string($conn, "Your request to rent the property in Kebele $kebele was accepted. The owner will contact you soon.");
    mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, link) VALUES ($tenant_id, 'rent_request', 'Rental request accepted', '$msg', 'index.php')");
    header("Location: manage_houses.php?msg=accepted");
} else {
    mysqli_query($conn, "UPDATE rental_requests SET status='rejected' WHERE id=$id");
    $msg = mysqli_real_escape_string($conn, "Your request to rent the property in Kebele $kebele was declined by the owner.");
    mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, link) VALUES ($tenant_id, 'rejection', 'Rental request declined', '$msg', 'index.php')");
    header("Location: manage_houses.php?msg=rejected");
}
exit();