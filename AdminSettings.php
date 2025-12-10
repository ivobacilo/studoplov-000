<?php
// updateAdminSettings.php
include 'connect.php';
// Assuming you have a database connection established
// Include your database connection code here

// Check if the request is an AJAX request

// Get the values from the AJAX request
foreach ($_GET as $switchName => $switchValue) {
    // Sanitize the switch name to prevent SQL injection
    $switchName = filter_var($switchName, FILTER_SANITIZE_STRING);
    $dbc = mysqli_connect($servername, $username, $password, $basename) or die('Error connecting to MySQL server.' . mysqli_connect_error());
    $query = "UPDATE admin SET $switchName = ?";

    $stmt = mysqli_prepare($dbc, $query);
    if ($stmt === false) {
        die('Error preparing query.' . mysqli_error($dbc));
    }
    mysqli_stmt_bind_param($stmt, "i", $switchValue);

    // Execute the statement
    $result = mysqli_stmt_execute($stmt);

    // Check for success
    if ($result === false) {
        die('Error updating database.' . mysqli_error($dbc));
    }

    mysqli_stmt_close($stmt);
    mysqli_close($dbc);

}





