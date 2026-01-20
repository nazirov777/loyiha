<?php
$host = 'localhost';
$db_name = 'eduvision';
$username = 'root';
$password = ''; // Default OpenServer/XAMPP password is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // If database not found, try connecting without dbname to create it
    if ($e->getCode() == 1049) {
        try {
            $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $query = "CREATE DATABASE $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $pdo->exec($query);
            $pdo->exec("USE $db_name");
            // Determine if we need to run initial setup scripts here or separately
        } catch (PDOException $ex) {
            die("Connection failed: " . $ex->getMessage());
        }
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}
?>
