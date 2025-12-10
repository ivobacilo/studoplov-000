<?php
session_start();
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}
include 'connect.php';
include 'brojac.php';
$poruka = isset($_SESSION['poruka']) ? $_SESSION['poruka'] : '';
unset($_SESSION['poruka']);
$kategorija = isset($_GET['kategorija']) ? $_GET['kategorija'] : 'svi';


if (isset($_POST["objava"])) {
    if ($_SESSION['permitMarket'] == 1) {
        $poruka = "Ova funkcija je trenutno onemogućena zbog održavanja stranice. Hvala na razumijevanju!";
    } else if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        die("CSRF token validation failed");
    } else {
        $naslov = htmlspecialchars($_POST['title']);
        $autor = htmlspecialchars($_SESSION['name']);
        $today = new DateTime();
        $trajanje = $today->modify('+30 days')->format('Y-m-d');
        $opis = htmlspecialchars($_POST['about']);
        $kontakt = htmlspecialchars($_POST['contact']);
        $cijena = htmlspecialchars($_POST['price']);
        $autorId = $_SESSION['idAuthor'];
        $odabir = $_POST['odabir'];

        $conn = new mysqli($servername, $username, $password, $basename);
        if ($conn->connect_error)
            die('Error connecting to MySQL server: ' . $conn->connect_error);


        $apiKey = "VIRUS_TOTAL";


        function scanFileVirusTotal($fileTmpPath, $apiKey)
        {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://www.virustotal.com/api/v3/files",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => ["file" => new CURLFile($fileTmpPath)],
                CURLOPT_HTTPHEADER => ["x-apikey: $apiKey"]
            ]);
            $uploadResponse = curl_exec($curl);
            curl_close($curl);

            if (!$uploadResponse)
                return false;
            $uploadData = json_decode($uploadResponse, true);
            if (!isset($uploadData['data']['id']))
                return false;
            $analysisId = $uploadData['data']['id'];


            sleep(8);

            $curl = curl_init("https://www.virustotal.com/api/v3/analyses/$analysisId");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ["x-apikey: $apiKey"]);
            $analysisResponse = curl_exec($curl);
            curl_close($curl);

            if (!$analysisResponse)
                return false;
            $analysisData = json_decode($analysisResponse, true);

            if (isset($analysisData['data']['attributes']['stats']['malicious'])) {
                $mal = $analysisData['data']['attributes']['stats']['malicious'];
                return $mal === 0;
            }
            return false;
        }


        $images = ['image1', 'image2', 'image3'];
        $pictures = [];
        $clean = true;

        foreach ($images as $index => $imgName) {
            if (!empty($_FILES[$imgName]['tmp_name'])) {
                $tmp = $_FILES[$imgName]['tmp_name'];


                if (!scanFileVirusTotal($tmp, $apiKey)) {
                    $poruka = "Slika " . ($index + 1) . " sadrži virus ili nije moguće provjeriti.";
                    $clean = false;
                    break;
                }

                $data = file_get_contents($tmp);
                if ($data === false)
                    die('Error reading file contents.');
                $pictures[] = $data;
            }
        }


        if ($clean) {
            $count = count($pictures);

            if ($count === 3) {
                $sql = "INSERT INTO market (title, picture1, picture2, picture3, price, author, about, contact, expiry, idAuthor, category)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssssdssssis",
                    $naslov,
                    $pictures[0],
                    $pictures[1],
                    $pictures[2],
                    $cijena,
                    $autor,
                    $opis,
                    $kontakt,
                    $trajanje,
                    $autorId,
                    $odabir
                );
            } elseif ($count === 2) {
                $sql = "INSERT INTO market (title, picture1, picture2, price, author, about, contact, expiry, idAuthor, category)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "sssdssssis",
                    $naslov,
                    $pictures[0],
                    $pictures[1],
                    $cijena,
                    $autor,
                    $opis,
                    $kontakt,
                    $trajanje,
                    $autorId,
                    $odabir
                );
            } elseif ($count === 1) {
                $sql = "INSERT INTO market (title, picture1, price, author, about, contact, expiry, idAuthor, category)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssdssssis",
                    $naslov,
                    $pictures[0],
                    $cijena,
                    $autor,
                    $opis,
                    $kontakt,
                    $trajanje,
                    $autorId,
                    $odabir
                );
            } else {
                $poruka = "Niste odabrali nijednu sliku.";
                $clean = false;
            }

            if ($clean && isset($stmt)) {
                $stmt->execute();
                $stmt->close();
                $_SESSION['poruka'] = "Uspješno ste objavili oglas!";
            }

            $conn->close();
        }
    }
}







if (isset($_POST['save'])) {
    if ($_SESSION['permitMarket'] == 1) {
        $poruka = "Ova funkcija trenutno je onemogućena zbog održavanja stranice. Hvala na razumijevanju!";
        $_SESSION['poruka'] = $poruka; // Postavi poruku za prikaz nakon redirecta
    } else if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        die("CSRF token validation failed");
    } else {
        $id = $_POST['id'];
        $naslov = htmlspecialchars($_POST['edited_title']);
        $opis = htmlspecialchars($_POST['edited_about']);
        $kontakt = htmlspecialchars($_POST['edited_contact']);
        $cijena = htmlspecialchars($_POST['edited_price']);
        $autorId = $_SESSION['idAuthor'];

        $conn = new mysqli($servername, $username, $password, $basename);

        if ($conn->connect_error) {
            die('Error connecting to MySQL server: ' . $conn->connect_error);
        }


        $apiKey = "VIRUS_TOTAL";


        if (!function_exists('scanFileVirusTotal')) {
            function scanFileVirusTotal($fileTmpPath, $apiKey)
            {
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://www.virustotal.com/api/v3/files",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => ["file" => new CURLFile($fileTmpPath)],
                    CURLOPT_HTTPHEADER => ["x-apikey: $apiKey"]
                ]);
                $uploadResponse = curl_exec($curl);
                curl_close($curl);

                if (!$uploadResponse)
                    return false;
                $uploadData = json_decode($uploadResponse, true);
                if (!isset($uploadData['data']['id']))
                    return false;
                $analysisId = $uploadData['data']['id'];


                sleep(8);

                $curl = curl_init("https://www.virustotal.com/api/v3/analyses/$analysisId");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, ["x-apikey: $apiKey"]);
                $analysisResponse = curl_exec($curl);
                curl_close($curl);

                if (!$analysisResponse)
                    return false;
                $analysisData = json_decode($analysisResponse, true);

                if (isset($analysisData['data']['attributes']['stats']['malicious'])) {
                    $mal = $analysisData['data']['attributes']['stats']['malicious'];
                    return $mal === 0; // true ako nema detekcija
                }
                return false;
            }
        }


        $clean = true;
        $poruka = "";


        $fileInputs = ['picture1' => 'picture1', 'picture2' => 'picture2', 'picture3' => 'picture3'];


        $deleteImageNumber = isset($_POST['delete']) ? (int) $_POST['delete'] : 0;

        $sqlUpdates = [];
        $values = [];
        $types = "";


        $sqlUpdates[] = "title = ?";
        $values[] = $naslov;
        $types .= "s";

        $sqlUpdates[] = "price = ?";
        $values[] = $cijena;
        $types .= "s";

        $sqlUpdates[] = "about = ?";
        $values[] = $opis;
        $types .= "s";

        $sqlUpdates[] = "contact = ?";
        $values[] = $kontakt;
        $types .= "s";


        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'];
        $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jfif'];


        foreach ($fileInputs as $inputName => $columnName) {
            $imageIndex = (int) substr($inputName, -1);
            $isDelete = ($deleteImageNumber == $imageIndex);


            if (!empty($_FILES[$inputName]['tmp_name']) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                $tmp = $_FILES[$inputName]['tmp_name'];
                $name = $_FILES[$inputName]['name'];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $mime = mime_content_type($tmp);


                if (!in_array($ext, $allowedExt) || !in_array($mime, $allowedMime)) {
                    $poruka = "Slika " . $imageIndex . " mora biti JPG, PNG, GIF ili WEBP format!";
                    $clean = false;
                    break;
                }


                if (!scanFileVirusTotal($tmp, $apiKey)) {
                    $poruka = "Slika " . $imageIndex . " sadrži virus ili nije moguće provjeriti.";
                    $clean = false;
                    break;
                }


                $data = file_get_contents($tmp);
                if ($data === false) {
                    die('Error reading file contents.');
                }

                $sqlUpdates[] = "$columnName = ?";
                $values[] = $data;
                $types .= "s";

            } elseif ($isDelete) {
                $sqlUpdates[] = "$columnName = NULL";
            }
        }



        if ($clean) {


            $sql = "UPDATE market SET " . implode(", ", $sqlUpdates) . " WHERE id = ?";


            $values[] = $id;
            $types .= "i";

            $stmt = $conn->prepare($sql);


            $bind_names = [$types];
            for ($i = 0; $i < count($values); $i++) {

                $bind_names[] = &$values[$i];
            }
            call_user_func_array([$stmt, 'bind_param'], $bind_names);

            $stmt->execute();
            $stmt->close();

            $conn->close();


            $_SESSION['poruka'] = "Oglas je uspješno uređen!";

        } else {
            $_SESSION['poruka'] = $poruka;
            $conn->close();
        }
    }


}

if (isset($_POST['delete'])) {
    if ($_SESSION['permitMarket'] == 1) {
        $poruka = "Ova funkcija trenutno je onemogućena zbog održavanja stranice. Hvala na razumijevanju!";
    } else if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        die("CSRF token validation failed");
    } else {
        $id = $_POST['id'];

        $conn = new mysqli($servername, $username, $password, $basename);

        if ($conn->connect_error) {
            die('Error connecting to MySQL server: ' . $conn->connect_error);
        }

        $sql = "DELETE FROM market WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt->close();
        $conn->close();
        $poruka = "Oglas je uspješno izbrisan!";
    }

}

if (isset($_POST['renew'])) {
    if ($_SESSION['$permitMarket'] == 1) {
        $poruka = "Ova funkcija trenutno je onemogućena zbog održavanja stranice. Hvala na razumijevanju!";
    } else {
        if (isset($_SESSION["token"]) && isset($_POST["token"])) {
            $id = $_POST['id'];

            $conn = new mysqli($servername, $username, $password, $basename);

            if ($conn->connect_error) {
                die('Error connecting to MySQL server: ' . $conn->connect_error);
            }

            $sql = "SELECT * FROM market WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $today = new DateTime();
            if ($row['expiry'] <= date('Y-m-d')) {

                $sql = "UPDATE market SET expiry = DATE_ADD(expiry, INTERVAL 30 DAY) WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $poruka = "Oglas je uspješno obnovljen!";
            } else {
                $poruka = "Oglas je još uvijek aktivan!";
            }

            $stmt->close();
            $conn->close();

        }
    }
}

if (!isset($_SESSION['name'])) {
    header("Location: index.php?notif=Niste prijavljeni");
    exit();
}

if (isset($_POST['confirmed'])) {


    $projekt_id = $_POST['project_id'];
    $conn = new mysqli($servername, $username, $password, $basename);
    if ($_SESSION['access'] == 3) {
        $sql = "DELETE FROM market WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $projekt_id);
        $stmt->execute();
        $stmt->close();
        $poruka = "Oglas uspješno izbrisan!";
    } else {
        $sql = "UPDATE market SET reports = reports + 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $projekt_id);
        $stmt->execute();
        $stmt->close();
        $poruka = "Oglas uspješno prijavljen!";
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
    <link rel="manifest" href="favicon/site.webmanifest">
    <link rel="mask-icon" href="favicon/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#1c3f60">
    <meta name="theme-color" content="#1c3f60">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studoplov - Vaša Luka Informacija</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        header {
            font-family: Arial, Helvetica, sans-serif !important;
        }

        main {
            background-color: beige;
        }

        form input {
            width: 300px;
            padding: 10px;

            border-radius: 10px;

        }


        #favdrop {
            min-width: 900px !important;
            margin-left: -10px !important;
        }

        #favDIV {
            width: 40%;
            padding-right: 0px !important;
        }




        input[type="text"],
        input[type="password"],
        input[type="date"],
        textarea {
            width: 100%;
            padding: 10px;
            margin: 0px;
            border-radius: 10px;
            border: 3px solid #1c3f60 !important;
        }

        input[type="text"]:hover,
        input[type="password"]:hover,
        input[type="date"]:hover,
        textarea:hover {
            border: 3px solid darkblue !important;
        }


        input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin: 5px;
            border-radius: 10px;
            background-color: #2271b1;
            color: white;
            border: 0px;
            font-weight: bold;
        }

        form label {
            font-weight: bold;
        }

        .cijena {
            width: 70px !important;
            padding-bottom: 10px !important;
        }

        .autor {
            width: fit-content !important;
            padding-bottom: 10px !important;
        }

        #objava form {
            border: 3px solid gray;

            border-radius: 10px;
            width: 100%;

            width: 100%;
            min-width: 1080px;
            padding: 20px;
        }

        #objava {
            width: 100%;
            max-width: none;
            margin-right: 0px !important;
        }

        #tipke {
            flex-direction: row !important;
        }

        #tipke input {


            margin-top: 10px;
            margin-right: 5px;
            width: fit-content;
        }


        #traka {
            background-color: #2271b1;
            border-radius: 10px;
            flex-direction: column;
            width: 30%;

            margin-right: 20px;
            border: 1px solid gray;
            justify-content: center;
            order: 1;
        }

        #traka div {
            padding: 10px;
            justify-content: center;
        }

        #traka div button {
            padding: 5px;
            border: 3px solid white;
            color: #2271b1;
            font-weight: bold;
        }

        article {
            border: 3px solid gray;
            border-radius: 10px;
            width: 30%;
            background-color: teal;
            margin: 10px;
        }

        article img {
            width: 200px !important;
            height: 300px !important;
            min-width: 200px !important;
            min-height: 200px !important;
            padding: 5px;
            border-radius: 10px;
        }

        article h4,
        article p,
        article h5,
        article h2 {
            padding: 10px;
            margin: 5px;
            border-radius: 10px;
            border: 3px solid white;
            background-color: #f2f2f2;
        }

        article h2,
        article p {
            background-color: white;
        }

        article h2:hover,
        article p:hover {
            background-color: beige;
            color: black;
            border: 3px solid black;
        }

        #opcije button {
            min-width: none !important;
        }


        #moji form {
            width: 30%;
            margin-right: 20px;
        }

        #moji article {
            padding: 10px !important;
            width: 100% !important;
            margin-right: 20px;
        }


        .tipka {
            min-width: 100%;

        }

        #traka button:hover {
            border: 3px solid black;
            background-color: white;
            color: black;
        }

        #searchInput {
            margin-left: -1px !important;
            margin-top: -0px;
            border: 3px solid white !important;
        }



        #uvod {
            background-color: white !important;
            border-radius: 10px;
            padding: 20px;
            border: 3px solid #2271b1 !important;
            width: 100%;
            order: 2;
        }

        #ukupno {
            background-color: ghostwhite;
            border: 2px solid gray;
            padding: 20px;
            border-radius: 10px;
            flex-direction: row;
        }

        form label {
            padding-bottom: 5px;
        }

        #title {
            margin-left: 5px !important;
        }

        #slike button {
            display: none;
        }

        .delete-button {
            width: 30px !important;
            height: 30px !important;
            min-width: 30px !important;
            min-height: 30px !important;
            background-image: url("slike/iks.png");
            background-size: cover;
            border: 0px !important;
            margin-right: -30px !important;
            margin-top: -30px !important;
            background-color: transparent !important;
        }

        .delete-button:hover {
            background-color: white !important;
            border-radius: 50% !important;
        }

        .load {
            width: 150px !important;
            height: 150px !important;
            min-width: 150px !important;
            min-height: 150px !important;
            border: 3px solid #1c3f60 !important;
        }

        .load:hover {
            background-color: gray !important;
            color: white !important;
        }

        #dropmoji {
            min-width: 1200px;
        }

        .odabir {
            border: 3px solid #1c3f60 !important;
            border-radius: 10px;
            margin: 5px;
        }

        #obavijestiNAV::after {
            <?php if ($brojac != 0): ?>
                content: "<?php echo $brojac; ?>";
            <?php else: ?>
                display: none;
            <?php endif; ?>
        }

        .popis {
            padding-bottom: 0px !important;
        }

        #objaviDIV {
            padding-left: 0px !important;
        }


        @media (max-width: 575.98px) {
            #uvod {
                order: 1;
                margin-bottom: 20px;
            }

            .delete-button {
                margin-right: -30px !important;
            }

            #dropmoji {
                max-width: 400px;
                min-width: 0px;
            }

            #dropmoji #moji input[type="submit"] {
                margin: 0 auto;
                margin-top: 10px;
                margin-right: 5px;
                width: fit-content;
            }



            #projekt article {
                width: 90%;
                margin-left: 20px;

            }

            #projekt {
                flex-direction: column;
                justify-content: center;
            }

            #traka {
                flex-direction: column;
                width: 100%;
                order: 2;
            }



            #objava {
                width: 100%;
            }

            #objava form {
                width: 100%;
                min-width: 100%;
            }

            #moji {
                flex-direction: column;
                width: 100%;
            }

            #moji article {
                width: 100%;
                margin-left: 0px;
            }

            #moji form {
                width: 100%;
                margin-left: 0px;

            }

            #ukupno {
                flex-direction: column !important;
            }

            #slike {
                flex-direction: column !important;
                justify-content: center !important;
            }

            #moji .image-preview {
                flex-direction: column !important;
            }

            #moji #mojeslike {
                flex-direction: column !important;
            }

            #tipke {
                flex-direction: row !important;
                margin-left: 1px;
            }

            #objaviDIV {
                width: 250px;
            }

            #favdrop {
                min-width: 100px !important;
                margin-left: 10px;

            }

            #favdrop .articles {
                flex-direction: column;

            }

            #favdrop .articles article {
                width: auto;
                margin-left: 0px;
            }
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/5f61444440.js" crossorigin="anonymous"></script>

</head>

<body>
    <header>
        <nav class="navbar navbar-dark navbar-expand-lg " style="background-color:teal;">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php" id="naslov">
                    <img src="slike/novilogo.png" alt="" class="d-inline-block align-text-center">
                    Studoplov - Vaša Luka Informacija
                </a>

                <ul class="navbar-nav">
                    <?php if (!isset($_SESSION["idAuthor"])) { ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false" style="color:white;">
                                PROFIL
                            </a>
                            <ul class="dropdown-menu izbornik" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="#reg">Registriraj se</a></li>
                                <li><a class="dropdown-item" href="#prijava">Prijava</a></li>
                                <li><a class="dropdown-item" href="racun.php?cause=recovery">Zaboravljena
                                        lozinka</a>
                                </li>
                            </ul>
                        </li>
                    <?php } else { ?>
                        <li class="nav-item dropdown">

                            <a class="nav-link dropdown-toggle" href="#" id="obavijestiNAV" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false" style="color:white; ">
                                <i class="fa-sharp fa-solid fa-envelope"></i>
                            </a>



                            <ul class="dropdown-menu container" aria-labelledby="navbarDropdown" id="obavijesti">
                                <table class="table container" id="tablica">
                                    <?php

                                    $dbc = mysqli_connect($servername, $username, $password, $basename) or
                                        die('Error connecting to MySQL server.' . mysqli_connect_error());
                                    $query = "SELECT * FROM projects WHERE idAuthor = ? AND DATEDIFF(EXPIRY,NOW()) <= 5";
                                    $stmt = mysqli_stmt_init($dbc);

                                    if (mysqli_stmt_prepare($stmt, $query)) {
                                        mysqli_stmt_bind_param($stmt, 'i', $_SESSION['idAuthor']);
                                        mysqli_stmt_execute($stmt);
                                        $result = mysqli_stmt_get_result($stmt);
                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_array($result)) {
                                                $expiryDate = new DateTime($row['expiry']);
                                                $dateDifference = date_diff(new DateTime(), $expiryDate);
                                                echo '<tr><td><li><a  href="projekt.php?id=' . $row['id'] . '">Vaš projekt: ' . htmlspecialchars($row['title']) . ' će isteći za ' . $dateDifference->format('%a') . ' dana.</a></li></td></tr>';

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
                                                $expiryDate = new DateTime($row['expiry']);
                                                $dateDifference = date_diff(new DateTime(), $expiryDate);
                                                echo '<tr><td><li><a  href="forum.php?id=' . $row['id'] . '">Vaš oglas: ' . htmlspecialchars($row['title']) . ' će isteći za ' . $dateDifference->format('%a') . ' dana.</a></li></td></tr>';

                                            }
                                        }
                                    }

                                    ?>
                                </table>
                            </ul>
                        </li>
                    <?php } ?>
                    <li class="nav-item">
                        <a class="nav-link" href="projekti.php" style="color:white;">PROJEKTI</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kalendar.php" style="color:white;">KALENDAR</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="forum.php" style="color:white;">FORUM</a>
                    </li>
                </ul>
        </nav>
    </header>

    <main>
        <div class="container d-flex flex-direction-column" id="ukupno">

            <div class="container d-flex flex-direction-row" id="traka">
                <div class="container d-flex popis">
                    <div class="dropdown" id="objaviDIV">
                        <button type="button" class="btn btn-light btn-lg dropdown-toggle tipka"
                            data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                            OBJAVI
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <br>
                            <div class="container d-flex flex-direction-column justify-content-center" id="objava">
                                <form method="post" enctype="multipart/form-data">
                                    <div class="container">
                                        <label for="title">Naziv:</label>
                                        <input type="text" name="title" id="title" required>
                                    </div>
                                    <br>
                                    <div class="container d-flex flex-direction-column">
                                        <div class="odabir">
                                            <input type="radio" id="prodaja" name="odabir" class="dropdown-item"
                                                value="prodaja">
                                            <label for="prodaja">Prodaja predmeta</label>
                                        </div>
                                        <div class="odabir">
                                            <input type="radio" id="anotherAction" name="odabir" class="dropdown-item"
                                                value="usluga">
                                            <label for="anotherAction">Objava usluge</label>
                                        </div>
                                        <div class="odabir">
                                            <input type="radio" id="somethingElse" name="odabir" class="dropdown-item"
                                                value="lnf">
                                            <label for="somethingElse">Izgubljeno / nađeno</label>
                                        </div>
                                    </div>

                                    <div class="container">
                                        <label for="pictures">Slike:</label>


                                        <div id="slike" class="container d-flex flex-direction-row ">

                                            <div id="preview1"
                                                class="image-preview container d-flex flex-direction-column">

                                                <input type="button" class="load" id="loadFile1" value="+" required />
                                                <input type="file" style="display:none;" id="image1" name="image1"
                                                    accept="image/*" required><br>

                                                <button class="delete-button" id="deleteFile1"></button>
                                            </div>


                                            <div id="preview2"
                                                class="image-preview container d-flex flex-direction-column">

                                                <input type="button" class="load" id="loadFile2" value="+" />
                                                <input type="file" style="display:none;" id="image2" name="image2"
                                                    accept="image/*"><br>

                                                <button class="delete-button" id="deleteFile2"></button>
                                            </div>

                                            <div id="preview3"
                                                class="image-preview container d-flex flex-direction-column">

                                                <input type="button" class="load" id="loadFile3" value="+" />
                                                <input type="file" style="display:none;" id="image3" name="image3"
                                                    accept="image/*"><br>

                                                <button class="delete-button" id="deleteFile3"></button>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="container">
                                        <label for=" price">Cijena:</label>
                                        <input type="text" name="price" id="price" required>
                                    </div>
                                    <br>

                                    <div class="container">
                                        <label for="about">Opis:</label>
                                        <textarea name="about" id="about" required></textarea>
                                    </div>
                                    <br>

                                    <div class="container">
                                        <label for="contact">Kontakt:</label>
                                        <input type="text" name="contact" id="contact" required>
                                    </div>
                                    <br>
                                    <input type="hidden" name="token" value="<?= $_SESSION['token'] ?? '' ?>">
                                    <input type="submit" value="Objavi" name="objava">
                                </form>


                            </div>
                            <br>
                        </ul>
                    </div>
                    <div class="dropdown" id="favDIV">
                        <button type="button" class="btn btn-light btn-lg dropdown-toggle tipka"
                            data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                            <i class="fa-solid fa-heart"></i>
                        </button>
                        <ul class="dropdown-menu" id="favdrop" aria-labelledby="navbarDropdown">
                            <br>
                            <div class="articles container d-flex flex-wrap" id="fav">
                                <?php
                                $dbc = new mysqli($servername, $username, $password, $basename);
                                $query = "SELECT * FROM market INNER JOIN saved ON market.id = saved.idPost WHERE saved.idUser = ?";

                                $stmt = $dbc->prepare($query);
                                $stmt->bind_param("i", $_SESSION['idAuthor']);
                                $stmt->execute();

                                $result = $stmt->get_result();
                                $stmt->close();

                                $projectCounter = 0;
                                if (mysqli_num_rows($result) == 0) {
                                    echo '<p style="text-align:center;">Nemate spremljenih oglasa.</p>';
                                }
                                while ($row = mysqli_fetch_array($result)) {
                                    $today = date("Y-m-d");
                                    if ($row['expiry'] == $today) {
                                        continue;
                                    }

                                    if ($projectCounter == 0) {
                                        $bgColor = "3d59ab";
                                    } else if ($projectCounter == 1) {
                                        $bgColor = "blue";
                                    } else if ($projectCounter == 2) {
                                        $bgColor = "purple";
                                        $projectCounter = 0;
                                    }
                                    echo '<article class="artikl project" style="background-color: ' . $bgColor . '; color: ' . $bgColor . ';">';

                                    echo '<h2>' . htmlspecialchars($row['title']) . ' </h2>';
                                    echo '<div class="container d-flex justify-content-center">';
                                    echo '<p><b>AUTOR: </b>' . htmlspecialchars($row['author']) . '</p>';
                                    echo '<p><b>TRAJANJE: </b>' . htmlspecialchars($row['expiry']) . '</p>';
                                    echo '</div>';


                                    echo '<div class="project-details" style="display: none;">';
                                    echo '<p><b>OPIS: </b>' . htmlspecialchars($row['about']) . '</p>';
                                    echo '<p><b>TRAŽI SE: </b>' . htmlspecialchars($row['roles']) . '</p>';
                                    echo '<p><b>KONTAKT: </b>' . htmlspecialchars($row['contact']) . '</p>';


                                    echo '<div class="container d-flex flex-direction-column justify-content-center" id="opcije">';

                                    echo '<button type="button" id="' . $projectCounter . '" onclick="spremi(\'' . $row['idProject'] . '\', this)" class="btn btn-light btn-lg" style="margin: 10px; width: 100%; color:#ff2600 !important;"><i class="fa-solid fa-heart"></i></button>';

                                    echo '<button type="button" onclick="showToast(\'Jeste li sigurni da želite prijaviti ovaj projekt?\', ' . $row['id'] . ')" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#exampleModal" style="margin: 10px; width: 100%;"><i class="fa-regular fa-circle-xmark"></i></button>';
                                    echo '</div>';

                                    echo '</div>';


                                    echo '<div class="container d-flex flex-direction-column justify-content-center">';
                                    echo '<button type="button" class="btn btn-light btn-lg toggle-btn" style="margin: 10px; width: 100%;" onclick="toggleDetails(this)"><i class="fa-solid fa-angle-down"></i></button>';
                                    echo '</div>';

                                    echo '<div class="container d-flex flex-direction-column justify-content-center">';

                                    echo '<button type="button" id="' . $projectCounter . '" onclick="spremi(\'' . $row['idPost'] . '\', this)" class="btn btn-light btn-lg" style="margin: 10px; width: 100%; color:#ff2600 !important;"><i class="fa-solid fa-heart"></i></button>';

                                    echo '<button type="button" onclick="showToast(\'Jeste li sigurni da želite prijaviti ovaj oglas?\', ' . $row['id'] . ')" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#exampleModal" style="margin: 10px; width: 100%;"><i class="fa-regular fa-circle-xmark"></i></button>';
                                    echo '</div>';
                                    echo '</article>';
                                    $projectCounter++;

                                }
                                ?>
                            </div>
                            <br>
                        </ul>
                    </div>
                </div>

                <div class="dropdown" style="width:100%;">
                    <button type="button" class="btn btn-light btn-lg dropdown-toggle tipka" data-bs-toggle="dropdown"
                        aria-expanded="false" data-bs-auto-close="outside">
                        MOJI OGLASI
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown" id="dropmoji">
                        <br>
                        <div class="container d-flex flex-wrap" id="moji">
                            <?php

                            $dbc = new mysqli($servername, $username, $password, $basename);
                            $query = "SELECT * FROM market WHERE idAuthor = " . $_SESSION['idAuthor'] . "";
                            $result = mysqli_query($dbc, $query);
                            $projectCounter = 0;
                            $carouselIdCounter = 0;

                            while ($row = mysqli_fetch_array($result)) {
                                $today = date("Y-m-d");
                                if ($row['expiry'] == $today) {

                                }

                                if ($projectCounter == 0) {
                                    $bgColor = "#2b4570";
                                } else if ($projectCounter == 1) {
                                    $bgColor = "#a43852";
                                } else if ($projectCounter == 2) {
                                    $bgColor = "#4169e1";
                                    $projectCounter = 0;
                                }


                                $deleteButtonName = 'delete' . $carouselIdCounter;


                                echo '<form method="post"  enctype="multipart/form-data">';
                                echo '<input type="hidden" name="id" value="' . $row['id'] . '">';

                                echo '<article class="artikl container" style="background-color: ' . $bgColor . '; color: ' . $bgColor . ';">';
                                echo '<h2><input type="text" name="edited_title" value="' . htmlspecialchars($row['title']) . '"></h2>';

                                echo '<div class="container d-flex" style="background-color:white;border-radius:10px;margin-top:10px;">';
                                echo '<label for="pictures">Slike:</label><br>';
                                echo '<div  class="container d-flex flex-wrap justify-content-center" id="mojeslike"  >';
                                for ($i = 1; $i <= 3; $i++) {
                                    $imageFieldName = 'picture' . $i;
                                    $previewId = 'preview' . $i;
                                    $loadButtonId = 'loadFile' . $i;
                                    $deleteButtonId = 'deleteFile' . $i;

                                    $backgroundImage = !empty($row[$imageFieldName]) ? 'data:image/jpeg;base64,' . base64_encode($row[$imageFieldName]) : 'slike/filler.png';

                                    echo '<div id="' . $previewId . '" class="image-preview container d-flex flex-direction-row">';
                                    echo '<input type="button" class="load" id="' . $loadButtonId . '" value="+" style="background-image: url(' . $backgroundImage . '); background-size:cover;" required />';
                                    echo '<input type="file" style="display:none;" id="' . $imageFieldName . '" name="' . $imageFieldName . '" accept="image/*"><br>';
                                    echo '<button class="delete-button" id="' . $deleteButtonId . '" name="delete" value="' . $i . '"></button>';
                                    echo '<input type="hidden" name="delete_file' . $i . '" value="' . $i . '">';
                                    echo '</div>';
                                }

                                echo '</div>';
                                echo '</div>';

                                echo '<div class="container d-flex flex-direction-row">';
                                echo '<p><b>CIJENA: <input type="text" name="edited_price" value="' . htmlspecialchars($row['price']) . '" class="cijena"> €</b></p>';

                                echo '</div>';
                                echo '<p><b>OPIS: </b><textarea name="edited_about">' . htmlspecialchars($row['about']) . '</textarea></p>';
                                echo '<p><b>KONTAKT: </b><input type="text" name="edited_contact" value="' . htmlspecialchars($row['contact']) . '"></p>';
                                echo '<input type="hidden" name="token" value="' . htmlspecialchars($_SESSION['token'] ?? '') . '">';

                                echo '<div class="container d-flex justify-content-center" id="tipke">';
                                echo '<input type="hidden" name="token" value="' . htmlspecialchars($_SESSION['token'] ?? '') . '">';

                                echo '<input type="submit" name="save" value="SPREMI">';
                                echo '<input type="submit" name="delete" value="IZBRIŠI">';
                                echo '<input type="submit" name="renew" value="OBNOVI">';
                                echo '</div>';
                                echo '</form>';

                                echo '</article>';
                                $projectCounter++;
                                $carouselIdCounter++;
                            }
                            mysqli_close($dbc);
                            ?>

                        </div>
                        <br>
                    </ul>
                </div>
                <div class="dropdown">
                    <button type="button" class="btn btn-light btn-lg dropdown-toggle tipka" data-bs-toggle="dropdown"
                        aria-expanded="false" data-bs-auto-close="outside">
                        FILTRIRAJ
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?kategorija=svi">SVI OGLASI</a></li>
                        <li><a class="dropdown-item" href="?kategorija=prodaja">PRODAJA PREDMETA</a></li>
                        <li><a class="dropdown-item" href="?kategorija=usluga">OGLAŠAVANJE USLUGA</a></li>
                        <li><a class="dropdown-item" href="?kategorija=lnf">IZGUBLJENO / NAĐENO</a></li>
                    </ul>
                </div>
                <div class="container">
                    <input type="text" id="searchInput" placeholder="Pretraži oglase...">
                </div>
            </div><br>
            <div class="container" id="uvod">
                <h1>OGLASNIK</h1>
                <p>Dobrodošli na sekciju "Oglasnik", ovdje imate sljedeće mogućnosti:
                </p>
                <ul class="list-group">
                    <li class="list-group-item">Pregledavanje ostalih i, objava, uređivanje i brisanje vlastitih oglasa
                    </li>

                    <li class="list-group-item">Filtriranje po kategorijama oglasa</li>
                    <li class="list-group-item">Filtriranje po tražilici</li>
                    <li class="list-group-item">Napomena: Oglasi traju 30 dana, obnavljaju se tipkom "obnovi", a brišu 5
                        dana
                        nakon isteka trajanja</li>
                </ul>

            </div>

        </div>
        <br>
        <div class="container">
            <div class="articles container d-flex justify-content-center flex-wrap" id="projekt">
                <?php

                $dbc = new mysqli($servername, $username, $password, $basename);

                if ($kategorija == "svi") {
                    $query = "SELECT * FROM market";
                    $stmt = $dbc->prepare($query);
                } else {
                    $query = "SELECT * FROM market WHERE category = ?";
                    $stmt = $dbc->prepare($query);
                    $stmt->bind_param("s", $kategorija);
                }

                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();

                $projectCounter = 0;
                $carouselIdCounter = 0;

                while ($row = mysqli_fetch_array($result)) {
                    $today = date("Y-m-d");
                    if ($row['expiry'] == $today) {

                        continue;
                    }

                    if ($projectCounter == 0) {
                        $bgColor = "#2b4570";
                    } else if ($projectCounter == 1) {
                        $bgColor = "#a43852";
                    } else if ($projectCounter == 2) {
                        $bgColor = "#4169e1";
                        $projectCounter = 0;
                    }


                    $carouselId = 'carouselExampleCaptions' . $carouselIdCounter;

                    echo '<article class="artikl" style="background-color: ' . $bgColor . '; color: ' . $bgColor . ';">';
                    echo '<h2>' . HTMLSPECIALCHARS($row['title']) . '</h2>';
                    echo '<div id="' . $carouselId . '" class="carousel slide">';
                    echo '<div class="carousel-indicators">';
                    echo '<button type="button" data-bs-target="#' . $carouselId . '" data-bs-slide-to="0" class="active"
            aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#' . $carouselId . '" data-bs-slide-to="1"
            aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#' . $carouselId . '" data-bs-slide-to="2"
            aria-label="Slide 3"></button></div>';
                    echo '<div class="carousel-inner" id="karosel' . $projectCounter . '">';


                    if (!empty($row['picture1'])) {
                        echo '<div class="carousel-item active">';
                        echo '<img src="data:image/jpeg;base64,' . base64_encode($row['picture1']) . '" class="d-block w-100" alt="Slide 1">';
                        echo '</div>';
                    } else {
                        echo '<div class="carousel-item active">';
                        echo '<img src="slike/filler.png" class="d-block w-100" alt="Filler Image">';
                        echo '</div>';
                    }

                    if (!empty($row['picture2'])) {
                        echo '<div class="carousel-item">';
                        echo '<img src="data:image/jpeg;base64,' . base64_encode($row['picture2']) . '" class="d-block w-100" alt="Slide 2">';
                        echo '</div>';
                    } else {
                        echo '<div class="carousel-item">';
                        echo '<img src="slike/filler.png" class="d-block w-100" alt="Filler Image">';
                        echo '</div>';
                    }


                    if (!empty($row['picture3'])) {
                        echo '<div class="carousel-item">';
                        echo '<img src="data:image/jpeg;base64,' . base64_encode($row['picture3']) . '" class="d-block w-100" alt="Slide 3">';
                        echo '</div>';
                    } else {
                        echo '<div class="carousel-item">';
                        echo '<img src="slike/filler.png" class="d-block w-100" alt="Filler Image">';
                        echo '</div>';
                    }

                    echo '</div>';
                    echo '
        <button class="carousel-control-prev" type="button" data-bs-target="#' . $carouselId . '" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#' . $carouselId . '" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>';
                    echo '</div>';
                    echo '<div class="container d-flex flex-direction-row">';
                    echo '<p><b>' . htmlspecialchars($row['price']) . '€</b></p>';
                    echo '<p><b>OBJAVIO: </b>' . $row['author'] . '</p>';
                    echo '</div>';
                    echo '<p><b>OPIS: </b>' . htmlspecialchars($row['about']) . '</p>';
                    echo '<p><b>KONTAKT: </b>' . htmlspecialchars($row['contact']) . '</p>';
                    $query = "SELECT * FROM saved WHERE idPost = " . $row['id'] . " AND idUser = " . $_SESSION['idAuthor'] . "";
                    $result2 = mysqli_query($dbc, $query);

                    echo '<div class="container d-flex flex-direction-column justify-content-center">';
                    if (mysqli_num_rows($result2) > 0) {
                        echo '<button type="button" id="' . $projectCounter . '" onclick="spremi(\'' . $row['id'] . '\', this)" class="btn btn-light btn-lg" style="margin: 10px; width: 100%; color:#ff2600;"><i class="fa-solid fa-heart"></i></button>';
                    } else {
                        echo '<button type="button" id="' . $projectCounter . '" onclick="spremi(\'' . $row['id'] . '\', this)" class="btn btn-light btn-lg" style="margin: 10px; width: 100%;"><i class="fa-regular fa-heart"></i></button>';
                    }
                    if ($_SESSION['access'] == 3 || $_SESSION['access'] == 2) {
                        echo '<button type="button" onclick="showToast(\'Jeste li sigurni da želite izbrisati ovaj oglas?\', ' . $row['id'] . ')" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#exampleModal" style="margin: 10px; width: 100%;"><i class="fa-solid fa-trash-can"></i></button>';
                    } else {
                        echo '<button type="button" onclick="showToast(\'Jeste li sigurni da želite prijaviti ovaj oglas?\', ' . $row['id'] . ')" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#exampleModal" style="margin: 10px; width: 100%;"><i class="fa-regular fa-circle-xmark"></i></button>';
                    }
                    echo '</div>';
                    echo '</article>';
                    $projectCounter++;
                    $carouselIdCounter++;
                }
                mysqli_close($dbc);
                ?>


            </div>
        </div>
        <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 11">
            <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <img src="slike/warning.png" width="20px" class="rounded me-2" alt="...">
                    <strong class="me-auto">Obavijest</strong>
                    <small>Now</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    Hello, world! This is a toast message.
                </div>
                <div class="container d-flex justify-content-center" id="reporting" style="display:none !important;">
                    <form method="POST" class="d-flex justify-content-center" style="width:100%;">'
                        <input type="submit" name="confirmed" id="report" value="DA" class="btn btn-primary"></input>
                        <input type="submit" onclick="prevent()" name="rejected" id="report" value="NE"
                            class="btn btn-primary"></input>
                        <input type="hidden" id="project_id" name="project_id" value="0"></input>
                    </form>
                </div>
            </div>
        </div>

        <script>
            $(document).ready(function () {

                function handleFileUpload(containerId) {

                    $(document).on('click', '#' + containerId + ' .load', function () {

                        $(this).siblings('input[type=file]').click();
                    });

                    $(document).on('change', '#' + containerId + ' input[type=file]', function () {
                        var file = this.files[0];
                        var loadButton = $(this).siblings('.load');
                        var deleteButton = $(this).siblings('.delete-button');

                        deleteButton.show();


                        var reader = new FileReader();

                        reader.onload = function (e) {

                            loadButton.css({
                                'background-image': 'url(' + e.target.result + ')',
                                'background-size': 'cover',
                                'background-position': 'center',
                                'width': '200px',
                                'height': '200px',
                            });

                            loadButton.val('');
                        };


                        reader.readAsDataURL(file);
                    });

                    $(document).on('click', '#' + containerId + ' .delete-button', function (e) {

                        e.preventDefault();

                        var fileInput = $(this).siblings('input[type=file]');
                        var loadButton = $(this).siblings('.load');


                        fileInput.val('');


                        $(this).hide();

                        loadButton.css('background-image', 'none');
                        loadButton.val('+');


                        loadButton.show();
                    });
                }


                handleFileUpload('preview1');
                handleFileUpload('preview2');
                handleFileUpload('preview3');
            });
        </script>

        <script>
            $(document).ready(function () {
                $("#searchInput").on("keyup", function () {
                    var value = $(this).val().toLowerCase();
                    $.ajax({
                        method: 'POST',
                        url: 'traziajax.php?kategorija=<?php echo $kategorija ?>',
                        data: { search: value },
                        success: function (response) {
                            $("#projekt").html(response);
                        }
                    });

                });
            });
            function showToast(message, id) {
                if (id != 0) {
                    document.getElementById("project_id").value = id;
                    document.getElementById("reporting").style.display = "block";
                }
                var toastElement = new bootstrap.Toast(document.getElementById('liveToast'));
                document.querySelector('.toast-body').innerText = message;
                toastElement.show();

            }

            <?php
            if (isset($poruka) && $poruka !== "") {
                echo "showToast('" . addslashes($poruka) . "', 0);";
                $poruka = "";
            }
            ?>
            function prevent() {
                event.preventDefault();
                var toastElement = new bootstrap.Toast(document.getElementById('liveToast'));
                toastElement.hide();
            }

        </script>
        <script>
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        </script>
        <script src="js/javasc.js"></script>
        <script>
            function spremi(idPost, clickedButton) {
                $.ajax({
                    type: 'POST',
                    url: 'spremi_ajax_forum.php',
                    data: {
                        idPost: idPost,
                        idUser: <?php echo $_SESSION['idAuthor']; ?>
                    },
                    success: function (response) {
                        console.log("AJAX Request Success - Response:", response);
                        var status = response;

                        var iconElement = clickedButton.querySelector('i');

                        if (status == "inserted") {
                            console.log("Inserting - Changing color to #ff2600");
                            iconElement.classList.remove('fa-regular');
                            iconElement.classList.add('fa-solid');
                            iconElement.style.color = "#ff2600";
                        } else {
                            console.log("Deleting - Changing color to black");
                            iconElement.classList.remove('fa-solid');
                            iconElement.classList.add('fa-regular');
                            iconElement.style.color = "black";
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX Request Error:", status, error);
                        console.log(xhr.responseText);
                    }
                });
            }
        </script>


    </main>

    <footer>
        <p>Ivo Baćilo - Organizacija I Informatizacija Ureda, TVZ Završni Rad</p>

    </footer>

    </bo dy>