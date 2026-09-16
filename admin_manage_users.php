<?php
include('session_config.php');
session_start();
include('db.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1){
    header("Location: login.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$my_rank = $_SESSION['is_admin']; 

$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';

if($role_filter == 'staff') {
    $page_title = "Internal Staff";
    $query = "SELECT * FROM users WHERE is_admin >= 1 ORDER BY is_admin DESC";
} elseif($role_filter == 'landlords') {
    $page_title = "Landlords";
    $query = "SELECT * FROM users WHERE is_admin = 0 ORDER BY id DESC";
} else {
    $page_title = "All Users";
    $query = "SELECT * FROM users ORDER BY is_admin DESC, id DESC";
}

$result = mysqli_query($conn, $query);

$flash = [
    'deleted'         => ['User deleted successfully.', 'green'],
    'no_self_delete'  => ['You cannot delete your own account.', 'red'],
    'no_super_delete' => ['You cannot delete a super admin account.', 'red'],
    'invited'         => ['Admin invite generated.', 'green'],
    'revoked'         => ['Admin role revoked. User is now a landlord.', 'green'],
    'no_self_revoke'  => ['You cannot revoke your own admin role.', 'red'],
    'no_revoke_super' => ['You cannot revoke another super admin.', 'red'],
];
$msg = isset($_GET['msg'], $flash[$_GET['msg']]) ? $flash[$_GET['msg']] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - AdamaRent Admin</title>
    <?php include(__DIR__ . '/header.php'); ?>
</head>
<body>
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <div class="icon"><i class="fas fa-users"></i></div>
            <div>
                <h1><?php echo $page_title; ?></h1>
                <div class="page-sub">Manage user accounts and roles</div>
            </div>
        </div>
        <div class="filter-bar">
            <div class="filter-tabs">
                <a href="admin_manage_users.php" class="filter-tab <?= ($role_filter=='all')?'active':'' ?>">All</a>
                <a href="admin_manage_users.php?role=staff" class="filter-tab <?= ($role_filter=='staff')?'active':'' ?>">Staff</a>
                <a href="admin_manage_users.php?role=landlords" class="filter-tab <?= ($role_filter=='landlords')?'active':'' ?>">Landlords</a>
            </div>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="flash flash-<?php echo $msg[1]; ?>">
            <i class="fas fa-<?php echo $msg[1] === 'green' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $msg[0]; ?>
        </div>
    <?php endif; ?>
    
    <div class="data-card">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <?php if($my_rank == 2): ?><th>Permissions</th><?php endif; ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): 
                    $target_id = $row['id'];
                    $target_rank = $row['is_admin'];
                    $initial = strtoupper(substr(trim($row['full_name'] ?? 'U'), 0, 1));
                ?>
                <tr>
                    <td>
                        <div class="cell-user">
                            <div class="user-avatar-ms"><?php echo $initial; ?></div>
                            <div>
                                <strong><?php echo htmlspecialchars($row['full_name'] ?? 'User'); ?></strong>
                                <div class="user-email"><?php echo htmlspecialchars($row['email']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php 
                        $role_class = 'role-landlord';
                        $role_text = 'LANDLORD';
                        if($target_rank == 2) { $role_class = 'role-super'; $role_text = 'SUPER ADMIN'; }
                        elseif($target_rank == 1) { $role_class = 'role-admin'; $role_text = 'ADMIN'; }
                        ?>
                        <span class="badge-role <?php echo $role_class; ?>"><?php echo $role_text; ?></span>
                    </td>
                    
                    <?php if($my_rank == 2): ?>
                    <td>
                        <?php if($target_id != $my_id && $target_rank == 0): ?>
                            <a href="admin_invite.php" class="btn btn-sm btn-icon-promote" title="Generate an invite key to make this user an admin"><i class="fas fa-user-shield"></i> Invite as Admin</a>
                        <?php elseif($target_id != $my_id && $target_rank == 1): ?>
                            <form action="revoke_admin.php" method="POST" style="display:inline" id="revoke-user-<?php echo $target_id; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $target_id; ?>">
                                <button type="button" class="btn btn-sm btn-icon-revoke" title="Revoke admin role" onclick="confirmRevokeAdmin(<?php echo $target_id; ?>)"><i class="fas fa-user-slash"></i> Revoke Admin</button>
                            </form>
                        <?php elseif($target_id != $my_id): ?>
                            <span style="font-size:12px;color:#94a3b8">Already admin</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>

                    <td>
                        <div style="display:flex;gap:6px">
                        <a href="edit_user.php?id=<?php echo $target_id; ?>" class="btn-icon btn-icon-edit" title="Edit"><i class="fas fa-edit"></i></a>
                        <?php if($target_id != $my_id): ?>
                        <form action="delete_user.php" method="POST" style="display:inline" id="del-user-<?php echo $target_id; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $target_id; ?>">
                            <button type="button" class="btn-icon btn-icon-del" title="Delete" onclick="confirmUserDelete(<?php echo $target_id; ?>)"><i class="fas fa-trash"></i></button>
                        </form>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
