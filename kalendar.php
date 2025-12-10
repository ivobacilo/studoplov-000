<?php
session_start();
$_SESSION['token'] = bin2hex(random_bytes(32));
include 'connect.php';
include 'brojac.php';

if (!isset($_SESSION['name'])) {
    header("Location: index.php?notif=Niste prijavljeni");
    exit();
}

$html = file_get_contents('https://www.lisinski.hr/hr/dogadanja/');


if (isset($_POST['objava'])) {
    if ($_SESSION['permitEvents'] == 1) {
        $poruka = "Ova funkcija privremeno je onemogućena zbog održavanja stranice. Hvala na strpljenju!";
    } else {
        $conn = new mysqli($servername, $username, $password, $basename);
        $name = $_POST['name'];
        $expiry = $_POST['expiry'];
        $description = $_POST['description'];
        $link = $_POST['poveznica'];
        $idAuthor = $_SESSION['idAuthor'];
        $query = "INSERT INTO events (name, expiry, description, link, idAuthor) VALUES (?,?,?,?,?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssi", $name, $expiry, $description, $link, $idAuthor);
        $stmt->execute();
        $stmt->close();
        $poruka = "Uspješno ste objavili događaj!";
    }
}


if (isset($_POST['save'])) {
    if ($_SESSION['permitEvents'] == 1) {
        $poruka = "Ova funkcija privremeno je onemogućena zbog održavanja stranice. Hvala na strpljenju!";
    } else {
        $conn = new mysqli($servername, $username, $password, $basename);
        $id = $_POST['event_id'];
        $name = $_POST['title'];
        $expiry = $_POST['expiry'];
        $description = $_POST['about'];
        $link = $_POST['link'];
        $query = "UPDATE events SET name = ?, expiry = ?, description = ?, link = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssi", $name, $expiry, $description, $link, $id);
        $stmt->execute();
        $stmt->close();
        $poruka = "Uspješno ste ažurirali događaj!";
    }
}

if (isset($_POST['delete'])) {
    if ($_SESSION['permitEvents'] == 1) {
        $poruka = "Ova funkcija privremeno je onemogućena zbog održavanja stranice. Hvala na strpljenju!";
    } else {
        $conn = new mysqli($servername, $username, $password, $basename);
        $id = $_POST['event_id'];
        $query = "DELETE FROM events WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $poruka = "Uspješno ste izbrisali događaj!";
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
        main {
            background: linear-gradient(45deg, teal, navy);

        }

        form input {
            width: 300px;
            padding: 10px;
            margin: 5px;
            border-radius: 10px;
            border: 3px solid gray;
        }

        header {
            font-family: Arial, Helvetica, sans-serif !important;
            background-color: #1c3f60 !important;
        }

        #uvod {
            background-color: white !important;
            border-radius: 10px;
            padding: 20px;
            border: 3px solid blue;
            width: 100%;
            font-family: Arial, Helvetica, sans-serif !important;
        }


        input[type="text"],
        input[type="password"],
        input[type="date"],
        textarea {
            width: 100%;
            padding: 10px;
            margin: 5px;
            border-radius: 10px;
            border: 3px solid gray;
        }

        input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin: 5px;
            border-radius: 10px;
            background-color: #f2f2f2;
            color: #2f2f2f;
            font-weight: bold;
        }


        #calendar {
            margin-top: 50px;
            margin-bottom: 50px;
        }

        #slike img {
            width: 200px;
            height: 100px;
            margin: 10px;
        }

        #slike {
            flex-direction: column;
            width: 30%;
            background-color: #f2f2f2;
            border-radius: 10px;
            border: 3px solid blue;
            margin-right: 20px;
        }

        #searchInput {
            width: 100%;
            padding: 10px;
            margin: 5px;
            border-radius: 10px;
            border: 3px solid blue;
        }

        #search input {
            margin-left: 0px !important;
        }

        #obavijestiNAV::after {
            <?php if ($brojac != 0): ?>
                content: "<?php echo $brojac; ?>";
            <?php else: ?>
                display: none;
            <?php endif; ?>
        }


        @media (max-width: 575.98px) {
            #slike {
                flex-direction: column;
                width: 100%;
                align-items: center;
            }

            #pocetno {
                flex-direction: column;
            }

            #traka {
                flex-direction: column;
            }

            #traka button {
                width: 100% !important;
                margin: 0 auto;
            }

            #mojitipka {
                margin-left: 0px !important;
            }
        }

        #searchResults {
            z-index: 1000 !important;
            /* Adjust the value as needed */
            position: relative !important;
            /* Ensure the z-index property works */
        }

        #objava form,
        #moji form {
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
            padding: 20px;
        }

        form textarea {
            margin-left: 4px !important;
        }

        #traka div button {
            padding: 10px;
            border: 3px solid white;
            font-weight: bold;
            color: white;
            background-color: blue;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="css/evo-calendar.css">
    <link rel="stylesheet" type="text/css" href="css/evo-calendar.royal-navy.css">
    <script defer src="https://kit.fontawesome.com/5f61444440.js" crossorigin="anonymous"></script>

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
                                                echo '<tr><td><li><a  href="projekti.php?id=' . $row['id'] . '">Vaš projekt: ' . htmlspecialchars($row['title']) . ' će isteći za ' . $dateDifference->format('%a') . ' dana.</a></li></td></tr>';

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


        <script src="https://cdn.jsdelivr.net/npm/jquery@3.4.1/dist/jquery.min.js"></script>
        <script src="js/evo-calendar.js"></script>
        <div class="container d-flex" id="pocetno">
            <div class="container d-flex justify-content-center" id="slike">
                <img class="img-fluid" src="slike/ulaznicehr.png" />
            </div>
            <div class="container" id="uvod">
                <h1>KALENDAR</h1>
                <p>Na ovoj stranici možete vidjeti većinu događaja koji se održavaju u Zagrebu. Pritiskom na
                    zapis u kalendaru, bit ćete odvedeni na stranicu s više informacija o događaju. Događaji se
                    automatski
                    ažuriraju
                    pri svakom posjetu stranici.
                    Prolistajte kroz
                    kalendar, isplanirajte svoje vrijeme i zabavite se!
                </p>
                <div id="traka" class="container d-flex flex-direction-column">
                    <div class="dropdown" style="width:100%;" id="objavaDIV">
                        <button type="button" class="btn btn-light btn-lg dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false" data-bs-auto-close="outside">
                            OBJAVI DOGAĐAJ
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <br>
                            <div class="container" id="objava">
                                <form method="post">
                                    <div class="container">
                                        <label for="title">Naslov:</label>
                                        <input type="text" id="title" name="name" required placeholder="Naslov"><br>
                                    </div>

                                    <div class="container">
                                        <label for="expiry">Datum:</label>
                                        <input type="date" id="expiry" name="expiry" required><br>
                                    </div>

                                    <div class="container">
                                        <label for="description">Opis:</label>
                                        <textarea id="description" name="description" rows="4" required></textarea><br>
                                    </div>



                                    <div class="container">
                                        <label for="poveznica">Poveznica:</label>
                                        <input type="text" id="poveznica" name="poveznica" required>
                                    </div>
                                    <br>
                                    <input type="submit" name="objava" value="Objavi">
                                </form>
                            </div>
                            <br>
                        </ul>
                    </div>
                    <div class="dropdown" style="width:100%;">
                        <button type="button" class="btn btn-light btn-lg dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false" data-bs-auto-close="outside" id="mojitipka">
                            MOJI DOGAĐAJI
                        </button>
                        <ul class="dropdown-menu" id="mojidrop" aria-labelledby="navbarDropdown">
                            <br>
                            <div class="articles container d-flex flex-wrap" id="moji">
                                <?php

                                $dbc = new mysqli($servername, $username, $password, $basename);
                                $query = "SELECT * FROM events WHERE idAuthor = " . $_SESSION['idAuthor'] . "";
                                $result = mysqli_query($dbc, $query);
                                if ($result->num_rows > 0) {
                                    while ($row = mysqli_fetch_array($result)) {
                                        $today = date("Y-m-d");
                                        if ($row['expiry'] == $today) {

                                            continue;
                                        }


                                        echo '<article class="artikl">';
                                        echo '<form method="POST">';
                                        echo '<input type="hidden" name="event_id" value="' . $row['id'] . '">'; // Assuming 'id' is the primary key
                                        echo '<h2><input type="text" name="title" value="' . htmlspecialchars($row['name']) . '"></h2>';
                                        echo '
                                <p><b>TRAJANJE: </b><input type="date" name="expiry" value="' . $row['expiry'] . '"></p>';
                                        echo '<p><b>OPIS: </b><textarea name="about">' . htmlspecialchars($row['description']) . '</textarea></p>';
                                        echo '<p><b>POVEZNICA: </b><input type="text" name="link" value="' . htmlspecialchars($row['link']) . '"></p>';
                                        echo '<input type="submit" name="save" value="Spremi" style="padding-left: 0px; margin-left:3px; width:98%;"></input>';
                                        echo '<input type="submit" name="delete" value="Izbriši" style="padding-left: 0px; margin-left:3px; width:98%;"></input>';
                                        echo '</form>';
                                        echo '</article>';

                                    }
                                } else {
                                    echo "<h4>Nemate objavljenih događaja!</h4>";
                                }
                                ?>
                            </div>
                            <br>
                        </ul>
                    </div>
                    <div class="container" id="search">
                        <input type="text" id="searchInput" class="dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false" data-bs-auto-close="outside" oninput="searchEvents()"
                            placeholder="Pretraži događaje...">
                        <ul class="dropdown-menu container" aria-labelledby="navbarDropdown">
                            <div class="container" id="searchResults"></div>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div id="calendar"></div>
        <?php

        $html = file_get_contents("https://www.ulaznice.hr/web/events");
        error_reporting(0);
        $dom = new DOMDocument;
        $dom->loadHTML($html);
        $dom->encoding = 'UTF-8';
        libxml_use_internal_errors(false);
        $xpath = new DOMXPath($dom);
        $eventNodes = $xpath->query('//section[@class="eventGroup"]/article[@class="happeningContainer"]');

        $calendarEvents = [];
        $idCounter = 1;
        $firstSection = true;

        foreach ($eventNodes as $eventNode) {
            if ($firstSection) {
                $firstSection = false;
                continue;
            }
            $descNode = $xpath->query('.//div[@class="happeningInfoContainer"]/p/span[2]', $eventNode)->item(0)->nodeValue;
            $titleNode = $xpath->query('.//div[@class="happeningInfoContainer"]/h3[@class="smallTitle"]/a', $eventNode)->item(0)->nodeValue;
            $locationNode = $xpath->query('.//div[@class="happeningInfoContainer"]/p/span[1]', $eventNode)->item(0)->nodeValue;
            $locationNode .= $descNode;
            $linkNode = 'https://www.ulaznice.hr';
            $linkNode .= $xpath->query('.//div[@class="happeningInfoContainer"]/h3[@class="smallTitle"]/a/@href', $eventNode)->item(0)->nodeValue;
            $titleNode = strtoupper($titleNode);
            $titleNode = str_replace("Å¾", "Ž", $titleNode);
            $titleNode = str_replace("Å½", "Ž", $titleNode);
            $titleNode = preg_replace('/Å./u', 'Š', $titleNode);
            $titleNode = preg_replace('/Ä./u', 'C', $titleNode);
            $titleNode = str_replace("&TD", "", $titleNode);
            $titleNode = str_replace("Å¡", "Š", $titleNode);

            $locationNode = strtoupper($locationNode);
            $locationNode = str_replace("Å¾", "Ž", $locationNode);
            $locationNode = str_replace("Å½", "Ž", $locationNode);
            $locationNode = preg_replace('/Å./u', 'Š', $locationNode);
            $locationNode = preg_replace('/Ä./u', 'C', $locationNode);
            $locationNode = str_replace("&TD", "", $locationNode);
            $locationNode = str_replace("Å¡", "Š", $locationNode);

            if (stripos($locationNode, 'ZAGREB') !== false || stripos($locationNode, 'SAMOBOR') !== false || stripos($locationNode, 'VELIKA GORICA') !== false || stripos($locationNode, 'ZAPREŠIC') !== false || stripos($locationNode, 'ZAGREB') !== false || stripos($titleNode, 'ZAGREB') !== false || stripos($titleNode, 'ZAPRESIC') !== false || stripos($titleNode, 'ZELINA') !== false || stripos($titleNode, 'SAMOBOR') !== false) {


                $h2Node = $xpath->query('.//ancestor::section/header[@class="articleHeader"]/h2[@class="filter"]', $eventNode)->item(0);

                if ($h2Node !== null) {

                    $rawDate = substr($h2Node->nodeValue, 0, 11);



                    if (!empty($rawDate)) {

                        $formattedDate = DateTime::createFromFormat('d.m.Y.', $rawDate);

                        if ($formattedDate !== false) {
                            $formattedDate = $formattedDate->format('F/j/Y');
                        } else {

                            $formattedDate = 'Invalid Date';
                        }
                    } else {

                        $formattedDate = 'No Date';
                    }
                } else {

                    $formattedDate = 'No h2 Node';
                }

                $event = [
                    'id' => $idCounter++,
                    'name' => $titleNode,
                    'description' => $locationNode,
                    'date' => $formattedDate,
                    'type' => 'event',
                    'color' => '#63d867',
                    'link' => $linkNode,
                ];

                $calendarEvents[] = $event;
            } else {
                continue;
            }

        }


        $html = file_get_contents('https://www.lisinski.hr/hr/dogadanja/');

        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);


        $events_nodes = $xpath->query('//div[contains(@class, "table-wrap")]/table/tbody/tr');


        $mjeseci = [
            'siječnja' => '01',
            'veljače' => '02',
            'ožujka' => '03',
            'travnja' => '04',
            'svibnja' => '05',
            'lipnja' => '06',
            'srpnja' => '07',
            'kolovoza' => '08',
            'rujna' => '09',
            'listopada' => '10',
            'studenoga' => '11',
            'prosinca' => '12'
        ];

        foreach ($events_nodes as $node) {

            $title_node = $xpath->query('.//h2[@class="title"]/a', $node);
            if ($title_node->length == 0) {
                continue;
            }


            $name = trim($title_node->item(0)->textContent);
            $link = "https://www.lisinski.hr" . $title_node->item(0)->getAttribute('href');


            $day_node = $xpath->query('.//div[@class="day"]', $node);
            $month_year_node = $xpath->query('.//div[@class="month_year"]', $node);

            if ($day_node->length > 0 && $month_year_node->length > 0) {
                $day = trim($day_node->item(0)->textContent, '.');
                $month_year_parts = explode(' ', trim($month_year_node->item(0)->textContent));
                $month_name = mb_strtolower($month_year_parts[0]);
                $year = $month_year_parts[1];

                if (isset($mjeseci[$month_name])) {
                    $month = $mjeseci[$month_name];
                    $date = "$year-$month-$day";
                } else {
                    $date = 'N/A';
                }
            } else {
                $date = 'N/A';
            }


            $description_node = $xpath->query('.//div[@class="organizer"]', $node);
            $description = $description_node->length > 0 ? trim($description_node->item(0)->textContent) : 'N/A';


            $events[] = [
                'id' => uniqid(),
                'name' => $name,
                'date' => $date,
                'description' => $description,
                'link' => $link,
                'type' => 'lisinski'
            ];
        }
        error_reporting(1);


        $conn = new mysqli($servername, $username, $password, $basename);
        $query = "SELECT * FROM events";
        $result = mysqli_query($conn, $query);
        while ($row = mysqli_fetch_array($result)) {
            $today = date("Y-m-d");
            if ($row['expiry'] <= $today) {

                $que = "DELETE FROM events WHERE id = ?";

                $stmt = $conn->prepare($que);
                $stmt->bind_param("i", $row['id']);
                $stmt->execute();

                $stmt->close();
                continue;
            }

            $dateObject = DateTime::createFromFormat('Y-m-d', $row['expiry']);
            $formattedDate = $dateObject->format('F/j/Y');



            $link = $row['link'];
            if (!preg_match("~^(?:f|ht)tps?://~i", $link)) {
                $link = "https://" . $link;
            }


            $event = [
                'id' => $idCounter++,
                'name' => $row['name'],
                'description' => $row['description'],
                'date' => $formattedDate,
                'type' => 'event',
                'color' => '#c30010',
                'link' => $row['link'],
            ];

            $calendarEvents[] = $event;
            echo '<script>';
            echo 'console.log(' . json_encode($event) . ');';
            echo '</script>';
        }


        echo '<script>';
        echo '$(document).ready(function () {';
        echo '$("#calendar").evoCalendar({';
        echo '"language": "hr",';
        echo 'calendarEvents: ' . json_encode($calendarEvents) . ',';
        echo '});';
        echo '$("#calendar").evoCalendar("setTheme", "Royal Navy");';
        echo '});';
        echo '</script>';

        ?>
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


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>
    <script>
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
    </script>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
    <script>

        var calendarEvents = <?php echo json_encode($calendarEvents); ?>;


        function searchEvents() {
            var searchInput = document.getElementById('searchInput').value.trim().toLowerCase();
            var searchResults = document.getElementById('searchResults');
            searchResults.innerHTML = '';
            if (searchInput === '') return;

            console.log('Search Input:', searchInput);


            var table = document.createElement('table');
            table.classList.add('table', 'table-striped', 'search-results-table');


            var headerRow = table.insertRow(0);
            var titleHeader = headerRow.insertCell(0);
            var descriptionHeader = headerRow.insertCell(1);
            var dateHeader = headerRow.insertCell(2);
            titleHeader.textContent = 'NASLOV';
            descriptionHeader.textContent = 'OPIS';
            dateHeader.textContent = 'DATUM';


            calendarEvents.forEach(function (event) {
                console.log('Checking event:', event);


                if (event && event.name && event.description) {
                    if (event.name.toLowerCase().includes(searchInput) || event.description.toLowerCase().includes(searchInput)) {

                        var row = table.insertRow();
                        var titleCell = row.insertCell(0);
                        var descriptionCell = row.insertCell(1);
                        var dateCell = row.insertCell(2);


                        var anchor = document.createElement('a');
                        anchor.href = event.link;
                        anchor.textContent = event.name;


                        titleCell.appendChild(anchor);


                        descriptionCell.textContent = event.description;
                        dateCell.textContent = event.date;
                    }
                }
            });


            searchResults.appendChild(table);
        }

    </script>
    <script src="js/javasc.js"></script>

</body>