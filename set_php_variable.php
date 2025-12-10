<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPorukaValue = $_POST['poruka'];


    $poruka = $newPorukaValue;


    echo 'PHP variable updated successfully';
} else {

    http_response_code(405);
    echo 'Invalid request method';
}
?>