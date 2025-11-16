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
    item_code VARCHAR(20) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (item_code)
);
	
CREATE TABLE orders (
    order_code VARCHAR(20) NOT NULL, 
    user_id INT NOT NULL,
    item_code VARCHAR(20) NOT NULL,
    quantity INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('reserved','completed','cancelled') NOT NULL DEFAULT 'reserved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (order_code),

    CONSTRAINT fk_order_item
        FOREIGN KEY (item_code)
        REFERENCES items(item_code)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


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