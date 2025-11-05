<?php
if (isset($_POST['submit'])) {
    include 'db.php';

    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $publisher_name = mysqli_real_escape_string($conn, $_POST['publisher_name']);
    $copyright_year = intval($_POST['copyright_year']); // Ensure integer

    $sql = "INSERT INTO books (Title, Author, PublisherName, CopyrightYear) VALUES ('$title', '$author', '$publisher_name', $copyright_year)";

    if (mysqli_query($conn, $sql)) {
        echo "<p style='color: green;'>Book added successfully!</p>";
    } else {
        echo "<p style='color: red;'>Error adding book: " . mysqli_error($conn) . "</p>";
    }

    mysqli_close($conn); // Close connection after insertion
}
?>
