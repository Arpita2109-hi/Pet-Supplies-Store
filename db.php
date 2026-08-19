<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "userdetails"; 

$conn = mysqli_connect($servername, $username, $password, $database);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");


$createProductsTable = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    description TEXT,
    image VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!mysqli_query($conn, $createProductsTable)) {
    die("Could not create products table: " . mysqli_error($conn));
}

$countResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products");
$countRow = $countResult ? mysqli_fetch_assoc($countResult) : ["total" => 0];

if ((int)$countRow["total"] === 0) {
    $seedProducts = [
        ["Pedigree Adult Dry Dog Food", "Food & Treats", 1200, 1, "Complete roasted chicken and vegetable dry food for adult dogs - 3kg pack.", "images/dog-food.jpg"],
        ["Purina Friskies Adult Cat Food", "Food & Treats", 950, 1, "Complete dry cat food with fish and seafood for adult cats - 2.7kg pack.", "images/cat-food.jpg"],
        ["Round Knitted Pet Bed", "Beds & Furniture", 1850, 1, "Soft pet bed with a non-slip base. Available in several colours.", "images/pet-bed.jpg"],
        ["Ultra Moisturising Shampoo", "Grooming", 650, 0, "Deeply cleans and moisturises your pet's coat, leaving it soft and fresh.", "images/pet-shampoo.jpg"],
        ["Corn-Shaped Dental Dog Chew Toy", "Toys", 450, 0, "Durable chew toy that helps clean teeth and keeps dogs entertained.", "images/chew-toy.jpg"],
        ["Automatic Interactive Laser Cat Toy", "Toys", 750, 0, "Rechargeable laser toy with three play modes and 360-degree rotation.", "images/cat-toy.jpg"],
        ["3-in-1 Steam Pet Grooming Brush", "Grooming", 500, 0, "Removes loose fur, detangles hair and gently massages dogs and cats.", "images/pet-brush.jpg"],
        ["Adjustable Leather Pet Collar", "Accessories", 350, 0, "Adjustable PU leather collar for dogs and cats in multiple sizes and colours.", "images/pet-collar.jpg"],
        ["Wardley Pond Fish Food Stix", "Food & Treats", 300, 0, "Complete daily food for healthy aquarium fish - 1.36kg pack.", "images/fish-food.jpg"]
    ];

    $seedStmt = mysqli_prepare($conn, "INSERT INTO products (name, category, price, featured, description, image) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($seedProducts as $product) {
        $name = $product[0];
        $category = $product[1];
        $price = $product[2];
        $featured = $product[3];
        $description = $product[4];
        $image = $product[5];
        mysqli_stmt_bind_param($seedStmt, "ssdiss", $name, $category, $price, $featured, $description, $image);
        mysqli_stmt_execute($seedStmt);
    }
    mysqli_stmt_close($seedStmt);
}

// Customer wishlist table.
$createWishlistTable = "CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist_item (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!mysqli_query($conn, $createWishlistTable)) {
    die("Could not create wishlist table: " . mysqli_error($conn));
}

// Orders used by customer checkout and the existing admin order screen.
$createOrdersTable = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(150) NOT NULL,
    customer_email VARCHAR(190) NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    user_id INT NULL,
    phone VARCHAR(40) NULL,
    fulfilment_method VARCHAR(20) NOT NULL DEFAULT 'delivery',
    delivery_address VARCHAR(255) NULL,
    payment_method VARCHAR(30) NOT NULL DEFAULT 'cod',
    promo_code VARCHAR(50) NULL,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0,
    shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!mysqli_query($conn, $createOrdersTable)) {
    die("Could not create orders table: " . mysqli_error($conn));
}

// Add new checkout columns when an older orders table already exists.
function addColumnIfMissing(mysqli $conn, string $table, string $column, string $definition): void {
    $tableSafe = mysqli_real_escape_string($conn, $table);
    $columnSafe = mysqli_real_escape_string($conn, $column);
    $check = mysqli_query($conn, "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$tableSafe' AND COLUMN_NAME = '$columnSafe'");
    $exists = $check ? (int)mysqli_fetch_assoc($check)['total'] > 0 : false;
    if (!$exists) {
        mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}
addColumnIfMissing($conn, 'orders', 'user_id', 'INT NULL');
addColumnIfMissing($conn, 'orders', 'phone', 'VARCHAR(40) NULL');
addColumnIfMissing($conn, 'orders', 'fulfilment_method', "VARCHAR(20) NOT NULL DEFAULT 'delivery'");
addColumnIfMissing($conn, 'orders', 'delivery_address', 'VARCHAR(255) NULL');
addColumnIfMissing($conn, 'orders', 'payment_method', "VARCHAR(30) NOT NULL DEFAULT 'cod'");
addColumnIfMissing($conn, 'orders', 'promo_code', 'VARCHAR(50) NULL');
addColumnIfMissing($conn, 'orders', 'discount', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
addColumnIfMissing($conn, 'orders', 'shipping_fee', 'DECIMAL(10,2) NOT NULL DEFAULT 0');

$createOrderItemsTable = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    line_total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!mysqli_query($conn, $createOrderItemsTable)) {
    die("Could not create order_items table: " . mysqli_error($conn));
}

?>
