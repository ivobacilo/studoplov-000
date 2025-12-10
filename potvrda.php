<?php
session_start();
include 'connect.php';
include 'brojac.php';
if (!isset($_SESSION['name'])) {
    header("Location: index.php?notif=Niste prijavljeni");
    exit();
}


if (isset($_POST["verify_email"])) {
    $email = "";
    $email = $_GET["email"];
    $verification_code = $_POST["verification_code"];


    $conn = new mysqli($servername, $username, $password, $basename);

    if ($email == "") {
        header("Location: index.php?error=Niste prijavljeni.");
        exit();
    }


    $sql = "UPDATE users SET verification_code = 0 WHERE email = ? AND verification_code = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die('Error preparing statement: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "ss", $email, $verification_code);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);


    $rows_affected = mysqli_stmt_affected_rows($stmt);

    if ($rows_affected == 0) {
        session_unset();
        header("Location: index.php?notif=Vaša adresa nije uspješno potvrđena.");
        exit();
    } else {
        header("Location: index.php?notif=Vaša adresa je uspješno potvrđena.");
        exit();
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
        #obavijestiNAV::after {
            <?php if ($brojac != 0): ?>
                content: "<?php echo $brojac; ?>";
            <?php else: ?>
                display: none;
            <?php endif; ?>
        }

        form input {
            width: 300px;
            padding: 10px;
            margin: 5px;
            border-radius: 10px;
            border: 3px solid gray;
        }

        input[type="text"] {
            width: 410px;
            padding: 10px;
            margin: 5px;
            border-radius: 10px;
            border: 3px solid gray;
        }

        input[type="submit"] {
            width: 100px;
            padding: 10px;
            margin: 5px;
            border-radius: 10px;
            background-color: #f2f2f2;
            color: #2f2f2f;
            font-weight: bold;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script defer src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
        integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/5f61444440.js" crossorigin="anonymous"></script>
</head>

<body>
    <header>
        <nav class="navbar navbar-dark navbar-expand-lg ">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php" id="naslov">
                    <img src="slike/slike/novilogo.png" alt="" class="d-inline-block align-text-center">
                    Studoplov - Vaša Luka Informacija
                </a>
                <div id="navbarNav">
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
                </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="row">
                <div class="container">
                    <form method="POST">
                        <input type="hidden" name="email" required>
                        <input type="number" placeholder="Molimo unesite Vaš kod za autentifikaciju"
                            name="verification_code">
                        <input type="submit" value="Potvrdi" name="verify_email">
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <p>Studoplov - Vaša Luka Informacija</p>
                </div>
            </div>
        </div>
    </footer>
    <script src="js/javasc.js"></script>
</body>