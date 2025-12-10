<?php
session_start();
include 'connect.php';
include 'brojac.php';

if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$poruka = isset($_SESSION['message']) ? $_SESSION['message'] : "";
unset($_SESSION['message']);
include 'connect.php';

if (!isset($_SESSION['name'])) {
    header("Location: index.php?notif=Niste prijavljeni");
    exit();
}

if (isset($_POST['objava'])) {
    if ($_SESSION['permitProjects'] == 1) {
        $poruka = "Ova funkcija je trenutno onemogućena zbog održavanja stranice. Hvala na razumijevanju!";
    } else {
        if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
            die("CSRF token validation failed");
        } else {
            $naslov = $_POST['title'];
            $idAuthor = $_SESSION['idAuthor'];
            $trajanje = $_POST['expiry'];
            $opis = $_POST['description'];
            $uloge = $_POST['roles'];
            $kontakt = $_POST['contact'];
            $ime = $_SESSION['name'];

            $conn = new mysqli($servername, $username, $password, $basename);

            if ($conn->connect_error) {
                die('Error connecting to MySQL server: ' . $conn->connect_error);
            }

            $sql = "INSERT INTO projects (title, author, expiry, about, roles, contact, idAuthor) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $naslov, $ime, $trajanje, $opis, $uloge, $kontakt, $idAuthor);
            $stmt->execute();

            $stmt->close();
            $conn->close();
            $message = "Projekt uspješno objavljen!";

            $_SESSION['message'] = $message;
        }
    }

}



if (isset($_POST['save'])) {
    if ($_SESSION['permitProjects'] == 1) {
        $poruka = "Ova funkcija je trenutno onemogućena zbog održavanja stranice. Hvala na razumijevanju!";
    } else {
        if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
            die("CSRF token validation failed");
        } else {
            $id = $_POST['project_id'];
            $naslov = $_POST['title'];
            $trajanje = $_POST['expiry'];
            $opis = $_POST['about'];
            $uloge = $_POST['roles'];
            $kontakt = $_POST['contact'];


            $conn = new mysqli($servername, $username, $password, $basename);

            if ($conn->connect_error) {
                die('Error connecting to MySQL server: ' . $conn->connect_error);
            }

            $sql = "UPDATE projects SET title=?, expiry=?, about=?, roles=?, contact=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $naslov, $trajanje, $opis, $uloge, $kontakt, $id);
            $stmt->execute();


            $stmt->close();
            $conn->close();
            $poruka = "Projekt uspješno uređen!";
        }
    }
}

if (isset($_POST['delete'])) {
    if ($_SESSION['permitProjects'] == 1) {
        $poruka = "Ova funkcija je trenutno onemogućena zbog održavanja stranice. Hvala na razumijevanju!";
    } else {
        if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
            die("CSRF token validation failed");
        } else {
            $id = $_POST['project_id'];

            $conn = new mysqli($servername, $username, $password, $basename);

            if ($conn->connect_error) {
                die('Error connecting to MySQL server: ' . $conn->connect_error);
            }

            $sql = "DELETE FROM projects WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt->close();
            $conn->close();
            $poruka = "Projekt uspješno izbrisan!";
        }
    }
}

if (isset($_POST['confirmed'])) {


    $projekt_id = $_POST['project_id'];
    $conn = new mysqli($servername, $username, $password, $basename);
    if ($_SESSION['access'] === 3) {
        $sql = "DELETE FROM projects WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $projekt_id);
        $stmt->execute();
        $stmt->close();
        $poruka = "Projekt uspješno izbrisan!";
    } else {
        $sql = "UPDATE projects SET reports = reports + 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $projekt_id);
        $stmt->execute();
        $stmt->close();
        $poruka = "Projekt uspješno prijavljen!";
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
            margin: 5px;
            border-radius: 10px;
            border: 3px solid white;
        }

        #traka button:hover {
            border: 3px solid black;
            background-color: white;
            color: black;
        }


        input[type="text"],
        .artikl input[type="password"],
        input[type="date"],
        textarea {
            width: 100%;
            padding: 10px;
            margin: 5px;
            border-radius: 10px;
            border: 3px solid gray !important;
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
            font-weight: bold;
        }

        form label {
            font-weight: bold;
        }

        #objava form {
            border: 3px solid gray;
            padding: 20px;
            border-radius: 10px;
            width: 100%;
            background-color: #f2f2f2;
        }


        #objava {
            width: 100%;
            max-width: none;
            margin-right: 0px !important;
        }

        #objava form {
            width: 100%;
            min-width: 1080px;
            padding: 20px;
        }



        #traka {
            background-color: rgb(60, 63, 77);
            border-radius: 10px;
            flex-direction: column;
            width: 40%;

            margin-right: 20px;
            border: 1px solid gray;
            justify-content: center;
            align-items: center order: 1;
        }

        #traka div {
            padding: 10px;
        }

        #traka div button {
            padding: 10px;
            border: 3px solid white;
            font-weight: bold;
            color: rgb(60, 63, 77);
            width: 100%;
        }

        article {
            border: 3px solid gray;
            border-radius: 10px;
            width: 30%;
            background-color: teal;
            margin: 10px;
        }

        .project-details {
            overflow: hidden;
            transition: height 0.3s ease;

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



        #moji article {
            padding: 0px !important;
            width: 30% !important;
        }



        #searchInput {
            margin-left: 0px !important;
            margin-top: -0px;
            border: 3px solid white !important;
            width: 100%;
        }

        #searchInput:hover {
            border: 3px solid black !important;
        }


        #uvod {
            background-color: white !important;
            border-radius: 10px;
            padding: 20px;
            border: 3px solid rgb(60, 63, 77);
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


        form textarea {
            margin-left: 4px !important;
        }

        #mojidrop {
            min-width: 1000px;
        }

        #project form {
            width: 100%;
        }

        #popis {
            width: 100%;
            margin-left: -10px;
            padding-bottom: 0px !important;
        }

        #favdrop {
            min-width: 900px !important;
            margin-left: -10px !important;
        }

        #favDIV {
            width: 40%;
            margin-right: -20px;
        }



        #objavaDIV {
            width: 100% !important;
        }

        #obavijestiNAV::after {
            <?php if ($brojac != 0): ?>
                content: "<?php echo $brojac; ?>";
            <?php else: ?>
                display: none;
            <?php endif; ?>
        }


        @media (max-width: 575.98px) {
            #traka {
                order: 2;
            }

            #uvod {
                order: 1;
                margin-bottom: 20px;
            }

            #moji article {
                margin-left: 0px !important;
                width: 100% !important;
            }

            #mojidrop {
                min-width: 0px !important;
            }

            #objava {
                width: 100%;
            }

            #objava form {
                width: 100%;
                min-width: 100%;
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
            }



            #objava {
                justify-content: center;
            }

            #moji {
                flex-direction: column;
                width: 100%;
                min-width: none;
            }

            #ukupno {
                flex-direction: column;
            }

            #traka button {
                width: 95.5%;
                margin-right: 0px !important;
            }



            #objavaDIV {
                width: 100% !important;
                margin-right: -10px !important;
            }

            #favDIV {
                width: 100%;
            }


            #fav {
                flex-direction: column;
                min-width: 0px;
            }

            #fav article {
                width: 100%;
                margin-left: 0px
            }

            #favdrop {
                min-width: 0px !important;
                margin-left: -50px !important;
                align-items: center;
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
        <nav class="navbar navbar-dark navbar-expand-lg ">
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
                <div class="container d-flex" id="popis">
                    <div class="dropdown" id="objavaDIV">
                        <button type="button" class="btn btn-light btn-lg dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false" data-bs-auto-close="outside">
                            OBJAVI
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <br>
                            <div class="container" id="objava">
                                <form method="post">
                                    <div class="container">
                                        <label for="title">Naslov:</label>
                                        <input type="text" id="title" name="title" required placeholder="Naslov"><br>
                                    </div>

                                    <div class="container">
                                        <label for="expiry">Trajanje:</label>
                                        <input type="date" id="expiry" name="expiry" required><br>
                                    </div>

                                    <div class="container">
                                        <label for="description">Opis:</label>
                                        <textarea id="description" name="description" rows="4" required></textarea><br>
                                    </div>

                                    <div class="container">
                                        <label for="roles">Traži se:</label>
                                        <textarea id="roles" name="roles" required
                                            placeholder="npr. Programer - Izrada mobilne aplikacije"></textarea><br>
                                    </div>

                                    <div class="container">
                                        <label for=" contact">Kontakt:</label>
                                        <input type="text" id="contact" name="contact" required>
                                    </div>
                                    <br>
                                    <input type="hidden" name="token" value="<?= $_SESSION['token'] ?? '' ?>">
                                    <input type="submit" class="btn btn-primary" name="objava" value="Objavi">
                                </form>
                            </div>
                            <br>
                        </ul>
                    </div>
                    <div class="dropdown" id="favDIV">
                        <button type="button" class="btn btn-light btn-lg dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false" data-bs-auto-close="outside">
                            <i class="fa-solid fa-heart"></i>
                        </button>
                        <ul class="dropdown-menu" id="favdrop" aria-labelledby="navbarDropdown">
                            <br>
                            <div class="articles container d-flex flex-wrap" id="fav">
                                <?php
                                $dbc = new mysqli($servername, $username, $password, $basename);
                                $query = "SELECT * FROM projects INNER JOIN saved ON projects.id = saved.idProject WHERE saved.idUser = ?";

                                $stmt = $dbc->prepare($query);
                                $stmt->bind_param("i", $_SESSION['idAuthor']);
                                $stmt->execute();

                                $result = $stmt->get_result();
                                $stmt->close();

                                $projectCounter = 0;
                                if (mysqli_num_rows($result) == 0) {
                                    echo '<p style="text-align:center;">Nemate spremljenih projekata.</p>';
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


                                    echo '<div class="container d-flex flex-direction-column justify-content-center">';

                                    echo '<button type="button" id="' . $projectCounter . '" onclick="spremi(\'' . $row['idProject'] . '\', this)" class="btn btn-light btn-lg" style="margin: 10px; width: 100%; color:#ff2600 !important;"><i class="fa-solid fa-heart"></i></button>';

                                    echo '<button type="button" onclick="showToast(\'Jeste li sigurni da želite prijaviti ovaj projekt?\', ' . $row['id'] . ')" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#exampleModal" style="margin: 10px; width: 100%;"><i class="fa-regular fa-circle-xmark"></i></button>';
                                    echo '</div>';

                                    echo '</div>';



                                    echo '<div class="container d-flex flex-direction-column justify-content-center">';
                                    echo '<button type="button" class="btn btn-light btn-lg toggle-btn" style="margin: 10px; width: 100%;" onclick="toggleDetails(this)"><i class="fa-solid fa-angle-down"></i></button>';
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
                    <button type="button" class="btn btn-light btn-lg dropdown-toggle" data-bs-toggle="dropdown"
                        aria-expanded="false" data-bs-auto-close="outside">
                        MOJI PROJEKTI
                    </button>
                    <ul class="dropdown-menu" id="mojidrop" aria-labelledby="navbarDropdown">
                        <br>
                        <div class="articles container d-flex flex-wrap" id="moji">
                            <?php

                            $dbc = new mysqli($servername, $username, $password, $basename);
                            $query = "SELECT * FROM projects WHERE idAuthor = ?";

                            $stmt = $dbc->prepare($query);
                            $stmt->bind_param("i", $_SESSION['idAuthor']);
                            $stmt->execute();

                            $result = $stmt->get_result();
                            $stmt->close();
                            $projectCounter = 0;
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

                                echo '<article class="artikl" style="background-color: ' . $bgColor . '; color: ' . $bgColor . ';">';
                                echo '<form method="POST">';
                                echo '<input type="hidden" name="project_id" value="' . $row['id'] . '">'; // Assuming 'id' is the primary key
                                echo '<h2><input type="text" name="title" value="' . htmlspecialchars($row['title']) . '"></h2>';
                                echo '
                                <p><b>TRAJANJE: </b><input type="date" name="expiry" value="' . htmlspecialchars($row['expiry']) . '"></p>';
                                echo '<p><b>OPIS: </b><textarea name="about">' . htmlspecialchars($row['about']) . '</textarea></p>';
                                echo '<p><b>TRAŽI SE: </b><input type="text" name="roles" value="' . htmlspecialchars($row['roles']) . '"></p>';
                                echo '<p><b>KONTAKT: </b><input type="text" name="contact" value="' . htmlspecialchars($row['contact']) . '"></p>';
                                echo '<input type="hidden" name="token" value="' . htmlspecialchars($_SESSION['token'] ?? '') . '">';
                                echo '<input type="submit" name="save" value="Spremi" style="padding-left: 0px; margin-left:3px; width:98%;"></input>';
                                echo '<input type="submit" name="delete" value="Izbriši" style="padding-left: 0px; margin-left:3px; width:98%;"></input>';
                                echo '</form>';
                                echo '</article>';
                                $projectCounter++;
                            }
                            ?>
                        </div>
                        <br>
                    </ul>
                </div>
                <div class="container">
                    <input type="text" id="searchInput" placeholder="Pretraži projekte...">
                </div>
            </div>

            <br>
            <div class="container" id="uvod">
                <h1>PROJEKTI</h1>
                <p>Dobrodošli na sekciju "Projekti". Ovdje imate mogućnosti:
                </p>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Pregledavanje ostalih i, objava, uređivanje i brisanje vlastitih
                        projekata</li>
                    <li class="list-group-item">Filtriranje pomoću tražilice</li>
                    <li class="list-group-item">Napomena: Projekti kojima je trajanje isteklo neće se prikazivati, te se
                        jednostavno obnove
                        tako što uredite vrijeme trajanja u sekciji "Moji Projekti".
                    </li>
                </ul>
            </div><br>

        </div>
        <br>
        <div class="container">
            <div class="articles container d-flex justify-content-center flex-wrap" id="projekt">
                <?php
                $dbc = new mysqli($servername, $username, $password, $basename);
                $query = "SELECT * FROM projects";
                $result = mysqli_query($dbc, $query);
                $projectCounter = 0;
                $bgColors = ["#3d59ab", "darkslateblue", "#00688b"];

                while ($row = mysqli_fetch_array($result)) {
                    $today = date("Y-m-d");
                    if ($row['expiry'] == $today) {

                        continue;
                    }

                    $bgColor = $bgColors[$projectCounter % count($bgColors)];

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
                    $query = "SELECT * FROM saved WHERE idProject = " . $row['id'] . " AND idUser = " . $_SESSION['idAuthor'] . "";
                    $result2 = mysqli_query($dbc, $query);

                    echo '<div class="container d-flex flex-direction-column justify-content-center">';
                    if (mysqli_num_rows($result2) > 0) {
                        echo '<button type="button" id="' . $projectCounter . '" onclick="spremi(\'' . $row['id'] . '\', this)" class="btn btn-light btn-lg" style="margin: 10px; width: 100%; color:#ff2600;"><i class="fa-solid fa-heart"></i></button>';
                    } else {
                        echo '<button type="button" id="' . $projectCounter . '" onclick="spremi(\'' . $row['id'] . '\', this)" class="btn btn-light btn-lg" style="margin: 10px; width: 100%;"><i class="fa-regular fa-heart"></i></button>';
                    }
                    if ($_SESSION['access'] == 3 || $_SESSION['access'] == 2) {
                        echo '<button type="button" onclick="showToast(\'Jeste li sigurni da želite izbrisati ovaj projekt?\', ' . $row['id'] . ')" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#exampleModal" style="margin: 10px; width: 100%;"><i class="fa-solid fa-trash-can"></i></button>';
                    } else {
                        echo '<button type="button" onclick="showToast(\'Jeste li sigurni da želite prijaviti ovaj projekt?\', ' . $row['id'] . ')" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#exampleModal" style="margin: 10px; width: 100%;"><i class="fa-regular fa-circle-xmark"></i></button>';
                    }
                    echo '</div>';

                    echo '</div>';


                    echo '<div class="container d-flex flex-direction-column justify-content-center">';
                    echo '<button type="button" class="btn btn-light btn-lg toggle-btn" style="margin: 10px; width: 100%;" onclick="toggleDetails(this)"><i class="fa-solid fa-angle-down"></i></button>';
                    echo '</div>';

                    echo '</article>';
                    $projectCounter++;
                }
                ?>


            </div>
        </div>
        <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 9999">
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
            function showToast(message, id) {
                if (id != 0) {
                    document.getElementById("project_id").value = id;
                    document.getElementById("reporting").style.display = "block";
                }
                var toastElement = new bootstrap.Toast(document.getElementById('liveToast'));
                document.querySelector('.toast-body').innerText = message;
                toastElement.show();

            }
            $(document).ready(function () {
                $("#searchInput").on("keyup", function () {
                    var value = $(this).val().toLowerCase();
                    $.ajax({
                        method: 'POST',
                        url: 'searchajax.php',
                        data: { search: value },
                        success: function (response) {
                            $("#projekt").html(response);
                        }
                    });

                });
            });



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
            function toggleDetails(button) {

                var icon = button.querySelector('.fa-solid ');
                var article = button.closest('.artikl');

                var details = article.querySelector('.project-details');


                if (details.style.display === 'none' || details.style.display === '') {
                    hideOtherDetails(article);
                    details.style.display = 'block';
                    details.style.height = 'auto';
                    var height = details.offsetHeight;
                    details.style.height = '0';
                    setTimeout(function () {
                        details.style.height = height + 'px';
                    }, 0);
                    icon.className = 'fa-solid fa-angle-up';
                } else {

                    icon.className = 'fa-solid fa-angle-down';

                    details.style.height = '0';
                    details.addEventListener('transitionend', function () {
                        details.style.display = 'none';
                    }, { once: true });
                }
            }

            function hideOtherDetails(article) {
                // Set all other details to collapse
                var allDetails = document.querySelectorAll('.project-details');
                allDetails.forEach(function (otherDetails) {
                    if (otherDetails.closest('.artikl') !== article && otherDetails.style.display !== 'none') {
                        otherDetails.style.height = '0';
                        otherDetails.addEventListener('transitionend', function () {
                            otherDetails.style.display = 'none';
                        }, { once: true });
                    }
                });
            }
        </script>
        <script>
            function spremi(idProject, clickedButton) {
                $.ajax({
                    type: 'POST',
                    url: 'spremi_ajax.php',
                    data: {
                        idProject: idProject,
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

        <script>
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        </script>
        <script src="js/javasc.js"></script>





    </main>

    <footer>
        <p>Ivo Baćilo - Organizacija I Informatizacija Ureda, TVZ Završni Rad</p>

    </footer>

</body>