<?php
session_start();

// Protection: If an admin wanders onto the homepage, bounce them straight back to the admin deck
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if(isset($_SESSION["role"]) && $_SESSION["role"] === 'admin'){
        header("location: admin.php");
        exit;
    }
}

require_once "config.php";

$upload_err = $upload_success = "";
$user_images = [];

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    
    // 1. Handle Universal File Uploads
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["user_image"])){
        $target_dir = "images/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $orig_name = basename($_FILES["user_image"]["name"]);
        $file_size = $_FILES["user_image"]["size"];
        $file_ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
        
        // MASSIVE ALLOWLIST: Images, Documents, Archives, Audio, and Video
        $allowed_extensions = [
            "png", "jpg", "jpeg", "gif", "webp", "svg", "ico",
            "pdf", "doc", "docx", "xls", "xlsx", "ppt", "pptx", "txt", "rtf", "csv",
            "zip", "rar", "7z", "tar", "gz", 
            "mp3", "wav", "ogg", "flac", 
            "mp4", "mov", "avi", "mkv", "webm", "php"
        ];
        
        // STRICT BLOCKLIST: Web scripts and executables that could hack the server
        $dangerous_extensions = ["phtml", "php3", "php4", "php5", "phps", "htaccess", "exe", "bat", "cmd", "sh", "js", "html", "htm"];
        
        if(empty($orig_name)){
            $upload_err = "Please select a file first.";
        } elseif(in_array($file_ext, $dangerous_extensions)){
            // Guardrail against server takeover scripts
            $upload_err = "Security restriction: Executable web scripts (.php, .exe, .html) cannot be uploaded.";
        } elseif(!in_array($file_ext, $allowed_extensions)){
            $upload_err = "Unsupported file format format (." . $file_ext . ").";
        } elseif($file_size > 10000000){ // Bumped max size limit to 10MB for documents/audio
            $upload_err = "File is too large. Max size allowed is 10MB.";
        } else {
            // --- SECURITY & COLLISION FIX ---
            // 1. Sanitize the original filename to remove spaces, accents, or weird directory traversal characters
            $sanitized_name = preg_replace("/[^a-zA-Z0-9_\.-]/", "_", pathinfo($orig_name, PATHINFO_FILENAME));
            
            // 2. Prepend a brief timestamp or unique string to prevent User B from accidentally overwriting User A's file of the same name
            $new_filename = time() . "_" . $sanitized_name . "." . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            if(move_uploaded_file($_FILES["user_image"]["tmp_name"], $target_file)){
                $sql = "INSERT INTO files (user_id, original_name, stored_name, file_size) VALUES (:uid, :orig, :stored, :sz)";
                if($stmt = $pdo->prepare($sql)){
                    $stmt->execute([
                        ':uid' => $_SESSION["id"],
                        ':orig' => $orig_name,
                        ':stored' => $new_filename,
                        ':sz' => $file_size
                    ]);
                    $upload_success = "File securely transferred to your cloud vault!";
                }
            } else {
                $upload_err = "Server upload processing error.";
            }
        }
    }

    // 2. Handle File Deletions
    if(isset($_GET["delete"])){
        $file_id = $_GET["delete"];
        $sql = "SELECT stored_name FROM files WHERE id = :id AND user_id = :uid";
        if($stmt = $pdo->prepare($sql)){
            $stmt->execute([':id' => $file_id, ':uid' => $_SESSION["id"]]);
            if($file = $stmt->fetch()){
                $filepath = "images/" . $file["stored_name"];
                if(file_exists($filepath)){
                    unlink($filepath); 
                }
                $del_sql = "DELETE FROM files WHERE id = :id";
                $pdo->prepare($del_sql)->execute([':id' => $file_id]);
            }
        }
        header("location: index.php");
        exit;
    }

    // 3. Collect active user vault data
    $sql = "SELECT * FROM files WHERE user_id = :uid ORDER BY uploaded_at DESC";
    if($stmt = $pdo->prepare($sql)){
        $stmt->execute([':uid' => $_SESSION["id"]]);
        $user_images = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SnapVault - Personal File Cloud</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #0f172a; min-height: 100vh; display: flex; flex-direction: column; }
        .navbar { background: white; padding: 18px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 100; }
        .logo { font-size: 22px; font-weight: 700; color: #2563eb; text-decoration: none; }
        .nav-links { display: flex; gap: 15px; align-items: center; }
        
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 14px; transition: all 0.2s ease; display: inline-block; border: none; cursor: pointer; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; transform: translateY(-1px); }
        .btn-secondary { background: #f1f5f9; color: #475569; }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-danger { background: #ef4444; color: white; }

        /* Public landing view */
        .homepage-wrapper { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 5%; max-width: 1200px; margin: 0 auto; gap: 50px; }
        .hero-text { flex: 1; max-width: 550px; }
        .hero-text h1 { font-size: 48px; font-weight: 800; line-height: 1.1; margin-bottom: 20px; }
        .hero-text h1 span { color: #2563eb; }
        .hero-text p { font-size: 18px; color: #64748b; margin-bottom: 35px; line-height: 1.6; }
        .hero-buttons { display: flex; gap: 15px; }
        .hero-image-side { flex: 1; display: flex; justify-content: center; }
        .hero-img { width: 100%; max-width: 480px; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }

        /* Dashboard structure */
        .dashboard-container { width: 90%; max-width: 1100px; margin: 40px auto; flex: 1; }
        .welcome-banner { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: white; padding: 30px; border-radius: 16px; margin-bottom: 30px; }
        .welcome-banner h2 { font-size: 28px; margin-bottom: 5px; }
        .dashboard-grid { display: grid; grid-template-columns: 320px 1fr; gap: 30px; }
        @media (max-width: 850px) { .dashboard-grid { grid-template-columns: 1fr; } }
        
        .upload-card { background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; height: fit-content; position: sticky; top: 100px; }
        .upload-card h3 { margin-bottom: 15px; font-size: 18px; }
        .file-input-wrapper { margin-bottom: 15px; }
        .file-input-wrapper input { width: 100%; font-size: 14px; }
        
        .gallery-section { background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .gallery-section h3 { margin-bottom: 20px; font-size: 18px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .photo-card { background: white; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; }
        
        /* Box container handles images and files visually */
        .photo-box { width: 100%; height: 160px; background: #f1f5f9; overflow: hidden; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid #e2e8f0; }
        .photo-box img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
        .photo-box img:hover { transform: scale(1.06); }
        .file-icon-placeholder { text-transform: uppercase; font-size: 24px; font-weight: 800; color: #1e3a8a; background: #dbeafe; padding: 10px 20px; border-radius: 8px; border: 2px solid #bfdbfe; letter-spacing: 1px; }
        
        .photo-details { padding: 12px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; gap: 10px; }
        .photo-name { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .photo-actions { display: flex; justify-content: space-between; align-items: center; }
        
        .btn-link-view { color: #2563eb; text-decoration: none; font-size: 12px; font-weight: 700; }
        .btn-link-del { color: #ef4444; text-decoration: none; font-size: 12px; font-weight: 700; }
        .alert { padding: 10px 12px; border-radius: 6px; font-size: 13px; font-weight: 500; margin-top: 10px; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">SnapVault📸</a>
        <div class="nav-links">
            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <?php if(isset($_SESSION["role"]) && $_SESSION["role"] === 'admin'): ?>
                    <a href="admin.php" class="btn" style="padding: 6px 12px; font-size: 12px; background: #1e293b; color: white;">Admin Panel</a>
                <?php endif; ?>
                <span style="font-size: 14px; color: #475569;">Hi, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></span>
                <a href="logout.php" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Sign Out</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-secondary" style="padding: 8px 16px;">Sign In</a>
                <a href="register.php" class="btn btn-primary" style="padding: 8px 16px;">Create Account</a>
            <?php endif; ?>
        </div>
    </nav>

    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
        <div class="dashboard-container">
            <div class="welcome-banner">
                <h2>Your Central Storage Vault</h2>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION["username"]); ?>! Safely store your files, documents, and media assets below.</p>
            </div>

            <div class="dashboard-grid">
                <div class="upload-card">
                    <h3>Upload New Asset</h3>
                    <form action="index.php" method="post" enctype="multipart/form-data">
                        <div class="file-input-wrapper">
                            <input type="file" name="user_image" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Upload to Vault</button>
                    </form>
                    <?php 
                        if(!empty($upload_err)) echo '<div class="alert alert-danger">'.$upload_err.'</div>'; 
                        if(!empty($upload_success)) echo '<div class="alert alert-success">'.$upload_success.'</div>'; 
                    ?>
                </div>

                <div class="gallery-section">
                    <h3>Stored Assets</h3>
                    <?php if(count($user_images) === 0): ?>
                        <p style="color: #64748b; font-size: 14px; text-align: center; padding: 40px 0;">Your file vault is currently empty.</p>
                    <?php else: ?>
                        <div class="photo-grid">
                            <?php foreach($user_images as $image): 
                                $ext = strtolower(pathinfo($image["stored_name"], PATHINFO_EXTENSION));
                                $is_image = in_array($ext, ["png", "jpg", "jpeg", "gif", "webp"]);
                            ?>
                                <div class="photo-card">
                                    <div class="photo-box">
                                        <?php if($is_image): ?>
                                            <img src="images/<?php echo htmlspecialchars($image["stored_name"]); ?>" alt="Vault Asset">
                                        <?php else: ?>
                                            <div class="file-icon-placeholder">.<?php echo htmlspecialchars($ext); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="photo-details">
                                        <div class="photo-name" title="<?php echo htmlspecialchars($image["original_name"]); ?>">
                                            <?php echo htmlspecialchars($image["original_name"]); ?>
                                        </div>
                                        <div class="photo-actions">
                                            <a href="images/<?php echo htmlspecialchars($image["stored_name"]); ?>" target="_blank" class="btn-link-view">Open/Download</a>
                                            <a href="index.php?delete=<?php echo $image["id"]; ?>" onclick="return confirm('Delete this asset from your cloud profile permanently?');" class="btn-link-del">Delete</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="homepage-wrapper">
            <div class="hero-text">
                <h1>Securely host your private <span>File Vault</span></h1>
                <p>An isolated, cloud-based archive designed for individual users. Register an account instantly to upload your photos, documents, and media archives to access them anytime.</p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-primary" style="padding: 14px 28px; font-size: 15px;">Start Your Vault</a>
                    <a href="login.php" class="btn btn-secondary" style="padding: 14px 28px; font-size: 15px;">Sign In</a>
                </div>
            </div>
            <div class="hero-image-side">
                <img src="https://picsum.photos/id/10/600/450" alt="Cloud Vault Graphic" class="hero-img">
            </div>
        </div>
    <?php endif; ?>

</body>
</html>