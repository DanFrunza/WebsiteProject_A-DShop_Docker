<?php

$db_server = "mysql_db";
$db_user = "root";
$db_pass = "toor";
$db_name = "database1";

try {
    $pdo = new PDO("mysql:host=$db_server;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Conexiunea la baza de date a eșuat: " . $e->getMessage();
    exit();
}



?>