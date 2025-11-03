<?php
// db.php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'atendances_db';
$DB_USER = 'root';
$DB_PASS = 'Cris@027'; // set your MySQL password

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    die("DB Connection failed: " . $e->getMessage());
}
session_start();
