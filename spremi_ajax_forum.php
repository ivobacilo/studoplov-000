<?php
include 'connect.php';
$idPost = $_POST['idPost'];
$idUser = $_POST['idUser'];


$mysqli = new mysqli($servername, $username, $password, $basename);



if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}


$query = "SELECT * FROM saved WHERE idPost = ? AND idUser = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('ii', $idPost, $idUser);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {

    $deleteQuery = "DELETE FROM saved WHERE idPost = ? AND idUser = ?";
    $deleteStmt = $mysqli->prepare($deleteQuery);
    $deleteStmt->bind_param('ii', $idPost, $idUser);
    $deleteStmt->execute();
    $status = 'deleted';
    $deleteStmt->close();
} else {

    $insertQuery = "INSERT INTO saved (idPost, idUser) VALUES (?, ?)";
    $insertStmt = $mysqli->prepare($insertQuery);
    $insertStmt->bind_param('ii', $idPost, $idUser);
    $insertStmt->execute();
    $status = 'inserted';
    $insertStmt->close();
}

$stmt->close();




$mysqli->close();


echo $status;
?>