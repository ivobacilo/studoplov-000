<?php
include 'connect.php';


$conn = new mysqli($servername, $username, $password, $basename);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$sql = "DELETE FROM users WHERE request_date < DATE_SUB(NOW(), INTERVAL 3 DAY) AND verification_code != 0";
$stmt = $conn->prepare($sql);


if ($stmt === false) {
    die("Error in preparing statement: " . $conn->error);
}

$stmt->execute();

$sql = "DELETE FROM market WHERE DATEDIFF(NOW(), expiry) >= 5";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error in preparing statement: " . $conn->error);
}


$stmt->execute();

$stmt->close();
$conn->close();

?>