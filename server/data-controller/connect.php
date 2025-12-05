<?php

$servername = "localhost";
$username = "is207";
$password = "admin";
$dbname = "db_ie104";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// echo "Connected successfully";