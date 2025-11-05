<?php
require("db.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books - View Only</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        table {
            width: 100%;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .no-books {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Book Management System</h1>
    
    <div id="message"></div>

    <table border="1">
        <thead>
            <tr>
                <th>BookID</th>
                <th>Title</th>
                <th>Author</th>
                <th>Publisher Name</th>
                <th>Copyright Year</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $result = mysqli_query($conn, "SELECT * FROM books");
        if (mysqli_num_rows($result) > 0) {
            while ($test = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($test['BookID']) . "</td>";
                echo "<td>" . htmlspecialchars($test['Title']) . "</td>";
                echo "<td>" . htmlspecialchars($test['Author']) . "</td>";
                echo "<td>" . htmlspecialchars($test['PublisherName']) . "</td>";
                echo "<td>" . htmlspecialchars($test['CopyrightYear']) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5' class='no-books'>No books found.</td></tr>";
        }
        mysqli_close($conn);
        ?>
        </tbody>
    </table>
</body>
</html>
