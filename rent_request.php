<?php
include('db.php');
include('session_config.php');
session_start();

// Tenant "rent request" action — requires login.
if(!isset($_SESSION['user_id'])){
    header("Location: login.php?redirect=" . urlencode('rent_request.php?house=' . (int)($_GET['house'] ?? 0)));
    exit();
}

$status = null; // ['type','message','title','redirect']
$house_id = (int)($_GET['house'] ?? 0);

if($house_id <= 0){
    $status = ['type' => 'error', 'message' => 'No property was specified.', 'title' => 'Invalid request', 'redirect' => 'index.php'];
} else {
    // Verify house exists, is approved and available
    $hq = mysqli_query($conn, "SELECT * FROM houses WHERE id=$house_id AND status='Available' AND is_approved=1");
    $house = $hq ? mysqli_fetch_assoc($hq) : null;

    if(!$house){
        $status = ['type' => 'error', 'message' => 'This property is not currently available for rent.', 'title' => 'Not available', 'redirect' => 'index.php'];
    } else {
        $uid = (int)$_SESSION['user_id'];

        if($house['user_id'] == $uid){
            $status = ['type' => 'error', 'message' => 'You cannot rent your own property.', 'title' => 'Your own listing', 'redirect' => 'index.php'];
        } else {
            // Prevent duplicate pending rental requests for the same user + house
            $dup = mysqli_query($conn, "SELECT id FROM rental_requests WHERE user_id=$uid AND house_id=$house_id AND status='pending'");
            if($dup && mysqli_num_rows($dup) > 0){
                $status = ['type' => 'info', 'message' => 'You already requested this property. The owner has been notified.', 'title' => 'Already requested', 'redirect' => 'index.php'];
            } else {
                $ins = mysqli_query($conn, "INSERT INTO rental_requests (user_id, house_id, status, created_at) VALUES ($uid, $house_id, 'pending', NOW())");
                if($ins){
                    // Notify the property owner
                    $requester = mysqli_real_escape_string($conn, $_SESSION['user_name'] ?? 'A user');
                    $notifMsg = $requester . ' requested to rent your property in Kebele ' . $house['kebele'] . '.';
                    mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, link) VALUES ('{$house['user_id']}', 'rent_request', 'New rental request', '" . mysqli_real_escape_string($conn, $notifMsg) . "', 'manage_houses.php')");
                    $status = ['type' => 'success', 'message' => 'Your rental request was sent to the property owner. They will contact you soon.', 'title' => 'Request sent', 'redirect' => 'index.php'];
                } else {
                    $status = ['type' => 'error', 'message' => 'Something went wrong. Please try again.', 'title' => 'Error', 'redirect' => 'index.php'];
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body style="margin:0;background:#f8fafc;font-family:'Inter',sans-serif">
    <?php include(__DIR__ . '/popup.php'); ?>
    <script>
        window.addEventListener('DOMContentLoaded', function(){
            showToast(<?php echo json_encode($status['message']); ?>, <?php echo json_encode($status['type']); ?>, <?php echo json_encode($status['title']); ?>);
            setTimeout(function(){ window.location = <?php echo json_encode($status['redirect']); ?>; }, 2000);
        });
    </script>
</body>
</html>