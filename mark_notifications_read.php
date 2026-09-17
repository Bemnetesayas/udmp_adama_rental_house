<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

if(!isset($_SESSION['user_id'])){
    http_response_code(401);
    exit('0');
}

csrf_validate();

$uid = (int)$_SESSION['user_id'];
mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_id=$uid AND is_read=0");
echo '1';