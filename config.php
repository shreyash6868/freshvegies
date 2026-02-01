<?php
$host = 'localhost';
$user = 'root'; // Change to your MySQL user
$pass = ''; // Change to your password
$db = 'farmer_market';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>