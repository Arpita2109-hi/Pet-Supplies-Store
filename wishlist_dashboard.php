<?php
session_start();
require_once "db.php";
require_once "cart_helpers.php";
if(!isset($_SESSION["user_id"])){
    header("Location: signin.html");
    exit();
}
$userId=(int)$_SESSION["user_id"];
if(isset($_GET["remove"])){
    $productId=(int)$_GET["remove"];
    $stmt=mysqli_prepare($conn,"DELETE FROM wishlist WHERE user_id=? AND product_id=?");
    mysqli_stmt_bind_param($stmt,"ii",$userId,$productId);
    mysqli_stmt_execute($stmt);
    header("Location: wishlist_dashboard.php");
    exit();
}
$stmt=mysqli_prepare($conn,"SELECT p.* FROM products p INNER JOIN wishlist w ON p.id=w.product_id WHERE w.user_id=? ORDER BY w.id DESC");
mysqli_stmt_bind_param($stmt,"i",$userId);
mysqli_stmt_execute($stmt);
$wishlistProducts=mysqli_stmt_get_result($stmt);
$wishlistCount=mysqli_num_rows($wishlistProducts);
$cartItemCount=cartCount();
$categories=mysqli_query($conn,"SELECT DISTINCT category FROM products ORDER BY category");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Wishlist - Happy Paws</title>
<link rel="stylesheet" href="dashboard.css?v=4">
</head>
<body>

<header class="main-header">
<div class="top-header">

<a href="dashboard.php" class="logo">
<span class="logo-icon">🐾</span>
<span class="logo-text">Happy Paws</span>
</a>

<form class="search-box" method="get" action="dashboard.php">
<input name="search" type="search" placeholder="Search food, toys, grooming products...">
<button type="submit">Search</button>
</form>

<nav class="account-navigation">
<a href="#">Hi, <?= htmlspecialchars($_SESSION["name"]??"Customer") ?></a>
<a href="logout.php">Logout</a>
<a href="wishlist_dashboard.php" class="wishlist-link">
Wishlist (<span class="wishlist-count"><?= $wishlistCount ?></span>)
</a>
<a href="cart.php" class="cart-link">Cart (<span class="cart-header-count"><?= $cartItemCount ?></span>)</a>
<a href="checkout.php" class="checkout-link">Checkout</a>
<a href="transaction_history.php" class="transaction-link">My Purchases</a>
</nav>

</div>

<div class="bottom-header">
<nav class="main-navigation">
<a href="dashboard.php">Home</a>
<a href="dashboard.php#featured-products">Featured</a>
<a href="dashboard.php#products">Products</a>
<a href="dashboard.php#contact">Contact Us</a>
</nav>
</div>
</header>

<main class="page-layout">

<aside class="category-sidebar">
<h2>Categories</h2>

<div class="category-list">
<a href="dashboard.php">All Products</a>

<?php while($cat=mysqli_fetch_assoc($categories)): ?>
<a href="dashboard.php?category=<?= urlencode($cat["category"]) ?>">
<?= htmlspecialchars($cat["category"]) ?>
</a>
<?php endwhile; ?>

</div>
</aside>

<div class="main-content">

<section class="product-section">

<div class="section-heading">
<div>
<span class="section-label">Saved Products</span>
<h2>My Wishlist</h2>
</div>
</div>

<div class="product-grid">

<?php if($wishlistCount>0): ?>

<?php while($product=mysqli_fetch_assoc($wishlistProducts)): ?>

<article class="product-card">

<div class="product-image">
<img src="<?= htmlspecialchars($product["image"]) ?>" alt="<?= htmlspecialchars($product["name"]) ?>">
</div>

<div class="product-information">

<h3><?= htmlspecialchars($product["name"]) ?></h3>

<p><?= htmlspecialchars($product["description"]) ?></p>

<div class="product-price">
Rs. <?= number_format((float)$product["price"],2) ?>
</div>

<div class="wishlist-card-actions">

<a href="wishlist_dashboard.php?remove=<?= $product["id"] ?>" class="remove-wishlist" title="Remove from Wishlist" aria-label="Remove from Wishlist">
    <svg class="trash-icon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M8 7V5.5C8 4.67 8.67 4 9.5 4h5c.83 0 1.5.67 1.5 1.5V7"/>
        <path d="M5 7h14"/>
        <path d="M6.5 7l.8 12a2 2 0 0 0 2 1.87h5.4a2 2 0 0 0 2-1.87l.8-12"/>
        <path d="M10 10v7M14 10v7"/>
    </svg>
</a>

<form class="wishlist-cart-form">

<input
    type="hidden"
    name="product_id"
    value="<?= (int)$product["id"] ?>"
>

<button
    type="submit"
    class="wishlist-checkout"
>
    Add to Cart
</button>

<button
    type="button"
    class="wishlist-direct-checkout"
    title="Checkout"
    aria-label="Checkout"
>✓</button>

</form>
</div>

</div>
</article>

<?php endwhile; ?>

<?php else: ?>

<div class="empty-wishlist">
<div class="empty-heart">♡</div>
<h2>Your wishlist is empty</h2>
<p>Add your favourite Happy Paws products to see them here.</p>
<a href="dashboard.php">Shop Products</a>
</div>

<?php endif; ?>

</div>

</section>

</div>

</main>
<script>

function addWishlistProductToCart(form, button, goToCheckout) {
    const productId = form.querySelector('input[name="product_id"]').value;
    const originalText = button.innerHTML;

    button.disabled = true;
    if (goToCheckout) button.innerHTML = "…";

    fetch("cart_action.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body:
            "action=add" +
            "&product_id=" + encodeURIComponent(productId) +
            "&quantity=1" +
            "&ajax=1"
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || "Could not add product to cart.");
        }

        const cartCount = document.querySelector(".cart-header-count");
        if (cartCount) cartCount.textContent = data.cartCount;

        if (goToCheckout) {
            window.location.href = "checkout.php";
            return;
        }

        button.innerHTML = "✓ Added to Cart";
        setTimeout(function() {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 1200);
    })
    .catch(function(error) {
        console.error("Cart error:", error);
        button.innerHTML = originalText;
        button.disabled = false;
        alert("Unable to continue. Please try again.");
    });
}

document.querySelectorAll(".wishlist-cart-form").forEach(function(form) {
    form.addEventListener("submit", function(e) {
        e.preventDefault();
        const button = form.querySelector(".wishlist-checkout");
        addWishlistProductToCart(form, button, false);
    });

    const checkoutButton = form.querySelector(".wishlist-direct-checkout");
    checkoutButton.addEventListener("click", function() {
        addWishlistProductToCart(form, checkoutButton, true);
    });
});

</script>
</body>
</html>