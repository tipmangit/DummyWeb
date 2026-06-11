<?php
require_once "config.php";

$username = $password = "";
$username_err = $password_err = $success_msg = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else {
        $sql = "SELECT id FROM users WHERE username = :username";
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $param_username = trim($_POST["username"]);
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            }
            unset($stmt);
        }
    }
    
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    if(empty($username_err) && empty($password_err)){
        $sql = "INSERT INTO users (username, password) VALUES (:username, :password)";
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);
            
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            
            if($stmt->execute()){
                $success_msg = "Registration successful! You can now log in.";
            } else{
                $username_err = "Something went wrong. Please try again.";
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
    <title>Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .wrapper { width: 100%; max-width: 400px; padding: 40px; background: white; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        h2 { text-align: center; font-size: 24px; margin-bottom: 8px; }
        p.subtitle { text-align: center; font-size: 14px; color: #6b7280; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; outline: none; }
        input[type="text"]:focus, input[type="password"]:focus { border-color: #2563eb; }
        .btn { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; }
        .error-msg { color: #dc2626; font-size: 13px; margin-top: 6px; display: block; }
        .success-alert { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Create Account</h2>
        <p class="subtitle">Join the gallery network.</p>

        <?php if(!empty($success_msg)) echo '<div class="success-alert">'.$success_msg.' <a href="login.php">Login here</a></div>'; ?>

        <form action="register.php" method="post">
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
            <button type="submit" class="btn">Sign Up</button>
            <p style="margin-top:20px; text-align:center; font-size:14px; color:#6b7280;">Already have an account? <a href="login.php" style="color:#2563eb;">Sign In</a></p>
        </form>
    </div>
</body>
</html>