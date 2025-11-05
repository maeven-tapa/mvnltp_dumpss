<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books</title>
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
        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
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
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        a {
            display: inline-block;
            padding: 5px 10px;
            margin: 5px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        a:hover {
            background-color: #3e8e41;
        }
        a.delete {
            background-color: #f44336;
        }
        a.delete:hover {
            background-color: #da190b;
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
    <form id="bookForm" action="add_book.php" method="post">
        <h2 id="formTitle">Add New Book</h2>
        <table>
            <tr>
                <td><strong>Title:</strong></td>
                <td><input type="text" name="title" required /></td>
            </tr>
            <tr>
                <td><strong>Author:</strong></td>
                <td><input type="text" name="author" required /></td>
            </tr>
            <tr>
                <td><strong>Publisher Name:</strong></td>
                <td><input type="text" name="publisher_name" required /></td>
            </tr>
            <tr>
                <td><strong>Copyright Year:</strong></td>
                <td><input type="number" name="copyright_year" required /></td>
            </tr>
            <tr>
                <td colspan="2">
                    <input type="submit" name="submit" value="Add" />
                </td>
            </tr>
        </table>
    </form>
    <div id="message"></div>
    <table border="1">
        <thead>
            <tr>
                <th>BookID</th>
                <th>Title</th>
                <th>Author</th>
                <th>Publisher Name</th>
                <th>Copyright Year</th>
                <th>Edit</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
        <?php
        include("db.php");
        $result = mysqli_query($conn, "SELECT * FROM books");
        if (mysqli_num_rows($result) > 0) {
            while ($test = mysqli_fetch_assoc($result)) {
                $id = $test['BookID'];
                echo "<tr>";
                echo "<td>" . htmlspecialchars($test['BookID']) . "</td>";
                echo "<td>" . htmlspecialchars($test['Title']) . "</td>";
                echo "<td>" . htmlspecialchars($test['Author']) . "</td>";
                echo "<td>" . htmlspecialchars($test['PublisherName']) . "</td>";
                echo "<td>" . htmlspecialchars($test['CopyrightYear']) . "</td>";
                echo "<td><a href='edit_book.php?BookID=$id'>Edit</a></td>";
                echo "<td><a href='delete_book.php?BookID=$id' class='delete' onclick=\"return confirm('Are you sure you want to delete this book?');\">Delete</a></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No books found.</td></tr>";
        }
        mysqli_close($conn);
        ?>
        </tbody>
    </table>
</body>
</html>