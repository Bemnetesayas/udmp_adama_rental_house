<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') || isset($_POST['ajax']);
$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? $isAjax ? ($_POST['action'] ?? '') : ($_GET['action'] ?? '');
$me = (int)$_SESSION['user_id'];

if($id <= 0 || !in_array($action, ['accept', 'reject', 'cancel'], true)){
    header("Location: manage_houses.php");
    exit();
}

csrf_validate();

// Tenant withdraws their own pending rental request
if($action === 'cancel'){
    $rr = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT rr.*, h.user_id AS house_owner, h.kebele
        FROM rental_requests rr
        JOIN houses h ON rr.house_id = h.id
        WHERE rr.id=$id AND rr.user_id=$me AND rr.status='pending'
    "));
    if(!$rr){
        $resp = ['type' => 'info', 'message' => 'This rental request is no longer pending.', 'title' => 'No action', 'redirect' => 'index.php'];
    } else {
        mysqli_query($conn, "UPDATE rental_requests SET status='cancelled' WHERE id=$id");
        $msg = mysqli_real_escape_string($conn, (($_SESSION['user_name'] ?? 'A tenant') . ' cancelled their request for the property in Kebele ' . $rr['kebele'] . '.'));
        mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, link) VALUES ({$rr['house_owner']}, 'info', 'Rental request cancelled', '$msg', 'manage_houses.php')");
        $resp = ['type' => 'success', 'message' => 'Your rental request was cancelled.', 'title' => 'Request cancelled', 'redirect' => 'house_detail.php?house=' . (int)$rr['house_id']];
    }
    if($isAjax){
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resp);
        exit();
    }
    header("Location: " . $resp['redirect']);
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