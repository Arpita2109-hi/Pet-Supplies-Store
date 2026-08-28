<?php
session_start();
require_once 'db.php';
require_once 'cart_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: signin.html');
    exit();
}

if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: dashboard.php?preview=1');
    exit();
}

$userId = (int)$_SESSION['user_id'];
$items = getCartProducts($conn);
$subtotal = cartSubtotal($items);
$itemCount = cartCount();

$wishlistCount = 0;
$wishlistStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM wishlist WHERE user_id=?");
mysqli_stmt_bind_param($wishlistStmt, "i", $userId);
mysqli_stmt_execute($wishlistStmt);
$wishlistResult = mysqli_stmt_get_result($wishlistStmt);
if ($wishlistRow = mysqli_fetch_assoc($wishlistResult)) {
    $wishlistCount = (int)$wishlistRow['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Happy Paws</title>
    <link rel="stylesheet" href="dashboard.css?v=2">
    <link rel="stylesheet" href="customer-commerce.css?v=12">
</head>
<body>
<header class="main-header">
    <div class="top-header">
        <a href="dashboard.php" class="logo">
            <span class="logo-icon">🐾</span>
            <span class="logo-text">Happy Paws</span>
        </a>

        <form class="search-box" method="get" action="dashboard.php">
            <input
                name="search"
                type="search"
                placeholder="Search food, toys, grooming products..."
            >
            <button type="submit">Search</button>
        </form>

        <nav class="account-navigation">
            <a href="#">Hi, <?= htmlspecialchars($_SESSION["name"] ?? "Customer") ?></a>
            <a href="logout.php">Logout</a>
            <a href="wishlist_dashboard.php" class="wishlist-link">Wishlist (<span class="wishlist-count"><?= $wishlistCount ?></span>)</a>
            <a href="cart.php" class="cart-link">Cart (<span class="cart-header-count"><?= $itemCount ?></span>)</a>
            <a href="checkout.php" class="checkout-link">Checkout</a>
        </nav>
    </div>

    <div class="bottom-header">
        <nav class="main-navigation">
            <a href="dashboard.php">Shop</a>
            <a href="wishlist_dashboard.php">Wishlist</a>
            <a href="cart.php" class="active-link">Cart</a>
            <a href="checkout.php">Checkout</a>
        </nav>
    </div>
</header>

<main class="commerce-shell">


        <h1 class="page-title">
            My Cart
        </h1>


        <p class="page-subtitle">
            Review your products, change quantities and continue to checkout.
        </p>



        <?php if ($items): ?>


            <div class="commerce-grid">


                <section class="panel cart-list">


                    <?php foreach ($items as $item): ?>


                        <article class="cart-item">


                            <img
                                src="<?= htmlspecialchars($item['image']) ?>"
                                alt="<?= htmlspecialchars($item['name']) ?>"
                            >



                            <div>


                                <h3>
                                    <?= htmlspecialchars($item['name']) ?>
                                </h3>


                                <p>
                                    <?= htmlspecialchars($item['category']) ?>
                                </p>


                                <div class="item-price">

                                    Rs.
                                    <?= number_format((float)$item['price'], 2) ?>
                                    each

                                </div>



                                <div class="qty-row">


                                    <form
                                        method="post"
                                        action="cart_action.php"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="decrease"
                                        >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= (int)$item['id'] ?>"
                                        >

                                        <button
                                            class="qty-button"
                                            type="submit"
                                        >
                                            −
                                        </button>

                                    </form>



                                    <span class="qty-number">

                                        <?= (int)$item['quantity'] ?>

                                    </span>



                                    <form
                                        method="post"
                                        action="cart_action.php"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="increase"
                                        >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= (int)$item['id'] ?>"
                                        >

                                        <button
                                            class="qty-button"
                                            type="submit"
                                        >
                                            +
                                        </button>

                                    </form>



                                    <form
                                        method="post"
                                        action="cart_action.php"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="remove"
                                        >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= (int)$item['id'] ?>"
                                        >

                                        <button
                                            class="remove-button"
                                            type="submit"
                                        >
                                            Remove
                                        </button>

                                    </form>


                                </div>


                            </div>



                            <div class="line-total">

                                Rs.
                                <?= number_format((float)$item['line_total'], 2) ?>

                            </div>


                        </article>


                    <?php endforeach; ?>


                </section>



                <aside class="panel summary">


                    <h2>
                        Cart Summary
                    </h2>



                    <div class="summary-row">

                        <span>
                            Items
                        </span>

                        <strong>
                            <?= $itemCount ?>
                        </strong>

                    </div>



                    <div class="summary-row">

                        <span>
                            Subtotal
                        </span>

                        <strong>
                            Rs. <?= number_format($subtotal, 2) ?>
                        </strong>

                    </div>



                    <div class="summary-row">

                        <span>
                            Shipping
                        </span>

                        <strong>
                            Calculated at checkout
                        </strong>

                    </div>



                    <div class="summary-row total">

                        <span>
                            Total Amount
                        </span>

                        <strong>
                            Rs. <?= number_format($subtotal, 2) ?>
                        </strong>

                    </div>



                    <a
                        class="primary-button"
                        href="checkout.php"
                    >
                        Proceed to Checkout
                    </a>



                    <a
                        class="secondary-button"
                        href="dashboard.php"
                    >
                        Continue Shopping
                    </a>


                </aside>


            </div>



        <?php else: ?>


            <section class="panel empty-cart">


                <div class="icon">
                    🛒
                </div>


                <h2>
                    Your cart is empty
                </h2>


                <p>
                    Add products from the store or from your wishlist.
                </p>


                <a
                    class="primary-button"
                    href="dashboard.php"
                >
                    Shop Products
                </a>


            </section>


        <?php endif; ?>


    </main>


</body>

</html>