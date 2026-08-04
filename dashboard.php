<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: signin.html");
    exit();
}
if (($_SESSION["role"] ?? "") === "admin" && !isset($_GET["preview"])) {
    header("Location: admin-dashboard.php");
    exit();
}

$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");

$sql = "SELECT * FROM products WHERE 1";
$params = [];
$types = "";
if ($search !== "") {
    $sql .= " AND (name LIKE ? OR category LIKE ? OR description LIKE ?)";
    $like = "%" . $search . "%";
    $params = [$like, $like, $like];
    $types = "sss";
}
if ($category !== "") {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}
$sql .= " ORDER BY featured DESC, id DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$products = mysqli_stmt_get_result($stmt);

$featuredResult = mysqli_query($conn, "SELECT * FROM products WHERE featured = 1 ORDER BY id DESC LIMIT 6");
$categoriesResult = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category");

function productCard(array $product, string $prefix = "product"): void {
    $id = (int)$product["id"];
    $name = htmlspecialchars($product["name"]);
    $description = htmlspecialchars($product["description"] ?? "");
    $image = htmlspecialchars($product["image"]);
    $price = number_format((float)$product["price"], 2);
    ?>
    <article class="product-card">
        <?php if ((int)$product["featured"] === 1): ?>
            <span class="product-badge">Featured</span>
        <?php endif; ?>
        <div class="product-image">
            <img src="<?= $image ?>" alt="<?= $name ?>">
        </div>
        <div class="product-information">
            <h3><?= $name ?></h3>
            <p><?= $description ?></p>
            <div class="product-price">Rs. <?= $price ?></div>
            <label for="<?= $prefix ?>_quantity_<?= $id ?>">Quantity</label>
            <input type="number" id="<?= $prefix ?>_quantity_<?= $id ?>" min="1" value="1">
            <button type="button">Add to Cart</button>
        </div>
    </article>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Happy Paws Pet Store</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
<header class="main-header">
    <div class="top-header">
        <a href="dashboard.php" class="logo"><span class="logo-icon">🐾</span><span class="logo-text">Happy Paws</span></a>
        <form class="search-box" method="get" action="dashboard.php">
            <input name="search" type="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search food, toys, grooming products...">
            <button type="submit">Search</button>
        </form>
        <nav class="account-navigation">
            <a href="#">Hi, <?= htmlspecialchars($_SESSION["name"] ?? "Customer") ?></a>
            <a href="logout.php">Logout</a>
            <a href="#" class="cart-link">Cart</a>
            <a href="#" class="checkout-link">Checkout</a>
        </nav>
    </div>
    <div class="bottom-header">
        <nav class="main-navigation">
            <a href="dashboard.php" class="active-link">Home</a>
            <a href="#featured-products">Featured</a>
            <a href="#products">Products</a>
            <a href="#contact">Contact Us</a>
        </nav>
    </div>
</header>

<main class="page-layout">
    <aside class="category-sidebar">
        <h2>Categories</h2>
        <div class="category-list">
            <a href="dashboard.php">All Products</a>
            <?php while ($cat = mysqli_fetch_assoc($categoriesResult)): ?>
                <a href="dashboard.php?category=<?= urlencode($cat["category"]) ?>"><?= htmlspecialchars($cat["category"]) ?></a>
            <?php endwhile; ?>
        </div>
    </aside>

    <div class="main-content">
        <section class="hero-section">
            <div class="hero-text">
                <span class="hero-label">Everything your pet needs</span>
                <h1>Quality products for happy and healthy pets</h1>
                <p>Shop pet food, toys, grooming essentials, accessories and more for your furry friends.</p>
                <a href="#products" class="shop-button">Shop Now</a>
            </div>
            <div class="hero-visual"><img src="images/hero-pets.png" alt="Happy Pets"></div>
        </section>

        <?php if ($search === "" && $category === ""): ?>
        <section class="product-section" id="featured-products">
            <div class="section-heading"><div><span class="section-label">Most popular</span><h2>Featured Products</h2></div><a href="#products">View all products</a></div>
            <div class="product-grid">
                <?php if ($featuredResult && mysqli_num_rows($featuredResult) > 0): ?>
                    <?php while ($product = mysqli_fetch_assoc($featuredResult)) productCard($product, "featured"); ?>
                <?php else: ?>
                    <p>No featured products yet.</p>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <section class="product-section" id="products">
            <div class="section-heading">
                <div>
                    <span class="section-label"><?= $search !== "" ? "Search results" : "Explore our store" ?></span>
                    <h2><?= $category !== "" ? htmlspecialchars($category) : "Products" ?></h2>
                </div>
            </div>
            <div class="product-grid">
                <?php if ($products && mysqli_num_rows($products) > 0): ?>
                    <?php while ($product = mysqli_fetch_assoc($products)) productCard($product); ?>
                <?php else: ?>
                    <p>No products found.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="contact-section" id="contact">
            <div class="contact-information">
                <span class="section-label">Need assistance?</span><h2>Contact Us</h2>
                <p>Contact the Happy Paws team for product information, delivery questions and order support.</p>
                <div class="contact-details"><p><strong>Email:</strong> happypaws@gmail.com</p><p><strong>Phone:</strong> +977 98665690289</p><p><strong>Address:</strong> Kathmandu, Nepal</p></div>
            </div>
            <form class="contact-form">
                <label for="contact_name">Your Name</label><input type="text" id="contact_name" placeholder="Enter your name">
                <label for="contact_email">Email Address</label><input type="email" id="contact_email" placeholder="Enter your email">
                <label for="contact_message">Message</label><textarea id="contact_message" rows="5" placeholder="How can we help you?"></textarea>
                <button type="button">Send Message</button>
            </form>
        </section>
    </div>
</main>
<footer class="main-footer"><div class="footer-bottom"><p>&copy; 2026 Happy Paws Pet Store. All rights reserved.</p></div></footer>
</body>
</html>
