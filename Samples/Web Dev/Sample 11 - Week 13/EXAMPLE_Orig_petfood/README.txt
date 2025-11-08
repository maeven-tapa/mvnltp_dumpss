Pet Food Reservation Website — Database Setup and README
--------------------------------------------


Database name: petshop

1. Upload files to XAMPP/Laragon htdocs folder.
2. Start Apache and MySQL.
3. To start admin, type admin credentials on Login Page

Login:
Admin email = admin@petfoodplace.com
Admin password = admin123 (change this inside home_admin.php)

Admin can:
- view recent orders
- add/edit/delete pet food items

4. For users to buy items, you must first Sign Up. Then, Log-In your credentials to start buying pet foods.

Database Setup:

Step 1.)

Run the following SQL commands if you want to create tables manually:

CREATE DATABASE petshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE petshop;

Step 2.)

Type this command for the items and orders table.

CREATE TABLE IF NOT EXISTS items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_code VARCHAR(20) UNIQUE,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        stock INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
	
CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_code VARCHAR(20) UNIQUE,
        item_id INT NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        customer_contact VARCHAR(255) NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        status VARCHAR(20) NOT NULL DEFAULT 'reserved',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_code VARCHAR(20) UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (user_code, name, email, password, role)
VALUES ('ADM-0001', 'Administrator', 'admin@petfoodplace.com', MD5('admin123'), 'admin');