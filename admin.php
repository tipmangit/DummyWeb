<?php
session_start();
require_once "config.php";

// Kicks out non-admins instantly
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin'){
    header("location: index.php");
    exit;
}

$msg = "";

// Handle Admin File Force Deletion
if(isset($_GET["delete_file"])){
    $file_id = $_GET["delete_file"];
    $sql = "SELECT stored_name FROM files WHERE id = :id";
    if($stmt = $pdo->prepare($sql)){
        $stmt->execute([':id' => $file_id]);
        if($file = $stmt->fetch()){
            $filepath = "images/" . $file["stored_name"];
            if(file_exists($filepath)){ unlink($filepath); }
            $del_sql = "DELETE FROM files WHERE id = :id";
            $pdo->prepare($del_sql)->execute([':id' => $file_id]);
            $msg = "File deleted successfully.";
        }
    }
}

// Handle Admin User Deletion (Ban tool)
if(isset($_GET["delete_user"])){
    $user_id = $_GET["delete_user"];
    if($user_id != $_SESSION["id"]){
        // Delete their physical photos from the server
        $sql = "SELECT stored_name FROM files WHERE user_id = :uid";
        if($stmt = $pdo->prepare($sql)){
            $stmt->execute([':uid' => $user_id]);
            $files = $stmt->fetchAll();
            foreach($files as $file){
                $filepath = "images/" . $file["stored_name"];
                if(file_exists($filepath)){ unlink($filepath); }
            }
        }
        // Delete the user record (ON DELETE CASCADE handles database file rows)
        $del_sql = "DELETE FROM users WHERE id = :id";
        $pdo->prepare($del_sql)->execute([':id' => $user_id]);
        $msg = "User profile and all associated data cleared.";
    } else {
        $msg = "Action denied: You cannot delete your own admin account.";
    }
}

$all_users = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
$all_files = $pdo->query("SELECT f.*, u.username FROM files f JOIN users u ON f.user_id = u.id ORDER BY f.uploaded_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - SnapVault</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #0f172a; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #1e293b; color: white; padding: 20px 30px; border-radius: 12px; margin-bottom: 30px; }
        .header a { color: white; text-decoration: none; background: #334155; padding: 8px 16px; border-radius: 6px; font-size: 14px; }
        .header a:hover { background: #475569; }
        .card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid #e2e8f0; }
        h2 { margin-bottom: 20px; font-size: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f8fafc; color: #475569; font-weight: 600; }
        .badge { background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-admin { background: #fef08a; color: #854d0e; }
        .btn-del { color: #ef4444; text-decoration: none; font-weight: 600; font-size: 13px; }
        .btn-view { color: #2563eb; text-decoration: none; font-weight: 600; font-size: 13px; margin-right: 10px; }
        .alert { background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ Admin Dashboard</h1>
            <div>
                <a href="index.php">Public Gallery</a>
                <a href="logout.php" style="background: #ef4444; margin-left: 10px;">Sign Out</a>
            </div>
        </div>

        <?php if(!empty($msg)) echo "<div class='alert'>$msg</div>"; ?>

        <div class="card">
            <h2>Registered Members</h2>
            <table>
                <thead><tr><th>ID</th><th>Username</th><th>User Role</th><th>Created</th><th>Control Action</th></tr></thead>
                <tbody>
                    <?php foreach($all_users as $user): ?>
                    <tr>
                        <td>#<?php echo $user['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                        <td><span class="badge <?php echo $user['role'] == 'admin' ? 'badge-admin' : ''; ?>"><?php echo strtoupper($user['role']); ?></span></td>
                        <td><?php echo $user['created_at']; ?></td>
                        <td>
                            <?php if($user['id'] != $_SESSION['id']): ?>
                                <a href="admin.php?delete_user=<?php echo $user['id']; ?>" onclick="return confirm('Permanently ban user and wipe all their files?');" class="btn-del">Delete & Ban</a>
                            <?php else: ?>
                                <span style="color:#94a3b8; font-size: 13px;">(Active Admin Session)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Total Files Hosted</h2>
            <table>
                <thead><tr><th>File ID</th><th>Owner</th><th>Original File Name</th><th>Size (Bytes)</th><th>Uploaded At</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach($all_files as $file): ?>
                    <tr>
                        <td>#<?php echo $file['id']; ?></td>
                        <td><?php echo htmlspecialchars($file['username']); ?></td>
                        <td><?php echo htmlspecialchars($file['original_name']); ?></td>
                        <td><?php echo number_format($file['file_size']); ?></td>
                        <td><?php echo $file['uploaded_at']; ?></td>
                        <td>
                            <a href="images/<?php echo htmlspecialchars($file["stored_name"]); ?>" target="_blank" class="btn-view">View Raw</a>
                            <a href="admin.php?delete_file=<?php echo $file['id']; ?>" onclick="return confirm('Force removal of this photo?');" class="btn-del">Force Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>