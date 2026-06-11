<?php
session_start();

// If already logged in, route them to their proper dashboard immediately
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if(isset($_SESSION["role"]) && $_SESSION["role"] === 'admin') { 
        header("location: admin.php"); 
    } else { 
        header("location: index.php"); 
    }
    exit;
}

require_once "config.php";

$username = $password = "";
$username_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["username"]))) $username_err = "Please enter username.";
    else $username = trim($_POST["username"]);
    
    if(empty(trim($_POST["password"]))) $password_err = "Please enter password.";
    else $password = trim($_POST["password"]);
    
    if(empty($username_err) && empty($password_err)){
        $sql = "SELECT id, username, password, role FROM users WHERE username = :username";
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $param_username = $username;
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    if($row = $stmt->fetch()){
                        if(password_verify($password, $row["password"])){
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $row["id"];
                            $_SESSION["username"] = $row["username"]; 
                            $_SESSION["role"] = $row["role"]; 
                            
                            // THE REDIRECT ENGINE: Admin goes to admin panel, user goes to gallery
                            if($_SESSION["role"] === 'admin'){
                                header("location: admin.php");
                            } else {
                                header("location: index.php");
                            }
                            exit;
                        } else $login_err = "Invalid username or password.";
                    }
                } else $login_err = "Invalid username or password.";
            }
            unset($stmt);
        }
    }
    unset($pdo);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .wrapper { width: 100%; max-width: 400px; padding: 40px; background: white; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        h2 { text-align: center; font-size: 24px; margin-bottom: 25px; color: #0f172a; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px; color: #334155; }
        input[type="text"], input[type="password"] { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; outline: none; width: 100%; }
        .btn { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: 600; margin-top: 10px; }
        .btn:hover { background: #1d4ed8; }
        .error-msg { color: #dc2626; font-size: 13px; margin-top: 6px; display: block; }
        .alert { background: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Sign In</h2>
        <?php if(!empty($login_err)) echo '<div class="alert">'.$login_err.'</div>'; ?>
        <form action="login.php" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>">
                <span class="error-msg"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password">
                <span class="error-msg"><?php echo $password_err; ?></span>
            </div>
            <button type="submit" class="btn">Sign In</button>
            <p style="margin-top:20px; text-align:center; font-size:14px; color:#64748b;">Need an account? <a href="register.php" style="color:#2563eb; text-decoration:none; font-weight:500;">Sign Up</a></p>
        </form>
    </div>
</body>
</html>