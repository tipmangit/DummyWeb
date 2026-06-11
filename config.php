<?php
// Database configuration definitions
define('DB_SERVER', 'sql205.infinityfree.com'); 
define('DB_USERNAME', 'if0_42146942');         
define('DB_PASSWORD', '814k9Z5SgXC');        
define('DB_NAME', 'if0_42146942_data_base');      

try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERROR: Connection failure encountered. " . $e->getMessage());
}
?>
