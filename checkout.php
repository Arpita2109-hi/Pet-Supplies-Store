<?php
session_start();
require_once 'db.php';
require_once 'cart_helpers.php';

if (!isset($_SESSION['user_id'])) { header('Location: signin.html'); exit(); }
if (($_SESSION['role'] ?? '') === 'admin') { header('Location: dashboard.php?preview=1'); exit(); }

$items = getCartProducts($conn);
if (!$items) { header('Location: cart.php'); exit(); }

$subtotal = cartSubtotal($items);
$message = '';
$messageType = '';
$promoCode = $_SESSION['promo_code'] ?? '';
$selectedFulfilment = $_POST['fulfilment'] ?? 'delivery';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['checkout_action'] ?? '';

    if ($action === 'apply_promo') {
        $candidate = strtoupper(trim($_POST['promo_code'] ?? ''));
        if ($candidate === '') {
            unset($_SESSION['promo_code']);
            $promoCode = '';
            $message = 'Promo code removed.';
            $messageType = 'success';
        } elseif (promoDescription($candidate) !== '' && promoDiscount($subtotal, $candidate) > 0) {
            $_SESSION['promo_code'] = $candidate;
            $promoCode = $candidate;
            $message = $candidate . ' applied: ' . promoDescription($candidate) . '.';
            $messageType = 'success';
        } else {
            unset($_SESSION['promo_code']);
            $promoCode = '';
            $message = 'That promo code is invalid for this cart.';
            $messageType = 'error';
        }
    }

    if ($action === 'continue_payment') {
        $fulfilment = $_POST['fulfilment'] ?? 'delivery';
        $name = trim($_POST['full_name'] ?? ($_SESSION['name'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $email = $_SESSION['email'] ?? '';
        $promoCode = $_SESSION['promo_code'] ?? '';
        $discount = promoDiscount($subtotal, $promoCode);
        $shipping = $fulfilment === 'pickup' ? 0.00 : 100.00;
        $total = max(0, $subtotal - $discount + $shipping);
        $payment = 'bank';

        $errors = [];
        if ($name === '') $errors[] = 'Full name is required.';
        if ($phone === '') $errors[] = 'Phone number is required.';
        if (!in_array($fulfilment, ['delivery', 'pickup'], true)) $errors[] = 'Choose delivery or pickup.';
        if ($fulfilment === 'delivery' && ($address === '' || $city === '')) $errors[] = 'Delivery address and city are required.';

        if (!$errors) {
            $deliveryAddress = $fulfilment === 'pickup'
                ? 'Pickup from Happy Paws Store, Kathmandu'
                : $address . ', ' . $city;
            $status = 'pending';
            $paymentStatus = 'pending';
            $sessionUserId = (int)$_SESSION['user_id'];

            mysqli_begin_transaction($conn);
            try {
                $stmt = mysqli_prepare($conn, "INSERT INTO orders (customer_name, customer_email, total, status, user_id, phone, fulfilment_method, delivery_address, payment_method, promo_code, discount, shipping_fee, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) throw new Exception(mysqli_error($conn));
                mysqli_stmt_bind_param($stmt, 'ssdsisssssdds', $name, $email, $total, $status, $sessionUserId, $phone, $fulfilment, $deliveryAddress, $payment, $promoCode, $discount, $shipping, $paymentStatus);
                if (!mysqli_stmt_execute($stmt)) throw new Exception(mysqli_stmt_error($stmt));

                $orderId = mysqli_insert_id($conn);
                $itemStmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, product_name, price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$itemStmt) throw new Exception(mysqli_error($conn));

                foreach ($items as $item) {
                    $pid = (int)$item['id'];
                    $pname = $item['name'];
                    $price = (float)$item['price'];
                    $qty = (int)$item['quantity'];
                    $line = (float)$item['line_total'];
                    mysqli_stmt_bind_param($itemStmt, 'iisdid', $orderId, $pid, $pname, $price, $qty, $line);
                    if (!mysqli_stmt_execute($itemStmt)) throw new Exception(mysqli_stmt_error($itemStmt));
                }

                mysqli_commit($conn);
                $_SESSION['pending_order_id'] = $orderId;
                header('Location: bank_payment.php?order_id=' . $orderId);
                exit();
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $message = 'Order could not be prepared for payment: ' . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = implode(' ', $errors);
            $messageType = 'error';
        }
    }
}

$discount = promoDiscount($subtotal, $promoCode);
$itemCount = cartCount();
$userId = (int)$_SESSION['user_id'];
$wishlistCount = 0;
$wishlistStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM wishlist WHERE user_id=?");
mysqli_stmt_bind_param($wishlistStmt, 'i', $userId);
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
    <title>Checkout - Happy Paws</title>
    <link rel="stylesheet" href="dashboard.css?v=2">
    <link rel="stylesheet" href="customer-commerce.css?v=13">
</head>
<body>
<header class="main-header">
    <div class="top-header">
        <a href="dashboard.php" class="logo"><span class="logo-icon">🐾</span><span class="logo-text">Happy Paws</span></a>
        <form class="search-box" method="get" action="dashboard.php">
            <input name="search" type="search" placeholder="Search food, toys, grooming products...">
            <button type="submit">Search</button>
        </form>
        <nav class="account-navigation">
            <a href="#">Hi, <?= htmlspecialchars($_SESSION['name'] ?? 'Customer') ?></a>
            <a href="logout.php">Logout</a>
            <a href="transaction_history.php">My Purchases</a>
            <a href="wishlist_dashboard.php" class="wishlist-link">Wishlist (<span class="wishlist-count"><?= $wishlistCount ?></span>)</a>
            <a href="cart.php" class="cart-link">Cart (<span class="cart-header-count"><?= $itemCount ?></span>)</a>
            <a href="checkout.php" class="checkout-link">Checkout</a>
        </nav>
    </div>
    <div class="bottom-header"><nav class="main-navigation"><a href="dashboard.php">Shop</a><a href="wishlist_dashboard.php">Wishlist</a><a href="cart.php">Cart</a><a href="checkout.php" class="active-link">Checkout</a><a href="transaction_history.php">My Purchases</a></nav></div>
</header>

<main class="commerce-shell">
    <h1 class="page-title">Checkout</h1>
    <p class="page-subtitle">Enter your delivery details, then continue to secure digital payment.</p>
    <?php if ($message): ?><div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <form method="post" id="checkoutForm" novalidate>
        <div class="checkout-layout">
            <div>
                <section class="panel checkout-section">
                    <h2>1. Delivery or Pickup</h2>
                    <div class="option-grid">
                        <label class="option-card"><input type="radio" name="fulfilment" value="delivery" <?= $selectedFulfilment === 'delivery' ? 'checked' : '' ?>><span><strong>Home Delivery</strong><small>Delivered to your address. Shipping fee Rs. 100.</small></span></label>
                        <label class="option-card"><input type="radio" name="fulfilment" value="pickup" <?= $selectedFulfilment === 'pickup' ? 'checked' : '' ?>><span><strong>Store Pickup</strong><small>Pick up from Happy Paws Store, Kathmandu. Free.</small></span></label>
                    </div>
                </section>

                <section class="panel checkout-section" id="contactSection">
                    <h2 id="detailsHeading">2. Contact & Shipping Details</h2>
                    <div class="form-grid">
                        <div class="form-group"><label>Full Name</label><input name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? ($_SESSION['name'] ?? '')) ?>" placeholder="Your full name"></div>
                        <div class="form-group"><label>Phone Number</label><input id="pickupPhone" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="98XXXXXXXX"></div>
                        <div class="form-group"><label>Email</label><input value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" disabled></div>
                    </div>

                    <div id="deliveryOnlyFields" class="delivery-only-fields">
                        <div class="form-grid">
                            <div class="form-group full"><label>Address</label><input id="deliveryAddress" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" placeholder="Street / Area / Ward"></div>
                            <div class="form-group"><label>City</label><input id="deliveryCity" name="city" value="<?= htmlspecialchars($_POST['city'] ?? 'Kathmandu') ?>" placeholder="City"></div>
                        </div>
                    </div>
                    <p id="pickupNote" class="pickup-contact-note hidden">For store pickup, only your name and phone number are required so the store can contact you.</p>
                </section>

                <section class="panel checkout-section">
                    <h2>3. Digital Payment</h2>
                    <div class="digital-payment-card">
                        <div class="digital-payment-icon">🏦</div>
                        <div><strong>Happy Paws Bank</strong><small>Pay digitally using your demo bank account. Your balance will be checked and deducted securely after PIN verification.</small></div>
                        <span class="payment-badge">DIGITAL ONLY</span>
                    </div>
                    <input type="hidden" name="payment_method" value="bank">
                </section>

                <section class="panel checkout-section">
                    <h2>4. Promo Code</h2>
                    <div class="promo-row"><input name="promo_code" value="<?= htmlspecialchars($promoCode) ?>" placeholder="Enter promo code"><button type="submit" name="checkout_action" value="apply_promo">Apply</button></div>
                    <p class="promo-help">Try <strong>HAPPY10</strong> for 10% off or <strong>PAWS50</strong> for Rs. 50 off orders above Rs. 500.</p>
                </section>
            </div>

            <aside class="panel summary">
                <h2>Order Summary</h2>
                <ul class="mini-items"><?php foreach ($items as $item): ?><li><span><?= (int)$item['quantity'] ?> × <?= htmlspecialchars($item['name']) ?></span><strong>Rs. <?= number_format((float)$item['line_total'], 2) ?></strong></li><?php endforeach; ?></ul>
                <div class="summary-row"><span>Items</span><strong><?= $itemCount ?></strong></div>
                <div class="summary-row"><span>Subtotal</span><strong>Rs. <?= number_format($subtotal, 2) ?></strong></div>
                <div class="summary-row"><span>Promo Discount</span><strong>− Rs. <?= number_format($discount, 2) ?></strong></div>
                <div class="summary-row"><span>Shipping</span><strong id="shippingAmount">Rs. 100.00</strong></div>
                <div class="summary-row total"><span>Total Amount</span><strong id="grandTotal">Rs. <?= number_format(max(0, $subtotal - $discount + 100), 2) ?></strong></div>
                <p class="payment-note">Fill the required delivery details first. Then you will be taken to Happy Paws Bank to enter your bank account name, account number and PIN.</p>
                <button class="primary-button" type="submit" name="checkout_action" value="continue_payment">Proceed to Happy Paws Bank</button>
                <a class="secondary-button" href="cart.php">Back to Cart</a>
            </aside>
        </div>
    </form>
</main>

<script>
const subtotal = <?= json_encode($subtotal) ?>;
const discount = <?= json_encode($discount) ?>;
const deliveryOnlyFields = document.getElementById('deliveryOnlyFields');
const pickupNote = document.getElementById('pickupNote');
const detailsHeading = document.getElementById('detailsHeading');
const shipText = document.getElementById('shippingAmount');
const grand = document.getElementById('grandTotal');
function updateDelivery() {
    const selected = document.querySelector('input[name="fulfilment"]:checked');
    const pickup = selected && selected.value === 'pickup';
    if (deliveryOnlyFields) deliveryOnlyFields.classList.toggle('hidden', pickup);
    if (pickupNote) pickupNote.classList.toggle('hidden', !pickup);
    if (detailsHeading) detailsHeading.textContent = pickup ? '2. Pickup Contact Details' : '2. Contact & Shipping Details';
    const addressInput = document.getElementById('deliveryAddress');
    const cityInput = document.getElementById('deliveryCity');
    if (addressInput) addressInput.required = !pickup;
    if (cityInput) cityInput.required = !pickup;
    const shipping = pickup ? 0 : 100;
    shipText.textContent = 'Rs. ' + shipping.toFixed(2);
    grand.textContent = 'Rs. ' + Math.max(0, subtotal - discount + shipping).toFixed(2);
}
document.querySelectorAll('input[name="fulfilment"]').forEach(r => r.addEventListener('change', updateDelivery));
updateDelivery();
<?php if ($message): ?>
window.addEventListener('load', () => {
    const msg = document.querySelector('.message');
    if (msg) msg.scrollIntoView({behavior: 'smooth', block: 'center'});
});
<?php endif; ?>
</script>
</body>
</html>
