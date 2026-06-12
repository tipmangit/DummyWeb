<?php
// Securely grab the connection string injected by Render
$database_url = getenv('DATABASE_URL');

if (!$database_url) {
    die("Database configuration environment variable is missing.");
}

try {
    // Parse the PostgreSQL URL into usable connection components
    $db_parts = parse_url($database_url);

    $host = $db_parts['host'];
    $port = $db_parts['port'] ?? '5432';
    $user = $db_parts['user'];
    $pass = $db_parts['pass'];
    $dbname = ltrim($db_parts['path'], '/');

    // Establish standard secure PDO connection to PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create Users Table (PostgreSQL Syntax uses SERIAL instead of AUTOINCREMENT)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL
    )");

    // Create Files Table 
    $pdo->exec("CREATE TABLE IF NOT EXISTS files (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL,
        filename VARCHAR(255) NOT NULL,
        filepath VARCHAR(255) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

} catch (PDOException $e) {
    // Hide raw database details from rendering to the end-user for security
    die("A secure database connection error occurred.");
}
?>
