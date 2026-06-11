<?php
require 'db.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    if (!empty($username) && !empty($_POST['password'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $password]);
            $message = "Registration successful! <a href='index.php'>Login here</a>";
        } catch (PDOException $e) {
            $message = "Username already exists.";
        }
    } else {
        $message = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Vault - Register</title></head>
<body>
    <h2>Register Account</h2>
    <?php if($message) echo "<p>$message</p>"; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="index.php">Login here</a></p>
</body>
</html>
