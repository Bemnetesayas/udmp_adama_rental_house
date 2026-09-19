<?php
include('includes/db.php');
include('includes/session_config.php');
session_start();
include('includes/security.php');

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

if(!isset($_POST['delete_btn'])){
    header("Location: manage_houses.php");
    exit();
}
csrf_validate();

$id = (int)$_POST['id'];
$input_key = isset($_POST['key']) ? mysqli_real_escape_string($conn, $_POST['key']) : null;
$current_user = $_SESSION['user_id'];

$status = ['type' => 'error', 'message' => 'Something went wrong. Please try again.', 'title' => 'Error', 'redirect' => 'manage_houses.php'];

$query = mysqli_query($conn, "SELECT image, delete_key FROM houses WHERE id = $id AND user_id = $current_user");

if($query && mysqli_num_rows($query) > 0){
    $data = mysqli_fetch_assoc($query);

    $allow_delete = false;
    if($input_key !== null && $input_key !== ''){
        if($data['delete_key'] === $input_key){
            $allow_delete = true;
        }
    } else {
        $allow_delete = true;
    }

    if($allow_delete){
        if(!empty($data['image']) && file_exists("uploads/" . $data['image'])){
            unlink("uploads/" . $data['image']);
        }
        $imgs = mysqli_query($conn, "SELECT filename FROM house_images WHERE house_id = $id");
        if($imgs && mysqli_num_rows($imgs) > 0){
            while($im = mysqli_fetch_assoc($imgs)){
                if(!empty($im['filename']) && file_exists("uploads/" . $im['filename'])){
                    @unlink("uploads/" . $im['filename']);
                }
            }
        }
        mysqli_query($conn, "DELETE FROM requests WHERE house_id = $id");
        mysqli_query($conn, "DELETE FROM rental_requests WHERE house_id = $id");
        mysqli_query($conn, "DELETE FROM house_images WHERE house_id = $id");
        mysqli_query($conn, "DELETE FROM houses WHERE id = $id");
        $status = ['type' => 'success', 'message' => 'Post removed successfully.', 'title' => 'Listing deleted', 'redirect' => 'manage_houses.php'];
    } else {
        $status = ['type' => 'error', 'message' => 'Incorrect secret key.', 'title' => 'Action blocked', 'redirect' => 'manage_houses.php'];
    }
} else {
    $status = ['type' => 'error', 'message' => 'Unauthorized! You can only delete your own posts.', 'title' => 'Restricted', 'redirect' => 'manage_houses.php'];
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
    <?php include(__DIR__ . '/includes/popup.php'); ?>
    <script>
        window.addEventListener('DOMContentLoaded', function(){
            showToast(<?php echo json_encode($status['message']); ?>, <?php echo json_encode($status['type']); ?>, <?php echo json_encode($status['title']); ?>);
            setTimeout(function(){ window.location = <?php echo json_encode($status['redirect']); ?>; }, 2000);
        });
    </script>
</body>
</html>