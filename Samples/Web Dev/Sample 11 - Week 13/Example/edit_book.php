<?php
require("db.php");

$id = $_GET['BookID'];
$result = mysqli_query($conn, "SELECT * FROM books WHERE BookID = '$id'");
$test = mysqli_fetch_array($result);

if (!$test) {
    die("Error: Data not found.");
}

$Title = $test['Title'];
$Author = $test['Author'];
$PublisherName = $test['PublisherName'];
$CopyrightYear = $test['CopyrightYear'];

if (isset($_POST['save'])) {
    $title_save = mysqli_real_escape_string($conn, $_POST['title']);
    $author_save = mysqli_real_escape_string($conn, $_POST['author']);
    $name_save = mysqli_real_escape_string($conn, $_POST['name']);
    $copy_save = mysqli_real_escape_string($conn, $_POST['copy']);

    $sql = "UPDATE books SET 
            Title = '$title_save',
            Author = '$author_save',
            PublisherName = '$name_save',
            CopyrightYear = '$copy_save'
            WHERE BookID = '$id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: home_admin.php");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Book</title>
</head>
<body>
    <h2>Edit Book</h2>
    <form method="post">
        <table>
            <tr><td>Title:</td><td><input type="text" name="title" value="<?php echo htmlspecialchars($Title); ?>"></td></tr>
            <tr><td>Author:</td><td><input type="text" name="author" value="<?php echo htmlspecialchars($Author); ?>"></td></tr>
            <tr><td>Publisher Name:</td><td><input type="text" name="name" value="<?php echo htmlspecialchars($PublisherName); ?>"></td></tr>
            <tr><td>Copyright Year:</td><td><input type="text" name="copy" value="<?php echo htmlspecialchars($CopyrightYear); ?>"></td></tr>
            <tr><td></td><td><input type="submit" name="save" value="Save"></td></tr>
        </table>
    </form>
</body>
</html>
