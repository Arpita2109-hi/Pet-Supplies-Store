<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: signin.html'); exit(); }
$orderId = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id=? AND user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'ii', $orderId, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$order) { header('Location: dashboard.php'); exit(); }
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Order Confirmed - Happy Paws</title><link rel="stylesheet" href="customer-commerce.css?v=1"></head><body><section class="panel order-success"><div class="success-icon">✅</div><h1>Order Placed Successfully!</h1><div class="order-number">Order #<?= (int)$order['id'] ?></div><p>Thank you, <?= htmlspecialchars($order['customer_name']) ?>. Your order has been received and is now pending confirmation.</p><p><strong>Total:</strong> Rs. <?= number_format((float)$order['total'],2) ?><br><strong>Payment:</strong> <?= htmlspecialchars(strtoupper($order['payment_method'])) ?><br><strong>Method:</strong> <?= htmlspecialchars(ucfirst($order['fulfilment_method'])) ?></p><a class="primary-button" href="dashboard.php">Continue Shopping</a></section></body></html>
