<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: signin.html'); exit(); }
$orderId = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT o.*, bt.transaction_id AS bank_transaction_id, bt.balance_after FROM orders o LEFT JOIN bank_transactions bt ON bt.transaction_id=o.transaction_id WHERE o.id=? AND o.user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'ii', $orderId, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$order) { header('Location: dashboard.php'); exit(); }
if (($order['payment_status'] ?? 'pending') !== 'paid') { header('Location: bank_payment.php?order_id=' . $orderId); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Payment Successful - Happy Paws</title>
<link rel="stylesheet" href="customer-commerce.css?v=13"><link rel="stylesheet" href="bank.css?v=1">
</head>
<body>
<section class="panel order-success payment-success-card">
    <div class="success-icon">✅</div>
    <h1>Payment Successful!</h1>
    <div class="order-number">Order #<?= (int)$order['id'] ?></div>
    <p>Thank you, <?= htmlspecialchars($order['customer_name']) ?>. Your digital payment has been completed and your order has been received.</p>
    <div class="success-details">
        <div><span>Total Paid</span><strong>Rs. <?= number_format((float)$order['total'], 2) ?></strong></div>
        <div><span>Payment Method</span><strong>Happy Paws Bank</strong></div>
        <div><span>Payment Status</span><strong>PAID</strong></div>
        <?php if (!empty($order['bank_transaction_id'])): ?><div><span>Transaction ID</span><strong>#<?= (int)$order['bank_transaction_id'] ?></strong></div><?php endif; ?>
    </div>
    <a class="primary-button" href="dashboard.php">Continue Shopping</a>
    <a class="secondary-button" href="transaction_history.php">View My Purchases</a>
</section>
</body>
</html>
