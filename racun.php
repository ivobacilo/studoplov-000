<?php
session_start();
$error_message = "";
include 'connect.php';
include 'brojac.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require './mail/Exception.php';
require './mail/PHPMailer.php';
require './mail/SMTP.php';


if (isset($_POST['zaboravljena_lozinka'])) {
    $recovery_code = substr(number_format(time() * rand(), 0, '', ''), 0, 6);
    $email = $_POST["email"];



    try {
        $dbc = mysqli_connect($servername, $username, $password, $basename) or die('Error connecting to MySQL server.' . mysqli_error());


        $checkEmailQuery = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($dbc, $checkEmailQuery);

        if (mysqli_num_rows($result) == 0) {

            $error_message = "Vaš korisnički račun ne postoji.";
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
            $mail->addAddress($email);
            $mail->Subject = 'Oporavak zaporke Vaseg Studoplov racuna';

            $mail->msgHTML('Vaš kod za oporavak zaporke je:' . $recovery_code);
            $mail->AltBody = 'HTML messaging not supported';

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );


            if ($mail->send()) {

                $encrypted_password = password_hash($password, PASSWORD_DEFAULT);


                $sql = "UPDATE users SET recovery_code = '$recovery_code' WHERE email = '$email'";
                mysqli_query($dbc, $sql);

                $error_message = "Poruka sa šifrom oporavka je poslana na Vašu eAdresu.";
            } else {
                $error_message = "Pogreška pri slanju emaila:";
            }
        }
    } catch (Exception $e) {
        $error_message = "Pogreška";
    }
}

if (isset($_POST['resetPassword'])) {
    $newPassword1 = $_POST['newPassword1'];
    $newPassword2 = $_POST['newPassword2'];
    $recovery_code = $_POST['recovery_code'];

    try {
        $dbc = mysqli_connect($servername, $username, $password, $basename) or die('Error connecting to MySQL server.' . mysqli_error());


        $checkEmailQuery = "SELECT * FROM users WHERE recovery_code = '$recovery_code'";
        $result = mysqli_query($dbc, $checkEmailQuery);

        if (mysqli_num_rows($result) == 0) {

            $error_message = "Šifra oporavka nije ispravna.";
        } else {

            if ($newPassword1 != $newPassword2) {
                $error_message = "Lozinke se ne podudaraju.";
            } else {
                $encrypted_password = password_hash($newPassword1, PASSWORD_DEFAULT);

                $sql = "UPDATE users SET password = ? WHERE recovery_code = ?";
                $stmt = mysqli_prepare($dbc, $sql);
                mysqli_stmt_bind_param($stmt, 'ss', $encrypted_password, $recovery_code);
                mysqli_stmt_execute($stmt);

                $error_message = "Lozinka je uspješno promijenjena.";
            }
        }
    } catch (Exception $e) {
        $error_message = "Pogreška pri promjeni lozinke.";
    }
}

if (isset($_POST['changePassword'])) {
    $oldPassword = $_POST['oldPassword'];
    $newPassword1 = $_POST['newPassword1'];
    $newPassword2 = $_POST['newPassword2'];

    try {
        $dbc = mysqli_connect($servername, $username, $password, $basename) or die('Error connecting to MySQL server.' . mysqli_error());
        $email = $_SESSION['email'];

        if ($newPassword1 != $newPassword2) {
            $error_message = "Lozinke se ne podudaraju.";
        } else {

            $sql = "SELECT * FROM users WHERE email = '" . $email . "'";
            $result = mysqli_query($dbc, $sql);
            $user = mysqli_fetch_object($result);
            if (!password_verify($oldPassword, $user->password)) {
                $error_message = "Pogrešna lozinka.";
            } else {
                $encrypted_password = password_hash($newPassword1, PASSWORD_DEFAULT);

                $sql = "UPDATE users SET password = '$encrypted_password' WHERE email = '$email'";
                mysqli_query($dbc, $sql);

                $error_message = "Lozinka je uspješno promijenjena.";
            }
        }
    } catch (Exception $e) {
        $error_message = "Pogreška pri promjeni lozinke.";
    }
}

if (isset($_POST['deleteAccount'])) {
    $pass = $_POST['password'];
    $dbc = mysqli_connect($servername, $username, $password, $basename) or die('Error connecting to MySQL server.' . mysqli_error());
    $id = $_SESSION['idAuthor'];
    if ($email == "") {
        $error_message = "Niste prijavljeni.";
    } else {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $dbc->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_object();

        $stmt->close();

        if (!$user || !password_verify($pass, $user->password)) {
            $error_message = "Pogrešna lozinka.";
        } else {
            $sql = "DELETE FROM projects WHERE idAuthor = ?";
            $stmt = $dbc->prepare($sql);
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $sql = "DELETE FROM market WHERE idAuthor = ?";
            $stmt = $dbc->prepare($sql);
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $sql = "DELETE FROM saved WHERE idUser = ?";
            $stmt = $dbc->prepare($sql);
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $sql = "DELETE FROM events WHERE idAuthor = ?";
            $stmt = $dbc->prepare($sql);
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $dbc->prepare($sql);
            $stmt->bind_param("s", $id);
            $stmt->execute();


            $stmt->close();

            $error_message = "Vaš račun je uspješno izbrisan.";
        }

    }


}

if (isset($_POST['changeUsername'])) {
    $newUsername = $_POST['newUsername'];
    $pass = $_POST['currentPassword'];
    $dbc = mysqli_connect($servername, $username, $password, $basename) or die('Error connecting to MySQL server.' . mysqli_error());
    $id = $_SESSION['idAuthor'];
    if ($email == "") {
        $error_message = "Niste prijavljeni.";
    } else {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $dbc->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_object();

        $stmt->close();

        if (!$user || !password_verify($pass, $user->password)) {
            $error_message = "Pogrešna lozinka.";
        } else {
            $sql = "UPDATE users SET name = ? WHERE id = ?";
            $stmt = $dbc->prepare($sql);
            $stmt->bind_param("ss", $newUsername, $id);
            $stmt->execute();

            $stmt->close();

            $error_message = "Vaše korisničko ime je uspješno promijenjeno.";
        }
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
                content: "

                    <?php echo $brojac; ?>
                    ";

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


        input[type="text"],
        input[type="password"] {
            width: 90%;
            padding: 10px;
            margin: 5px;
            border-radius: 10px;
            border: 3px solid gray;
        }

        input[type="submit"] {
            width: fit-content !important;
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
                    <img src="slike/novilogo.png" alt="" class="d-inline-block align-text-center">
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
                                                    echo '<tr><td><li><a  href="projekt.php?id=' . $row['id'] . '">Vaš projekt: ' . $row['title'] . ' će isteći za ' . $dateDifference->format('%a') . ' dana.</a></li></td></tr>';

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
                                                    echo '<tr><td><li><a  href="market.php?id=' . $row['id'] . '">Vaš oglas: ' . $row['title'] . ' će isteći za ' . $dateDifference->format('%a') . ' dana.</a></li></td></tr>';

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

            <?php
            if (isset($_GET['cause']) && $_GET['cause'] == 'recovery') {
                echo '
                <div class="row">
                <div class="container">
                    <form method="POST">
                        <input type="text" name=" email" required placeholder="Molimo unesite Vašu AAI@EDU adresu">
                        <input type="submit" value="Pošalji kod" name="zaboravljena_lozinka">
                    </form><br>

                </div>
            </div>
                <form method="POST">
                <div class="mb-3">
                    <label for="newPassword1" class="form-label">Nova Lozinka:</label>
                    <input type="password" class="form-control" id="newPassword1" name="newPassword1" required>
                </div>
                <div class="mb-3">
                    <label for="newPassword2" class="form-label">Potvrdite Novu Lozinku:</label>
                    <input type="password" class="form-control" id="newPassword2" name="newPassword2" required>
                </div>
                <div class="mb-3">
                    <label for="verificationCode" class="form-label">Šifra Oporavka:</label>
                    <input type="text" class="form-control" id="recovery_code" name="recovery_code" required>
                </div>
                <input type="submit" name="resetPassword" value="Resetiraj lozinku"></input>

            </form>';
            } else if (isset($_GET['cause']) && $_GET['cause'] == 'change') {
                echo '<form method="POST">
                <div class="mb-3">
                    <label for="verificationCode" class="form-label">Stara Lozinka:</label>
                    <input type="text" class="form-control" id="oldPassword" name="oldPassword" required>
                </div>
                <div class="mb-3">
                    <label for="newPassword1" class="form-label">Nova Lozinka:</label>
                    <input type="password" class="form-control" id="newPassword1" name="newPassword1" required>
                </div>
                <div class="mb-3">
                    <label for="newPassword2" class="form-label">Potvrdite Novu Lozinku:</label>
                    <input type="password" class="form-control" id="newPassword2" name="newPassword2" required>
                </div>
                <input type="submit" name="changePassword" value="Promjeni lozinku"></input>
            </form><br>';
            } else if (isset($_GET['cause']) && $_GET['cause'] == 'delete') {
                echo '<form method="POST">
                <div class="mb-3">
                    <label for="verificationCode" class="form-label">Unesite Vašu lozinku:</label>
                    <input type="text" class="form-control" id="password" name="password" required>
                </div>
                <input type="submit" name="deleteAccount" value="Izbriši račun"></input>
            </form><br>';
            } else if (isset($_GET['cause']) && $_GET['cause'] == 'changeName') {
                echo '<form method="POST">
                <div class="mb-3">
                    <label for="currentPassword" class="form-label">Unesite Vašu lozinku:</label>
                    <input type="text" class="form-control" id="currentPassword" name="currentPassword" required>
                </div>
                <div class="mb-3">
                    <label for="verificationCode" class="form-label ">Unesite Vaše novo korisničko ime:</label>
                    <input type="text" class="form-control" id="newUsername" name="newUsername" required>
                </div>
                <input type="submit" name="changeUsername" value="Promjeni korisničko ime"></input>
            </form><br>';
            }
            ?>

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
    <script type="text/javascript">
        function showToast(message) {
            var toastElement = new bootstrap.Toast(document.getElementById('liveToast'));
            document.querySelector('.toast-body').innerText = message;
            toastElement.show();
        }

        <?php
        if (isset($error_message) && $error_message !== "") {
            echo "showToast('" . addslashes($error_message) . "');";
            $error_message = "";
        }
        ?>

    </script>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
    <script src="js/javasc.js"></script>
</body>