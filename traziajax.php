<?php
$servername = "localhost";
$username = "root";
$password = "0000";
$basename = "id21764729_studoplov";
$dbc = new mysqli($servername, $username, $password, $basename);
$search = $_POST['search'];
$kategorija = $_GET['kategorija'];
if ($kategorija == "svi") {
    $sql = "SELECT * FROM market WHERE title LIKE '%$search%' OR about LIKE '%$search%'";
} else {
    $sql = "SELECT * FROM market WHERE (title LIKE '%$search%' OR about LIKE '%$search%') AND category = '" . $kategorija . "'";
}
$query = mysqli_query($dbc, $sql);
$data = '';
$projectCounter = 0;
$carouselIdCounter = 0;
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


    $carouselId = 'carouselExampleCaptions' . $carouselIdCounter;

    $data .= '<article class="artikl" style="background-color: ' . $bgColor . '; color: ' . $bgColor . ';">';
    $data .= '<h2>' . $row['title'] . '</h2>';
    $data .= '<div id="' . $carouselId . '" class="carousel slide">';
    $data .= '<div class="carousel-indicators">';
    $data .= '<button type="button" data-bs-target="#' . $carouselId . '" data-bs-slide-to="0" class="active"
    aria-current="true" aria-label="Slide 1"></button>';
    $data .= '<button type="button" data-bs-target="#' . $carouselId . '" data-bs-slide-to="1"
    aria-label="Slide 2"></button>';
    $data .= '<button type="button" data-bs-target="#' . $carouselId . '" data-bs-slide-to="2"
    aria-label="Slide 3"></button></div>';
    $data .= '<div class="carousel-inner" id="karosel' . $projectCounter . '">';

    if (!empty($row['picture1'])) {
        $data .= '<div class="carousel-item active">';
        $data .= '<img src="data:image/jpeg;base64,' . base64_encode($row['picture1']) . '" class="d-block w-100" alt="Slide 1">';
        $data .= '</div>';
    } else {
        $data .= '<div class="carousel-item active">';
        $data .= '<img src="slike/filler.png" class="d-block w-100" alt="Filler Image">';
        $data .= '</div>';
    }


    if (!empty($row['picture2'])) {
        $data .= '<div class="carousel-item">';
        $data .= '<img src="data:image/jpeg;base64,' . base64_encode($row['picture2']) . '" class="d-block w-100" alt="Slide 2">';
        $data .= '</div>';
    } else {
        $data .= '<div class="carousel-item">';
        $data .= '<img src="slike/filler.png" class="d-block w-100" alt="Filler Image">';
        $data .= '</div>';
    }


    if (!empty($row['picture3'])) {
        $data .= '<div class="carousel-item">';
        $data .= '<img src="data:image/jpeg;base64,' . base64_encode($row['picture3']) . '" class="d-block w-100" alt="Slide 3">';
        $data .= '</div>';
    } else {
        $data .= '<div class="carousel-item">';
        $data .= '<img src="slike/filler.png" class="d-block w-100" alt="Filler Image">';
        $data .= '</div>';
    }

    $data .= '</div>';
    $data .= '<button class="carousel-control-prev" type="button" data-bs-target="#' . $carouselId . '" data-bs-slide="prev">';
    $data .= '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
    $data .= '<span class="visually-hidden">Previous</span>';
    $data .= '</button>';
    $data .= '<button class="carousel-control-next" type="button" data-bs-target="#' . $carouselId . '" data-bs-slide="next">';
    $data .= '<span class="carousel-control-next-icon" aria-hidden="true"></span>';
    $data .= '<span class="visually-hidden">Next</span>';
    $data .= '</button>';
    $data .= '</div>';
    $data .= '<div class="container d-flex flex-direction-row">';
    $data .= '<p><b>' . $row['price'] . '€</b></p>';
    $data .= '<p><b>OBJAVIO: </b>' . $row['author'] . '</p>';
    $data .= '</div>';
    $data .= '<p><b>OPIS: </b>' . $row['about'] . '</p>';
    $data .= '<p><b>KONTAKT: </b>' . $row['contact'] . '</p>';
    $data .= '</article>';
    $projectCounter++;
    $carouselIdCounter++;
}


echo $data;
?>