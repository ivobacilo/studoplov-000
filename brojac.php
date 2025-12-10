<?php

include 'connect.php';
$dbc = mysqli_connect($servername, $username, $password, $basename) or
    die('Error connecting to MySQL server.' . mysqli_connect_error());
$query = "SELECT * FROM projects WHERE idAuthor = ? AND DATEDIFF(EXPIRY,NOW()) <= 5";
$stmt = mysqli_stmt_init($dbc);
$brojac = 0;
if (mysqli_stmt_prepare($stmt, $query)) {
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['idAuthor']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {

            $brojac++;
        }
    }
}
$query = "SELECT * FROM market WHERE idAuthor = ? AND DATEDIFF(EXPIRY,NOW()) <= 5";
$stmt = mysqli_stmt_init($dbc);
if (mysqli_stmt_prepare($stmt, $query)) {
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['idAuthor']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {

            $brojac++;
        }
    }
}



