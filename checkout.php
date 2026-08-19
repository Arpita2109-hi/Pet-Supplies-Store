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
$selectedPayment = $_POST['payment_method'] ?? 'cod';

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

    if ($action === 'place_order') {
        $fulfilment = $_POST['fulfilment'] ?? 'delivery';
        $payment = $_POST['payment_method'] ?? 'cod';
        $name = trim($_POST['full_name'] ?? ($_SESSION['name'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $email = $_SESSION['email'] ?? '';
        $promoCode = $_SESSION['promo_code'] ?? '';
        $discount = promoDiscount($subtotal, $promoCode);
        $shipping = $fulfilment === 'pickup' ? 0.00 : 100.00;
        $total = max(0, $subtotal - $discount + $shipping);

        $errors = [];
        if ($name === '') $errors[] = 'Full name is required.';
        if ($phone === '') $errors[] = 'Phone number is required.';
        if (!in_array($fulfilment, ['delivery','pickup'], true)) $errors[] = 'Choose delivery or pickup.';
        if ($fulfilment === 'delivery' && ($address === '' || $city === '')) $errors[] = 'Delivery address and city are required.';
        if (!in_array($payment, ['cod','card'], true)) $errors[] = 'Choose a payment method.';
        if ($payment === 'card') {
            $cardName = trim($_POST['card_name'] ?? '');
            $cardNumber = preg_replace('/\D+/', '', $_POST['card_number'] ?? '');
            $expiry = trim($_POST['expiry'] ?? '');
            $cvv = preg_replace('/\D+/', '', $_POST['cvv'] ?? '');
            if ($cardName === '' || strlen($cardNumber) < 12 || $expiry === '' || strlen($cvv) < 3) $errors[] = 'Enter valid card details.';
        }

        if (!$errors) {
            $deliveryAddress = $fulfilment === 'pickup' ? 'Pickup from Happy Paws Store, Kathmandu' : $address . ', ' . $city;
            $status = 'pending';
            $stmt = mysqli_prepare($conn, "INSERT INTO orders (customer_name, customer_email, total, status, user_id, phone, fulfilment_method, delivery_address, payment_method, promo_code, discount, shipping_fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssdsisssssdd', $name, $email, $total, $status, $_SESSION['user_id'], $phone, $fulfilment, $deliveryAddress, $payment, $promoCode, $discount, $shipping);
            if (mysqli_stmt_execute($stmt)) {
                $orderId = mysqli_insert_id($conn);
                $itemStmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, product_name, price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($items as $item) {
                    $pid = (int)$item['id']; $pname = $item['name']; $price = (float)$item['price']; $qty = (int)$item['quantity']; $line = (float)$item['line_total'];
                    mysqli_stmt_bind_param($itemStmt, 'iisdid', $orderId, $pid, $pname, $price, $qty, $line);
                    mysqli_stmt_execute($itemStmt);
                }
                $_SESSION['cart'] = [];
                unset($_SESSION['promo_code']);
                header('Location: order_success.php?id=' . $orderId);
                exit();
            } else {
                $message = 'Order could not be placed. Please try again.';
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
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Checkout - Happy Paws</title><link rel="stylesheet" href="customer-commerce.css?v=1"></head><body>
<header class="commerce-header"><div class="commerce-header-inner"><a class="commerce-logo" href="dashboard.php"><span>🐾</span> Happy Paws</a><nav class="commerce-nav"><a href="dashboard.php">Shop</a><a href="wishlist_dashboard.php">Wishlist</a><a href="cart.php">Cart <span class="count-pill"><?= $itemCount ?></span></a><a class="active" href="checkout.php">Checkout</a><a href="logout.php">Logout</a></nav></div></header>
<main class="commerce-shell"><h1 class="page-title">Checkout</h1><p class="page-subtitle">Choose how you want to receive and pay for your order.</p>
<?php if ($message): ?><div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<form method="post" id="checkoutForm"><div class="checkout-layout"><div>
<section class="panel checkout-section"><h2>1. Delivery or Pickup</h2><div class="option-grid"><label class="option-card"><input type="radio" name="fulfilment" value="delivery" <?= $selectedFulfilment==='delivery'?'checked':'' ?>><span><strong>Home Delivery</strong><small>Delivered to your address. Shipping fee Rs. 100.</small></span></label><label class="option-card"><input type="radio" name="fulfilment" value="pickup" <?= $selectedFulfilment==='pickup'?'checked':'' ?>><span><strong>Store Pickup</strong><small>Pick up from Happy Paws Store, Kathmandu. Free.</small></span></label></div></section>
<section class="panel checkout-section" id="addressSection"><h2>2. Shipping Address</h2><div class="form-grid"><div class="form-group"><label>Full Name</label><input name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? ($_SESSION['name'] ?? '')) ?>" placeholder="Your full name"></div><div class="form-group"><label>Phone Number</label><input name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="98XXXXXXXX"></div><div class="form-group full"><label>Address</label><input name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" placeholder="Street / Area / Ward"></div><div class="form-group"><label>City</label><input name="city" value="<?= htmlspecialchars($_POST['city'] ?? 'Kathmandu') ?>" placeholder="City"></div><div class="form-group"><label>Email</label><input value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" disabled></div></div></section>
<section class="panel checkout-section"><h2>3. Payment Method</h2><div class="option-grid"><label class="option-card"><input type="radio" name="payment_method" value="cod" <?= $selectedPayment==='cod'?'checked':'' ?>><span><strong>Cash on Delivery</strong><small>Pay when your order arrives.</small></span></label><label class="option-card"><input type="radio" name="payment_method" value="card" <?= $selectedPayment==='card'?'checked':'' ?>><span><strong>Credit / Debit Card</strong><small>Visa or Mastercard (demo checkout).</small></span></label></div><div class="card-fields <?= $selectedPayment==='card'?'':'hidden' ?>" id="cardFields"><div class="form-grid"><div class="form-group full"><label>Name on Card</label><input name="card_name" placeholder="Cardholder name"></div><div class="form-group full"><label>Card Number</label><input name="card_number" inputmode="numeric" maxlength="19" placeholder="1234 5678 9012 3456"></div><div class="form-group"><label>Expiry</label><input name="expiry" placeholder="MM/YY"></div><div class="form-group"><label>CVV</label><input name="cvv" type="password" inputmode="numeric" maxlength="4" placeholder="123"></div></div></div></section>
<section class="panel checkout-section"><h2>4. Promo Code</h2><div class="promo-row"><input name="promo_code" value="<?= htmlspecialchars($promoCode) ?>" placeholder="Enter promo code"><button type="submit" name="checkout_action" value="apply_promo">Apply</button></div><p class="promo-help">Try <strong>HAPPY10</strong> for 10% off or <strong>PAWS50</strong> for Rs. 50 off orders above Rs. 500.</p></section>
</div><aside class="panel summary"><h2>Order Summary</h2><ul class="mini-items"><?php foreach($items as $item): ?><li><span><?= (int)$item['quantity'] ?> × <?= htmlspecialchars($item['name']) ?></span><strong>Rs. <?= number_format((float)$item['line_total'],2) ?></strong></li><?php endforeach; ?></ul><div class="summary-row"><span>Items</span><strong><?= $itemCount ?></strong></div><div class="summary-row"><span>Subtotal</span><strong>Rs. <?= number_format($subtotal,2) ?></strong></div><div class="summary-row"><span>Promo Discount</span><strong id="discountAmount">− Rs. <?= number_format($discount,2) ?></strong></div><div class="summary-row"><span>Shipping</span><strong id="shippingAmount">Rs. 100.00</strong></div><div class="summary-row total"><span>Total Amount</span><strong id="grandTotal">Rs. <?= number_format(max(0,$subtotal-$discount+100),2) ?></strong></div><button class="primary-button" type="submit" name="checkout_action" value="place_order">Place Order</button><a class="secondary-button" href="cart.php">Back to Cart</a></aside></div></form></main>
<script>
const subtotal=<?= json_encode($subtotal) ?>,discount=<?= json_encode($discount) ?>;const address=document.getElementById('addressSection'),shipText=document.getElementById('shippingAmount'),grand=document.getElementById('grandTotal');function updateDelivery(){const pickup=document.querySelector('input[name="fulfilment"]:checked').value==='pickup';address.classList.toggle('hidden',pickup);const shipping=pickup?0:100;shipText.textContent='Rs. '+shipping.toFixed(2);grand.textContent='Rs. '+Math.max(0,subtotal-discount+shipping).toFixed(2)}document.querySelectorAll('input[name="fulfilment"]').forEach(r=>r.addEventListener('change',updateDelivery));document.querySelectorAll('input[name="payment_method"]').forEach(r=>r.addEventListener('change',()=>document.getElementById('cardFields').classList.toggle('hidden',document.querySelector('input[name="payment_method"]:checked').value!=='card')));updateDelivery();
</script></body></html>
