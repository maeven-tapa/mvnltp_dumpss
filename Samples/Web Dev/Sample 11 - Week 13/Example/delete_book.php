<?php
include("db.php");

if (isset($_GET['BookID'])) {
    $id = mysqli_real_escape_string($conn, $_GET['BookID']);

    $result = mysqli_query($conn, "DELETE FROM books WHERE BookID = '$id'");

    if ($result) {
        header("Location: home_admin.php");
        exit();
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>
