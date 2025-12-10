<?php
session_start();
$_SESSION['token'] = bin2hex(random_bytes(32));
include 'brojac.php';


function getSwitchValueFromDatabase($switchName)
{

    include 'connect.php';
    $dbc = mysqli_connect($servername, $username, $password, $basename) or die('Error connecting to MySQL server.' . mysqli_connect_error());
    $query = "SELECT $switchName FROM admin LIMIT 1";

    $result = mysqli_query($dbc, $query);

    if ($result === false) {
        die('Error fetching switch value from database.' . mysqli_error($dbc));
    }

    $row = mysqli_fetch_assoc($result);

    mysqli_close($dbc);

    // Return the default switch value
    return (int) $row[$switchName];
}
$permitProjects = getSwitchValueFromDatabase('projects');
$permitMarket = getSwitchValueFromDatabase('market');
$permitEvents = getSwitchValueFromDatabase('events');
$permitLogin = getSwitchValueFromDatabase('login');

$_SESSION['permitProjects'] = $permitProjects;
$_SESSION['permitMarket'] = $permitMarket;
$_SESSION['permitEvents'] = $permitEvents;
$_SESSION['permitLogin'] = $permitLogin;



include 'connect.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require './mail/Exception.php';
require './mail/PHPMailer.php';
require './mail/SMTP.php';
$error_message = "";
$cause = "";

$poruka = "";
if (isset($_GET['notif'])) {
    $poruka = $_GET['notif'];
} else if (isset($_GET['message'])) {
    $poruka = $_GET['message'];
} else {
    $poruka = "";
}






if (isset($_POST["register"])) {
    $verification_code = substr(number_format(time() * rand(), 0, '', ''), 0, 6);
    $name = $_POST["name"];
    $email = $_POST["email"];
    $pass = $_POST["password"];
    $dbc = new mysqli($servername, $username, $password, $basename);
    if (isset($_POST['g-recaptcha-response'])) {
        $captcha = $_POST['g-recaptcha-response'];
        if (!$captcha) {
            $poruka = "Molimo potvrdite captcha-u.";
        } else {
            $secret = "GOOGLE_SECRET";
            $url = "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$captcha";
            $response = file_get_contents($url);
            $responseKeys = json_decode($response, true);
            if ($responseKeys["success"]) {
                try {


                    if ($dbc->connect_error) {
                        die('Error connecting to MySQL server: ' . $dbc->connect_error);
                    }


                    $checkEmailQuery = "SELECT * FROM users WHERE email = ?";
                    $checkEmailStmt = $dbc->prepare($checkEmailQuery);
                    $checkEmailStmt->bind_param("s", $email);
                    $checkEmailStmt->execute();
                    $result = $checkEmailStmt->get_result();

                    if ($result->num_rows > 0) {

                        $poruka = "Vaš korisnički račun već postoji.";
                    } else {

                        $mail = new PHPMailer;
                        $mail->isSMTP();
                        $mail->SMTPDebug = 0;
                        $mail->Host = 'smtp.gmail.com';
                        $mail->Port = 465;
                        $mail->SMTPSecure = 'ssl';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'ivo.bacilo1@gmail.com';
                        $mail->Password = 'GMAIL_API';
                        $mail->setFrom('ivo.bacilo1@gmail.com', 'Studoplov - Vasa Luka Informacija');
                        $mail->addAddress($email, $name);
                        $mail->Subject = 'Potvrda Studoplov Profila';
                        $mail->msgHTML('Kod za potvrdu Vašeg računa je: ' . $verification_code);
                        $mail->AltBody = 'HTML messaging not supported';

                        $mail->SMTPOptions = array(
                            'ssl' => array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            )
                        );
                        if ($mail->send()) {

                            $encrypted_password = password_hash($pass, PASSWORD_DEFAULT);

                            $request_date = date("Y-m-d");
                            $insertQuery = "INSERT INTO users(name, email, password, verification_code, recovery_code, request_date, access_level) VALUES (?, ?, ?, ?, ?,?, ?)";
                            $insertStmt = $dbc->prepare($insertQuery);
                            $recovery_code = 0;
                            $access_level = 1;
                            $insertStmt->bind_param("sssssss", $name, $email, $encrypted_password, $verification_code, $recovery_code, $request_date, $access_level);
                            $insertStmt->execute();

                            $poruka = "Kod za potvrdu Vašeg računa je poslan.";
                            $_SESSION['message'] = $poruka;
                            header("Location: {$_SERVER['REQUEST_URI']}", true, 303);
                        } else {
                            $poruka = "Pogreška pri slanju e-maila.";

                        }
                    }
                } catch (Exception $e) {
                    $poruka = "Pogreška pri slanju emaila:" . $e->getMessage();
                } finally {
                    if (isset($dbc)) {
                        $dbc->close();
                    }
                }
            }
        }
    }

}


if (isset($_POST["login"])) {
    if ($_SESSION['permitLogin'] == 1) {
        $poruka = "Prijave su trenutno onemogućene.";
        ;
    } else {
        $poruka = "";
        $email = $_POST["email"];
        $pass = $_POST["password"];

        $conn = new mysqli($servername, $username, $password, $basename);
        $idAuthor = 0;


        if ($conn->connect_error) {
            die('Error connecting to MySQL server: ' . $conn->connect_error);
        }


        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $poruka = "Molimo registrirajte se.";
        } else {
            $user = $result->fetch_object();
            if (password_verify($pass, $user->password)) {
                if ($user->verification_code != 0) {
                    $URL = "potvrda.php?email=";
                    $URL .= $email;
                    echo "<script type='text/javascript'>document.location.href='{$URL}';</script>";
                    echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $URL . '">';
                }

                if (isset($_POST['zapamti_me'])) {
                    setcookie('remember_email', $email, time() + (86400 * 30), "/"); // 86400 seconds = 1 day
                    setcookie('remember_password', $pass, time() + (86400 * 30), "/"); // Adjust the expiration time as needed
                }


                $sql = "SELECT name FROM users WHERE email = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $userData = $result->fetch_object();
                $_SESSION["email"] = $email;
                $_SESSION["name"] = $userData->name;
                $_SESSION["idAuthor"] = $user->id;
                $_SESSION["access"] = $user->access_level;
            } else {
                $poruka = "Pogrešna lozinka.";
            }
        }

        $stmt->close();
        $conn->close();
    }
}



if (isset($_POST['odjava'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}


if (isset($_POST['promjena_lozinke'])) {
    header("Location: racun.php?cause=change");
    exit();
}

if (isset($_POST['brisanje_racuna'])) {
    header("Location: racun.php?cause=delete");
    exit();
}


if (isset($_POST['promjena_imena'])) {
    header("Location: racun.php?cause=changeName");
    exit();
}
//BROJANJE VIJESTI

if (isset($_POST['ažur'])) {
    $lastVersionSql = "SELECT version FROM updates ORDER BY id DESC LIMIT 1";
    $lastVersionResult = mysqli_query($dbc, $lastVersionSql);
    $lastVersionRow = mysqli_fetch_assoc($lastVersionResult);

    // Increment the version number by 0.1
    $lastVersion = $lastVersionRow['version'];
    $newVersion = $lastVersion + 0.1;
    $sql = "INSERT INTO updates (description, expiry, version) VALUES (?, ?, ?)";
    $stmt = mysqli_stmt_init($dbc);
    if (mysqli_stmt_prepare($stmt, $sql)) {
        $description = $_POST['ažuriranje'];
        $expiry = new DateTime();
        $expiry->modify('+5 days');
        // Convert DateTime object to string
        $expiryString = $expiry->format('Y-m-d'); // Adjust the format as needed
        mysqli_stmt_bind_param($stmt, 'sss', $description, $expiryString, $newVersion);
        mysqli_stmt_execute($stmt);
    }
    $poruka = "Ažuriranje uspješno.";
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

        #error-message {
            width: 300px !important;
            font-size: 30px !important;
            font-weight: bold !important;
            margin-bottom: 10px !important;
        }

        #karousel p {
            color: #2f2f2f;
            font-size: 30px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        form input {
            width: 300px;
            padding: 10px;
            margin: 5px;
            border-radius: 10px;
            border: 3px solid gray;
        }

        #reg {
            background-color: #2271b1;
            margin-bottom: -20px;
            padding: 10px;
            color: white;
        }


        #prijava {
            background-color: #2271b1;
            padding: 15px;
            color: white;
            font-weight: bold;
            font-size: 20px;
            padding-right: 25px;
        }

        #reg h2,
        #reg p {
            padding-left: 20px;
            padding-top: 10px;
        }

        #prijava h2,
        #prijava p {
            padding-left: 20px;
            padding-top: 10px;
            color: white;
        }

        #harmonika {
            background-color: #f2f2f2;
            padding: 20px;
            border-radius: 20px;
            border: 3px solid gray;
            color: black;
        }

        input[type="submit"] {
            color: black;
            font-weight: bold;
        }

        #harmonika form {
            margin-top: -34px !important;
        }

        #harmonika input[type="submit"] {
            color: white !important;
        }


        #karosel h5 {
            font-size: 35px;
        }

        #karosel img {
            height: 650px;
        }

        .py-2 {
            padding-right: 10px;
        }

        #opis {
            background-color: #f2f2f2;
            padding: 20px;
            border-radius: 20px;
            border: 3px solid gray;
            color: black;
        }

        #opis img {
            border-radius: 10%;
        }

        .g-recaptcha {
            margin-left: 4px;
            width: 96% !important;
        }

        #obavijestiNAV::after {
            <?php if ($brojac != 0): ?>
                content: "<?php echo $brojac; ?>";
            <?php else: ?>
                display: none;
            <?php endif; ?>
        }

        #dropdownCheck {
            margin-left: 0px !important;
            padding-left: 0px !important;
        }

        #pauziraj {
            margin-right: 10px;
        }

        #ažuriranje {
            margin-left: 10px;
            margin-right: 10px;
            width: 90%;
        }

        #ažuriraj {
            margin-right: 10px;
        }

        #ažuriraj input[type="submit"] {
            color: white !important;
            margin-left: 10px;
            margin-top: 10px;
            width: 60%;
        }

        #reported button {
            margin-left: 10px;
        }

        @media (max-width: 575.98px) {
            #prijava {
                flex-direction: column;
                align-items: center;
            }

            #prijava div {
                margin: 0 auto;
                padding: 0px;
            }


            #karosel img {
                height: 350px;
            }

            #opis {
                flex-direction: column;
            }

            #harmonika {
                flex-direction: column;
            }

            #harmonika form {
                text-align: center;
                margin-left: -8px;
            }

            #harmonika form input {
                width: 100%;
            }

            #harmonika form input[type="submit"] {
                margin-bottom: 30px;
                padding-left: 0px;
            }

            #opis img {
                width: 100% !important;
                margin: 0px !important;
            }

            .form-check .form-check-input {
                margin-left: 0px !important;
            }



            #obavijesti {
                width: 90%;
                min-width: 0px;
                margin-left: 0px !important;
            }

            #tablica {
                max-width: 400px;
            }

            #pauziraj {
                width: 90%;
                margin-left: 18px !important;
                margin-top: -20px;
            }

            #pauziraj button {
                margin-left: -18px;
            }

            .DROP {
                width: 300px !important;
            }

            .pauziraj {
                margin-left: 10px !important;
            }

            #overall div {
                margin-right: 10px;

            }

            #overall {
                max-width: 400px;
            }

            #reported button {
                margin-top: 60px !important;
                margin-left: -347px !important;
                min-width: 335px !important;
            }

            #prijavljeno button {
                margin-bottom: -18px;
                padding-left: 30px;
                padding-right: 26px;
            }
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
                                                $currentDate = new DateTime();
                                                if ($expiryDate > $currentDate) {
                                                    echo '<tr><td><li><h5>Obavijesti o projektima</h5></li></td></tr>';
                                                    $dateDifference = date_diff($currentDate, $expiryDate);
                                                    echo '<tr><td><li><a  href="projekti.php?id=' . $row['id'] . '">Vaš projekt: ' . htmlspecialchars($row['title']) . ' će isteći za ' . $dateDifference->format('%a') . ' dana.</a></li></td></tr>';
                                                }
                                            }
                                        }
                                    }

                                    $query = "SELECT * FROM market WHERE idAuthor = ? AND DATEDIFF(EXPIRY, NOW()) <= 5";
                                    $stmt = mysqli_stmt_init($dbc);
                                    if (mysqli_stmt_prepare($stmt, $query)) {
                                        mysqli_stmt_bind_param($stmt, 'i', $_SESSION['idAuthor']);
                                        mysqli_stmt_execute($stmt);
                                        $result = mysqli_stmt_get_result($stmt);
                                        if (mysqli_num_rows($result) > 0) {
                                            echo '<tr><td><li><h5>Obavijesti o oglasima</h5></li></td></tr>';
                                            while ($row = mysqli_fetch_array($result)) {
                                                $expiryDate = new DateTime($row['expiry']);
                                                $dateDifference = date_diff(new DateTime(), $expiryDate);
                                                echo '<tr><td><li><a  href="forum.php?id=' . $row['id'] . '">Vaš oglas: ' . htmlspecialchars($row['title']) . ' će isteći za ' . $dateDifference->format('%a') . ' dana.</a></li></td></tr>';
                                            }
                                        }
                                    }

                                    $query = "SELECT * FROM updates WHERE DATEDIFF(EXPIRY, NOW()) <= 5";
                                    $stmt = mysqli_stmt_init($dbc);
                                    if (mysqli_stmt_prepare($stmt, $query)) {
                                        mysqli_stmt_execute($stmt);
                                        $result = mysqli_stmt_get_result($stmt);
                                        if (mysqli_num_rows($result) > 0) {
                                            echo '<tr><td><li><h5>Obavijesti administratora</h5></li></td></tr>';
                                            while ($row = mysqli_fetch_array($result)) {
                                                echo '<tr><td><li><a>' . htmlspecialchars($row['description']) . '</a></li></td></tr>';
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
        <div id="carouselExampleCaptions" class="carousel slide">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active"
                    aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1"
                    aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="2"
                    aria-label="Slide 3"></button>
            </div>
            <div class="carousel-inner" id="karosel">
                <div class="carousel-item active">
                    <img src="slike/studenti.jpg" class="d-block w-100" alt="...">
                    <div class="carousel-caption ">
                        <h5>Suradnja</h5>
                        <p>Pronađi kolege-suradnike za svoj projekt, ili se prijavi na nečiji.</p>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="slike/market.jpg" class="d-block w-100" alt="...">
                    <div class="carousel-caption ">
                        <h5>Virtualni Student Market</h5>
                        <p>Saznaj. Razmjeni. Kupi. Prodaj.</p>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="slike/kalendar.jpg" class="d-block w-100" alt="...">
                    <div class="carousel-caption">
                        <h5>Kalendar Događaja</h5>
                        <p>Ne brini, nećeš ništa propustiti.</p>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions"
                data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions"
                data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
        <br>
        <div class="container" id="reg">
            <h2>Registracija</h2>
            <p>Registrirajte se kako biste mogli koristiti sve funkcionalnosti Studoplova.</p>
        </div>
        <br>

        <div class="container d-flex" id="harmonika">
            <form method="POST" onsubmit="return validate();">
                <br>
                <input type="text" name="name" id="name" placeholder="Unesite Vaše ime i prezime" required /><br>
                <input type="email" name="email" id="email" placeholder="Unesite Vašu AAI@EDU adresu" required /><br>
                <input type="password" name="password" id="password" placeholder="Unesite Vašu lozinku" required /><br>
                <input type="password" name="repeated" id="repeated" placeholder="Ponovo unesite Vašu lozinku"
                    required /><br>
                <div class="g-recaptcha" data-sitekey="DATA_SITEKEY"></div><br>
                <input type="submit" class="btn btn-primary" name="register" id="register" value="Registriraj se">
            </form><br>

            <div class="accordion container-sm" id="accordionPanelsStayOpenExample">
                <!-- Element 1: Projekti -->
                <div class="accordion-item" style="color:black;">
                    <h2 class="accordion-header" id="projekti-heading">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                            data-bs-target="#projekti-collapse" aria-expanded="true" aria-controls="projekti-collapse">
                            Projekti
                        </button>
                    </h2>
                    <div id="projekti-collapse" class="accordion-collapse collapse show"
                        aria-labelledby="projekti-heading">
                        <div class="accordion-body">
                            <p>Ova sekcija omogućuje suradnju između studenata različitih visokih učilišta.
                                Imate vještine iz ekonomije i menadžmenta, a treba vam programer? Ovdje možete
                                pronaći kolege-suradnike za svoj projekt, ili se prijaviti na nečiji. Sve što trebate
                                je napraviti profil i pronaći projekt koji vam odgovara, ili objaviti svoj.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Element 2: Kalendar -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="kalendar-heading">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#kalendar-collapse" aria-expanded="false" aria-controls="kalendar-collapse">
                            Kalendar
                        </button>
                    </h2>
                    <div id="kalendar-collapse" class="accordion-collapse collapse" aria-labelledby="kalendar-heading">
                        <div class="accordion-body">
                            <p>Ostanite informirani pomoću funkcije kalendara na našoj platformi. Pristupite
                                relevantnim
                                informacijama o studentskim obavijestima i raznim događajima, uključujući
                                rekreacijske,
                                edukativne i službene manifestacije.</p>
                        </div>
                    </div>
                </div>


                <div class="accordion-item">
                    <h2 class="accordion-header" id="forum-heading">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#forum-collapse" aria-expanded="false" aria-controls="forum-collapse">
                            Forum
                        </button>
                    </h2>
                    <div id="forum-collapse" class="accordion-collapse collapse" aria-labelledby="forum-heading">
                        <div class="accordion-body">
                            <p>Sudjelujte u raspravama unutar našeg foruma, s odjeljcima poput "Izgubljeno - Nađeno"
                                za
                                pronalaženje izgubljenih stvari i "Student Market" za kupnju i prodaju predmeta ili
                                oglašavanje usluga, uključujući prilike za instrukcije.</p>
                        </div>
                    </div>
                </div>


            </div>
        </div><br>
        <div class="container d-flex" id="opis">
            <div>
                <h5>Što je Studoplov?</h5>
                <p>Kao student Tehničkog Veleučilišta u Zagrebu, a ponajprije hrvatski student uvidio sam da ne postoji
                    platforma
                    za interakademsku suradnju s ekskluzivnim pristupom studentima. Nešto najbliže "svaštari" studenata
                    je grupa
                    Studentski dom "Stjepan Radić" na Facebooku, gdje se stvari prodaju, instrukcije oglašavaju, memovi
                    dijele, događaji oglašavaju
                    . Uvidjevši probleme s trenutnim funkcioniranjem grupe, poput
                    <i>zatrpavanja</i>
                    objava zbog nedostatka sekcija (universal feed), odlučio sam napraviti ovu platformu kao koncept
                    toga
                    što studentima treba. S tim na umu, moj moto uvijek će biti "od
                    studenta, za
                    studente".
                </p>
            </div>
            <img src="slike/sava.jpg" class="img-fluid" alt="" style="width: 300px; height: 300px; margin-left: 50px;">
        </div>
        <br>
        <div class="container d-flex" id="prijava">
            <?php if (!isset($_SESSION["email"])) {
                echo '<div class="dropdown">
                <button type="button" class="btn btn-light dropdown-toggle btn-lg" data-bs-toggle="dropdown"
                    aria-expanded="false" data-bs-auto-close="outside">
                    Već imate račun? - Prijavite se
                </button>
                <form class="dropdown-menu p-4" method="POST">
                    <div class="mb-3">
                        <label for="exampleDropdownFormEmail2" class="form-label">Vaša AAI@EDU adresa</label>
                        <input type="email" class="form-control" id="exampleDropdownFormEmail2" name="email"
                          value ="' . (isset($_COOKIE['remember_email']) ? htmlspecialchars($_COOKIE['remember_email']) : '') . '"   >
                    </div>
                    <div class="mb-3">
                        <label for="exampleDropdownFormPassword2" class="form-label">Lozinka</label>
                        <input type="password" class="form-control" id="exampleDropdownFormPassword2" placeholder=""
                            name="password" value="' . (isset($_COOKIE['remember_password']) ? htmlspecialchars($_COOKIE['remember_password']) : '') . '">
                    </div>
                    <div class="mb-3">
                        <div class="form-check" id="dropdownCheck">
                            <input type="checkbox" class="form-check-input" id="dropdownCheck2" name="zapamti_me">
                            <label class="form-check-label" for="dropdownCheck2">
                                Zapamti me
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" name="login">Prijava</button>
                </form>
            </div>';
            } else {
                echo '<div class="dropdown" id="prijavljeno">
                <button type="button" class="btn btn-light btn-lg dropdown-toggle" data-bs-toggle="dropdown"
                    aria-expanded="false" data-bs-auto-close="outside">
                    Prijavljeni ste kao - ' . $_SESSION["name"] . '
                </button>
                <ul class="dropdown-menu" aria-labelledby="navbarDropdown" style="width:250px; margin-left:-10px !important;">
                    <form method="POST" class="px-1 py-0" style="margin-left:-10px;" >
                        <li><input type="submit" class="dropdown-item" name="odjava" value="Odjava"></input></li>
                        <li><input type="submit" class="dropdown-item" name="promjena_lozinke" value="Promijeni lozinku"></input></li>
                        <li><input type="submit" class="dropdown-item" name="promjena_imena" value="Promijeni ime"></input></li>
                        <li><input type="submit" class="dropdown-item" name="brisanje_racuna" value="Izbriši račun"></input></li>
                    </form>
                </ul>
            </div><br>';
            }


            if (isset($_SESSION["access"])) {
                if ($_SESSION["access"] == 3) {
                    echo '
                    <div class ="flex d-flex"  id="overall">
                        <div class="dropdown" id="pauziraj" >
                            <button type="button" class="btn btn-light btn-lg dropdown-toggle" data-bs-toggle="dropdown"
                                aria-expanded="false" data-bs-auto-close="outside">
                            Administracija
                        </button>
                        <ul class="dropdown-menu" class="DROP" aria-labelledby="navbarDropdown">
                            <li>
                                <div class="form-check form-switch dropdown pauziraj">
                                    <input class="form-check-input" type="checkbox" role="switch" id="loginSwitch" name="login"';
                    if (getSwitchValueFromDatabase('login') == 1) {
                        echo ' checked';
                    }
                    echo '>
                                    <label class="form-check-label" for="loginSwitch">Onemogući prijavu</label>
                                </div>
                            </li>
                            <li>
                                <div class="form-check form-switch dropdown pauziraj">
                                    <input class="form-check-input" type="checkbox" role="switch" id="projectSwitch" name="projects"';
                    if (getSwitchValueFromDatabase('projects') == 1) {
                        echo ' checked';
                    }
                    echo '>
                                    <label class="form-check-label" for="projectSwitch">Onemogući projekte</label>
                                </div>
                            </li>
                            <li>
                                <div class="form-check form-switch dropdown pauziraj">
                                    <input class="form-check-input" type="checkbox" role="switch" id="marketSwitch" name="market"';
                    if (getSwitchValueFromDatabase('market') == 1) {
                        echo ' checked';
                    }
                    echo '>
                                    <label class="form-check-label" for="marketSwitch">Onemogući forum</label>
                                </div>
                            </li>
                            <li>
                                <div class="form-check form-switch dropdown pauziraj">
                                    <input class="form-check-input" type="checkbox" role="switch" id="eventSwitch" name="events"';
                    if (getSwitchValueFromDatabase('events') == 1) {
                        echo ' checked';
                    }
                    echo '>
                                    <label class="form-check-label" for="eventSwitch">Onemogući događaje</label>
                                </div>
                            </li>
                        </ul>
                    </div><br>';
                    echo '<div class="dropdown" id="ažuriraj">
                        <button type="button" class="btn btn-light btn-lg dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false" data-bs-auto-close="outside">
                            Ažuriranja
                        </button>
                        <ul class="dropdown-menu" class="DROP" aria-labelledby="navbarDropdown">
                            <li>
                                <div>
                                <form method="POST">
                                <textarea class="form-control" id="ažuriranje" rows="3" name="ažuriranje" placeholder="Unesite ažuriranje"></textarea>
                                <input type="submit" class="btn btn-primary" name="ažur" id="ažur" value="Ažuriraj">
                                </form>
                                </div>
                            </li>
                        </ul>
                    </div>';
                }
                if ($_SESSION["access"] == 2 || $_SESSION["access"] == 3) {
                    echo '<br><div class="dropdown" id="reported">
                        <button type="button" class="btn btn-light btn-lg dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false" data-bs-auto-close="outside">
                            Prijavljeno
                        </button>
                        <ul class="dropdown-menu" class="DROP" aria-labelledby="navbarDropdown">
                            <li>';
                    $sql = "SELECT * FROM projects WHERE reports > 0";
                    $result = mysqli_query($dbc, $sql);
                    if (mysqli_num_rows($result) > 0) {
                        echo '<a class="dropdown-item" href="#" style="font-weight:bold;">Prijavljeni projekti</a>';
                        echo '<div class="dropdown-divider"></div>';
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<a class="dropdown-item" href="projekti.php?id=' . $row['id'] . '">' . $row['title'] . '</a>';
                        }
                    } else {
                        echo '<div class="dropdown-divider"></div>';
                        echo '<a class="dropdown-item" href="#">Nema prijavljenih projekata</a>';
                    }
                    $sql = "SELECT * FROM market WHERE reports > 0";
                    $result = mysqli_query($dbc, $sql);
                    if (mysqli_num_rows($result) > 0) {
                        echo '<div class="dropdown-divider"></div>';
                        echo '<a class="dropdown-item" href="#" style="font-weight:bold;">Prijavljeni oglasi</a>';
                        echo '<div class="dropdown-divider"></div>';
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<a class="dropdown-item" href="forum.php?id=' . $row['id'] . '">' . $row['title'] . '</a>';
                        }
                    } else {
                        echo '<div class="dropdown-divider"></div>';
                        echo '<a class="dropdown-item" href="#">Nema prijavljenih oglasa</a>';
                    }
                    echo '</li>
                        </ul> 
                        </div>';
                    echo '</div>';
                }

            }
            ?>
        </div><br>
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
            </div>
        </div>
    </main>

    <footer>
        <p>Ivo Baćilo - Organizacija I Informatizacija Ureda, TVZ Završni Rad</p>
    </footer>
    <script type="text/javascript">
        function showToast(message) {
            var toastElement = new bootstrap.Toast(document.getElementById('liveToast'));
            document.querySelector('.toast-body').innerText = message;
            toastElement.show();
        }

        <?php
        if (isset($poruka) && $poruka !== "") {
            echo "showToast('" . addslashes($poruka) . "');";
            $poruka = "";
        }
        ?>
        function validate() {
            var name = document.getElementById('name').value;
            var email = document.getElementById('email').value;
            var password = document.getElementById('password').value;
            var repeatedPassword = document.getElementById('repeated').value;


            if (name === "") {
                showToast("Molimo unesite vaše ime!");
                document.getElementById('name').focus();
                return false;
            }

            if (email === "") {
                showToast("Molimo unesite vašu email adresu!");
                document.getElementById('email').focus();
                return false;
            }


            if (password.length < 8) {
                showToast("Lozinka mora imati barem 8 znakova.");
                document.getElementById('password').focus();
                return false;
            }


            if (password !== repeatedPassword) {
                showToast("Lozinke se ne podudaraju.");
                document.getElementById('repeated').focus();
                return false;
            }

            return true;
        }
    </script>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
    <?php if (isset($_SESSION["idAuthor"])) {
        echo '
    <script type="text/javascript">
        $(document).ready(function () {
            //Check if the current URL contains 
            if (document.URL.indexOf("#") == -1) {
               
                url = document.URL + "#";
                location = "#";

    
                location.reload(true);
            }
        });
    </script>';
    }
    ?>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
    <script src="js/javasc.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var checkboxes = document.querySelectorAll('.pauziraj input[type="checkbox"]');

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {

                    var switchName = checkbox.name;
                    var switchValue = checkbox.checked ? 1 : 0;


                    if (checkbox.getAttribute('data-last-state') !== switchValue.toString()) {

                        checkbox.setAttribute('data-last-state', switchValue);


                        var formData = new FormData();


                        formData.append(switchName, switchValue);


                        var xhr = new XMLHttpRequest();


                        xhr.onreadystatechange = function () {
                            if (xhr.readyState === 4 && xhr.status === 200) {

                                console.log(xhr.responseText);
                            }
                        };


                        xhr.open('GET', 'AdminSettings.php?' + new URLSearchParams(formData).toString(), true);
                        xhr.send();
                    }
                });


                checkbox.setAttribute('data-last-state', checkbox.checked ? '1' : '0');
            });
        });
    </script>





</body>

</html>