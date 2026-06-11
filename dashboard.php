<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['vault_file'])) {
    $file = $_FILES['vault_file'];
    $filename = basename($file['name']);
    $target_dir = __DIR__ . "/uploads/";
    
    // Create unique filename to prevent overwriting
    $unique_filename = time() . '_' . $filename;
    $target_file = $target_dir . $unique_filename;

    if ($file['error'] === 0) {
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $stmt = $pdo->prepare("INSERT INTO files (user_id, filename, filepath) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $filename, 'uploads/' . $unique_filename]);
            $message = "File uploaded successfully!";
        } else {
            $message = "Failed to save uploaded file.";
        }
    } else {
        $message = "Error uploading file.";
    }
}

// Fetch User Files
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$user_id]);
$files = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Your Vault</title></head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! (<a href="logout.php">Logout</a>)</h2>
    
    <h3>Upload a File to your Vault</h3>
    <?php if($message) echo "<p>$message</p>"; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="vault_file" required>
        <button type="submit">Upload</button>
    </form>

    <h3>Your Stored Files</h3>
    <ul>
        <?php foreach ($files as $f): ?>
            <li>
                <a href="<?php echo htmlspecialchars($f['filepath']); ?>" download>
                    <?php echo htmlspecialchars($f['filename']); ?>
                </a> 
                (Uploaded: <?php echo $f['uploaded_at']; ?>)
            </li>
        <?php endforeach; ?>
        <?php if (count($files) === 0) echo "<p>No files found in your vault yet.</p>"; ?>
    </ul>
</body>
</html>
