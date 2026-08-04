<?php
session_start();
require_once "db.php";
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: signin.html");
    exit();
}
$productCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM products"))["total"] ?? 0;
$featuredCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM products WHERE featured = 1"))["total"] ?? 0;
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Admin Dashboard | Happy Paws</title><link rel="stylesheet" href="admin-dashboard.css"></head><body>
<aside class="sidebar"><h2>🐾 Happy Paws</h2><p>Admin Panel</p><a class="active" href="admin-dashboard.php">Dashboard</a><a href="admin-products.php">Products</a><a href="admin-products.php#product-form">Add Product</a><a href="manage-orders.php">Orders</a><a href="dashboard.php?preview=1">View Storefront</a><a href="logout.php">Logout</a></aside>
<main class="content"><div class="admin-top"><div><h1>Admin Dashboard</h1><p>Welcome, <?= htmlspecialchars($_SESSION["name"] ?? "Admin") ?>.</p></div><a class="primary-button" href="admin-products.php#product-form">+ Add Product</a></div>
<div class="cards"><div class="card"><h3>Total Products</h3><strong><?= (int)$productCount ?></strong><p>Products stored in the MySQL database.</p></div><div class="card"><h3>Featured Products</h3><strong><?= (int)$featuredCount ?></strong><p>Products displayed in the featured section.</p></div><div class="card"><h3>Manage Store</h3><p>Add, edit, delete and search products from one page.</p><a href="admin-products.php">Open Products</a></div></div></main></body></html>
