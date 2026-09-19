<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1){
    die('Access Denied');
}

$my_level = (int)($_SESSION['is_admin'] ?? 0);
$my_id = (int)($_SESSION['user_id'] ?? 0);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    csrf_validate();
    $target_id = (int)$_POST['user_id'];
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));

    $res = mysqli_query($conn, "SELECT is_admin FROM users WHERE id=$target_id");
    if(!$res || mysqli_num_rows($res) === 0){ die('User not found'); }
    $target = mysqli_fetch_assoc($res);
    $target_level = (int)$target['is_admin'];

    if($my_level === 1 && $target_level >= 1 && $target_id !== $my_id){
        die('Only Super Admins can edit other admin accounts.');
    }

    mysqli_query($conn, "UPDATE users SET full_name='$full_name', email='$email' WHERE id=$target_id");
    header('Location: admin_manage_users.php?updated=1');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$res = mysqli_query($conn, "SELECT id, full_name, email, is_admin FROM users WHERE id=$id");
if(!$res || mysqli_num_rows($res) === 0){ die('User not found'); }
$user = mysqli_fetch_assoc($res);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - AdamaRent Admin</title>
    <?php include(__DIR__ . '/includes/header.php'); ?>
</head>
<body>
    <div class="content">
        <div class="page-header">
            <div class="page-title">
                <div class="icon"><i class="fas fa-user-edit"></i></div>
                <div>
                    <h1>Edit User</h1>
                    <div class="page-sub">Update account details</div>
                </div>
            </div>
        </div>
        <div class="form-card">
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="btn-row">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                    <a href="admin_manage_users.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
