<?php
$servername = "localhost";
$username = "root";
$password = "0000";
$basename = "id21764729_studoplov";
$dbc = new mysqli($servername, $username, $password, $basename);
$search = $_POST['search'];
$sql = "SELECT * FROM projects WHERE about LIKE '%$search%' OR roles LIKE '%$search%'";
$query = mysqli_query($dbc, $sql);
$data = '';
$projectCounter = 0;
while ($row = mysqli_fetch_assoc($query)) {
    $today = date("Y-m-d");
    if ($row['expiry'] == $today) {

        continue;
    }

    if ($projectCounter == 0) {
        $bgColor = "teal";
    } else if ($projectCounter == 1) {
        $bgColor = "blue";
    } else if ($projectCounter == 2) {
        $bgColor = "purple";
        $projectCounter = 0;
    }
    $data .=
        '<article class="artikl project" style="background-color: ' . $bgColor . '; color: ' . $bgColor . ';">' .
        '<h2>' . $row['title'] . ' </h2>' .
        '<div class="container d-flex justify-content-center">' .
        '<p><b>AUTOR: </b>' . $row['author'] . '</p>' .
        '<p><b>TRAJANJE: </b>' . $row['expiry'] . '</p>' .
        '</div>' .
        '<p><b>OPIS: </b>' . $row['about'] . '</p>' .
        '<p><b>TRAŽI SE: </b>' . $row['roles'] . '</p>' .
        '<p><b>KONTAKT: </b>' . $row['contact'] . '</p>' .
        '</article>';
    $projectCounter++;
}
echo $data;
?>