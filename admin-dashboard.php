<?php
session_start();
require_once "db.php";

if (
    !isset($_SESSION["user_id"]) ||
    strtolower($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: signin.html");
    exit();
}

$productCount = 0;
$featuredCount = 0;
$orderCount = 0;
$pendingCount = 0;
$messageCount = 0;
$unreadMessageCount = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM products"
);

if ($result) {
    $productCount = mysqli_fetch_assoc($result)["total"];
}

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM products
     WHERE featured = 1"
);

if ($result) {
    $featuredCount = mysqli_fetch_assoc($result)["total"];
}

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM orders"
);

if ($result) {
    $orderCount = mysqli_fetch_assoc($result)["total"];
}

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE status = 'pending'"
);

if ($result) {
    $pendingCount = mysqli_fetch_assoc($result)["total"];
}


mysqli_query($conn, "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM contact_messages");
if ($result) {
    $messageCount = (int)mysqli_fetch_assoc($result)["total"];
}
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM contact_messages WHERE status = 'unread'");
if ($result) {
    $unreadMessageCount = (int)mysqli_fetch_assoc($result)["total"];
}

$adminName = htmlspecialchars(
    $_SESSION["name"] ?? "Admin"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard | Happy Paws</title>

    <link
        rel="stylesheet"
        href="admin-dashboard.css?v=12"
    >

    <link rel="stylesheet" href="admin-header.css?v=1">
</head>

<body>

<?php
$pageTitle = "Admin Dashboard";
$headerButtonText = "+ Add Product";
$headerButtonLink = "admin-products.php?view=add";
include "admin-header.php";
?>

<div class="admin-page-layout">

    <aside class="sidebar">

        <div class="admin-panel-title">
            Admin Panel
        </div>

        <div class="admin-profile">

            <span class="admin-profile-icon">
                👤
            </span>

            <div>
                <small>Welcome</small>

                <strong>
                    Hi, <?php echo $adminName; ?>
                </strong>
            </div>

        </div>

        <nav class="sidebar-menu">

            <a
                class="active"
                href="admin-dashboard.php"
            >
                <span>⌂</span>
                Dashboard
            </a>

            <a href="admin-products.php?view=list">
           <span>▦</span>
             Products
            </a>

            <a href="admin-products.php?view=add">
           <span>＋</span>
           Add Product
           </a>

            <a href="manage-orders.php">
                <span>🛒</span>
                Orders
            </a>

            <a href="admin-messages.php">
                <span>✉</span>
                Messages<?php if ($unreadMessageCount > 0): ?> (<?php echo $unreadMessageCount; ?>)<?php endif; ?>
            </a>

            <a href="dashboard.php?preview=1">
                <span>◉</span>
                View Storefront
            </a>

            <a href="logout.php">
                <span>↪</span>
                Logout
            </a>

        </nav>

    </aside>

    <main class="content">

        <section class="cards">

            <article class="card">

                <h3>Total Products</h3>

                <strong>
                    <?php echo (int) $productCount; ?>
                </strong>

                <p>
                    Products currently stored in the database.
                </p>

            </article>

            <article class="card">

                <h3>Featured Products</h3>

                <strong>
                    <?php echo (int) $featuredCount; ?>
                </strong>

                <p>
                    Products displayed in the homepage featured section.
                </p>

            </article>

            <article class="card">

                <h3>Total Orders</h3>

                <strong>
                    <?php echo (int) $orderCount; ?>
                </strong>

                <p>
                    Customer orders stored in the database.
                </p>

            </article>

            <article class="card">

                <h3>Pending Orders</h3>

                <strong>
                    <?php echo (int) $pendingCount; ?>
                </strong>

                <p>
                    Orders waiting for admin processing.
                </p>

            </article>

            <article class="card">

                <h3>Contact Messages</h3>

                <strong>
                    <?php echo (int) $messageCount; ?>
                </strong>

                <p>
                    <?php echo (int) $unreadMessageCount; ?> unread customer message(s).
                </p>

            </article>

        </section>

        <section class="manage-section">

            <article class="manage-card">

                <span class="manage-icon">
                    📦
                </span>

                <div>

                    <h2>Manage Products</h2>

                    <p>
                        Add, edit, delete and search products.
                    </p>

                    <a href="admin-products.php?view=list">
                        Open Products
                    </a>

                </div>

            </article>

            <article class="manage-card">

                <span class="manage-icon">
                    🛒
                </span>

                <div>

                    <h2>Manage Orders</h2>

                    <p>
                        Process, complete, reject and delete orders.
                    </p>

                    <a href="manage-orders.php">
                        Open Orders
                    </a>

                </div>

            </article>

            <article class="manage-card">
                <span class="manage-icon">✉</span>
                <div>
                    <h2>Customer Messages</h2>
                    <p>Read, mark as read and delete Contact Us messages.</p>
                    <a href="admin-messages.php">Open Messages</a>
                </div>
            </article>

        </section>

    </main>

</div>

</body>
</html>